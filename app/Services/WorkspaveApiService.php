<?php

namespace App\Services;

use Google\Client;
use Google\Service\Directory;
use Google\Service\Directory\User;
use Google\Service\Directory\UserName;
use Illuminate\Support\Facades\Log;
use Exception;


class  WorkspaveApiService
{
    protected $client;
    protected $service;
    protected $domain;
    protected $adminEmail;


    public function __construct()
    {
        $this->domain = config('google.domain');
        $this->adminEmail = config('google.admin_email');
        
        $this->initializeGoogleClient();
        $this->service = new Directory($this->client);
    }


    private function initializeGoogleClient()
    {
        try {
            $this->client = new Client();
            
            // Set authentication using service account
            $keyPath = config('google.service_account_path');
            
            if (!file_exists($keyPath)) {
                throw new Exception("Google service account file not found at: {$keyPath}");
            }

            $this->client->setAuthConfig($keyPath);
            
            // Set the scopes (permissions) required
            $this->client->setScopes(config('google.scopes'));
            
            // Important: Set the subject (admin user to impersonate) must be: vol@leads.edu.pk 
            // This allows the service account to act as the admin user
            $this->client->setSubject($this->adminEmail);
            
            // Application name for Google's logs
            $this->client->setApplicationName('LAMS');
            
            Log::info('Google Workspace client initialized successfully');
            
        } catch (Exception $e) {
            Log::error('Failed to initialize Google client: ' . $e->getMessage());
            throw $e;
        }
    }


