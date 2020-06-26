<?php

namespace Drupal\event_form;

use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;

/**
 * Access controller for the Event applicant entity.
 *
 * @see \Drupal\event_form\Entity\EventApplicant.
 */
class EventApplicantAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    /** @var \Drupal\event_form\Entity\EventApplicantInterface $entity */
    switch ($operation) {
      case 'view':
        if (!$entity->isPublished()) {
          return AccessResult::allowedIfHasPermission($account, 'view unpublished event applicant entities');
        }
        return AccessResult::allowedIfHasPermission($account, 'view published event applicant entities');

      case 'update':
        return AccessResult::allowedIfHasPermission($account, 'edit event applicant entities');

      case 'delete':
        return AccessResult::allowedIfHasPermission($account, 'delete event applicant entities');
    }

    // Unknown operation, no opinion.
    return AccessResult::neutral();
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    return AccessResult::allowedIfHasPermission($account, 'add event applicant entities');
  }

}
