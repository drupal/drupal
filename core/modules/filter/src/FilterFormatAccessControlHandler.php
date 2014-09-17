<?php

/**
 * @file
 * Contains \Drupal\filter\FilterFormatAccessControlHandler.
 */

namespace Drupal\filter;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Defines the access control handler for the filter format entity type.
 *
 * @see \Drupal\filter\Entity\FilterFormat
 */
class FilterFormatAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $filter_format, $operation, $langcode, AccountInterface $account) {
    /** @var \Drupal\filter\FilterFormatInterface $filter_format */

    // All users are allowed to use the fallback filter.
    if ($operation == 'use') {
      if ($filter_format->isFallbackFormat()) {
        return AccessResult::allowed();
      }
      else {
        return AccessResult::allowedIfHasPermission($account, $filter_format->getPermissionName());
      }
    }

    // The fallback format may not be disabled.
    if ($operation == 'disable' && $filter_format->isFallbackFormat()) {
      return AccessResult::forbidden();
    }

    // We do not allow filter formats to be deleted through the UI, because that
    // would render any content that uses them unusable.
    if ($operation == 'delete') {
      return AccessResult::forbidden();
    }

    if (in_array($operation, array('disable', 'update'))) {
      return parent::checkAccess($filter_format, $operation, $langcode, $account);
    }

    // No opinion.
    return AccessResult::create();
  }

}
