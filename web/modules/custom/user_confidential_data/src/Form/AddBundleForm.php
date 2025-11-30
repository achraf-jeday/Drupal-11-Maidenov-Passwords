<?php

namespace Drupal\user_confidential_data\Form;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Form to add a new User Confidential Data Type bundle.
 */
class AddBundleForm extends FormBase {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a new AddBundleForm.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'user_confidential_data_add_bundle_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Bundle Label'),
      '#description' => $this->t('The human-readable name of this bundle.'),
      '#maxlength' => 255,
      '#required' => TRUE,
    ];

    $form['id'] = [
      '#type' => 'machine_name',
      '#title' => $this->t('Machine name'),
      '#description' => $this->t('A unique machine-readable name for this bundle. It must only contain lowercase letters, numbers, and underscores.'),
      '#default_value' => '',
      '#machine_name' => [
        'exists' => '\Drupal\user_confidential_data\Entity\UserConfidentialDataType::load',
        'source' => ['label'],
      ],
      '#disabled' => FALSE,
      '#required' => TRUE,
    ];

    $form['actions'] = [
      '#type' => 'actions',
    ];

    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Create Bundle'),
      '#button_type' => 'primary',
    ];

    $form['actions']['cancel'] = [
      '#type' => 'link',
      '#title' => $this->t('Cancel'),
      '#url' => Url::fromRoute('entity.user_confidential_data_type.collection'),
      '#attributes' => ['class' => ['button']],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $bundle_id = $form_state->getValue('id');

    // Validate machine name format
    if (!preg_match('/^[a-z0-9_]+$/', $bundle_id)) {
      $form_state->setErrorByName('id', $this->t('The machine name must only contain lowercase letters, numbers, and underscores.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $label = $form_state->getValue('label');
    $id = $form_state->getValue('id');

    // Create the new bundle
    $bundle_storage = $this->entityTypeManager->getStorage('user_confidential_data_type');

    $bundle = $bundle_storage->create([
      'id' => $id,
      'label' => $label,
    ]);

    $bundle->save();

    $this->messenger()->addMessage($this->t('The bundle %label has been created.', ['%label' => $label]));

    // Redirect to the bundle listing page
    $form_state->setRedirect('entity.user_confidential_data_type.collection');
  }

}
