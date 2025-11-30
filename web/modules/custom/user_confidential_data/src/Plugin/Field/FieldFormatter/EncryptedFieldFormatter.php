<?php

namespace Drupal\user_confidential_data\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;

/**
 * Plugin implementation of the 'encrypted_field_formatter' formatter.
 *
 * @FieldFormatter(
 *   id = "encrypted_field_formatter",
 *   label = @Translation("Encrypted Field Formatter"),
 *   field_types = {
 *     "encrypted_field"
 *   }
 * )
 */
class EncryptedFieldFormatter extends FormatterBase {

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];

    foreach ($items as $delta => $item) {
      // Get the decrypted value.
      $decrypted_value = $item->getDecryptedValue();

      if ($decrypted_value !== NULL && $decrypted_value !== '') {
        $elements[$delta] = [
          '#type' => 'item',
          '#markup' => $this->t('Confidential Data: @value', ['@value' => nl2br(htmlspecialchars($decrypted_value))]),
        ];
      }
    }

    return $elements;
  }

}