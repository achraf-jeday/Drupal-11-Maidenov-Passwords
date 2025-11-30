<?php

namespace Drupal\user_confidential_data;

use Drupal\Core\Entity\Sql\SqlContentEntityStorageSchema;
use Drupal\Core\Database\Database;
use Drupal\Core\Entity\EntityTypeInterface;

/**
 * Schema handler for User Confidential Data entity.
 */
class UserConfidentialDataStorageSchema extends SqlContentEntityStorageSchema {

  /**
   * {@inheritdoc}
   */
  protected function getEntitySchema(EntityTypeInterface $entity_type, $reset = FALSE) {
    $schema = parent::getEntitySchema($entity_type, $reset);

    // Add indexes for performance.
    $schema['user_confidential_data']['indexes'] += [
      'user_confidential_data_user_id' => ['user_id'],
      'user_confidential_data_created' => ['created'],
      'user_confidential_data_changed' => ['changed'],
    ];

    return $schema;
  }

}