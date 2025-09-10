# Microsoft Outlook OAuth API Usage

This Laravel application provides OAuth integration with Microsoft Outlook for sending emails.

## Features

1. **Connect to MS Outlook** - OAuth authentication flow
2. **Refresh Token** - Refresh expired access tokens
3. **Clear Token** - Remove stored OAuth credentials
4. **Send Test Email** - Send test emails using OAuth

## API Endpoints

### 1. Connect to Microsoft Outlook
```
GET /connect-ms
```
Initiates the OAuth flow. User will be redirected to Microsoft login page.

### 2. OAuth Callback
```
GET /get-token?code={authorization_code}
```
Handles the OAuth callback and saves token details to database.

**Response:**
```json
{
    "success": true,
    "message": "Successfully connected to Microsoft Outlook",
    "email": "user@example.com",
    "expires_at": "2024-01-01 12:00:00"
}
```

### 3. Refresh Token
```
POST /refresh-token
Content-Type: application/json

{
    "email": "user@example.com"
}
```

**Response:**
```json
{
    "success": true,
    "message": "Token refreshed successfully",
    "expires_at": "2024-01-01 12:00:00"
}
```

### 4. Clear Token
```
POST /clear-token
Content-Type: application/json

{
    "email": "user@example.com"
}
```

**Response:**
```json
{
    "success": true,
    "message": "Token cleared successfully"
}
```

### 5. Send Test Email
```
POST /send-test-email
Content-Type: application/json

{
    "email": "user@example.com",
    "to_email": "recipient@example.com"  // Optional, defaults to same as email
}
```

**Response:**
```json
{
    "success": true,
    "message": "Test email sent successfully to recipient@example.com"
}
```

## Environment Variables Required

Add these to your `.env` file:

```env
MS_CLIENT_ID=your_microsoft_client_id
MS_CLIENT_SECRET=your_microsoft_client_secret
MS_TENANT=common  # Supports both personal and work/school accounts
MS_REDIRECT_URI=http://your-domain.com/get-token
MS_SCOPES=https://graph.microsoft.com/User.Read https://graph.microsoft.com/Mail.Send offline_access
```

## Database

The application uses an `oauth_mail_accounts` table to store:
- `provider` - OAuth provider (microsoft)
- `email` - User's email address
- `access_token` - Encrypted access token
- `refresh_token` - Encrypted refresh token
- `expires_at` - Token expiration timestamp

## Usage Flow

1. User visits `/connect-ms` to start OAuth flow
2. After Microsoft login, user is redirected to `/get-token` with authorization code
3. Application exchanges code for tokens and saves to database
4. Use `/send-test-email` to send emails using stored credentials
5. Tokens are automatically refreshed when needed
6. Use `/refresh-token` to manually refresh tokens
7. Use `/clear-token` to remove stored credentials

## Error Handling

All endpoints return appropriate HTTP status codes and error messages in JSON format:

```json
{
    "error": "Error description"
}
```

Common error scenarios:
- Invalid or expired authorization code
- Token refresh failures
- Missing email parameter
- OAuth account not found
