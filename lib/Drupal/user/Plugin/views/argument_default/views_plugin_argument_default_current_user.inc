<?php

/**
 * @file
 * Contains the current user argument default plugin.
 */

use Drupal\views\Plugin\views\argument_default\ArgumentDefaultPluginBase;

/**
 * Default argument plugin to extract the global $user
 *
 * This plugin actually has no options so it odes not need to do a great deal.
 */
class views_plugin_argument_default_current_user extends ArgumentDefaultPluginBase {
  function get_argument() {
    global $user;
    return $user->uid;
  }
}
