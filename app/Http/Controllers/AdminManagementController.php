<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\CreateEmailRequest;
use App\Models\InstitutionalEmail;
use App\Services\GoogleAdminService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\JsonResponse;
use Exception;

class AdminManagementController extends Controller
{
    private $googleAdminService;

    public function __construct(GoogleAdminService $googleAdminService)
    {
        $this->googleAdminService = $googleAdminService;
    }


    
    //   Direct creation via API
    //   Receive data -> Validate -> Create Google account -> Store in DB 
    public function createInstitutionalEmail(CreateEmailRequest $request): JsonResponse
    {
        try {
            DB::beginTransaction();
            
            // Extract and prepare data
            $userData = $this->prepareUserData($request);
            
            // Check if email already exists in Google
            if ($this->googleAdminService->userExists($userData['email'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Email already exists in Google Workspace'
                ], 409);
            }
            
            // Create Google Workspace account
            $googleResult = $this->googleAdminService->createUser($userData);
            
            if (!$googleResult['success']) {
                throw new Exception($googleResult['error']);
            }
            
            // Store in local database
            $institutionalEmail = $this->storeEmailRecord($request, $userData, $googleResult);
            
            DB::commit();
            
            Log::info('Institutional email created successfully', [
                'user_id' => $request->user_id,
                'email' => $userData['email']
            ]);
            
            return response()->json([
                'success' => true,
                'message' => 'Institutional email created successfully',
                'data' => [
                    'id' => $institutionalEmail->id,
                    'email' => $institutionalEmail->email,
                    'username' => $institutionalEmail->username,
                    'status' => $institutionalEmail->status,
                    'temporary_password' => $userData['password'], // Return for admin use
                    'google_user_id' => $googleResult['google_id']
                ]
            ], 201);
            
        } catch (Exception $e) {
            DB::rollBack();
            
            Log::error('Failed to create institutional email', [
                'user_id' => $request->user_id ?? null,
                'username' => $request->username ?? null,
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to create institutional email',
                'error' => $e->getMessage()
            ], 500);
        }
    }


        //  Batch Creation from First Module
        //  Fetch from first module -> Validate -> Batch create
    public function createEmailFromFirstModule(Request $request): JsonResponse
    {
        $request->validate([
            'user_ids' => 'required|array',
            'user_ids.*' => 'exists:users,id'
        ]);
        
        try {
            $results = [];
            $successCount = 0;
            $failCount = 0;
            
            foreach ($request->user_ids as $userId) {
                try {
                    // Fetch from first module API
                    $userInfo = $this->fetchUserFromFirstModule($userId);
                    
                    if (!$userInfo) {
                        $results[] = [
                            'user_id' => $userId,
                            'success' => false,
                            'message' => 'User not found in first module'
                        ];
                        $failCount++;
                        continue;
                    }
                    
                    // Create email account
                    $result = $this->createEmailFromUserInfo($userInfo);
                    $results[] = $result;
                    
                    if ($result['success']) {
                        $successCount++;
                    } else {
                        $failCount++;
                    }
                    
                } catch (Exception $e) {
                    $results[] = [
                        'user_id' => $userId,
                        'success' => false,
                        'message' => $e->getMessage()
                    ];
                    $failCount++;
                }
            }
            
            return response()->json([
                'success' => $successCount > 0,
                'message' => "Processed {$successCount} successful, {$failCount} failed",
                'summary' => [
                    'total' => count($request->user_ids),
                    'successful' => $successCount,
                    'failed' => $failCount
                ],
                'results' => $results
            ]);
            
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Batch creation failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }


        //   Webhook endpoint for automatic creation
        //   When existed system creates a user, it can call this webhook
    public function webhookCreateEmail(Request $request): JsonResponse
    {
        // Validate webhook signature (security measure)
        if (!$this->validateWebhookSignature($request)) {
            return response()->json(['error' => 'Invalid signature'], 401);
        }
        
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'username' => 'required|string',
            'first_name' => 'required|string',
            'last_name' => 'required|string',
            'department' => 'nullable|string'
        ]);
        
        try {
            $userData = $this->prepareUserDataFromWebhook($request);
            $googleResult = $this->googleAdminService->createUser($userData);
            
            if ($googleResult['success']) {
                $this->storeEmailRecordFromWebhook($request, $userData, $googleResult);
                
                return response()->json([
                    'success' => true,
                    'message' => 'Email created via webhook',
                    'email' => $userData['email']
                ]);
            }
            
            return response()->json([
                'success' => false,
                'message' => $googleResult['message']
            ], 500);
            
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }




