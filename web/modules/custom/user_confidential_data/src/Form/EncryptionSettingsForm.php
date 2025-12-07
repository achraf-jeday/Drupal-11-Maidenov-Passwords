<?php

namespace Drupal\user_confidential_data\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\user_confidential_data\Encryption\FieldEncryptionService;

/**
 * Configure User Confidential Data encryption settings.
 */
class EncryptionSettingsForm extends ConfigFormBase {

  use StringTranslationTrait;

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'user_confidential_data_encryption_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['user_confidential_data.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('user_confidential_data.settings');
    $encryption_service = \Drupal::service('user_confidential_data.entity_encryption_helper');

    $form['encryption_info'] = [
      '#type' => 'details',
      '#title' => $this->t('Encryption Status'),
      '#open' => TRUE,
    ];

    $status = $encryption_service->getEncryptionStatus();

    $form['encryption_info']['status'] = [
      '#type' => 'item',
      '#title' => $this->t('Encryption Status'),
      '#markup' => $status['ready']
        ? $this->t('✅ Encryption is properly configured and ready to use.')
        : $this->t('❌ Encryption is not configured. Please set an encryption key.'),
    ];

    $form['encryption_info']['fields'] = [
      '#type' => 'item',
      '#title' => $this->t('Encrypted Fields'),
      '#markup' => implode(', ', $status['encrypted_fields']),
    ];

    $form['encryption_info']['key_sources'] = [
      '#type' => 'item',
      '#title' => $this->t('Key Sources (in priority order)'),
      '#markup' => '
        <ol>
          <li>Docker Secrets: <code>/run/secrets/user_confidential_data_key</code></li>
          <li>Environment Variable: <code>USER_CONFIDENTIAL_DATA_KEY</code></li>
          <li>Drupal Settings: <code>$settings["user_confidential_data_encryption_key"]</code></li>
        </ol>
      ',
    ];

    $form['encryption_key'] = [
      '#type' => 'details',
      '#title' => $this->t('Encryption Key Configuration'),
      '#open' => !$status['ready'],
      '#tree' => TRUE,
    ];

    $form['encryption_key']['key'] = [
      '#type' => 'password_confirm',
      '#title' => $this->t('Encryption Key'),
      '#description' => $this->t('Enter a strong encryption key (minimum 32 characters).'),
      '#required' => TRUE,
      '#size' => 64,
      '#attributes' => [
        'autocomplete' => 'new-password',
      ],
    ];

    $form['encryption_key']['help'] = [
      '#type' => 'item',
      '#markup' => $this->t('💡 For production, use Docker secrets or environment variables instead of this form.'),
    ];

    $form['actions'] = [
      '#type' => 'actions',
    ];

    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Save Configuration'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $key = $form_state->getValue(['encryption_key', 'key']);

    if (empty($key) || strlen($key) < 32) {
      $form_state->setErrorByName('encryption_key][key', $this->t('Encryption key must be at least 32 characters long.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $key = $form_state->getValue(['encryption_key', 'key']);

    if ($key) {
      // Store the key in Drupal settings
      $settings_file = DRUPAL_ROOT . '/sites/default/settings.php';
      $settings_content = file_get_contents($settings_file);

      // Remove existing key if present
      $pattern = '/\$settings\[\'user_confidential_data_encryption_key\'\]\s*=\s*[^;]+;/';
      $settings_content = preg_replace($pattern, '', $settings_content);

      // Add new key
      $key_config = "\n// User Confidential Data Encryption Key\n";
      $key_config .= "\$settings['user_confidential_data_encryption_key'] = '" . addslashes($key) . "';\n";

      file_put_contents($settings_file, $key_config, FILE_APPEND);

      $this->messenger()->addStatus($this->t('Encryption key has been saved to settings.php'));
      $this->messenger()->addWarning($this->t('For security, consider using Docker secrets or environment variables instead.'));
    }
  }

}