<?php

/**
 * @file
 * Post update functions for User module.
 */

use Drupal\Core\Config\Entity\ConfigEntityUpdater;
use Drupal\Core\Entity\Display\EntityViewDisplayInterface;

/**
 * Implements hook_removed_post_updates().
 */
function user_removed_post_updates(): array {
  return [
    'user_post_update_enforce_order_of_permissions' => '9.0.0',
    'user_post_update_update_roles' => '10.0.0',
    'user_post_update_sort_permissions' => '11.0.0',
    'user_post_update_sort_permissions_again' => '11.0.0',
  ];
}

/**
 * Add hidden 'created' field to user entity view displays.
 */
function user_post_update_add_created_to_display(array &$sandbox): void {
  $configUpdater = \Drupal::classResolver(ConfigEntityUpdater::class);
  // If the created field was previously already added as a content component on
  // the display content, do not update. Otherwise, save the display. Either
  // 'created' was previously saved as hidden, or it was added to the hidden
  // section when the display entity was instantiated, because the default
  // display configuration for created is 'hidden'.
  // @see \Drupal\Core\Entity\EntityDisplayBase::init()
  $configUpdater->update($sandbox, 'entity_view_display', function (EntityViewDisplayInterface $display): bool {
    return ($display->getTargetEntityTypeId() === 'user') && is_null($display->getComponent('created'));
  }, TRUE);
}
