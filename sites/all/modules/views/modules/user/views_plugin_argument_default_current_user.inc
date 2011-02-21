<?php
// $Id: views_plugin_argument_default_current_user.inc,v 1.1.6.1 2010/01/28 22:44:36 dereine Exp $
/**
 * @file
 * Contains the current user argument default plugin.
 */

/**
 * Default argument plugin to extract the global $user
 *
 * This plugin actually has no options so it odes not need to do a great deal.
 */
class views_plugin_argument_default_current_user extends views_plugin_argument_default {
  function get_argument() {
    global $user;
    return $user->uid;
  }
}

