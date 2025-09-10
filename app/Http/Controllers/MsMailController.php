<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use App\Models\OauthMailAccount;

class MsMailController extends Controller
{
    public function connect() {
        $params = [
          'client_id' => env('MS_CLIENT_ID'),
          'response_type' => 'code',
          'redirect_uri' => env('MS_REDIRECT_URI'),
          'response_mode' => 'query',
          'scope' => 'https://graph.microsoft.com/User.Read https://graph.microsoft.com/Mail.Send offline_access',
        ];
        $url = 'https://login.microsoftonline.com/'.env('MS_TENANT','common').'/oauth2/v2.0/authorize?'.http_build_query($params);

        return redirect()->away($url);
    }

    public function callback(Request $r) {
        try {
            $token = Http::asForm()->post(
              'https://login.microsoftonline.com/'.env('MS_TENANT','common').'/oauth2/v2.0/token',
              [
                'client_id' => env('MS_CLIENT_ID'),
                'client_secret' => env('MS_CLIENT_SECRET'),
                'grant_type' => 'authorization_code',
                'code' => $r->query('code'),
                'redirect_uri' => env('MS_REDIRECT_URI'),
                'scope' => 'https://graph.microsoft.com/User.Read https://graph.microsoft.com/Mail.Send offline_access',
              ]
            )->json();

            if (isset($token['error'])) {
                return response()->json(['error' => 'Token exchange failed: ' . $token['error_description']], 400);
            }

            // Get user email from Microsoft Graph
            $userInfo = Http::withHeaders([
                'Authorization' => 'Bearer ' . $token['access_token']
            ])->get('https://graph.microsoft.com/v1.0/me')->json();

            if (isset($userInfo['error'])) {
                return response()->json(['error' => 'Failed to get user info: ' . $userInfo['error']['message']], 400);
            }

            $email = $userInfo['mail'] ?? $userInfo['userPrincipalName'];
            $expiresAt = now()->addSeconds($token['expires_in']);

            // Save or update the OAuth account
            OauthMailAccount::updateOrCreate(
                [
                    'provider' => 'microsoft',
                    'email' => $email
                ],
                [
                    'access_token' => $token['access_token'],
                    'refresh_token' => $token['refresh_token'],
                    'expires_at' => $expiresAt
                ]
            );

            // Store email in session for the redirect
            session(['connected_email' => $email]);
            
            return redirect('/?connected=1&email=' . urlencode($email));

        } catch (\Exception $e) {
            return response()->json(['error' => 'An error occurred: ' . $e->getMessage()], 500);
        }
    }
    
    public function refreshToken(Request $request) {
        try {
            $email = $request->input('email');
            $acct = OauthMailAccount::where('provider', 'microsoft')
                ->where('email', $email)
                ->firstOrFail();

            $resp = Http::asForm()->post('https://login.microsoftonline.com/'.env('MS_TENANT','common').'/oauth2/v2.0/token', [
                'client_id' => env('MS_CLIENT_ID'),
                'client_secret' => env('MS_CLIENT_SECRET'),
                'grant_type' => 'refresh_token',
                'refresh_token' => $acct->refresh_token,
                'scope' => 'https://graph.microsoft.com/User.Read https://graph.microsoft.com/Mail.Send offline_access',
            ])->json();

            if (isset($resp['error'])) {
                return response()->json(['error' => 'Token refresh failed: ' . $resp['error_description']], 400);
            }

            $expiresAt = now()->addSeconds($resp['expires_in']);
            
            $acct->update([
                'access_token' => $resp['access_token'],
                'refresh_token' => $resp['refresh_token'],
                'expires_at' => $expiresAt
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Token refreshed successfully',
                'expires_at' => $expiresAt->toDateTimeString()
            ]);

        } catch (\Exception $e) {
            return response()->json(['error' => 'An error occurred: ' . $e->getMessage()], 500);
        }
    }

