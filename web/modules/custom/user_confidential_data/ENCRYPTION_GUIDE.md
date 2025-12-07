# User Confidential Data - Field Encryption Guide

## 🔐 Quick Overview

Automatic field-level encryption for your UserConfidentialData entity using AES-256-CBC.

**What's encrypted:**
- name, email, username, password, notes ✅
- link fields (URI/title) ⚠️ *Plain text in DB due to Drupal limitations*

## 🚀 Setup (5 minutes)

### 1. Set Encryption Key (Choose One)

**Option A: Environment Variable (Recommended)**
```bash
# Add to docker-compose.yml
environment:
  USER_CONFIDENTIAL_DATA_KEY: "your-32-byte-master-key-for-hkdf"
```

**Option B: Settings Form**
- Go to: `/admin/config/user-confidential-data/encryption`
- Enter key and save

**Option C: Docker Secrets**
```bash
docker secret create user_confidential_data_key "your-key"
```

### 2. Enable Module
```bash
drush cr && drush updb
```

### 3. Test It
```php
// Create entity - encryption happens automatically
$entity = UserConfidentialData::create([
  'name' => 'My Password',
  'password' => 'secret123',
  'user_id' => 1,
]);
$entity->save();
```

## 🔑 Key Management

**Priority order:**
1. Docker secrets (production)
2. Environment variable (recommended)
3. Settings form (development)

## ✅ Verification

**Check database (should show encrypted data):**
```sql
SELECT name, password FROM user_confidential_data LIMIT 3;
-- Should show base64 strings, not plain text
```

**Check in Drupal (should show decrypted values):**
```php
$entity = UserConfidentialData::load(1);
echo $entity->get('password')->value; // Shows actual password
```

## ⚠️ Important Notes

- Link fields stored in plain text (Drupal limitation)
- All other fields are encrypted
- Existing data automatically encrypted on module update
- No code changes needed - encryption is transparent

## 🛠️ Troubleshooting

**"No encryption key found"**
- Check environment variable: `echo $USER_CONFIDENTIAL_DATA_KEY`
- Verify settings form configuration
- Check Docker secrets if used

**"Encryption failed"**
- Ensure OpenSSL extension is enabled
- Verify key is 32+ characters
- Check Drupal logs for details

## 📁 Key Files

- `src/Encryption/FieldEncryptionService.php` - Core encryption
- `src/Storage/UserConfidentialDataStorage.php` - Storage handler
- `src/Encryption/EntityEncryptionHelper.php` - Helper methods