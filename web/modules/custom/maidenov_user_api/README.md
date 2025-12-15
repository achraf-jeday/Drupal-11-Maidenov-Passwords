# Maidenov User API

This module provides REST API endpoints for user registration and packing key management for the Maidenov Passwords application.

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
- **Purpose**: Set or update the user's encrypted packing key from the frontend

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
- **Purpose**: Validate if the provided packing key is correct (used to unlock encrypted data)

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
- **Purpose**: Check if the user has set a packing key (used after login to determine if setup is required)

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

The typical user flow with these endpoints:

1. **Registration**: User registers via `POST /api/register`
2. **Login**: User authenticates (via OAuth)
3. **Check Packing Key**: Frontend calls `GET /api/user/packing-key/exists`
   - If `exists: false` → Redirect to packing key setup
   - If `exists: true` → Prompt for packing key validation
4. **Set Packing Key** (first time): User sets packing key via `POST /api/user/packing-key`
5. **Validate Packing Key** (subsequent logins): User validates via `POST /api/user/validate-packing-key`
6. **Access Protected Data**: Once validated, user can view/edit/delete their encrypted passwords

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
- **Description**: Hashed encryption/decryption key for secure password storage

The field is automatically:
- Hidden from registration forms
- Stored as a hashed value (using Drupal's password hasher)
- Available on user edit form (see below)

## Backend User Edit Form Integration

The module adds a **"Packing Key Management"** section to the Drupal user edit form at `/user/{uid}/edit`.

**Features**:
- Shows current packing key status (set or not set)
- Password-style input fields (hidden characters)
- Confirmation field to prevent typos
- **Requires current account password** to change the packing key (for regular users)
- Similar UX to changing email/password in Drupal

**Form Fields**:
1. **Packing key status** - Visual indicator if key is set
2. **New packing key** - Password field for entering key
3. **Confirm packing key** - Password field for confirmation
4. **Current password** - Required for security verification (see below)

### Administrator Privileges (uid=1)

The **super administrator** (user ID 1) has special privileges when managing other users' packing keys:

**When uid=1 edits their own account** (`/user/1/edit`):
- ✅ Current password **is required** (normal security flow)
- ✅ Must confirm packing key
- Standard user experience

**When uid=1 edits another user's account** (e.g., `/user/2/edit`):
- ❌ Current password **not required** (password field is hidden)
- ✅ Must still confirm packing key
- Status messages show "This user's packing key..." instead of "Your packing key..."
- Success message shows "Packing key for @username has been updated"

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
