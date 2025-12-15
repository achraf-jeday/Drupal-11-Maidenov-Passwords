# Maidenov User API

This module provides REST API endpoints for user registration and packing key management for the **Maidenov Passwords** application.

## Overview

The Packing Key is a user-defined master key used by the frontend application to **encrypt and decrypt confidential password data**. This key is:
- Entered by the user in the frontend
- Hashed and stored securely in Drupal (using Bcrypt/Argon2)
- Never stored in plain text
- Used to validate the user's key before allowing access to encrypted data
- Essential for the security model of the password manager

The module provides four REST endpoints for managing user accounts and packing keys, plus a backend form for administrative management.

## Features

### 1. User Registration Endpoint
- **Endpoint**: `POST /api/register`
- **Access**: Anonymous users
- **Purpose**: Register new user accounts
- **Automatic Role Assignment**: All users registered via this endpoint automatically receive the `confidential_data_user` role

**Request Body**:
```json
{
  "email": "user@example.com",
  "password": "SecurePassword123",
  "username": "optional_username"
}
```

**Response** (201 Created):
```json
{
  "message": "User registered successfully.",
  "uid": 1,
  "email": "user@example.com",
  "username": "user@example.com"
}
```

### 2. Packing Key Update Endpoint
- **Endpoint**: `POST /api/user/packing-key`
- **Access**: Confidential Data User role only (OAuth2 protected)
- **Purpose**: Set or update the user's packing key (master key for encrypting/decrypting confidential password data in the frontend)

**Request Body**:
```json
{
  "packing_key": "user-entered-key",
  "packing_key_confirm": "user-entered-key"
}
```

**Response** (200 OK):
```json
{
  "message": "Packing key updated successfully."
}
```

**Note**: This endpoint does NOT require the current password since the user is already authenticated via OAuth2. Password verification is only required when changing the packing key through the Drupal backend user edit form.

