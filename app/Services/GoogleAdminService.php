<?php

namespace App\Services;

use Google\Client;
use Google\Service\Directory;
use Google\Service\Directory\User as GoogleUser;
use Google\Service\Directory\UserName;
use Illuminate\Support\Facades\Log;
use Exception;

class GoogleAdminService
{
    private $client;
    private $service;
    private $domain;


    public function __construct()
    {
        $this->domain = config('google.domain', 'leads.edu.pk');
        $this->initializeClient();
    }


    //  Initialize Google Client
    private function initializeClient()
    {
        try {
            $this->client = new Client();
            
            // Service Account Authentication (server-to-server)
            if (config('google.service_account_path')) {
                $this->client->setAuthConfig(config('google.service_account_path'));
                $this->client->addScope(Directory::ADMIN_DIRECTORY_USER);
                
                // Impersonate admin user (required for Admin SDK)
                $adminEmail = config('google.admin_email');
                if ($adminEmail) {
                    $this->client->setSubject($adminEmail);
                }
            }
            
            $this->service = new Directory($this->client);
            
        } catch (Exception $e) {
            Log::error('Google Client initialization failed: ' . $e->getMessage());
            throw new Exception('Google Admin SDK initialization failed');
        }
    }


    
    //   Create a new Google Workspace user
    public function createUser($userData)
    {
        try {
            // Validate required fields
            $this->validateUserData($userData);
            
            // Create Google User object
            $googleUser = $this->buildGoogleUser($userData);
            
            // Make API call to create user
            $createdUser = $this->service->users->insert($googleUser);
            
            Log::info('Google user created successfully', [
                'email' => $userData['email'],
                'google_id' => $createdUser->getId()
            ]);
            
            return [
                'success' => true,
                'user' => $createdUser,
                'google_id' => $createdUser->getId(),
                'email' => $createdUser->getPrimaryEmail(),
                'message' => 'User created successfully'
            ];
            
        } catch (Exception $e) {
            Log::error('Failed to create Google user', [
                'email' => $userData['email'] ?? 'unknown',
                'error' => $e->getMessage()
            ]);
            
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'message' => 'Failed to create user'
            ];
        }
    }


    
     //  Build Google User object from our data
    private function buildGoogleUser($userData)
    {
        $googleUser = new GoogleUser();
        
        // Set basic user information
        $googleUser->setPrimaryEmail($userData['email']);
        $googleUser->setPassword($userData['password']);
        
        // Set user name
        $userName = new UserName();
        $userName->setGivenName($userData['first_name']);
        $userName->setFamilyName($userData['last_name']);
        $googleUser->setName($userName);
        
        // Set additional properties
        $googleUser->setChangePasswordAtNextLogin(true); // Force password change on first login
        $googleUser->setIncludeInGlobalAddressList(true);
        
        // Set organizational unit (if specified)
        if (isset($userData['org_unit'])) {
            $googleUser->setOrgUnitPath($userData['org_unit']);
        }
        
        return $googleUser;
    }


    
    //   Validate user data before creating
    private function validateUserData($userData)
    {
        $required = ['email', 'first_name', 'last_name', 'password'];
        
        foreach ($required as $field) {
            if (empty($userData[$field])) {
                throw new Exception("Required field '$field' is missing");
            }
        }
        
        // Validate email format
        if (!filter_var($userData['email'], FILTER_VALIDATE_EMAIL)) {
            throw new Exception("Invalid email format");
        }
        
        // Check if email belongs to our domain
        $emailDomain = substr(strrchr($userData['email'], "@"), 1);
        if ($emailDomain !== $this->domain) {
            throw new Exception("Email must be from {$this->domain} domain");
        }
    }



    //   Generate secure temporary password  
    public function generateTemporaryPassword($length = 12)
    {
        $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*';
        return substr(str_shuffle($chars), 0, $length);
    }


    
    //   Check if user exists in Google Workspace
    public function userExists($email)
    {
        try {
            $this->service->users->get($email);
            return true;
        } catch (Exception $e) {
            return false;
        }
    }

    
    
    //   Get user information from Google
    public function getUser($email)
    {
        try {
            return $this->service->users->get($email);
        } catch (Exception $e) {
            Log::error('Failed to get Google user', [
                'email' => $email,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }
}