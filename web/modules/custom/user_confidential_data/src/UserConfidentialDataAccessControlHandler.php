<?php

namespace Drupal\user_confidential_data;

use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;

/**
 * Access controller for the User Confidential Data entity.
 */
class UserConfidentialDataAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    /** @var \Drupal\user_confidential_data\Entity\UserConfidentialData $entity */
    switch ($operation) {
      case 'view':
        return AccessResult::allowedIfHasPermission($account, 'view user confidential data');

      case 'update':
        return AccessResult::allowedIfHasPermission($account, 'edit user confidential data');

      case 'delete':
        return AccessResult::allowedIfHasPermission($account, 'delete user confidential data');
    }

    return AccessResult::neutral();
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    return AccessResult::allowedIfHasPermission($account, 'create user confidential data');
  }

}