### 3. Packing Key Validation Endpoint
- **Endpoint**: `POST /api/user/validate-packing-key`
- **Access**: Confidential Data User role only
- **Purpose**: Validate if the provided packing key is correct (required before the frontend can decrypt and display the user's stored passwords)

**Request Body**:
```json
{
  "packing_key": "user-entered-key"
}
```

**Response** (200 OK):
```json
{
  "valid": true,
  "message": "Packing key is correct."
}
```

**Or if incorrect**:
```json
{
  "valid": false,
  "message": "Packing key is incorrect."
}
```

### 4. Check Packing Key Exists Endpoint
- **Endpoint**: `GET /api/user/packing-key/exists`
- **Access**: Confidential Data User role only
- **Purpose**: Check if the user has set a packing key (determines if user needs to create a packing key or validate an existing one to access encrypted data)

**Response** (200 OK):
```json
{
  "exists": true,
  "message": "Packing key has been set."
}
```

**Or if not set**:
```json
{
  "exists": false,
  "message": "Packing key has not been set."
}
```

## User Flow

The typical user flow with encryption/decryption workflow:

1. **Registration**: User registers via `POST /api/register`
2. **Login**: User authenticates (via OAuth2)
3. **Check Packing Key**: Frontend calls `GET /api/user/packing-key/exists`
   - If `exists: false` → Redirect to packing key setup
   - If `exists: true` → Prompt for packing key validation
4. **Set Packing Key** (first time):
   - User creates a packing key via `POST /api/user/packing-key`
   - Frontend uses this key to encrypt password data before storing
5. **Validate Packing Key** (subsequent logins):
   - User enters their packing key in the frontend
   - Frontend validates via `POST /api/user/validate-packing-key`
   - If valid, frontend uses this key to decrypt stored passwords
6. **Access Protected Data**: Once packing key is validated, the frontend can decrypt and display the user's stored passwords, and encrypt any new passwords before storing them

## Testing the Endpoints (Command Line)

Here are practical curl examples for testing all four endpoints from the command line.

### Prerequisites

Set your base URL as a variable (adjust as needed):
```bash
BASE_URL="http://localhost:8080"
```

### OAuth Configuration & Permissions

**IMPORTANT**: Before using the API endpoints, ensure the OAuth scope is properly configured:

#### OAuth Scope Setup

The `test-frontend` OAuth consumer uses the `basic` scope which must include these permissions:

```bash
# Add required permissions to the basic scope
docker exec drupal-drupal-1 drush ev "\$scope = \Drupal::entityTypeManager()->getStorage('oauth2_scope')->load('basic'); \$perms = ['access user profiles', 'restful get packing_key_exists', 'restful post packing_key_validate', 'restful post packing_key_update']; \$scope->set('permissions', \$perms); \$scope->save();"

# Clear cache
docker exec drupal-drupal-1 drush cr
```

#### Required Role Permissions

Ensure the `confidential_data_user` role has these permissions:
- `access user profiles`
- `restful get packing_key_exists`
- `restful post packing_key_validate`
- `restful post packing_key_update`

```bash
# Grant permissions to the role
docker exec drupal-drupal-1 drush role:perm:add confidential_data_user 'access user profiles'
docker exec drupal-drupal-1 drush role:perm:add confidential_data_user 'restful get packing_key_exists'
docker exec drupal-drupal-1 drush role:perm:add confidential_data_user 'restful post packing_key_validate'
docker exec drupal-drupal-1 drush role:perm:add confidential_data_user 'restful post packing_key_update'
```

### 1. Test User Registration

Register a new user (no authentication required):

```bash
curl -X POST "${BASE_URL}/api/register" \
  -H "Content-Type: application/json" \
  -d '{
    "email": "testuser@example.com",
    "password": "SecurePass123!",
    "username": "testuser"
  }'
```

**Expected Response**:
```json
{
  "message": "User registered successfully.",
  "uid": 2,
  "email": "testuser@example.com",
  "username": "testuser"
}
```

### 2. Get OAuth Token (For Authenticated Requests)

Before testing the authenticated endpoints, you need an OAuth2 access token.

**Important Notes:**
- Tokens expire after **5 minutes** (300 seconds)
- Must include `scope=basic` to get the required permissions
- The `test-frontend` client uses password grant (no client secret required)

```bash
# Generate token and extract access_token
TOKEN=$(curl -s -X POST "${BASE_URL}/oauth/token" \
  -H "Content-Type: application/x-www-form-urlencoded" \
  -d "grant_type=password&client_id=test-frontend&username=testuser&password=SecurePass123!&scope=basic" \
  | grep -o '"access_token":"[^"]*' | cut -d'"' -f4)

# Verify token was obtained
echo "Token obtained: ${TOKEN:0:50}..."
```

**Alternative: Get full token response**
```bash
curl -s -X POST "${BASE_URL}/oauth/token" \
  -H "Content-Type: application/x-www-form-urlencoded" \
  -d "grant_type=password&client_id=test-frontend&username=testuser&password=SecurePass123!&scope=basic"
```

**Expected Response**:
```json
{
  "token_type": "Bearer",
  "expires_in": 300,
  "access_token": "eyJ0eXAiOiJKV1QiLCJhbGc...",
  "refresh_token": "def50200..."
}
```

### 3. Test Check Packing Key Exists

Check if the user has set a packing key:

```bash
curl -X GET "${BASE_URL}/api/user/packing-key/exists" \
  -H "Authorization: Bearer $TOKEN" \
  -H "Accept: application/json"
```

**Expected Response** (not set):
```json
{
  "exists": false,
  "message": "Packing key has not been set."
}
```

### 4. Test Set/Update Packing Key

Set the user's packing key for the first time:

```bash
curl -X POST "${BASE_URL}/api/user/packing-key" \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "packing_key": "MySecretKey123!",
    "packing_key_confirm": "MySecretKey123!"
  }'
```

**Expected Response**:
```json
{
  "message": "Packing key updated successfully."
}
```

### 5. Test Validate Packing Key

Validate the user's packing key (correct key):

**⚠️ IMPORTANT: Special Characters in Bash**

When your packing key contains special characters like `!`, you must use a JSON file to avoid shell interpretation issues:

```bash
# Create JSON file with packing key (recommended method)
printf '{"packing_key":"MySecretKey123!"}' > /tmp/packing_key.json

# Make the API call
curl -X POST "${BASE_URL}/api/user/validate-packing-key" \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  --data '@/tmp/packing_key.json'
```

**Alternative: Simple keys without special characters**
```bash
# If your key doesn't contain !, $, or other special chars
curl -X POST "${BASE_URL}/api/user/validate-packing-key" \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{"packing_key":"SimpleKey123"}'
```

**Expected Response** (correct):
```json
{
  "valid": true,
  "message": "Packing key is correct."
}
```

**Test with wrong key**:
```bash
printf '{"packing_key":"WrongKey456"}' > /tmp/wrong_key.json

curl -X POST "${BASE_URL}/api/user/validate-packing-key" \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  --data '@/tmp/wrong_key.json'
```

**Expected Response** (incorrect):
```json
{
  "valid": false,
  "message": "Packing key is incorrect."
}
```

### Complete Test Flow Script

Here's a complete bash script to test the entire flow. Save this as `test_api.sh`:

```bash
#!/bin/bash

BASE_URL="http://localhost:8080"

echo "=== Maidenov User API - Complete Test Flow ==="
echo ""

# 1. Register user
echo "=== 1. Registering user ==="
REGISTER_RESPONSE=$(curl -s -X POST "${BASE_URL}/api/register" \
  -H "Content-Type: application/json" \
  -d '{
    "email": "apitest@example.com",
    "password": "TestPass123!",
    "username": "apitest"
  }')
echo "$REGISTER_RESPONSE"
echo ""

# 2. Get OAuth token
echo "=== 2. Getting OAuth token ==="
TOKEN=$(curl -s -X POST "${BASE_URL}/oauth/token" \
  -H "Content-Type: application/x-www-form-urlencoded" \
  -d "grant_type=password&client_id=test-frontend&username=apitest&password=TestPass123!&scope=basic" \
  | grep -o '"access_token":"[^"]*' | cut -d'"' -f4)

if [ -z "$TOKEN" ]; then
  echo "❌ Failed to get token!"
  exit 1
fi

echo "✓ Token obtained: ${TOKEN:0:50}..."
echo ""

# 3. Check if packing key exists
echo "=== 3. Checking if packing key exists ==="
curl -s -X GET "${BASE_URL}/api/user/packing-key/exists" \
  -H "Authorization: Bearer $TOKEN" \
  -H "Accept: application/json"
echo ""
echo ""

# 4. Set packing key
echo "=== 4. Setting packing key ==="
printf '{"packing_key":"MySecretKey123!","packing_key_confirm":"MySecretKey123!"}' > /tmp/set_packing_key.json

curl -s -X POST "${BASE_URL}/api/user/packing-key" \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  --data '@/tmp/set_packing_key.json'
echo ""
echo ""

# 5. Verify packing key was set
echo "=== 5. Verifying packing key exists ==="
curl -s -X GET "${BASE_URL}/api/user/packing-key/exists" \
  -H "Authorization: Bearer $TOKEN" \
  -H "Accept: application/json"
echo ""
echo ""

# 6. Validate packing key (correct)
echo "=== 6. Validating packing key (correct) ==="
printf '{"packing_key":"MySecretKey123!"}' > /tmp/validate_correct.json

curl -s -X POST "${BASE_URL}/api/user/validate-packing-key" \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  --data '@/tmp/validate_correct.json'
echo ""
echo ""

# 7. Validate packing key (incorrect)
echo "=== 7. Validating packing key (incorrect) ==="
printf '{"packing_key":"WrongKey456"}' > /tmp/validate_wrong.json

curl -s -X POST "${BASE_URL}/api/user/validate-packing-key" \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  --data '@/tmp/validate_wrong.json'
echo ""
echo ""

# Cleanup
rm -f /tmp/set_packing_key.json /tmp/validate_correct.json /tmp/validate_wrong.json

echo "=== Testing complete! ==="
echo ""
echo "To reuse this token (valid for 5 minutes), run:"
echo "  export TOKEN=\"$TOKEN\""
```

**Run the script:**
```bash
chmod +x test_api.sh
./test_api.sh
```

**Expected output:**
```
=== Maidenov User API - Complete Test Flow ===

=== 1. Registering user ===
{"message":"User registered successfully.","uid":7,"email":"apitest@example.com","username":"apitest"}

=== 2. Getting OAuth token ===
✓ Token obtained: eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiIsImp0aSI6Ij...

=== 3. Checking if packing key exists ===
{"exists":false,"message":"Packing key has not been set."}

=== 4. Setting packing key ===
{"message":"Packing key updated successfully."}

=== 5. Verifying packing key exists ===
{"exists":true,"message":"Packing key has been set."}

=== 6. Validating packing key (correct) ===
{"valid":true,"message":"Packing key is correct."}

=== 7. Validating packing key (incorrect) ===
{"valid":false,"message":"Packing key is incorrect."}

=== Testing complete! ===
```

## Troubleshooting

### Common Issues and Solutions

#### 1. "Unauthorized" Error (401)

**Problem:** Getting `<h1>Unauthorized</h1>` or `{"message":"The resource owner or authorization server denied the request."}`

**Causes & Solutions:**

**a) Token Expired**
- Tokens expire after 5 minutes
- **Solution:** Generate a fresh token:
```bash
TOKEN=$(curl -s -X POST "${BASE_URL}/oauth/token" \
  -H "Content-Type: application/x-www-form-urlencoded" \
  -d "grant_type=password&client_id=test-frontend&username=YOUR_USERNAME&password=YOUR_PASSWORD&scope=basic" \
  | grep -o '"access_token":"[^"]*' | cut -d'"' -f4)
```

