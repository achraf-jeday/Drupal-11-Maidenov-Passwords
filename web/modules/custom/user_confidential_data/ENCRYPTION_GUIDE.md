# User Confidential Data - Field Encryption Guide

## 🔐 Quick Overview

Automatic field-level encryption for your UserConfidentialData entity using AES-256-CBC.

**What's encrypted:**
- name, email, username, password, notes ✅

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

**Option C: Docker Secrets (Production - Maximum Security)**
```bash
# 1. Generate cryptographically secure key (one-time setup)
openssl rand -hex 32 > /var/www/drupal/secrets/user_confidential_data_key
chmod 600 /var/www/drupal/secrets/user_confidential_data_key
chmod 700 /var/www/drupal/secrets/

# 2. Mount as tmpfs in docker-compose.yml (maximum security - already configured)
volumes:
  - type: tmpfs
    target: /run/secrets
    tmpfs:
      size: 1000000  # 1MB

# 3. Copy key to container (REQUIRED after each container restart)
# NOTE: docker cp doesn't work reliably with tmpfs, use pipe method instead:
cat /var/www/drupal/secrets/user_confidential_data_key | docker exec -i drupal-drupal-1 tee /run/secrets/user_confidential_data_key > /dev/null
docker exec drupal-drupal-1 chmod 600 /run/secrets/user_confidential_data_key
docker exec drupal-drupal-1 chown www-data:www-data /run/secrets/user_confidential_data_key

# 4. Verify the key is in place
docker exec drupal-drupal-1 ls -la /run/secrets/user_confidential_data_key
# Expected: -rw------- 1 www-data www-data 65 [date] user_confidential_data_key
```

**Why tmpfs is maximum security:**
- Key only exists in RAM, never written to disk
- Automatically deleted when container stops/restarts
- No risk of key exposure through disk forensics or backups
- Isolated memory space per container

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

**Current Active Source: Docker Secrets** (Production-ready, tmpfs)

**Security Features:**
- **Key Generation**: OpenSSL rand -hex 32 (256-bit entropy)
- **Storage**: tmpfs (in-memory, no disk persistence)
- **Permissions**: 600 file, 700 directory
- **Size**: 1MB tmpfs limit

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
- Check tmpfs mount: `docker exec drupal-drupal-1 mount | grep secrets`
- Verify secret file: `docker exec drupal-drupal-1 cat /run/secrets/user_confidential_data_key`
- Check file ownership: `docker exec drupal-drupal-1 ls -la /run/secrets/`
- Check host file: `ls -la /var/www/drupal/secrets/`
- Check environment variable: `docker exec drupal-drupal-1 env | grep USER_CONFIDENTIAL_DATA_KEY`

**"Encryption failed"**
- Ensure OpenSSL extension: `docker exec drupal-drupal-1 php -m | grep openssl`
- Verify key length (74 chars): `wc -c /var/www/drupal/secrets/user_confidential_data_key`
- Check tmpfs mount: `docker exec drupal-drupal-1 mount | grep tmpfs`
- Check Drupal logs: `docker exec drupal-drupal-1 /opt/drupal/vendor/bin/drush watchdog-show`

## 📁 Key Files

- `src/Encryption/FieldEncryptionService.php` - Core encryption
- `src/Storage/UserConfidentialDataStorage.php` - Storage handler
- `src/Encryption/EntityEncryptionHelper.php` - Helper methods