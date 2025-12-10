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
  public function getQuery($conjunction = 'AND') {
    // Use our custom query class that handles encrypted field filtering.
    // Get the standard namespaces from the base Drupal query system.
    $namespaces = ['Drupal\Core\Entity\Query\Sql'];
    return new \Drupal\user_confidential_data\Entity\Query\Sql\Query(
      $this->entityType,
      $conjunction,
      $this->database,
      $namespaces
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getAggregateQuery($conjunction = 'AND') {
    // For aggregate queries, use the default implementation.
    return parent::getAggregateQuery($conjunction);
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
          // Handle encrypted fields
          if (isset($item_values['value'])) {
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
   * {@inheritdoc}
   */
  public function loadByProperties(array $values = []) {
    // Separate encrypted and non-encrypted field conditions.
    $encryption_service = $this->getEncryptionService();
    $encrypted_fields = $encryption_service ? $encryption_service->getEncryptedFields() : [];

    $encrypted_conditions = [];
    $regular_conditions = [];

    foreach ($values as $field_name => $value) {
      if (in_array($field_name, $encrypted_fields)) {
        $encrypted_conditions[$field_name] = $value;
      }
      else {
        $regular_conditions[$field_name] = $value;
      }
    }

    // If there are no encrypted conditions, use the default behavior.
    if (empty($encrypted_conditions)) {
      return parent::loadByProperties($values);
    }

    // Load entities with only non-encrypted conditions.
    $entities = parent::loadByProperties($regular_conditions);

    // Filter entities by encrypted field values.
    if (!empty($encrypted_conditions)) {
      $entities = $this->filterByEncryptedFields($entities, $encrypted_conditions);
    }

    return $entities;
  }

  /**
   * Filters entities by encrypted field values.
   *
   * @param array $entities
   *   Array of entities to filter.
   * @param array $conditions
   *   Array of field => value conditions for encrypted fields.
   *
   * @return array
   *   Filtered array of entities.
   */
  protected function filterByEncryptedFields(array $entities, array $conditions) {
    $filtered = [];

    foreach ($entities as $entity) {
      $matches = TRUE;

      foreach ($conditions as $field_name => $expected_value) {
        if (!$entity->hasField($field_name)) {
          $matches = FALSE;
          break;
        }

        $field = $entity->get($field_name);
        if ($field->isEmpty()) {
          $matches = FALSE;
          break;
        }

        // The field is already decrypted by mapFromStorageRecords.
        $actual_value = $field->value;

        // Case-insensitive comparison for strings.
        if (is_string($actual_value) && is_string($expected_value)) {
          if (mb_strtolower($actual_value) !== mb_strtolower($expected_value)) {
            $matches = FALSE;
            break;
          }
        }
        elseif ($actual_value != $expected_value) {
          $matches = FALSE;
          break;
        }
      }

      if ($matches) {
        $filtered[$entity->id()] = $entity;
      }
    }

    return $filtered;
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