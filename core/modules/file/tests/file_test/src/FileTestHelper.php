<?php

declare(strict_types=1);

namespace Drupal\file_test;

/**
 * Helper for file tests.
 *
 * The caller must call reset() to initialize this module before
 * calling Drupal\file_test\FileTestHelper::getCalls() or setReturn().
 */
class FileTestHelper {

  /**
   * Reset/initialize the history of calls to the file_* hooks.
   *
   * @see Drupal\file_test\FileTestHelper::getCalls()
   * @see Drupal\file_test\FileTestHelper::reset()
   */
  public static function reset(): void {
    // Keep track of calls to these hooks
    $results = [
      'load' => [],
      'validate' => [],
      'download' => [],
      'insert' => [],
      'update' => [],
      'copy' => [],
      'move' => [],
      'delete' => [],
    ];
    \Drupal::keyValue('file_test')->set('results', $results);

    // These hooks will return these values, see FileTestHelper::setReturn().
    $return = [
      'validate' => [],
      'download' => NULL,
    ];
    \Drupal::keyValue('file_test')->set('return', $return);
  }

  /**
   * Gets the arguments passed to a given hook invocation.
   *
   * Arguments are gathered since Drupal\file_test\FileTestHelper::reset() was
   * last called.
   *
   * @param string $op
   *   One of the hook_file_* operations: 'load', 'validate', 'download',
   *   'insert', 'update', 'copy', 'move', 'delete'.
   *
   * @return array
   *   Array of the parameters passed to each call.
   *
   * @see Drupal\file_test\FileTestHelper::logCall()
   * @see Drupal\file_test\FileTestHelper::reset()
   */
  public static function getCalls($op): array {
    $results = \Drupal::keyValue('file_test')->get('results', []);
    return $results[$op];
  }

  /**
   * Get an array with the calls for all hooks.
   *
   * @return array
   *   An array keyed by hook name ('load', 'validate', 'download', 'insert',
   *   'update', 'copy', 'move', 'delete') with values being arrays of
   *   parameters passed to each call.
   */
  public static function getAllCalls(): array {
    return \Drupal::keyValue('file_test')->get('results', []);
  }

  /**
   * Store the values passed to a hook invocation.
   *
   * @param string $op
   *   One of the hook_file_* operations: 'load', 'validate', 'download',
   *   'insert', 'update', 'copy', 'move', 'delete'.
   * @param array $args
   *   Values passed to hook.
   *
   * @see Drupal\file_test\FileTestHelper::getCalls()
   * @see Drupal\file_test\FileTestHelper::reset()
   */
  public static function logCall($op, $args): void {
    if (\Drupal::state()->get('file_test.count_hook_invocations', TRUE)) {
      $results = \Drupal::keyValue('file_test')->get('results', []);
      $results[$op][] = $args;
      \Drupal::keyValue('file_test')->set('results', $results);
    }
  }

  /**
   * Assign a return value for a given operation.
   *
   * @param string $op
   *   One of the hook_file_[validate,download] operations.
   * @param array|int $value
   *   Value for the hook to return.
   *
   * @see Drupal\file_test\FileTestHelper::getReturn()
   * @see Drupal\file_test\FileTestHelper::reset()
   */
  public static function setReturn($op, $value): void {
    $return = \Drupal::keyValue('file_test')->get('return', []);

    $return[$op] = $value;
    \Drupal::keyValue('file_test')->set('return', $return);
  }

  /**
   * Helper function for testing FileSystemInterface::scanDirectory().
   *
   * Each time the function is called the file is stored in a static variable.
   * When the function is called with no $filepath parameter, the results are
   * returned.
   *
   * @param string|null $filepath
   *   File path.
   * @param bool $reset
   *   (optional) If to reset the internal memory cache. If TRUE is passed, the
   *   first parameter has no effect. Defaults to FALSE.
   *
   * @return array
   *   If $filepath is NULL, an array of all previous $filepath parameters
   */
  public static function fileScanCallback($filepath = NULL, $reset = FALSE): array {
    static $files = [];

    if ($reset) {
      $files = [];
    }
    elseif ($filepath) {
      $files[] = $filepath;
    }

    return $files;
  }

  /**
   * Reset static variables used by FileTestHelper::fileScanCallback().
   */
  public static function fileScanCallbackReset(): void {
    self::fileScanCallback(NULL, TRUE);
  }

}
