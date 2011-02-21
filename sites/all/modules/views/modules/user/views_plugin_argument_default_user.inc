<?php
// $Id: views_plugin_argument_default_user.inc,v 1.1.6.2 2010/12/14 00:00:52 merlinofchaos Exp $
/**
 * @file
 * Contains the user from URL argument default plugin.
 */

/**
 * Default argument plugin to extract a user via menu_get_object
 */
class views_plugin_argument_default_user extends views_plugin_argument_default {
  function option_definition() {
    $options = parent::option_definition();
    $options['user'] = array('default' => '', 'bool' => TRUE, 'translatable' => FALSE);

    return $options;
  }

  function options_form(&$form, &$form_state) {
    $form['user'] = array(
      '#type' => 'checkbox',
      '#title' => t('Also look for a node and use the node author'),
      '#default_value' => $this->options['user'],
    );
  }

  function convert_options(&$options) {
    if (!isset($options['user']) && isset($this->argument->options['default_argument_user'])) {
      $options['user'] = $this->argument->options['default_argument_user'];
    }
  }

  function get_argument() {
    foreach (range(1, 3) as $i) {
      $user = menu_get_object('user', $i);
      if (!empty($user)) {
        return $user->uid;
      }
    }

    foreach (range(1, 3) as $i) {
      $user = menu_get_object('user_uid_optional', $i);
      if (!empty($user)) {
        return $user->uid;
      }
    }

    if (!empty($this->options['user'])) {
      foreach (range(1, 3) as $i) {
        $node = menu_get_object('node', $i);
        if (!empty($node)) {
          return $node->uid;
        }
      }
    }

    if (arg(0) == 'user' && is_numeric(arg(1))) {
      return arg(1);
    }

    if (!empty($this->options['user'])) {
      if (arg(0) == 'node' && is_numeric(arg(1))) {
        $node = node_load(arg(1));
        if ($node) {
          return $node->uid;
        }
      }
    }
  }
}

