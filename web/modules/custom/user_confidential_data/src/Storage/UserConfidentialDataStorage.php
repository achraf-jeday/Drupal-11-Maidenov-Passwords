<?php

namespace Drupal\user_confidential_data\Storage;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\Sql\SqlContentEntityStorage;
use Drupal\user_confidential_data\Encryption\FieldEncryptionService;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Cache\MemoryCache\MemoryCacheInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;

/**
 * Custom storage handler for User Confidential Data with encryption support.
 */
class UserConfidentialDataStorage extends SqlContentEntityStorage {

  /**
   * Encryption service.
   *
   * @var \Drupal\user_confidential_data\Encryption\FieldEncryptionService
   */
  protected $encryptionService;

  /**
   * {@inheritdoc}
   */
  public function __construct(EntityTypeInterface $entity_type, Connection $database, EntityFieldManagerInterface $entity_field_manager, CacheBackendInterface $cache, LanguageManagerInterface $language_manager, MemoryCacheInterface $memory_cache, EntityTypeBundleInfoInterface $entity_type_bundle_info, EntityTypeManagerInterface $entity_type_manager) {
    parent::__construct($entity_type, $database, $entity_field_manager, $cache, $language_manager, $memory_cache, $entity_type_bundle_info, $entity_type_manager);
  }

  /**
   * {@inheritdoc}
   */
  protected function doPreSave(EntityInterface $entity) {
    // Encrypt confidential fields before saving
    $this->encryptEntityFields($entity);

    return parent::doPreSave($entity);
  }

  /**
   * {@inheritdoc}
   */
  protected function doSaveFieldItems(ContentEntityInterface $entity, array $names = []) {
    // Get the field values that are about to be saved
    $values = [];

    foreach ($names as $field_name) {
      $field = $entity->get($field_name);
      if (!$field->isEmpty()) {
        $values[$field_name] = $field->getValue();
      }
    }

    // Encrypt field values before database storage
    foreach ($values as $field_name => $field_values) {
      if ($this->isEncryptedField($field_name) && !empty($field_values)) {
        foreach ($field_values as $delta => $item_values) {
          // Handle link fields specially
          if ($field_name === 'link') {
            if (isset($item_values['uri'])) {
              $encrypted_uri = $this->encryptValue($item_values['uri']);
              if ($encrypted_uri !== null) {
                $values[$field_name][$delta]['uri'] = $encrypted_uri;
              }
            }
            if (isset($item_values['title'])) {
              $encrypted_title = $this->encryptValue($item_values['title']);
              if ($encrypted_title !== null) {
                $values[$field_name][$delta]['title'] = $encrypted_title;
              }
            }
          }
          // Handle other encrypted fields
          elseif (isset($item_values['value'])) {
            $encrypted_value = $this->encryptValue($item_values['value']);
            if ($encrypted_value !== null) {
              $values[$field_name][$delta]['value'] = $encrypted_value;
            }
          }
        }
      }
    }

    // Set the encrypted values back to the entity before saving
    foreach ($values as $field_name => $field_values) {
      $entity->set($field_name, $field_values);
    }

    return parent::doSaveFieldItems($entity, $names);
  }

  /**
   * {@inheritdoc}
   */
  protected function mapFromStorageRecords(array $records, $load_from_revision = false) {
    // Get entities from parent first
    $entities = parent::mapFromStorageRecords($records, $load_from_revision);

    // Decrypt confidential fields for all entities
    foreach ($entities as $entity) {
      $this->decryptEntityFields($entity);
    }

    return $entities;
  }

  /**
   * Encrypt a value using the encryption service.
   *
   * @param mixed $value
   *   The value to encrypt.
   *
   * @return string|null
   *   The encrypted value or NULL if encryption fails.
   */
  protected function encryptValue($value) {
    $encryption_service = $this->getEncryptionService();
    if (!$encryption_service) {
      return null;
    }
    return $encryption_service->encryptField($value);
  }

