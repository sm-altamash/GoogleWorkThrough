<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\WorkspaveApiService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;


class  WorkspaveApiController extends Controller
{
    protected $googleService;

    public function __construct(WorkspaveApiService $googleService)
    {
        $this->googleService = $googleService;
    }


    public function createUser(Request $request): JsonResponse
    {
        try {
            // Step 1: Validate the request
            $validator = Validator::make($request->all(), [
                'first_name' => 'required|string|min:2|max:50|regex:/^[a-zA-Z\s]+$/',
                'last_name' => 'required|string|min:2|max:50|regex:/^[a-zA-Z\s]+$/',
                'username' => 'required|email|max:255',
            ], [
                'first_name.regex' => 'First name should only contain letters and spaces',
                'last_name.regex' => 'Last name should only contain letters and spaces',
                'username.email' => 'Username must be a valid email address'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors(),
                    'data' => null
                ], 400);
            }

            // Step 2: Extract validated data
            $userData = $validator->validated();
            
            // Step 3: Log the request for auditing
            Log::info('Google Workspace user creation requested', [
                'requester_ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'username' => $userData['username']
            ]);

            // Step 4: Call the service to create user
            $result = $this->googleService->createUser($userData);

            // Step 5: Return appropriate response based on result
            if ($result['success']) {
                return response()->json([
                    'success' => true,
                    'message' => $result['message'],
                    'data' => [
                        'user' => [
                            'email' => $userData['username'],
                            'name' => $userData['first_name'] . ' ' . $userData['last_name'],
                            'temporary_password' => $result['temporary_password'],
                            'primary_email' => $result['user']->getPrimaryEmail(),
                            'id' => $result['user']->getId()
                        ]
                    ]
                ], 201);
            } else {
                // Determine appropriate HTTP status code based on error
                $statusCode = $this->determineErrorStatusCode($result['error']);
                
                return response()->json([
                    'success' => false,
                    'message' => $result['message'],
                    'error' => $result['error'],
                    'data' => null
                ], $statusCode);
            }

        } catch (\Exception $e) {
            Log::error('Unexpected error in createUser', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'An unexpected error occurred',
                'error' => 'Internal server error',
                'data' => null
            ], 500);
        }
    }


    public function createBulkUsers(Request $request): JsonResponse
    {
        try {
            // Step 1: Validate the request structure
            $validator = Validator::make($request->all(), [
                'users' => 'required|array|min:1|max:100', // Limit to 100 users per request
                'users.*.first_name' => 'required|string|min:2|max:50|regex:/^[a-zA-Z\s]+$/',
                'users.*.last_name' => 'required|string|min:2|max:50|regex:/^[a-zA-Z\s]+$/',
                'users.*.username' => 'required|email|max:255',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors(),
                    'data' => null
                ], 400);
            }

            $users = $request->input('users');
            
            Log::info('Bulk user creation requested', [
                'user_count' => count($users),
                'requester_ip' => $request->ip()
            ]);

            // Step 2: Process bulk creation
            $result = $this->googleService->createBulkUsers($users);

            // Step 3: Prepare response
            return response()->json([
                'success' => true,
                'message' => "Bulk user creation completed",
                'data' => [
                    'summary' => [
                        'total_users' => count($users),
                        'successful_creations' => $result['success_count'],
                        'failed_creations' => $result['failure_count'],
                        'success_rate' => round(($result['success_count'] / count($users)) * 100, 2) . '%'
                    ],
                    'results' => $result['results'],
                    'errors' => $result['errors']
                ]
            ], 200);

        } catch (\Exception $e) {
            Log::error('Unexpected error in createBulkUsers', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'An unexpected error occurred during bulk creation',
                'error' => 'Internal server error',
                'data' => null
            ], 500);
        }
    }


    public function getUser(string $email): JsonResponse
    {
        try {
            // Step 1: Validate email format
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid email format',
                    'error' => 'Email validation failed',
                    'data' => null
                ], 400);
            }

            // Step 2: Get user from Google Workspace
            $result = $this->googleService->getUser($email);

            if ($result['success']) {
                $user = $result['user'];
                
                return response()->json([
                    'success' => true,
                    'message' => $result['message'],
                    'data' => [
                        'user' => [
                            'id' => $user->getId(),
                            'primary_email' => $user->getPrimaryEmail(),
                            'name' => [
                                'full_name' => $user->getName()->getFullName(),
                                'given_name' => $user->getName()->getGivenName(),
                                'family_name' => $user->getName()->getFamilyName()
                            ],
                            'org_unit_path' => $user->getOrgUnitPath(),
                            'is_admin' => $user->getIsAdmin(),
                            'is_suspended' => $user->getSuspended(),
                            'creation_time' => $user->getCreationTime(),
                            'last_login_time' => $user->getLastLoginTime()
                        ]
                    ]
                ], 200);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => $result['message'],
                    'error' => $result['error'],
                    'data' => null
                ], 404);
            }

        } catch (\Exception $e) {
            Log::error('Unexpected error in getUser', [
                'email' => $email,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'An unexpected error occurred',
                'error' => 'Internal server error',
                'data' => null
            ], 500);
        }
    }


    public function updateUser(Request $request, string $email): JsonResponse
    {
        try {
            // Step 1: Validate email format
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid email format',
                    'error' => 'Email validation failed',
                    'data' => null
                ], 400);
            }

            // Step 2: Validate request data
            $validator = Validator::make($request->all(), [
                'first_name' => 'sometimes|string|min:2|max:50|regex:/^[a-zA-Z\s]+$/',
                'last_name' => 'sometimes|string|min:2|max:50|regex:/^[a-zA-Z\s]+$/',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors(),
                    'data' => null
                ], 400);
            }

            $updateData = $validator->validated();

            // Step 3: Check if there's actually data to update
            if (empty($updateData)) {
                return response()->json([
                    'success' => false,
                    'message' => 'No valid update data provided',
                    'error' => 'At least one field must be provided for update',
                    'data' => null
                ], 400);
            }

            // Step 4: Update user
            $result = $this->googleService->updateUser($email, $updateData);

            if ($result['success']) {
                return response()->json([
                    'success' => true,
                    'message' => $result['message'],
                    'data' => [
                        'user' => [
                            'email' => $email,
                            'updated_fields' => array_keys($updateData)
                        ]
                    ]
                ], 200);
            } else {
                $statusCode = $this->determineErrorStatusCode($result['error']);
                
                return response()->json([
                    'success' => false,
                    'message' => $result['message'],
                    'error' => $result['error'],
                    'data' => null
                ], $statusCode);
            }

        } catch (\Exception $e) {
            Log::error('Unexpected error in updateUser', [
                'email' => $email,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'An unexpected error occurred',
                'error' => 'Internal server error',
                'data' => null
            ], 500);
        }
    }


    public function deleteUser(string $email): JsonResponse
    {
        try {
            // Step 1: Validate email format
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid email format',
                    'error' => 'Email validation failed',
                    'data' => null
                ], 400);
            }

            // Step 2: Additional security check - prevent deletion of admin users
            // You should implement proper authorization here
            $adminEmails = [config('google.admin_email')]; // Add more admin emails as needed
            
            if (in_array($email, $adminEmails)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot delete admin user',
                    'error' => 'Operation not permitted',
                    'data' => null
                ], 403);
            }

            // Step 3: Delete user
            $result = $this->googleService->deleteUser($email);

            if ($result['success']) {
                return response()->json([
                    'success' => true,
                    'message' => $result['message'],
                    'data' => [
                        'deleted_user' => $email,
                        'deleted_at' => now()->toISOString()
                    ]
                ], 200);
            } else {
                $statusCode = $this->determineErrorStatusCode($result['error']);
                
                return response()->json([
                    'success' => false,
                    'message' => $result['message'],
                    'error' => $result['error'],
                    'data' => null
                ], $statusCode);
            }

        } catch (\Exception $e) {
            Log::error('Unexpected error in deleteUser', [
                'email' => $email,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'An unexpected error occurred',
                'error' => 'Internal server error',
                'data' => null
            ], 500);
        }
    }


    public function healthCheck(): JsonResponse
    {
        try {
            // Try to get the admin user to test connectivity
            $adminEmail = config('google.admin_email');
            $result = $this->googleService->getUser($adminEmail);

            if ($result['success']) {
                return response()->json([
                    'success' => true,
                    'message' => 'Google Workspace service is healthy',
                    'data' => [
                        'status' => 'healthy',
                        'domain' => config('google.domain'),
                        'admin_email' => $adminEmail,
                        'timestamp' => now()->toISOString()
                    ]
                ], 200);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Google Workspace service health check failed',
                    'error' => $result['error'],
                    'data' => [
                        'status' => 'unhealthy',
                        'timestamp' => now()->toISOString()
                    ]
                ], 503);
            }

        } catch (\Exception $e) {
            Log::error('Health check failed', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Google Workspace service is unavailable',
                'error' => 'Service unavailable',
                'data' => [
                    'status' => 'unhealthy',
                    'timestamp' => now()->toISOString()
                ]
            ], 503);
        }
    }


    private function determineErrorStatusCode(string $error): int
    {
        $error = strtolower($error);
        
        // Authentication and authorization errors
        if (strpos($error, 'unauthorized') !== false || strpos($error, 'authentication') !== false) {
            return 401;
        }
        
        if (strpos($error, 'forbidden') !== false || strpos($error, 'permission') !== false) {
            return 403;
        }
        
        // Resource conflicts
        if (strpos($error, 'already exists') !== false || strpos($error, 'duplicate') !== false) {
            return 409;
        }
        
        // Resource not found
        if (strpos($error, 'not found') !== false || strpos($error, 'does not exist') !== false) {
            return 404;
        }
        
        // Rate limiting
        if (strpos($error, 'rate limit') !== false || strpos($error, 'quota') !== false) {
            return 429;
        }
        
        // Server errors
        if (strpos($error, 'internal error') !== false || strpos($error, 'server error') !== false) {
            return 500;
        }
        
        // Default to bad request
        return 400;
    }
}
