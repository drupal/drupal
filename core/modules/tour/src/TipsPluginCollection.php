<?php

namespace Drupal\tour;

use Drupal\Core\Plugin\DefaultLazyPluginCollection;

/**
 * A collection of tips.
 */
class TipsPluginCollection extends DefaultLazyPluginCollection {

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
