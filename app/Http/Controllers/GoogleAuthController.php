 <?php

namespace App\Http\Controllers;

use App\Services\GoogleClientService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class GoogleAuthController extends Controller
{
    protected GoogleClientService $googleClientService;

    public function __construct(GoogleClientService $googleClientService)
    {
        $this->googleClientService = $googleClientService;
    }

    /**
     * Redirect to Google OAuth - returns redirect response instead of JSON
     */
    public function redirect(): \Illuminate\Http\RedirectResponse
    {
        try {
            $user = Auth::user();
            if (!$user) {
                return redirect()->route('login')->with('error', 'Please log in first');
            }

            $authUrl = $this->googleClientService->getAuthUrl();
            
            Log::info('Google Auth URL generated for user:', [
                'user_id' => $user->id,
                'url' => $authUrl
            ]);
            
            // Directly redirect to Google OAuth instead of returning JSON
            return redirect($authUrl);
            
        } catch (\Exception $e) {
            Log::error('Error generating Google auth URL:', [
                'user_id' => Auth::id(),
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return redirect()->back()->with('error', 'Failed to connect to Google. Please try again.');
        }
    }

    /**
     * Handle Google OAuth callback
     */
    public function callback(Request $request)
    {
        try {
            $user = Auth::user();
            if (!$user) {
                Log::warning('Google callback attempted without authenticated user');
                return redirect()->route('login')->with('error', 'Please log in to connect your Google account');
            }

            // Debug: Log all incoming parameters
            Log::info('Google callback parameters:', [
                'user_id' => $user->id,
                'params' => $request->all(),
                'url' => $request->fullUrl()
            ]);
            
            // Check if there's an error parameter
            if ($request->has('error')) {
                Log::error('Google OAuth error:', [
                    'user_id' => $user->id,
                    'error' => $request->get('error'),
                    'error_description' => $request->get('error_description')
                ]);
                
                return redirect()->route('calendar.view')->with('error', 'Google connection failed: ' . $request->get('error_description', $request->get('error')));
            }
            
            $code = $request->get('code');
            if (!$code) {
                Log::error('No authorization code received:', [
                    'user_id' => $user->id,
                    'params' => $request->all(),
                    'query_string' => $request->getQueryString(),
                    'full_url' => $request->fullUrl()
                ]);
                
                return redirect()->route('calendar.view')->with('error', 'Authorization code not received from Google');
            }

            Log::info('Attempting to get access token:', [
                'user_id' => $user->id,
                'code' => substr($code, 0, 10) . '...'
            ]);
            
            $tokenData = $this->googleClientService->getAccessToken($code);
            $this->googleClientService->storeToken($user, $tokenData);

            session(['google_connected' => true]);

            Log::info('Google token stored successfully:', ['user_id' => $user->id]);

            return redirect()->route('calendar.view')->with('success', 'Google account connected successfully!');
            
        } catch (\Exception $e) {
            Log::error('Google callback error:', [
                'user_id' => Auth::id(),
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'request_data' => $request->all()
            ]);
            
            return redirect()->route('calendar.view')->with('error', 'Failed to connect Google account: ' . $e->getMessage());
        }
    }

    public function disconnect(Request $request)
    {
        $user = Auth::user();
        if (!$user) {
            return redirect()->route('login');
        }

        // Eager-load the googleToken relationship
        $user->load('googleToken');

        try {
            // Safely clear token if it exists
            if ($user->googleToken) {
                $this->googleClientService->clearToken($user);
            }
            session(['google_connected' => false]);

            return redirect()->route('calendar.view')->with([
                'success' => 'Google account disconnected successfully!',
                'connection_message' => 'Google account disconnected successfully!'
            ]);
        } catch (\Exception $e) {
            \Log::error('Google disconnect error: ' . $e->getMessage());
            return redirect()->route('calendar.view')->with([
                'error' => 'Failed to disconnect Google account.',
                'connection_message' => 'Failed to disconnect Google account.'
            ]);
        }
    }
}