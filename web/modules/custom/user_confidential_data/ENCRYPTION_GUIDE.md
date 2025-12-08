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

**Option C: Docker Secrets (Production)**
```bash
# Create secret file (current setup)
echo "your-32-byte-master-key-for-hkdf" > /var/www/drupal/secrets/user_confidential_data_key
chmod 600 /var/www/drupal/secrets/user_confidential_data_key

# Mount in docker-compose.yml
volumes:
  - ./secrets:/run/secrets:ro
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

**Current Active Source: Docker Secrets** (Production-ready)

**Priority order:**
1. Docker secrets (`/run/secrets/user_confidential_data_key`) - **ACTIVE**
2. Environment variable (`USER_CONFIDENTIAL_DATA_KEY`) - Disabled
3. Drupal Settings (`$settings['user_confidential_data_encryption_key']`) - Fallback

## ✅ Verification

**Check encryption status:**
```bash
# Verify encryption is ready
docker exec drupal-drupal-1 /opt/drupal/vendor/bin/drush eval "print_r(\Drupal::service('user_confidential_data.field_encryption')->isEncryptionReady());"

# Test encryption/decryption
docker exec drupal-drupal-1 /opt/drupal/vendor/bin/drush eval "\$service = \Drupal::service('user_confidential_data.field_encryption'); \$test = 'test'; \$encrypted = \$service->encryptField(\$test); \$decrypted = \$service->decryptField(\$encrypted); print 'Match: ' . (\$test === \$decrypted ? 'YES' : 'NO');"
```

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
- Check Docker secrets: `docker exec drupal-drupal-1 cat /run/secrets/user_confidential_data_key`
- Verify secret file exists: `ls -la /var/www/drupal/secrets/`
- Check environment variable: `docker exec drupal-drupal-1 env | grep USER_CONFIDENTIAL_DATA_KEY`
- Verify settings form configuration

**"Encryption failed"**
- Ensure OpenSSL extension is enabled: `docker exec drupal-drupal-1 php -m | grep openssl`
- Verify key is 32+ characters: `wc -c /var/www/drupal/secrets/user_confidential_data_key`
- Check Drupal logs: `docker exec drupal-drupal-1 /opt/drupal/vendor/bin/drush watchdog-show`

## 📁 Key Files

- `src/Encryption/FieldEncryptionService.php` - Core encryption
- `src/Storage/UserConfidentialDataStorage.php` - Storage handler
- `src/Encryption/EntityEncryptionHelper.php` - Helper methods