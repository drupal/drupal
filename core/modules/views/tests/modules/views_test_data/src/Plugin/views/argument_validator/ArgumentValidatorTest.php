<?php

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
    if ($arg === 'this value should be replaced') {
      // Set the argument to a numeric value so this is valid on PostgreSQL for
      // numeric fields.
      $this->argument->argument = '1';
      return TRUE;
    }
    return $arg == $this->options['test_value'];
  }

}