**b) Missing or Empty TOKEN Variable**
- The `$TOKEN` variable isn't set in your shell
- **Solution:** Verify the token is set:
```bash
echo "Token: ${TOKEN:0:50}..."
```
If empty, regenerate the token using the command above.

**c) Missing `scope=basic` Parameter**
- **Solution:** Always include `scope=basic` in token requests:
```bash
# Correct
-d "grant_type=password&client_id=test-frontend&username=user&password=pass&scope=basic"

# Incorrect (missing scope)
-d "grant_type=password&client_id=test-frontend&username=user&password=pass"
```

#### 2. "The 'access user profiles' permission is required" (403)

**Problem:** Getting permission denied errors when accessing endpoints.

**Root Cause:** OAuth scope doesn't have the required permissions.

**Solution:** Configure the `basic` OAuth scope with required permissions:

```bash
docker exec drupal-drupal-1 drush ev "\$scope = \Drupal::entityTypeManager()->getStorage('oauth2_scope')->load('basic'); \$perms = ['access user profiles', 'restful get packing_key_exists', 'restful post packing_key_validate', 'restful post packing_key_update']; \$scope->set('permissions', \$perms); \$scope->save();"

docker exec drupal-drupal-1 drush cr
```

#### 3. "Syntax error" When Validating Packing Key

