<?php

namespace Drupal\user_confidential_data\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Form handler for User Confidential Data Type forms.
 */
class UserConfidentialDataTypeForm extends EntityForm {

  /**
   * The entity type bundle info service.
   *
   * @var \Drupal\Core\Entity\EntityTypeBundleInfoInterface
   */
  protected $entityTypeBundleInfo;

  /**
   * Constructs a new UserConfidentialDataTypeForm.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Entity\EntityTypeBundleInfoInterface $entity_type_bundle_info
   *   The entity type bundle info service.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, EntityTypeBundleInfoInterface $entity_type_bundle_info) {
    $this->entityTypeBundleInfo = $entity_type_bundle_info;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('entity_type.bundle.info')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    $user_confidential_data_type = $this->entity;
    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#maxlength' => 255,
      '#default_value' => $user_confidential_data_type->label(),
      '#description' => $this->t('The human-readable name of this user confidential data type.'),
      '#required' => TRUE,
    ];
    $form['id'] = [
      '#type' => 'machine_name',
      '#default_value' => $user_confidential_data_type->id(),
      '#machine_name' => [
        'exists' => '\Drupal\user_confidential_data\Entity\UserConfidentialDataType::load',
      ],
      '#disabled' => !$user_confidential_data_type->isNew(),
      '#description' => $this->t('A unique machine-readable name for this user confidential data type.'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  protected function actions(array $form, FormStateInterface $form_state) {
    $actions = parent::actions($form, $form_state);

    // Add a delete button for existing entities.
    if (!$this->entity->isNew()) {
      $actions['delete'] = [
        '#type' => 'link',
        '#title' => $this->t('Delete'),
        '#url' => $this->entity->toUrl('delete-form'),
        '#attributes' => [
          'class' => ['button'],
        ],
      ];
    }

    return $actions;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $user_confidential_data_type = $this->entity;
    $status = $user_confidential_data_type->save();

    if ($status) {
      $this->messenger()->addMessage($this->t('Saved the %label user confidential data type.', [
        '%label' => $user_confidential_data_type->label(),
      ]));
    }
    else {
      $this->messenger()->addMessage($this->t('The %label user confidential data type was not saved.', [
        '%label' => $user_confidential_data_type->label(),
      ]), 'error');
    }

    $form_state->setRedirect('entity.user_confidential_data_type.collection');
  }

}