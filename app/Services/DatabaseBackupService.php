<?php

namespace App\Services;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

/**
 * DatabaseBackupService
 * 
 * This service handles database backup operations
 * 
 * WHY WE USE THIS?
 * - Separates backup logic from controllers (Single Responsibility Principle)
 * - Makes code reusable across different parts of application
 * - Easier to test and maintain
 */
class DatabaseBackupService
{
    /**
     * METHOD 1: Using mysqldump command (Traditional & Fast)
     * 
     * WHY USE mysqldump?
     * - Native MySQL tool, very fast
     * - Handles large databases efficiently
     * - Creates consistent backups
     * - Industry standard
     * 
     * WHERE TO USE?
     * - Production environments
     * - Large databases (>1GB)
     * - When you need full database structure + data
     * 
     * @return array ['success' => bool, 'path' => string, 'size' => int, 'message' => string]
     */
    public function createBackupUsingMysqldump(): array
    {
        try {
            // STEP 1: Get database configuration
            // WHY? We need to know which database to backup
            $dbHost = env('DB_HOST', '127.0.0.1');
            $dbPort = env('DB_PORT', '3306');
            $dbName = env('DB_DATABASE');
            $dbUser = env('DB_USERNAME');
            $dbPass = env('DB_PASSWORD');

            // STEP 2: Create backup filename with timestamp
            // WHY TIMESTAMP? Makes each backup unique and sortable
            // FORMAT: backup_2025-11-13_14-30-45.sql
            $timestamp = Carbon::now()->format('Y-m-d_H-i-s');
            $fileName = "backup_{$timestamp}.sql";
            
            // STEP 3: Define temporary storage path
            // WHY STORAGE_PATH? Laravel's standard location for files
            $backupPath = storage_path("app/backups/{$fileName}");
            
            // STEP 4: Create backups directory if doesn't exist
            // WHY? Prevents "directory not found" errors
            if (!File::isDirectory(storage_path('app/backups'))) {
                File::makeDirectory(storage_path('app/backups'), 0755, true);
            }

            // STEP 5: Build mysqldump command
            // COMMAND BREAKDOWN:
            // mysqldump: MySQL backup utility
            // -h: host address
            // -P: port number
            // -u: username
            // -p{password}: password (no space after -p)
            // --single-transaction: Ensures consistent backup without locking tables
            // --skip-lock-tables: Prevents table locking during backup
            // --add-drop-table: Adds DROP TABLE before CREATE TABLE (safer restore)
            // {database}: database name
            // > {file}: redirect output to file
            $command = sprintf(
                'mysqldump -h%s -P%s -u%s -p%s --single-transaction --skip-lock-tables --add-drop-table %s > %s',
                escapeshellarg($dbHost),
                escapeshellarg($dbPort),
                escapeshellarg($dbUser),
                escapeshellarg($dbPass),
                escapeshellarg($dbName),
                escapeshellarg($backupPath)
            );

            // STEP 6: Execute command
            // WHY exec()? Executes system commands from PHP
            // $output: command output
            // $returnVar: 0 = success, non-zero = error
            exec($command, $output, $returnVar);

            // STEP 7: Check if command executed successfully
            if ($returnVar !== 0) {
                throw new \Exception('mysqldump command failed with return code: ' . $returnVar);
            }

            // STEP 8: Verify file was created
            if (!File::exists($backupPath)) {
                throw new \Exception('Backup file was not created');
            }

            // STEP 9: Get file size for logging
            $fileSize = File::size($backupPath);

            // STEP 10: Return success response
            return [
                'success' => true,
                'path' => $backupPath,
                'filename' => $fileName,
                'size' => $fileSize,
                'size_human' => $this->formatBytes($fileSize),
                'message' => 'Database backup created successfully'
            ];

        } catch (\Exception $e) {
            Log::error('Database backup failed (mysqldump method)', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'success' => false,
                'path' => null,
                'filename' => null,
                'size' => 0,
                'message' => 'Backup failed: ' . $e->getMessage()
            ];
        }
    }