  /**
   * Decrypt a value using the encryption service.
   *
   * @param string|null $value
   *   The encrypted value.
   *
   * @return mixed
   *   The decrypted value or NULL if decryption fails.
   */
  protected function decryptValue($value) {
    $encryption_service = $this->getEncryptionService();
    if (!$encryption_service) {
      return null;
    }
    return $encryption_service->decryptField($value);
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
  protected function isEncryptedField($field_name) {
    $encryption_service = $this->getEncryptionService();
    if (!$encryption_service) {
      return false;
    }
    return $encryption_service->isEncryptedField($field_name);
  }

  /**
   * Get the encryption service.
   *
   * @return \Drupal\user_confidential_data\Encryption\FieldEncryptionService|null
   *   The encryption service or NULL if not available.
   */
  protected function getEncryptionService() {
    if (\Drupal::hasService('user_confidential_data.field_encryption')) {
      return \Drupal::service('user_confidential_data.field_encryption');
    }
    return null;
  }

  /**
   * Encrypt confidential fields before saving to database.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The entity to encrypt fields for.
   */
  protected function encryptEntityFields($entity) {
    $encryption_service = $this->getEncryptionService();
    if (!$encryption_service) {
      return;
    }

    foreach ($encryption_service->getEncryptedFields() as $field_name) {
      if ($entity->hasField($field_name) && !$entity->get($field_name)->isEmpty()) {
        $field = $entity->get($field_name);

        // Handle different field types
        if ($field->getItemDefinition()->getClass() === 'Drupal\Core\Field\Plugin\Field\FieldType\StringItem') {
          // For string fields, encrypt the value directly
          $value = $field->value;
          $encrypted_value = $encryption_service->encryptField($value);
          if ($encrypted_value !== null) {
            $field->value = $encrypted_value;
          }
        }
        elseif ($field->getItemDefinition()->getClass() === 'Drupal\Core\Field\Plugin\Field\FieldType\EmailItem') {
          // For email fields
          $value = $field->value;
          $encrypted_value = $encryption_service->encryptField($value);
          if ($encrypted_value !== null) {
            $field->value = $encrypted_value;
          }
        }
        elseif ($field->getItemDefinition()->getClass() === 'Drupal\Core\Field\Plugin\Field\FieldType\LinkItem') {
          // For link fields
          $url = $field->uri;
          $title = $field->title;
          $options = $field->options;

          $encrypted_url = $encryption_service->encryptField($url);
          $encrypted_title = $encryption_service->encryptField($title);

          if ($encrypted_url !== null) {
            $field->uri = $encrypted_url;
          }
          if ($encrypted_title !== null) {
            $field->title = $encrypted_title;
          }
          // Note: options are not encrypted as they contain technical data
        }
        elseif ($field->getItemDefinition()->getClass() === 'Drupal\Core\Field\Plugin\Field\FieldType\StringLongItem') {
          // For long text fields
          $value = $field->value;
          $encrypted_value = $encryption_service->encryptField($value);
          if ($encrypted_value !== null) {
            $field->value = $encrypted_value;
          }
        }
      }
    }
  }

  /**
   * Decrypt confidential fields after loading from database.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The entity to decrypt fields for.
   */
  protected function decryptEntityFields($entity) {
    $encryption_service = $this->getEncryptionService();
    if (!$encryption_service) {
      return;
    }

    foreach ($encryption_service->getEncryptedFields() as $field_name) {
      if ($entity->hasField($field_name) && !$entity->get($field_name)->isEmpty()) {
        $field = $entity->get($field_name);

        // Handle different field types
        if ($field->getItemDefinition()->getClass() === 'Drupal\Core\Field\Plugin\Field\FieldType\StringItem') {
          // For string fields
          $encrypted_value = $field->value;
          $decrypted_value = $encryption_service->decryptField($encrypted_value);
          if ($decrypted_value !== null) {
            $field->value = $decrypted_value;
          }
        }
        elseif ($field->getItemDefinition()->getClass() === 'Drupal\Core\Field\Plugin\Field\FieldType\EmailItem') {
          // For email fields
          $encrypted_value = $field->value;
          $decrypted_value = $encryption_service->decryptField($encrypted_value);
          if ($decrypted_value !== null) {
            $field->value = $decrypted_value;
          }
        }
        elseif ($field->getItemDefinition()->getClass() === 'Drupal\Core\Field\Plugin\Field\FieldType\LinkItem') {
          // For link fields
          $encrypted_url = $field->uri;
          $encrypted_title = $field->title;

          $decrypted_url = $encryption_service->decryptField($encrypted_url);
          $decrypted_title = $encryption_service->decryptField($encrypted_title);

          if ($decrypted_url !== null) {
            $field->uri = $decrypted_url;
          }
          if ($decrypted_title !== null) {
            $field->title = $decrypted_title;
          }
        }
        elseif ($field->getItemDefinition()->getClass() === 'Drupal\Core\Field\Plugin\Field\FieldType\StringLongItem') {
          // For long text fields
          $encrypted_value = $field->value;
          $decrypted_value = $encryption_service->decryptField($encrypted_value);
          if ($decrypted_value !== null) {
            $field->value = $decrypted_value;
          }
        }
      }
    }
  }

}