    public function createUser(array $userData)
    {
        try {
            // Step 1: Input Validation
            $validatedData = $this->validateUserData($userData);
            
            // Step 2: Generate secure password
            $temporaryPassword = $this->generateSecurePassword();
            
            // Step 3: Create Google User object
            $googleUser = new User();
            
            // Step 4: Set user name
            $userName = new UserName();
            $userName->setGivenName($validatedData['first_name']);
            $userName->setFamilyName($validatedData['last_name']);
            $userName->setFullName($validatedData['first_name'] . ' ' . $validatedData['last_name']);
            
            $googleUser->setName($userName);
            
            // Step 5: Set primary email and password
            $googleUser->setPrimaryEmail($validatedData['username']);
            $googleUser->setPassword($temporaryPassword);
            
            // Step 6: Additional user properties
            $googleUser->setChangePasswordAtNextLogin(config('google.force_password_change'));
            $googleUser->setOrgUnitPath(config('google.default_org_unit'));
            
            // Step 7: Execute API call with retry logic
            $result = $this->executeWithRetry(function() use ($googleUser) {
                return $this->service->users->insert($googleUser);
            });
            
            Log::info("User created successfully in Google Workspace", [
                'email' => $validatedData['username'],
                'name' => $validatedData['first_name'] . ' ' . $validatedData['last_name']
            ]);
            
            return [
                'success' => true,
                'user' => $result,
                'temporary_password' => $temporaryPassword,
                'message' => 'User created successfully in Google Workspace'
            ];
            
        } catch (Exception $e) {
            Log::error('Failed to create user in Google Workspace', [
                'error' => $e->getMessage(),
                'user_data' => $userData
            ]);
            
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'message' => 'Failed to create user in Google Workspace'
            ];
        }
    }


    private function validateUserData(array $userData)
    {
        $rules = [
            'first_name' => 'required|string|min:2|max:50',
            'last_name' => 'required|string|min:2|max:50',
            'username' => 'required|email'
        ];
        
        $validator = validator($userData, $rules);
        
        if ($validator->fails()) {
            throw new Exception('Invalid user data: ' . implode(', ', $validator->errors()->all()));
        }
        
        // Additional validation: Check if email belongs to our domain
        if (!str_ends_with($userData['username'], '@' . $this->domain)) {
            throw new Exception("Email must belong to domain: {$this->domain}");
        }
        
        // Check if user already exists in Google Workspace
        if ($this->userExists($userData['username'])) {
            throw new Exception("User already exists in Google Workspace: {$userData['username']}");
        }
        
        return $validator->validated();
    }

    public function userExists(string $email): bool
    {
        try {
            $this->service->users->get($email);
            return true;
        } catch (Exception $e) {
            // If user doesn't exist, Google API throws an exception
            return false;
        }
    }


    private function generateSecurePassword(int $length = null): string
    {
        $length = $length ?? config('google.default_password_length');
        
        // Using Laravel's Str helper 
        return \Illuminate\Support\Str::password($length);
        

    }


    private function executeWithRetry(callable $operation, int $maxAttempts = null)
    {
        $maxAttempts = $maxAttempts ?? config('google.retry_attempts');
        $attempt = 1;
        
        while ($attempt <= $maxAttempts) {
            try {
                return $operation();
            } catch (Exception $e) {
                if ($attempt === $maxAttempts || !$this->isRetryableError($e)) {
                    throw $e;
                }
                
                $delay = pow(2, $attempt - 1);
                sleep($delay);
                
                Log::warning("API call failed, retrying in {$delay}s", [
                    'attempt' => $attempt,
                    'error' => $e->getMessage()
                ]);
                
                $attempt++;
            }
        }
    }


    private function isRetryableError(Exception $e): bool
    {
        $retryableCodes = [429, 500, 502, 503, 504];
        
        if (method_exists($e, 'getCode')) {
            return in_array($e->getCode(), $retryableCodes);
        }
        
        // Check error message for network issues
        $retryableMessages = ['timeout', 'connection', 'network'];
        $errorMessage = strtolower($e->getMessage());
        
        foreach ($retryableMessages as $message) {
            if (strpos($errorMessage, $message) !== false) {
                return true;
            }
        }
        
        return false;
    }


    public function getUser(string $email): array
    {
        try {
            $user = $this->service->users->get($email);
            
            return [
                'success' => true,
                'user' => $user,
                'message' => 'User retrieved successfully'
            ];
        } catch (Exception $e) {
            Log::error('Failed to retrieve user from Google Workspace', [
                'email' => $email,
                'error' => $e->getMessage()
            ]);
            
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'message' => 'User not found or error occurred'
            ];
        }
    }


    public function updateUser(string $email, array $updateData): array
    {
        try {
            $googleUser = new User();
            
            // Update name if provided
            if (isset($updateData['first_name']) || isset($updateData['last_name'])) {
                $userName = new UserName();
                if (isset($updateData['first_name'])) {
                    $userName->setGivenName($updateData['first_name']);
                }
                if (isset($updateData['last_name'])) {
                    $userName->setFamilyName($updateData['last_name']);
                }
                if (isset($updateData['first_name']) && isset($updateData['last_name'])) {
                    $userName->setFullName($updateData['first_name'] . ' ' . $updateData['last_name']);
                }
                $googleUser->setName($userName);
            }
            
            $result = $this->executeWithRetry(function() use ($email, $googleUser) {
                return $this->service->users->update($email, $googleUser);
            });
            
            Log::info("User updated successfully in Google Workspace", ['email' => $email]);
            
            return [
                'success' => true,
                'user' => $result,
                'message' => 'User updated successfully'
            ];
            
        } catch (Exception $e) {
            Log::error('Failed to update user in Google Workspace', [
                'email' => $email,
                'error' => $e->getMessage()
            ]);
            
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'message' => 'Failed to update user'
            ];
        }
    }


    public function deleteUser(string $email): array
    {
        try {
            $this->executeWithRetry(function() use ($email) {
                return $this->service->users->delete($email);
            });
            
            Log::info("User deleted successfully from Google Workspace", ['email' => $email]);
            
            return [
                'success' => true,
                'message' => 'User deleted successfully'
            ];
            
        } catch (Exception $e) {
            Log::error('Failed to delete user from Google Workspace', [
                'email' => $email,
                'error' => $e->getMessage()
            ]);
            
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'message' => 'Failed to delete user'
            ];
        }
    }


    public function createBulkUsers(array $users): array
    {
        $results = [
            'success_count' => 0,
            'failure_count' => 0,
            'results' => [],
            'errors' => []
        ];
        
        $batchSize = 10; // Process 10 users at a time
        $batches = array_chunk($users, $batchSize);
        
        foreach ($batches as $batchIndex => $batch) {
            Log::info("Processing batch " . ($batchIndex + 1) . " of " . count($batches));
            
            foreach ($batch as $userData) {
                $result = $this->createUser($userData);
                
                if ($result['success']) {
                    $results['success_count']++;
                } else {
                    $results['failure_count']++;
                    $results['errors'][] = [
                        'user_data' => $userData,
                        'error' => $result['error']
                    ];
                }
                
                $results['results'][] = $result;
                
                // Small delay between requests to avoid rate limiting
                usleep(100000); // 0.1 second
            }
        }
        
        return $results;
    }
}