    public function clearToken(Request $request) {
        try {
            $email = $request->input('email');
            
            // Clear all Microsoft OAuth accounts for this email
            $deletedCount = OauthMailAccount::where('provider', 'microsoft')
                ->where('email', $email)
                ->delete();

            // Also clear any session data
            session()->forget('connected_email');
            session()->forget('oauth_data');

            // Clear any cached tokens or data
            \Cache::forget('oauth_token_' . $email);
            \Cache::forget('oauth_refresh_' . $email);

            if ($deletedCount > 0) {
                return response()->json([
                    'success' => true,
                    'message' => 'All OAuth data cleared successfully. You can now connect with a fresh account.',
                    'cleared_count' => $deletedCount
                ]);
            } else {
                return response()->json([
                    'success' => true,
                    'message' => 'No OAuth data found to clear. You can connect with any account.',
                    'cleared_count' => 0
                ]);
            }

        } catch (\Exception $e) {
            return response()->json(['error' => 'An error occurred: ' . $e->getMessage()], 500);
        }
    }

    public function sendTestEmail(Request $request) {
        try {
            $email = $request->input('email');
            $toEmail = $request->input('to_email', $email); // Default to same email if not provided

            // Debug logging
            \Log::info('Sending test email', [
                'from' => $email,
                'to' => $toEmail,
                'timestamp' => now()->toDateTimeString()
            ]);
            
            $this->sendViaOutlook($email, [$toEmail], 'Test Email - Outlook OAuth Integration', 
                $this->getTestEmailContent($email, $toEmail));

            return response()->json([
                'success' => true,
                'message' => 'Test email sent successfully to ' . $toEmail,
                'debug' => [
                    'from' => $email,
                    'to' => $toEmail,
                    'timestamp' => now()->toDateTimeString()
                ]
            ]);

        } catch (\Exception $e) {
            \Log::error('Test email failed', [
                'error' => $e->getMessage(),
                'from' => $email ?? 'unknown',
                'to' => $toEmail ?? 'unknown'
            ]);
            return response()->json(['error' => 'Failed to send test email: ' . $e->getMessage()], 500);
        }
    }

    public function getTokenStatus(Request $request) {
        try {
            $email = $request->input('email');
            $acct = OauthMailAccount::where('provider', 'microsoft')
                ->where('email', $email)
                ->first();

            if (!$acct) {
                return response()->json([
                    'connected' => false,
                    'message' => 'No OAuth account found for this email'
                ]);
            }

            return response()->json([
                'connected' => true,
                'email' => $acct->email,
                'expires_at' => $acct->expires_at ? $acct->expires_at->toDateTimeString() : null,
                'is_expired' => $acct->isTokenExpired(),
                'is_expiring_soon' => $acct->isTokenExpiringSoon(),
                'expires_in' => $acct->expires_at ? $acct->expires_at->diffForHumans() : 'Unknown'
            ]);

        } catch (\Exception $e) {
            return response()->json(['error' => 'An error occurred: ' . $e->getMessage()], 500);
        }
    }

    public function index() {
        return view('outlook-oauth');
    }

    public function checkAccountStatus(Request $request) {
        try {
            $email = $request->input('email');
            $acct = OauthMailAccount::where('provider', 'microsoft')
                ->where('email', $email)
                ->firstOrFail();

            // Check account permissions and status
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $acct->access_token
            ])->get('https://graph.microsoft.com/v1.0/me');

            if ($response->failed()) {
                throw new \Exception('Failed to get account info: ' . $response->body());
            }

            $userInfo = $response->json();
            
            // Check if account can send emails
            $mailboxSettings = Http::withHeaders([
                'Authorization' => 'Bearer ' . $acct->access_token
            ])->get('https://graph.microsoft.com/v1.0/me/mailboxSettings');

