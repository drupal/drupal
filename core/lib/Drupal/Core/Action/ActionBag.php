<?php

/**
 * @file
 * Contains \Drupal\Core\Action\ActionBag.
 */

namespace Drupal\Core\Action;

use Drupal\Core\Plugin\DefaultSinglePluginBag;

/**
 * Provides a container for lazily loading Action plugins.
 */
class ActionBag extends DefaultSinglePluginBag {

  /**
   * {@inheritdoc}
   *
   * @return \Drupal\Core\Action\ActionInterface
   */
  public function &get($instance_id) {
    return parent::get($instance_id);
  }

}
