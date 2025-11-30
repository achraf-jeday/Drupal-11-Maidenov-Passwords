<?php

namespace Drupal\user_confidential_data;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Url;

/**
 * Provides a listing of User Confidential Data entities.
 */
class UserConfidentialDataListBuilder extends EntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['id'] = $this->t('ID');
    $header['name'] = $this->t('Name');
    $header['user'] = $this->t('User');
    $header['created'] = $this->t('Created');
    $header['status'] = $this->t('Status');
    $header['operations'] = $this->t('Operations');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    /** @var \Drupal\user_confidential_data\Entity\UserConfidentialDataInterface $entity */
    $row['id'] = (string) $entity->id();

    // Safely get the name field, handling NULL values
    $name = $entity->getName();
    $row['name'] = (string) ($name ?? $this->t('Not set'));

    // Safely get the user field, handling NULL values
    $user = $entity->getUser();
    $row['user'] = (string) ($user ? $user->getDisplayName() : $this->t('Not set'));

    // Safely get the created time, handling NULL values
    $created_time = $entity->getCreatedTime();
    $row['created'] = (string) ($created_time ? \Drupal::service('date.formatter')->format($created_time, 'short') : $this->t('Not set'));

    // Safely get the status
    $row['status'] = (string) ($entity->isPublished() ? $this->t('Published') : $this->t('Unpublished'));

    // Add the operations column manually to ensure it works
    $operations = [
      'data' => [
        '#type' => 'operations',
        '#links' => [],
      ],
    ];

    // Add View operation
    if ($entity->access('view')) {
      $operations['data']['#links']['view'] = [
        'title' => (string) $this->t('View'),
        'url' => $entity->toUrl('canonical'),
      ];
    }

    // Add Edit operation
    if ($entity->access('update')) {
      $operations['data']['#links']['edit'] = [
        'title' => (string) $this->t('Edit'),
        'url' => $entity->toUrl('edit-form'),
      ];
    }

    // Add Delete operation
    if ($entity->access('delete')) {
      $operations['data']['#links']['delete'] = [
        'title' => (string) $this->t('Delete'),
        'url' => $entity->toUrl('delete-form'),
      ];
    }

    $row['operations'] = $operations;

    return $row;
  }

}