<?php

/**
 * @file
 * Contains \Drupal\tour\TipsBag.
 */

namespace Drupal\tour;

use Drupal\Core\Plugin\DefaultPluginBag;

/**
 * A collection of tips.
 */
class TipsBag extends DefaultPluginBag {

  /**
   * {@inheritdoc}
   */
  protected $pluginKey = 'plugin';

  /**
   * {@inheritdoc}
   *
   * @return \Drupal\tour\TipPluginInterface
   */
  public function &get($instance_id) {
    return parent::get($instance_id);
  }

}
