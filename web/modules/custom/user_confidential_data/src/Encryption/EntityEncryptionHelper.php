<?php

namespace Drupal\user_confidential_data\Encryption;

use Drupal\user_confidential_data\Entity\UserConfidentialData;

/**
 * Helper service for entity field encryption operations.
 */
class EntityEncryptionHelper {

  /**
   * Encryption service.
   *
   * @var \Drupal\user_confidential_data\Encryption\FieldEncryptionService
   */
  protected $encryptionService;

  /**
   * Constructor.
   */
  public function __construct(FieldEncryptionService $encryption_service) {
    $this->encryptionService = $encryption_service;
  }

  /**
   * Encrypt all confidential fields of an entity.
   *
   * @param \Drupal\user_confidential_data\Entity\UserConfidentialData $entity
   *   The entity.
   *
   * @return array
   *   Array of encrypted field values.
   */
  public function encryptEntity(UserConfidentialData $entity) {
    $encrypted_data = [];

    foreach ($this->encryptionService->getEncryptedFields() as $field_name) {
      if ($entity->hasField($field_name) && !$entity->get($field_name)->isEmpty()) {
        $field = $entity->get($field_name);

        // Get the raw value based on field type
        $value = $this->getFieldValue($field);

        if ($value !== null && $value !== '') {
          $encrypted_value = $this->encryptionService->encryptField($value);
          if ($encrypted_value !== null) {
            $encrypted_data[$field_name] = $encrypted_value;
          }
        }
      }
    }

    return $encrypted_data;
  }

  /**
   * Decrypt all confidential fields of an entity.
   *
   * @param \Drupal\user_confidential_data\Entity\UserConfidentialData $entity
   *   The entity.
   *
   * @return array
   *   Array of decrypted field values.
   */
  public function decryptEntity(UserConfidentialData $entity) {
    $decrypted_data = [];

    foreach ($this->encryptionService->getEncryptedFields() as $field_name) {
      if ($entity->hasField($field_name) && !$entity->get($field_name)->isEmpty()) {
        $field = $entity->get($field_name);

        // Get the encrypted value
        $encrypted_value = $this->getFieldValue($field);

        if ($encrypted_value !== null && $encrypted_value !== '') {
          $decrypted_value = $this->encryptionService->decryptField($encrypted_value);
          if ($decrypted_value !== null) {
            $decrypted_data[$field_name] = $decrypted_value;
          }
        }
      }
    }

    return $decrypted_data;
  }

  /**
   * Get the field value based on field type.
   *
   * @param \Drupal\Core\Field\FieldItemListInterface $field
   *   The field item list.
   *
   * @return mixed
   *   The field value.
   */
  protected function getFieldValue($field) {
    $field_class = $field->getItemDefinition()->getItemDefinition()->getClass();

    switch ($field_class) {
      case 'Drupal\Core\Field\Plugin\Field\FieldType\StringItem':
      case 'Drupal\Core\Field\Plugin\Field\FieldType\EmailItem':
      case 'Drupal\Core\Field\Plugin\Field\FieldType\StringLongItem':
        return $field->value;

      case 'Drupal\Core\Field\Plugin\Field\FieldType\LinkItem':
        return [
          'uri' => $field->uri,
          'title' => $field->title,
          'options' => $field->options,
        ];

      default:
        return $field->getValue();
    }
  }

  /**
   * Set decrypted values back to the entity.
   *
   * @param \Drupal\user_confidential_data\Entity\UserConfidentialData $entity
   *   The entity.
   * @param array $decrypted_data
   *   Array of decrypted field values.
   */
  public function setDecryptedValues(UserConfidentialData $entity, array $decrypted_data) {
    foreach ($decrypted_data as $field_name => $value) {
      if ($entity->hasField($field_name)) {
        $field = $entity->get($field_name);
        $field_class = $field->getItemDefinition()->getItemDefinition()->getClass();

        switch ($field_class) {
          case 'Drupal\Core\Field\Plugin\Field\FieldType\StringItem':
          case 'Drupal\Core\Field\Plugin\Field\FieldType\EmailItem':
          case 'Drupal\Core\Field\Plugin\Field\FieldType\StringLongItem':
            $field->value = $value;
            break;

          case 'Drupal\Core\Field\Plugin\Field\FieldType\LinkItem':
            if (is_array($value) && isset($value['uri'])) {
              $field->uri = $value['uri'];
              $field->title = $value['title'] ?? '';
              $field->options = $value['options'] ?? [];
            }
            break;
        }
      }
    }
  }

  /**
   * Check if encryption is properly configured.
   *
   * @return bool
   *   TRUE if encryption is ready, FALSE otherwise.
   */
  public function isEncryptionReady() {
    return $this->encryptionService->isEncryptionReady();
  }

  /**
   * Get encryption status information.
   *
   * @return array
   *   Status information.
   */
  public function getEncryptionStatus() {
    return [
      'ready' => $this->encryptionService->isEncryptionReady(),
      'key_available' => $this->encryptionService->isEncryptionReady(),
      'encrypted_fields' => $this->encryptionService->getEncryptedFields(),
    ];
  }

}