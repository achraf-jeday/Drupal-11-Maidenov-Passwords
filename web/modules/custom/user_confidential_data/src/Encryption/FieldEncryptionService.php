<?php

namespace Drupal\user_confidential_data\Encryption;

use Drupal\Component\Utility\Crypt;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;

/**
 * Service for encrypting/decrypting entity field values.
 */
class FieldEncryptionService {

  use StringTranslationTrait;

  /**
   * Encryption algorithm.
   */
  const ENCRYPTION_ALGORITHM = 'AES-256-CBC';

  /**
   * Key derivation method.
   */
  const KEY_DERIVATION_METHOD = 'sha256';

  /**
   * Salt for key derivation.
   */
  const KEY_SALT = 'user_confidential_data_encryption_v1';

  /**
   * The encryption key.
   *
   * @var string|null
   */
  protected $encryptionKey;

  /**
   * Logger service.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * Constructor.
   */
  public function __construct(LoggerChannelFactoryInterface $logger_factory) {
    $this->logger = $logger_factory->get('user_confidential_data');
  }

  /**
   * Check if encryption is ready (key is available).
   *
   * @return bool
   *   TRUE if encryption is ready, FALSE otherwise.
   */
  public function isEncryptionReady() {
    return $this->getEncryptionKey() !== null;
  }

  /**
   * Get the encryption key from secure sources.
   *
   * @return string|null
   *   The encryption key or NULL if not available.
   */
  protected function getEncryptionKey() {
    if ($this->encryptionKey !== null) {
      return $this->encryptionKey;
    }

    // Priority 1: Docker Secrets (most secure for production)
    $secret_file = '/run/secrets/user_confidential_data_key';
    if (file_exists($secret_file)) {
      $secret_key = file_get_contents($secret_file);
      if ($secret_key) {
        $this->encryptionKey = $this->deriveKey(trim($secret_key));
        return $this->encryptionKey;
      }
    }

    // Priority 2: Environment variable (for development)
    $env_key = getenv('USER_CONFIDENTIAL_DATA_KEY');
    if ($env_key) {
      $this->encryptionKey = $this->deriveKey($env_key);
      return $this->encryptionKey;
    }

    // Priority 3: Drupal settings.php (fallback)
    $settings_key = \Drupal::config('user_confidential_data.settings')
      ->get('encryption_key');
    if ($settings_key) {
      $this->encryptionKey = $this->deriveKey($settings_key);
      return $this->encryptionKey;
    }

    // No key available
    $this->logger->error('No encryption key found for User Confidential Data');
    return null;
  }

  /**
   * Derive a strong encryption key from the provided key material.
   *
   * @param string $key_material
   *   The raw key material from secrets, env vars, etc.
   *
   * @return string
   *   The derived encryption key.
   */
  protected function deriveKey($key_material) {
    // Use HKDF for key derivation (more secure than simple hashing)
    $derived_key = hash_hkdf(
      self::KEY_DERIVATION_METHOD,
      $key_material,
      32, // 256-bit key for AES-256
      self::KEY_SALT,
      'user_confidential_data'
    );

    return $derived_key;
  }

  /**
   * Generate and store an encryption key for development.
   *
   * @return string
   *   The generated encryption key.
   */
  protected function generateAndStoreKey() {
    // Generate a random 32-byte key
    $key = bin2hex(random_bytes(32));

    // Store in settings.php (development only)
    $settings_file = DRUPAL_ROOT . '/sites/default/settings.php';
    $settings_content = file_get_contents($settings_file);

    // Check if key already exists
    if (strpos($settings_content, 'user_confidential_data_encryption_key') === FALSE) {
      // Add key to settings.php
      $key_config = "\n// User Confidential Data Encryption Key (Development)\n";
      $key_config .= "\$settings['user_confidential_data_encryption_key'] = '$key';\n";

      file_put_contents($settings_file, $key_config, FILE_APPEND);
      $this->logger->warning('Generated and stored encryption key in settings.php (Development only)');
    }

    return $this->deriveKey($key);
  }

