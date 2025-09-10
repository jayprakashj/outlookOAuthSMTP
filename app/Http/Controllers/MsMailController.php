<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\OAuth;
use TheNetworg\OAuth2\Client\Provider\Azure as AzureProvider;
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
          'scope' => env('MS_SCOPES'), // offline_access + SMTP.Send
        ];
        $url = 'https://login.microsoftonline.com/'.env('MS_TENANT','common').'/oauth2/v2.0/authorize?'.http_build_query($params);

        return redirect()->away($url);
    }

    public function callback(Request $r) {
        $token = Http::asForm()->post(
          'https://login.microsoftonline.com/'.env('MS_TENANT','common').'/oauth2/v2.0/token',
          [
            'client_id' => env('MS_CLIENT_ID'),
            'client_secret' => env('MS_CLIENT_SECRET'),
            'grant_type' => 'authorization_code',
            'code' => $r->query('code'),
            'redirect_uri' => env('MS_REDIRECT_URI'),
          ]
        )->json();
    
        // token['access_token'], token['refresh_token'], token['expires_in']
        // Get the signed-in email (recommended: call "https://graph.microsoft.com/v1.0/me")
        // Save email + refresh_token (encrypted) + expires_at in your oauth_mail_accounts table.
    }
    
    function sendViaOutlook(string $fromEmail, array $to, string $subject, string $html) {
        $acct = OauthMailAccount::where('provider','microsoft')->where('email',$fromEmail)->firstOrFail();
    
        // Refresh access token if near expiry:
        $resp = Http::asForm()->post('https://login.microsoftonline.com/'.env('MS_TENANT','common').'/oauth2/v2.0/token', [
            'client_id' => env('MS_CLIENT_ID'),
            'client_secret' => env('MS_CLIENT_SECRET'),
            'grant_type' => 'refresh_token',
            'refresh_token' => decrypt($acct->refresh_token),
            'scope' => env('MS_SCOPES'),
        ])->json();
        $accessToken = $resp['access_token'];
    
        $mail = new PHPMailer(true);
        $mail->isSMTP();
        $mail->Host = 'smtp.office365.com';
        $mail->Port = 587;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->SMTPAuth = true;
        $mail->AuthType = 'XOAUTH2';
    
        // PHPMailer will use the provider + refresh token to mint tokens as needed
        $provider = new AzureProvider([
            'clientId' => env('MS_CLIENT_ID'),
            'clientSecret' => env('MS_CLIENT_SECRET'),
            'tenant' => env('MS_TENANT','common'),
          ]);
    
        $mail->setOAuth(new OAuth([
            'provider'     => $provider,
            'clientId'     => env('MS_CLIENT_ID'),
            'clientSecret' => env('MS_CLIENT_SECRET'),
            'refreshToken' => decrypt($acct->refresh_token),
            'userName'     => $fromEmail, // for shared mailboxes, set to the shared mailbox address
        ]));
    
        $mail->setFrom($fromEmail);
        foreach ($to as $rcpt) $mail->addAddress($rcpt);
        $mail->Subject = $subject;
        $mail->msgHTML($html);
        $mail->send();
    }
    
}