    /**
     * METHOD 2: Using Laravel's DB facade (Alternative method)
     * 
     * WHY USE THIS?
     * - Works without shell access
     * - Platform independent (works on Windows too)
     * - Good for shared hosting
     * 
     * WHERE TO USE?
     * - Shared hosting without shell access
     * - Small to medium databases
     * - When you don't have mysqldump access
     * 
     * LIMITATIONS:
     * - Slower than mysqldump
     * - Uses more PHP memory
     * - May timeout on large databases
     */
    public function createBackupUsingLaravel(): array
    {
        try {
            $timestamp = Carbon::now()->format('Y-m-d_H-i-s');
            $fileName = "backup_{$timestamp}.sql";
            $backupPath = storage_path("app/backups/{$fileName}");

            // Create directory
            if (!File::isDirectory(storage_path('app/backups'))) {
                File::makeDirectory(storage_path('app/backups'), 0755, true);
            }

            // Get all tables
            // WHY? We need to backup all tables in database
            $tables = \DB::select('SHOW TABLES');
            $dbName = env('DB_DATABASE');
            $tableKey = "Tables_in_{$dbName}";

            $sql = "-- Database Backup\n";
            $sql .= "-- Generated: " . Carbon::now()->toDateTimeString() . "\n\n";
            $sql .= "SET FOREIGN_KEY_CHECKS=0;\n\n";

            // Loop through each table
            foreach ($tables as $table) {
                $tableName = $table->$tableKey;
                
                // Get CREATE TABLE statement
                $createTable = \DB::select("SHOW CREATE TABLE `{$tableName}`")[0];
                $sql .= "-- Table: {$tableName}\n";
                $sql .= "DROP TABLE IF EXISTS `{$tableName}`;\n";
                $sql .= $createTable->{'Create Table'} . ";\n\n";

                // Get table data
                $rows = \DB::table($tableName)->get();
                
                if ($rows->count() > 0) {
                    foreach ($rows as $row) {
                        $values = array_map(function($value) {
                            return is_null($value) ? 'NULL' : "'" . addslashes($value) . "'";
                        }, (array)$row);
                        
                        $sql .= "INSERT INTO `{$tableName}` VALUES (" . implode(', ', $values) . ");\n";
                    }
                    $sql .= "\n";
                }
            }

            $sql .= "SET FOREIGN_KEY_CHECKS=1;\n";

            // Write to file
            File::put($backupPath, $sql);

            return [
                'success' => true,
                'path' => $backupPath,
                'filename' => $fileName,
                'size' => File::size($backupPath),
                'size_human' => $this->formatBytes(File::size($backupPath)),
                'message' => 'Database backup created successfully'
            ];

        } catch (\Exception $e) {
            Log::error('Database backup failed (Laravel method)', [
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'path' => null,
                'filename' => null,
                'size' => 0,
                'message' => 'Backup failed: ' . $e->getMessage()
            ];
        }
    }

    /**
     * METHOD 3: Compressed backup (Saves space)
     * 
     * WHY COMPRESS?
     * - Reduces file size by 70-90%
     * - Faster upload to Google Drive
     * - Saves storage space
     * 
     * WHERE TO USE?
     * - Large databases
     * - Limited storage space
     * - Slow internet connections
     */
    public function createCompressedBackup(): array
    {
        try {
            // First create SQL backup
            $backupResult = $this->createBackupUsingMysqldump();
            
            if (!$backupResult['success']) {
                return $backupResult;
            }

            $sqlPath = $backupResult['path'];
            $gzPath = $sqlPath . '.gz';

            // Compress using gzip
            // WHY GZIP? Best compression ratio for SQL files
            $command = sprintf(
                'gzip -c %s > %s',
                escapeshellarg($sqlPath),
                escapeshellarg($gzPath)
            );

            exec($command, $output, $returnVar);

            if ($returnVar !== 0 || !File::exists($gzPath)) {
                throw new \Exception('Compression failed');
            }

            // Delete original SQL file to save space
            File::delete($sqlPath);

            return [
                'success' => true,
                'path' => $gzPath,
                'filename' => basename($gzPath),
                'size' => File::size($gzPath),
                'size_human' => $this->formatBytes(File::size($gzPath)),
                'message' => 'Compressed backup created successfully'
            ];

        } catch (\Exception $e) {
            Log::error('Compressed backup failed', ['error' => $e->getMessage()]);
            
            return [
                'success' => false,
                'path' => null,
                'filename' => null,
                'size' => 0,
                'message' => 'Compressed backup failed: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Clean old backups from local storage
     * 
     * WHY? Prevents disk space from filling up
     * 
     * @param int $daysToKeep Number of days to keep backups
     */
    public function cleanOldBackups(int $daysToKeep = 7): void
    {
        try {
            $backupPath = storage_path('app/backups');
            $files = File::files($backupPath);
            $now = Carbon::now();

            foreach ($files as $file) {
                $fileTime = Carbon::createFromTimestamp(File::lastModified($file));
                
                // Delete if older than specified days
                if ($now->diffInDays($fileTime) > $daysToKeep) {
                    File::delete($file);
                    Log::info('Deleted old backup: ' . $file->getFilename());
                }
            }
        } catch (\Exception $e) {
            Log::error('Failed to clean old backups', ['error' => $e->getMessage()]);
        }
    }

    /**
     * Helper: Format bytes to human readable size
     * 
     * WHY? Makes file sizes easier to understand
     * Example: 1572864 bytes -> 1.5 MB
     */
    private function formatBytes(int $bytes, int $precision = 2): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        
        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, $precision) . ' ' . $units[$i];
    }
}
