<?php

namespace Drupal\user_confidential_data;

use Drupal\Core\Config\Entity\ConfigEntityListBuilder;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Url;

/**
 * Provides a listing of User Confidential Data Type entities.
 */
class UserConfidentialDataTypeListBuilder extends ConfigEntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['label'] = $this->t('User Confidential Data Type');
    $header['id'] = $this->t('Machine name');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    $row['label'] = $entity->label();
    $row['id'] = $entity->id();
    return $row + parent::buildRow($entity);
  }

  /**
   * {@inheritdoc}
   */
  public function render() {
    $build['table'] = parent::render();

    $build['add_link'] = [
      '#type' => 'link',
      '#title' => $this->t('Add user confidential data type'),
      '#url' => Url::fromRoute('entity.user_confidential_data_type.add_form'),
      '#attributes' => [
        'class' => ['button button-action button--primary', 'js-dialog-link', 'use-ajax'],
      ],
    ];

    return $build;
  }

}