  /**
   * Encrypt a field value.
   *
   * @param mixed $value
   *   The field value to encrypt.
   *
   * @return string|null
   *   The encrypted value or NULL if encryption fails.
   */
  public function encryptField($value) {
    if ($value === null || $value === '') {
      return $value;
    }

    $key = $this->getEncryptionKey();
    if (!$key) {
      $this->logger->error('Cannot encrypt field: no encryption key available');
      return null;
    }

    try {
      // Serialize the value to handle arrays and objects
      $plaintext = serialize($value);

      // Generate random IV for each encryption
      $iv = random_bytes(openssl_cipher_iv_length(self::ENCRYPTION_ALGORITHM));

      // Encrypt
      $encrypted = openssl_encrypt(
        $plaintext,
        self::ENCRYPTION_ALGORITHM,
        $key,
        OPENSSL_RAW_DATA,
        $iv
      );

      if ($encrypted === false) {
        throw new \Exception('Encryption failed');
      }

      // Combine IV and encrypted data
      $result = base64_encode($iv . $encrypted);

      return $result;
    }
    catch (\Exception $e) {
      $this->logger->error('Field encryption failed: @message', ['@message' => $e->getMessage()]);
      return null;
    }
  }

  /**
   * Decrypt a field value.
   *
   * @param string|null $encrypted_value
   *   The encrypted field value.
   *
   * @return mixed
   *   The decrypted value or NULL if decryption fails.
   */
  public function decryptField($encrypted_value) {
    if ($encrypted_value === null || $encrypted_value === '') {
      return $encrypted_value;
    }

    $key = $this->getEncryptionKey();
    if (!$key) {
      $this->logger->error('Cannot decrypt field: no encryption key available');
      return null;
    }

    try {
      // Decode the base64 encoded data
      $data = base64_decode($encrypted_value, true);
      if ($data === false) {
        throw new \Exception('Invalid base64 encoding');
      }

      // Extract IV and encrypted data
      $iv_length = openssl_cipher_iv_length(self::ENCRYPTION_ALGORITHM);
      $iv = substr($data, 0, $iv_length);
      $encrypted = substr($data, $iv_length);

      // Decrypt
      $decrypted = openssl_decrypt(
        $encrypted,
        self::ENCRYPTION_ALGORITHM,
        $key,
        OPENSSL_RAW_DATA,
        $iv
      );

      if ($decrypted === false) {
        throw new \Exception('Decryption failed');
      }

      // Unserialize the result
      $result = unserialize($decrypted);

      return $result;
    }
    catch (\Exception $e) {
      $this->logger->error('Field decryption failed: @message', ['@message' => $e->getMessage()]);
      return null;
    }
  }

  /**
   * Get the fields that should be encrypted.
   *
   * @return array
   *   Array of field names that should be encrypted.
   */
  public function getEncryptedFields() {
    return [
      'name',
      'email',
      'username',
      'password',
      'notes',
    ];
  }

  /**
   * Check if a field should be encrypted.
   *
   * @param string $field_name
   *   The field name.
   *
   * @return bool
   *   TRUE if the field should be encrypted.
   */
  public function isEncryptedField($field_name) {
    return in_array($field_name, $this->getEncryptedFields());
  }

  /**
   * Encrypt all confidential fields in an entity.
   *
   * @param \Drupal\user_confidential_data\Entity\UserConfidentialData $entity
   *   The entity to encrypt fields for.
   *
   * @return array
   *   Array of encrypted values.
   */
  public function encryptEntityFields($entity) {
    $encrypted_data = [];

    foreach ($this->getEncryptedFields() as $field_name) {
      if ($entity->hasField($field_name)) {
        $value = $entity->get($field_name)->getValue();
        $encrypted_value = $this->encryptField($value);
        if ($encrypted_value !== null) {
          $encrypted_data[$field_name] = $encrypted_value;
        }
      }
    }

    return $encrypted_data;
  }

  /**
   * Decrypt all confidential fields in an entity.
   *
   * @param \Drupal\user_confidential_data\Entity\UserConfidentialData $entity
   *   The entity to decrypt fields for.
   *
   * @return array
   *   Array of decrypted values.
   */
  public function decryptEntityFields($entity) {
    $decrypted_data = [];

    foreach ($this->getEncryptedFields() as $field_name) {
      if ($entity->hasField($field_name)) {
        $encrypted_value = $entity->get($field_name)->getValue();
        $decrypted_value = $this->decryptField($encrypted_value);
        if ($decrypted_value !== null) {
          $decrypted_data[$field_name] = $decrypted_value;
        }
      }
    }

    return $decrypted_data;
  }

}