<?php

/**
 * @file
 * Contains \Drupal\filter\FilterFormatAccess.
 */

namespace Drupal\filter;

use Drupal\Core\Entity\EntityAccessController;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Defines the access controller for the filter format entity type.
 */
class FilterFormatAccess extends EntityAccessController {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $filter_format, $operation, $langcode, AccountInterface $account) {
    /** @var \Drupal\filter\FilterFormatInterface $filter_format */

    // All users are allowed to use the fallback filter.
    if ($operation == 'use') {
      return $filter_format->isFallbackFormat() || $account->hasPermission($filter_format->getPermissionName());
    }

    // The fallback format may not be disabled.
    if ($operation == 'disable' && $filter_format->isFallbackFormat()) {
      return FALSE;
    }

    // We do not allow filter formats to be deleted through the UI, because that
    // would render any content that uses them unusable.
    if ($operation == 'delete') {
      return FALSE;
    }

    if (in_array($operation, array('disable', 'update'))) {
      return parent::checkAccess($filter_format, $operation, $langcode, $account);
    }
  }

}
