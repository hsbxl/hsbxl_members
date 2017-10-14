<?php

namespace Drupal\hsbxl_members;

use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;

/**
 * Access controller for the Membership entity.
 *
 * @see \Drupal\hsbxl_members\Entity\Membership.
 */
class MembershipAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    /** @var \Drupal\hsbxl_members\Entity\MembershipInterface $entity */
    switch ($operation) {
      case 'view':
        if (!$entity->isPublished()) {
          return AccessResult::allowedIfHasPermission($account, 'view unpublished membership entities');
        }
        return AccessResult::allowedIfHasPermission($account, 'view published membership entities');

      case 'update':
        return AccessResult::allowedIfHasPermission($account, 'edit membership entities');

      case 'delete':
        return AccessResult::allowedIfHasPermission($account, 'delete membership entities');
    }

    // Unknown operation, no opinion.
    return AccessResult::neutral();
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    return AccessResult::allowedIfHasPermission($account, 'add membership entities');
  }

}
