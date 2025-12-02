<?php

declare(strict_types=1);

namespace Drupal\config_test;

use Symfony\Component\Validator\Context\ExecutionContextInterface;

// cspell:ignore nyans

/**
 * Provides a collection of validation callbacks for testing purposes.
 */
class ConfigValidation {

  /**
   * Keep track of called validators.
   *
   * @var array
   */
  public static array $calledValidators = [];

  /**
   * Validates a llama.
   *
   * @param string $string
   *   The string to validate.
   * @param \Symfony\Component\Validator\Context\ExecutionContextInterface $context
   *   The validation execution context.
   */
  public static function validateLlama($string, ExecutionContextInterface $context) {
    if (!in_array($string, ['llama', 'alpaca', 'guanaco', 'vicuña'], TRUE)) {
      $context->addViolation('no valid llama');
    }
  }

  /**
   * Validates cats.
   *
   * @param string $string
   *   The string to validate.
   * @param \Symfony\Component\Validator\Context\ExecutionContextInterface $context
   *   The validation execution context.
   */
  public static function validateCats($string, ExecutionContextInterface $context) {
    self::$calledValidators[__FUNCTION__] = (self::$calledValidators[__FUNCTION__] ?? 0) + 1;
    if (!in_array($string, ['kitten', 'cats', 'nyans'])) {
      $context->addViolation('no valid cat');
    }
  }

  /**
   * Validates a number.
   *
   * @param int $count
   *   The integer to validate.
   * @param \Symfony\Component\Validator\Context\ExecutionContextInterface $context
   *   The validation execution context.
   */
  public static function validateCatCount($count, ExecutionContextInterface $context) {
    self::$calledValidators[__FUNCTION__] = (self::$calledValidators[__FUNCTION__] ?? 0) + 1;
    if ($count <= 1) {
      $context->addViolation('no enough cats');
    }
  }

  /**
   * Validates giraffes.
   *
   * @param string $string
   *   The string to validate.
   * @param \Symfony\Component\Validator\Context\ExecutionContextInterface $context
   *   The validation execution context.
   */
  public static function validateGiraffes($string, ExecutionContextInterface $context) {
    if (!str_starts_with($string, 'hum')) {
      $context->addViolation('Giraffes just hum');
    }
  }

  /**
   * Validates a mapping.
   *
   * @param array $mapping
   *   The data to validate.
   * @param \Symfony\Component\Validator\Context\ExecutionContextInterface $context
   *   The validation execution context.
   */
  public static function validateMapping($mapping, ExecutionContextInterface $context) {
    self::$calledValidators[__FUNCTION__] = (self::$calledValidators[__FUNCTION__] ?? 0) + 1;

    // Ensure we are validating the entire mapping by diffing against all the
    // keys.
    $mapping_schema = \Drupal::service('config.typed')->get('config_test.validation')->getValue();
    if ($diff = array_diff_key($mapping, $mapping_schema)) {
      $context->addViolation('Unexpected keys: ' . implode(', ', array_keys($diff)));
    }
  }

  /**
   * Validates a sequence.
   *
   * @param array $sequence
   *   The data to validate.
   * @param \Symfony\Component\Validator\Context\ExecutionContextInterface $context
   *   The validation execution context.
   */
  public static function validateSequence($sequence, ExecutionContextInterface $context) {
    if (isset($sequence['invalid-key'])) {
      $context->addViolation('Invalid giraffe key.');
    }
  }

}