            return response()->json([
                'success' => true,
                'account_info' => [
                    'email' => $userInfo['mail'] ?? $userInfo['userPrincipalName'],
                    'display_name' => $userInfo['displayName'],
                    'account_type' => $userInfo['userType'] ?? 'Unknown',
                    'mailbox_settings_accessible' => $mailboxSettings->successful(),
                    'token_expires_at' => $acct->expires_at->toDateTimeString(),
                    'is_token_expired' => $acct->isTokenExpired()
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to check account status: ' . $e->getMessage()], 500);
        }
    }


    public function disconnectCurrentAccount(Request $request) {
        try {
            // Get the current connected account from the email field
            $email = $request->input('email');
            
            if (!$email) {
                return response()->json(['error' => 'No email provided to disconnect'], 400);
            }

            // Find and delete the current account
            $acct = OauthMailAccount::where('provider', 'microsoft')
                ->where('email', $email)
                ->first();

            if (!$acct) {
                return response()->json([
                    'success' => true,
                    'message' => 'No connected account found for this email. You can connect with any account.',
                    'cleared_count' => 0
                ]);
            }

            // Store account info before deletion
            $accountEmail = $acct->email;
            $accountDisplayName = $acct->email; // We can get display name from Graph API if needed

            // Delete the account
            $acct->delete();

            // Clear session data
            session()->forget('connected_email');
            session()->forget('oauth_data');

            // Clear cached tokens
            \Cache::forget('oauth_token_' . $email);
            \Cache::forget('oauth_refresh_' . $email);

            return response()->json([
                'success' => true,
                'message' => "Successfully disconnected from {$accountEmail}. You can now connect with a fresh account.",
                'disconnected_email' => $accountEmail,
                'cleared_count' => 1
            ]);

        } catch (\Exception $e) {
            return response()->json(['error' => 'An error occurred: ' . $e->getMessage()], 500);
        }
    }

    function sendViaOutlook(string $fromEmail, array $to, string $subject, string $html) {
        $acct = OauthMailAccount::where('provider','microsoft')->where('email',$fromEmail)->firstOrFail();
    
        // Refresh access token if near expiry:
        if ($acct->isTokenExpiringSoon()) {
            $resp = Http::asForm()->post('https://login.microsoftonline.com/'.env('MS_TENANT','common').'/oauth2/v2.0/token', [
                'client_id' => env('MS_CLIENT_ID'),
                'client_secret' => env('MS_CLIENT_SECRET'),
                'grant_type' => 'refresh_token',
                'refresh_token' => $acct->refresh_token,
                'scope' => 'https://graph.microsoft.com/User.Read https://graph.microsoft.com/Mail.Send offline_access',
            ])->json();
            
            if (isset($resp['error'])) {
                throw new \Exception('Token refresh failed: ' . $resp['error_description']);
            }
            
            $expiresAt = now()->addSeconds($resp['expires_in']);
            $acct->update([
                'access_token' => $resp['access_token'],
                'refresh_token' => $resp['refresh_token'],
                'expires_at' => $expiresAt
            ]);
        }

        // Use Microsoft Graph API to send email instead of SMTP
        $this->sendEmailViaGraph($acct->access_token, $fromEmail, $to, $subject, $html);
    }

    private function sendEmailViaGraph($accessToken, $fromEmail, $to, $subject, $html) {
        // Prepare recipients
        $recipients = [];
        foreach ($to as $email) {
            $recipients[] = [
                'emailAddress' => [
                    'address' => $email
                ]
            ];
        }

        // Prepare email message with proper headers to avoid spam detection
        $message = [
            'message' => [
                'subject' => $subject,
                'body' => [
                    'contentType' => 'Text',
                    'content' => $html
                ],
                'toRecipients' => $recipients,
                'from' => [
                    'emailAddress' => [
                        'address' => $fromEmail,
                        'name' => 'Outlook OAuth Test'
                    ]
                ],
                'internetMessageHeaders' => [
                    [
                        'name' => 'X-Mailer',
                        'value' => 'Outlook OAuth Integration'
                    ],
                    [
                        'name' => 'X-Priority',
                        'value' => '3'
                    ],
                    [
                        'name' => 'X-MSMail-Priority',
                        'value' => 'Normal'
                    ]
                ]
            ]
        ];

        // Send email via Microsoft Graph API
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $accessToken,
            'Content-Type' => 'application/json'
        ])->post('https://graph.microsoft.com/v1.0/me/sendMail', $message);

        if ($response->failed()) {
            $error = $response->json();
            throw new \Exception('Failed to send email via Graph API: ' . ($error['error']['message'] ?? 'Unknown error'));
        }
    }

    private function getTestEmailContent($fromEmail, $toEmail) {
        $currentTime = now()->toDateTimeString();
        
        // Simple, plain text email to avoid spam detection
        return "
        Hello,
        
        This is a test email sent from the Outlook OAuth integration.
        
        Details:
        - From: {$fromEmail}
        - To: {$toEmail}
        - Time: {$currentTime}
        - Method: Microsoft Graph API
        
        If you received this email, the OAuth integration is working correctly.
        
        Best regards,
        Outlook OAuth Integration System
        ";
    }
    
}
