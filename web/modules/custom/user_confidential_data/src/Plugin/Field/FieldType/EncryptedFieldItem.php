<?php

namespace Drupal\user_confidential_data\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldItemBase;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\TypedData\DataDefinition;

/**
 * Plugin implementation of the 'encrypted_field' field type.
 *
 * @FieldType(
 *   id = "encrypted_field",
 *   label = @Translation("Encrypted Field"),
 *   description = @Translation("Stores encrypted data."),
 *   category = @Translation("Confidential"),
 *   default_widget = "encrypted_field_widget",
 *   default_formatter = "encrypted_field_formatter",
 *   list_class = "\Drupal\Core\Field\FieldItemList",
 * )
 */
class EncryptedFieldItem extends FieldItemBase {

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {
    $properties['value'] = DataDefinition::create('string')
      ->setLabel(t('Encrypted value'))
      ->setRequired(TRUE);

    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition) {
    return [
      'columns' => [
        'value' => [
          'type' => 'text',
          'size' => 'big',
        ],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function setValue($values, $notify = TRUE) {
    // Handle both direct value setting and array format.
    if (is_array($values) && isset($values['value'])) {
      $value = $values['value'];
    } else {
      $value = $values;
    }

    // Encrypt the value before storing.
    if ($value !== NULL && $value !== '') {
      $encrypted_value = $this->encrypt($value);
      $values['value'] = $encrypted_value;
    }

    parent::setValue($values, $notify);
  }

  /**
   * {@inheritdoc}
   */
  public function get($property_name) {
    $property = parent::get($property_name);
    return $property;
  }

  /**
   * {@inheritdoc}
   */
  public function isEmpty() {
    $value = $this->get('value')->getValue();
    return $value === NULL || $value === '';
  }

  /**
   * Get the decrypted value.
   *
   * @return string|null
   *   The decrypted value, or NULL if empty.
   */
  public function getDecryptedValue() {
    $encrypted_value = $this->get('value')->getValue();
    if ($encrypted_value === NULL || $encrypted_value === '') {
      return NULL;
    }
    return $this->decrypt($encrypted_value);
  }

  /**
   * {@inheritdoc}
   */
  public function __toString() {
    $decrypted = $this->getDecryptedValue();
    return $decrypted !== NULL ? $decrypted : '';
  }

  /**
   * Encrypt a value using Drupal's encryption service.
   *
   * @param string $value
   *   The value to encrypt.
   *
   * @return string
   *   The encrypted value.
   */
  protected function encrypt($value) {
    $encryption_service = \Drupal::service('encryption');
    return $encryption_service->encrypt($value);
  }

  /**
   * Decrypt a value using Drupal's encryption service.
   *
   * @param string $encrypted_value
   *   The encrypted value.
   *
   * @return string
   *   The decrypted value.
   */
  protected function decrypt($encrypted_value) {
    $encryption_service = \Drupal::service('encryption');
    return $encryption_service->decrypt($encrypted_value);
  }

}