<?php

namespace Drupal\views\Tests;

use Drupal\views\Plugin\views\PluginBase;

/**
 * Wraps the plugin base class to be able to instantiate it.
 *
 * @see \Drupal\views\Plugin\views\PluginBase.
 */
class TestHelperPlugin extends PluginBase {

  /**
   * Stores the defined options.
   *
   * @var array
   */
  protected $definedOptions = [];

  /**
   * Calls the protected method setOptionDefaults().
   *
   * @see \Drupal\views\Plugin\views\PluginBase::setOptionDefaults()
   */
  public function testSetOptionDefaults(&$storage, $options, $level = 0) {
    $this->setOptionDefaults($storage, $options, $level);
  }

  /**
   * Allows to set the defined options.
   *
   * @param array $options
   *   The options to set.
   *
   * @return $this
   */
  public function setDefinedOptions($options) {
    $this->definedOptions = $options;

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  protected function defineOptions() {
    // Normally we provide a limited set of options, but for testing purposes we
    // make it possible to set the defined options statically.
    return $this->definedOptions;
  }

}
