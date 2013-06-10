<?php

/**
 * @file
 * Definition of Drupal\user\Plugin\views\argument_default\User.
 */

namespace Drupal\user\Plugin\views\argument_default;

use Drupal\Component\Annotation\Plugin;
use Drupal\Core\Annotation\Translation;
use Drupal\views\Plugin\views\argument_default\ArgumentDefaultPluginBase;

/**
 * Default argument plugin to extract a user via menu_get_object.
 *
 * @Plugin(
 *   id = "user",
 *   module = "user",
 *   title = @Translation("User ID from URL")
 * )
 */
class User extends ArgumentDefaultPluginBase {

  protected function defineOptions() {
    $options = parent::defineOptions();
    $options['user'] = array('default' => '', 'bool' => TRUE, 'translatable' => FALSE);

    return $options;
  }

  public function buildOptionsForm(&$form, &$form_state) {
    $form['user'] = array(
      '#type' => 'checkbox',
      '#title' => t('Also look for a node and use the node author'),
      '#default_value' => $this->options['user'],
    );
  }

  public function getArgument() {
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

    // If the current page is a view that takes uid as an argument, return the uid.
    $view = views_get_page_view();

    if ($view && isset($view->argument['uid'])) {
      return $view->argument['uid']->argument;
    }
  }

}
