<?php

/**
 * @file
 * Post update functions for Action module.
 */

use Drupal\Core\Config\Entity\ConfigEntityUpdater;
use Drupal\system\ActionConfigEntityInterface;

/**
 * Moves action plugins to core.
 */
function action_post_update_move_plugins(&$sandbox = NULL) {
  $resave_ids = [
    'action_goto_action',
    'action_message_action',
    'action_send_email_action',
  ];
  \Drupal::classResolver(ConfigEntityUpdater::class)->update($sandbox, 'action', function (ActionConfigEntityInterface $action) use ($resave_ids) {
    // Save entity to recalculate dependencies.
    return $action->isConfigurable() && in_array($action->getPlugin()->getPluginId(), $resave_ids, TRUE);
  });
}

/**
 * Removes action settings.
 */
function action_post_update_remove_settings() {
  \Drupal::configFactory()->getEditable('action.settings')->delete();
}
