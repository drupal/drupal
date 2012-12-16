<?php

/**
 * @file
 * Contains \Drupal\views\Tests\TestHelperPlugin.
 */

namespace Drupal\views\Tests;

use Drupal\views\Plugin\views\PluginBase;

/**
 * Wraps the plugin base class to be able to instantiate it.
 *
 * @see \Drupal\views\Plugin\views\PluginBase.
 */
class TestHelperPlugin extends PluginBase {

  /**
   * Calls the protected method setOptionDefaults().
   *
   * @see \Drupal\views\Plugin\views\PluginBase::setOptionDefaults().
   */
  public function testSetOptionDefaults(&$storage, $options, $level = 0) {
    $this->setOptionDefaults($storage, $options, $level);
  }

}