**Problem:** Getting `{"message":"Syntax error"}` when calling `/api/user/validate-packing-key`

**Root Cause:** Special characters (like `!`, `$`, `\`) in the packing key are being interpreted by bash.

**Solution:** Use a JSON file instead of inline JSON:

```bash
# Create JSON file (avoids shell interpretation)
printf '{"packing_key":"MySecretKey123!"}' > /tmp/packing_key.json

# Use the file
curl -X POST "${BASE_URL}/api/user/validate-packing-key" \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  --data '@/tmp/packing_key.json'
```

**What NOT to do:**
```bash
# This will fail with special characters
curl ... -d '{"packing_key":"MySecretKey123!"}'

# This will also fail
curl ... --data-raw '{"packing_key":"MySecretKey123!"}'
```

#### 4. Token Variable Not Persisting

**Problem:** `$TOKEN` works in one command but not the next.

**Root Cause:** Each terminal session has its own environment variables.

**Solution:** Run the token generation and API call in the same terminal session, or export the token:

```bash
# Option 1: Run commands in sequence in same terminal
TOKEN=$(curl -s ...)
curl -X POST ... -H "Authorization: Bearer $TOKEN"

# Option 2: Export the token for the session
export TOKEN=$(curl -s ...)
# Now TOKEN persists for all commands in this terminal
```

#### 5. OAuth Consumer Configuration Issues

**Problem:** 401 errors even with valid credentials.

**Solution:** Verify OAuth consumer configuration:

```bash
# Check consumer exists
docker exec drupal-drupal-1 drush ev "\$consumer = \Drupal::entityTypeManager()->getStorage('consumer')->load(2); echo 'Client ID: ' . \$consumer->getClientId() . PHP_EOL; echo 'Label: ' . \$consumer->label() . PHP_EOL;"

# Expected output:
# Client ID: test-frontend
# Label: React + Vite fronend
```

#### 6. Permissions Not Working After Configuration

**Problem:** Changed permissions but still getting 403 errors.

**Solution:** Clear Drupal cache after any permission or scope changes:

```bash
docker exec drupal-drupal-1 drush cr
```

## Installation

1. Enable the module:
```bash
drush en maidenov_user_api -y
```

2. Clear cache:
```bash
drush cr
```

**What Gets Created Automatically:**
- ✅ `field_packing_key` - Custom field on user entity
- ✅ Form display configuration
- ✅ REST resource configurations

**Prerequisites:**
- The `confidential_data_user` role must exist (managed via configuration export/import)

3. Grant permissions:
   - Navigate to `/admin/people/permissions`
   - Grant "Access POST on User Registration resource" to **Anonymous user**
   - Grant "Access POST on Packing Key Update resource" to **Confidential Data User**
   - Grant "Access POST on Packing Key Validation resource" to **Confidential Data User**
   - Grant "Access GET on Packing Key Exists resource" to **Confidential Data User**

4. (Optional) Configure permissions for the `confidential_data_user` role:
   - Navigate to `/admin/people/permissions`
   - Assign appropriate permissions to the `confidential_data_user` role based on your application needs
   - This role is automatically assigned to all users registered via the API endpoint

## User Role Information

### Confidential Data User Role

The module requires a `confidential_data_user` role to exist (managed via Drupal configuration):

- **Machine Name**: `confidential_data_user`
- **Label**: Confidential Data User
- **Automatically Assigned**: Yes, to all users registered via `POST /api/register`
- **Management**: Role is managed via configuration export/import (not created by the module)
- **Purpose**: Distinguish API-registered users from other user types
- **Permissions**: Configure at `/admin/people/permissions` based on your needs

**Use Cases**:
- Grant specific permissions only to users who register through the app
- Restrict certain content or features to confidential data users
- Implement role-based access control in your React frontend

## Field Information

The module creates a custom field on the user entity:

- **Field Name**: `field_packing_key`
- **Label**: Packing Key
- **Type**: String (255 characters)
- **Required**: No
- **Description**: Hashed master key used by the frontend to encrypt/decrypt confidential password data

**Security Model**:
- The packing key itself is **never stored in plain text**
- Only a **one-way hash** (Bcrypt/Argon2) is stored in Drupal
- The hash is used to **validate** the user's key, not to decrypt data
- The actual encryption/decryption happens **client-side** in the React frontend
- This ensures that even with database access, passwords cannot be decrypted without the user's packing key

The field is automatically:
- Hidden from registration forms
- Stored as a hashed value (using Drupal's password hasher - same as user passwords)
- Available on user edit form with password-style protection (see below)

## Backend User Edit Form Integration

The module adds a **"Packing Key Management"** section to the Drupal user edit form at `/user/{uid}/edit`.

**Features**:
- Shows current packing key status (set or not set)
- Password-style input fields (hidden characters)
- Confirmation field to prevent typos
- **Uses Drupal's existing "Current password" field** for verification (same field used for email/password changes)
- Integrates seamlessly with standard Drupal user form behavior

**Form Fields in "Packing Key Management" Section**:
1. **Packing key status** - Visual indicator showing if key is set or not
2. **New packing key** - Password field for entering the packing key
3. **Confirm packing key** - Password field for confirmation

**Existing Drupal Field (Updated)**:
- **Current password** (at top of form) - Description updated to include: "Required if you want to change the Email address, Password, or Packing Key below"
- This field is shared with email/password changes, maintaining consistency with Drupal's standard pattern

### Administrator Privileges (uid=1)

The **super administrator** (user ID 1) has special privileges when managing other users' packing keys:

**When uid=1 edits their own account** (`/user/1/edit`):
- ✅ Current password **is required** (uses the existing "Current password" field at the top)
- ✅ Must confirm packing key
- Standard user experience

**When uid=1 edits another user's account** (e.g., `/user/2/edit`):
- ❌ Current password **not required** (validation skips password check for admin)
- ✅ Must still confirm packing key
- Status messages show "This user's packing key..." instead of "Your packing key..."
- Success message shows "Packing key for @username has been updated"
- The existing "Current password" field remains visible but is not validated for packing key changes

**Purpose**: This allows the super administrator to set/reset packing keys for testing purposes without needing to know users' passwords (similar to how uid=1 can change any user's password).

This provides administrators and users with a secure way to manage the packing key through the Drupal backend interface.

## Security Features

### API Endpoints (Frontend)
1. **Role-Based Access Control**: All packing key endpoints require the `confidential_data_user` role
2. **OAuth2 Authentication**: All endpoints (except registration) require valid OAuth2 authentication
3. **Key Confirmation**: Packing key must be entered twice to prevent typos
4. **No Password Required**: Frontend API trusts OAuth2 authentication

### Backend User Form
1. **Password Verification**: Users must provide their current account password to change packing key
2. **Key Confirmation**: Packing key must be entered twice to prevent typos
3. **Form Validation**: Comprehensive validation before saving

### General Security
1. **Hashing**: The packing key is hashed before storage using Bcrypt/Argon2 (never stored in plain text)
2. **One-Way Hash**: The packing key cannot be retrieved, only validated (same as password hashing)
3. **Validation Logging**: Failed validation attempts are logged for security monitoring

## CORS Configuration

If you're calling these endpoints from a React frontend, ensure CORS is properly configured in `sites/default/services.yml`:

```yaml
cors.config:
  enabled: true
  allowedHeaders: ['*']
  allowedMethods: ['POST', 'GET', 'OPTIONS']
  allowedOrigins: ['http://localhost:5173']  # Your React dev server
  exposedHeaders: false
  maxAge: false
  supportsCredentials: true
```

## Uninstallation

When the module is uninstalled:
- The `field_packing_key` field is automatically removed
- All packing key data is deleted
- The `confidential_data_user` role is NOT removed (managed via configuration)
- Users keep their accounts and role assignments

## Quick Reference

### One-Line Commands for Common Tasks

**Get a fresh OAuth token:**
```bash
TOKEN=$(curl -s -X POST "http://localhost:8080/oauth/token" -H "Content-Type: application/x-www-form-urlencoded" -d "grant_type=password&client_id=test-frontend&username=YOUR_USERNAME&password=YOUR_PASSWORD&scope=basic" | grep -o '"access_token":"[^"]*' | cut -d'"' -f4)
```

**Check if packing key exists:**
```bash
curl -s "${BASE_URL}/api/user/packing-key/exists" -H "Authorization: Bearer $TOKEN" -H "Accept: application/json"
```

**Validate packing key (use JSON file for special characters):**
```bash
printf '{"packing_key":"YOUR_KEY_HERE"}' > /tmp/key.json && curl -s -X POST "${BASE_URL}/api/user/validate-packing-key" -H "Authorization: Bearer $TOKEN" -H "Content-Type: application/json" -H "Accept: application/json" --data '@/tmp/key.json'
```

**Configure OAuth scope permissions:**
```bash
docker exec drupal-drupal-1 drush ev "\$scope = \Drupal::entityTypeManager()->getStorage('oauth2_scope')->load('basic'); \$perms = ['access user profiles', 'restful get packing_key_exists', 'restful post packing_key_validate', 'restful post packing_key_update']; \$scope->set('permissions', \$perms); \$scope->save();" && docker exec drupal-drupal-1 drush cr
```

**Grant permissions to role:**
```bash
docker exec drupal-drupal-1 drush role:perm:add confidential_data_user 'access user profiles' && docker exec drupal-drupal-1 drush role:perm:add confidential_data_user 'restful get packing_key_exists' && docker exec drupal-drupal-1 drush role:perm:add confidential_data_user 'restful post packing_key_validate' && docker exec drupal-drupal-1 drush role:perm:add confidential_data_user 'restful post packing_key_update' && docker exec drupal-drupal-1 drush cr
```

### Key Points to Remember

✅ **Always include `scope=basic`** in OAuth token requests
✅ **Tokens expire after 5 minutes** - regenerate as needed
✅ **Use JSON files** for packing keys with special characters (`!`, `$`, etc.)
✅ **Clear cache** after permission/scope changes: `docker exec drupal-drupal-1 drush cr`
✅ **Run token generation and API calls in the same terminal session**

### Testing Script Location

A complete test script is available at `/tmp/complete_test.sh`:

```bash
bash /tmp/complete_test.sh
```

This script demonstrates the complete flow: token generation → check packing key → set packing key → validate packing key.

## Support

For issues or questions, contact the development team.

---

**Last Updated:** December 2025
**Tested With:** Drupal 11, Simple OAuth, PostgreSQL, Docker
