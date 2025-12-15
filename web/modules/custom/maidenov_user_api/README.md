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
- **Access**: Authenticated users only (OAuth2 protected)
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
- **Access**: Authenticated users only
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
- **Access**: Authenticated users only
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

Here are practical curl examples for testing all four endpoints from the command line:

### Prerequisites

Set your base URL as a variable (adjust as needed):
```bash
BASE_URL="https://localhost"
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

Before testing the authenticated endpoints, you need an OAuth2 access token. Example:

```bash
TOKEN=$(curl -X POST "${BASE_URL}/oauth/token" \
  -H "Content-Type: application/x-www-form-urlencoded" \
  -d "grant_type=password" \
  -d "client_id=YOUR_CLIENT_ID" \
  -d "client_secret=YOUR_CLIENT_SECRET" \
  -d "username=testuser@example.com" \
  -d "password=SecurePass123!" \
  | jq -r '.access_token')

echo "Token: $TOKEN"
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

```bash
curl -X POST "${BASE_URL}/api/user/validate-packing-key" \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "packing_key": "MySecretKey123!"
  }'
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
curl -X POST "${BASE_URL}/api/user/validate-packing-key" \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "packing_key": "WrongKey456!"
  }'
```

**Expected Response** (incorrect):
```json
{
  "valid": false,
  "message": "Packing key is incorrect."
}
```

### Complete Test Flow Script

Here's a complete bash script to test the entire flow:

```bash
#!/bin/bash

BASE_URL="https://localhost"

# 1. Register user
echo "=== 1. Registering user ==="
curl -X POST "${BASE_URL}/api/register" \
  -H "Content-Type: application/json" \
  -d '{
    "email": "testuser@example.com",
    "password": "SecurePass123!",
    "username": "testuser"
  }'
echo -e "\n"

# 2. Get OAuth token (adjust OAuth endpoint and credentials)
echo "=== 2. Getting OAuth token ==="
TOKEN="YOUR_ACCESS_TOKEN_HERE"
echo "Token: $TOKEN"
echo -e "\n"

# 3. Check if packing key exists
echo "=== 3. Checking if packing key exists ==="
curl -X GET "${BASE_URL}/api/user/packing-key/exists" \
  -H "Authorization: Bearer $TOKEN"
echo -e "\n"

# 4. Set packing key
echo "=== 4. Setting packing key ==="
curl -X POST "${BASE_URL}/api/user/packing-key" \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "packing_key": "MySecretKey123!",
    "packing_key_confirm": "MySecretKey123!"
  }'
echo -e "\n"

# 5. Validate packing key (correct)
echo "=== 5. Validating packing key (correct) ==="
curl -X POST "${BASE_URL}/api/user/validate-packing-key" \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "packing_key": "MySecretKey123!"
  }'
echo -e "\n"

# 6. Validate packing key (incorrect)
echo "=== 6. Validating packing key (incorrect) ==="
curl -X POST "${BASE_URL}/api/user/validate-packing-key" \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "packing_key": "WrongKey!"
  }'
echo -e "\n"

echo "=== Testing complete! ==="
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

3. Grant permissions:
   - Navigate to `/admin/people/permissions`
   - Grant "Access POST on User Registration resource" to **Anonymous user**
   - Grant "Access POST on Packing Key Update resource" to **Authenticated user**
   - Grant "Access POST on Packing Key Validation resource" to **Authenticated user**
   - Grant "Access GET on Packing Key Exists resource" to **Authenticated user**

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
1. **OAuth2 Authentication**: All endpoints (except registration) require valid authentication
2. **Key Confirmation**: Packing key must be entered twice to prevent typos
3. **No Password Required**: Frontend API trusts OAuth2 authentication

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

## Support

For issues or questions, contact the development team.
