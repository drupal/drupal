<?php

namespace Drupal\views_test_data\Plugin\views\argument_default;

use Drupal\views\Plugin\views\argument_default\ArgumentDefaultPluginBase;

/**
 * Defines a argument default test plugin.
 *
 * @ViewsArgumentDefault(
 *   id = "argument_default_test",
 *   title = @Translation("Argument default test")
 * )
 */
class ArgumentDefaultTest extends ArgumentDefaultPluginBase {

  /**
   * {@inheritdoc}
   */
  protected function defineOptions() {
    $options = parent::defineOptions();
    $options['value'] = array('default' => '');

    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function getArgument() {
    return $this->options['value'];
  }

  /**
   * {@inheritdoc}
   */
  public function calculateDependencies() {
    return [
      'content' => ['ArgumentDefaultTest'],
    ];
  }

}
