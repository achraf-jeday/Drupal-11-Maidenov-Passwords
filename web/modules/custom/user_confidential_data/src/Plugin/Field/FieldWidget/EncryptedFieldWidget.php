<?php

namespace Drupal\user_confidential_data\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Plugin implementation of the 'encrypted_field_widget' widget.
 *
 * @FieldWidget(
 *   id = "encrypted_field_widget",
 *   label = @Translation("Encrypted Field Widget"),
 *   field_types = {
 *     "encrypted_field"
 *   },
 *   multiple_values = TRUE
 * )
 */
class EncryptedFieldWidget extends WidgetBase {

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $element['value'] = $element + [
      '#type' => 'textarea',
      '#title' => $this->t('Value'),
      '#default_value' => isset($items[$delta]->value) ? $items[$delta]->value : NULL,
      '#description' => $this->t('Enter confidential data that will be encrypted.'),
      '#rows' => 5,
      '#maxlength' => 2000,
      '#attributes' => [
        'class' => ['form-control'],
      ],
    ];

    return $element;
  }

}