    /**
     * Get institutional email status
     */
    public function getEmailStatus($userId): JsonResponse
    {
        try {
            $email = InstitutionalEmail::where('user_id', $userId)->first();
            
            if (!$email) {
                return response()->json([
                    'success' => false,
                    'message' => 'No institutional email found'
                ], 404);
            }
            
            // Sync with Google to get latest status
            $googleUser = $this->googleAdminService->getUser($email->email);
            
            if ($googleUser) {
                // Update local record with Google status
                $email->update([
                    'status' => $googleUser->getSuspended() ? 'suspended' : 'active',
                    'last_synced_at' => now()
                ]);
            }
            
            return response()->json([
                'success' => true,
                'data' => [
                    'id' => $email->id,
                    'email' => $email->email,
                    'username' => $email->username,
                    'status' => $email->status,
                    'created_at' => $email->email_created_at,
                    'last_synced' => $email->last_synced_at,
                    'google_status' => $googleUser ? 'active' : 'not_found'
                ]
            ]);
            
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get email status',
                'error' => $e->getMessage()
            ], 500);
        }
    }





    // ===========================================
    // PRIVATE HELPER METHODS
    // ===========================================

    

    //   Prepare user data for Google account creation
    private function prepareUserData($request): array
    {
        // Convert username to email
        $email = $request->username . '@' . config('google.domain', 'leads.edu.pk');
        
        return [
            'email' => $email,
            'first_name' => $request->first_name,
            'last_name' => $request->last_name,
            'password' => $this->googleAdminService->generateTemporaryPassword(),
            'org_unit' => $request->org_unit ?? '/Students', // Default Lahore Leads University unit
            'department' => $request->department
        ];
    }

   
    //   Store email record in local database
    private function storeEmailRecord($request, $userData, $googleResult): InstitutionalEmail
    {
        return InstitutionalEmail::create([
            'user_id' => $request->user_id,
            'username' => $request->username,
            'email' => $userData['email'],
            'first_name' => $userData['first_name'],
            'last_name' => $userData['last_name'],
            'department' => $userData['department'],
            'google_user_id' => $googleResult['google_id'],
            'status' => 'active',
            'password' => $userData['password'],
            'google_response' => $googleResult,
            'email_created_at' => now(),
            'last_synced_at' => now()
        ]);
    }


    //   Fetch user information from existed system's API
    private function fetchUserFromFirstModule($userId)
    {
        // HTTP Client to first module API
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . config('app.first_module_api_key'),
                'Accept' => 'application/json'
            ])->get(config('app.first_module_url') . '/api/users/' . $userId);
            
            if ($response->successful()) {
                return $response->json();
            }
            
        } catch (Exception $e) {
            Log::error('Failed to fetch from Existing API', [
                'user_id' => $userId,
                'error' => $e->getMessage()
            ]);
        }
        
    }


    //   Create email from user info fetched from existing system
    private function createEmailFromUserInfo($userInfo): array
    {
        try {
            DB::beginTransaction();
            
            // Check if already exists
            if (InstitutionalEmail::where('user_id', $userInfo['id'])->exists()) {
                return [
                    'user_id' => $userInfo['id'],
                    'success' => false,
                    'message' => 'Email already exists for this user'
                ];
            }
            
            $userData = [
                'email' => $userInfo['username'] . '@' . config('google.domain'),
                'first_name' => $userInfo['first_name'],
                'last_name' => $userInfo['last_name'],
                'password' => $this->googleAdminService->generateTemporaryPassword(),
                'department' => $userInfo['department'] ?? null
            ];
            
            $googleResult = $this->googleAdminService->createUser($userData);
            
            if ($googleResult['success']) {
                InstitutionalEmail::create([
                    'user_id' => $userInfo['id'],
                    'username' => $userInfo['username'],
                    'email' => $userData['email'],
                    'first_name' => $userData['first_name'],
                    'last_name' => $userData['last_name'],
                    'department' => $userData['department'],
                    'google_user_id' => $googleResult['google_id'],
                    'status' => 'active',
                    'password' => $userData['password'],
                    'google_response' => $googleResult,
                    'email_created_at' => now()
                ]);
                
                DB::commit();
                
                return [
                    'user_id' => $userInfo['id'],
                    'success' => true,
                    'email' => $userData['email'],
                    'message' => 'Email created successfully'
                ];
            }
            
            DB::rollBack();
            return [
                'user_id' => $userInfo['id'],
                'success' => false,
                'message' => $googleResult['message']
            ];
            
        } catch (Exception $e) {
            DB::rollBack();
            return [
                'user_id' => $userInfo['id'],
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }


    //   Validate webhook signature for security
    private function validateWebhookSignature($request): bool
    {
        $signature = $request->header('X-Webhook-Signature');
        $payload = $request->getContent();
        $secret = config('app.webhook_secret');
        
        if (!$signature || !$secret) {
            return false;
        }
        
        $computedSignature = 'sha256=' . hash_hmac('sha256', $payload, $secret);
        
        return hash_equals($computedSignature, $signature);
    }

    
    //  Prepare user data from webhook request
    private function prepareUserDataFromWebhook($request): array
    {
        $email = $request->username . '@' . config('google.domain');
        
        return [
            'email' => $email,
            'first_name' => $request->first_name,
            'last_name' => $request->last_name,
            'password' => $this->googleAdminService->generateTemporaryPassword(),
            'department' => $request->department
        ];
    }


    //  Store email record from webhook
    private function storeEmailRecordFromWebhook($request, $userData, $googleResult): InstitutionalEmail
    {
        return InstitutionalEmail::create([
            'user_id' => $request->user_id,
            'username' => $request->username,
            'email' => $userData['email'],
            'first_name' => $userData['first_name'],
            'last_name' => $userData['last_name'],
            'department' => $userData['department'],
            'google_user_id' => $googleResult['google_id'],
            'status' => 'active',
            'password' => $userData['password'],
            'google_response' => $googleResult,
            'email_created_at' => now(),
            'notes' => 'Created via webhook'
        ]);
    }
}
