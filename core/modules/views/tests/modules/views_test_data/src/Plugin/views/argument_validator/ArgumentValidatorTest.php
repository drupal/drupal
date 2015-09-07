<?php

/**
 * @file
 * Contains \Drupal\views_test_data\Plugin\views\argument_validator\ArgumentValidatorTest.
 */

namespace Drupal\views_test_data\Plugin\views\argument_validator;
use Drupal\views\Plugin\views\argument_validator\ArgumentValidatorPluginBase;

/**
 * Defines a argument validator test plugin.
 *
 * @ViewsArgumentValidator(
 *   id = "argument_validator_test",
 *   title = @Translation("Argument validator test")
 * )
 */
class ArgumentValidatorTest extends ArgumentValidatorPluginBase {

  /**
   * {@inheritdoc}
   */
  public function calculateDependencies() {
    return [
      'content' => ['ArgumentValidatorTest'],
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function defineOptions() {
    $options = parent::defineOptions();
    $options['test_value'] = ['default' => ''];

    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function validateArgument($arg) {
    return $arg == $this->options['test_value'];
  }

}
