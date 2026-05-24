# Google OAuth Setup Instructions

To enable Google Sign-In for your Fleet Rental Pro application, follow these steps:

## 1. Create Google OAuth Credentials

1. Go to [Google Cloud Console](https://console.cloud.google.com/)
2. Create a new project or select an existing one
3. Go to "APIs & Services" → "Credentials"
4. Click "Create Credentials" → "OAuth client ID"
5. Select "Web application" as application type
6. Add authorized redirect URI: `https://yourdomain.com/auth/google-callback.php`
   - Replace `yourdomain.com` with your actual domain
7. Click "Create"
8. Note down the **Client ID** and **Client Secret**

## 2. Configure Environment Variables

Add the following to your environment (`.env` file or server environment variables):

```bash
GOOGLE_CLIENT_ID=your_google_client_id_here
GOOGLE_CLIENT_SECRET=your_google_client_secret_here
```

## 3. Run Database Migration

Execute the SQL migration to add Google OAuth support:

```sql
ALTER TABLE users 
ADD COLUMN google_id VARCHAR(100) NULL AFTER driving_license_url,
ADD INDEX idx_google_id (google_id);
```

Or run the migration file:
```bash
mysql -u username -p database_name < database/migrations/add_google_oauth.sql
```

## 4. Test the Integration

1. Visit your login page
2. Click "Sign in with Google"
3. Complete the Google authentication flow
4. Verify you're logged in correctly

## Important Notes

- **New Users**: Google OAuth will create new accounts as 'customer' role
- **Existing Users**: If they sign in with Google, their account will be linked to their Google ID
- **Tenant Isolation**: Users can only sign in to their assigned tenant (subdomain)
- **Security**: State parameter prevents CSRF attacks
- **Privacy**: Google only provides email and basic profile information

## Troubleshooting

- **"Google OAuth not configured"**: Check your environment variables
- **"Invalid state parameter"**: Clear browser cookies and try again
- **"Account not found"**: Ensure users are signing in through their tenant's subdomain
- **Redirect loop**: Check that the redirect URI in Google Console matches exactly

## Required Scopes

The integration requests these Google OAuth scopes:
- `openid` - Basic authentication
- `email` - User's email address
- `profile` - User's basic profile information (name)
