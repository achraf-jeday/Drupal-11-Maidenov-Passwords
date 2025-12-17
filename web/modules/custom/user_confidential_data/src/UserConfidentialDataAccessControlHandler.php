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

    // Super admin (uid=1) has access to everything.
    if ($account->id() == 1) {
      return AccessResult::allowed()->cachePerUser();
    }

    switch ($operation) {
      case 'view':
        // Check for own view permission.
        if ($account->hasPermission('view own user confidential data')) {
          $is_owner = $entity->get('user_id')->target_id == $account->id();
          return AccessResult::allowedIf($is_owner)
            ->cachePerPermissions()
            ->cachePerUser()
            ->addCacheableDependency($entity);
        }

        return AccessResult::neutral();

      case 'update':
        // Check for own edit permission.
        if ($account->hasPermission('edit own user confidential data')) {
          $is_owner = $entity->get('user_id')->target_id == $account->id();
          return AccessResult::allowedIf($is_owner)
            ->cachePerPermissions()
            ->cachePerUser()
            ->addCacheableDependency($entity);
        }

        return AccessResult::neutral();

      case 'delete':
        // Check for own delete permission.
        if ($account->hasPermission('delete own user confidential data')) {
          $is_owner = $entity->get('user_id')->target_id == $account->id();
          return AccessResult::allowedIf($is_owner)
            ->cachePerPermissions()
            ->cachePerUser()
            ->addCacheableDependency($entity);
        }

        return AccessResult::neutral();
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
