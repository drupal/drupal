<?php

declare(strict_types=1);

namespace Drupal\big_pipe_regression_test\Hook;

use Drupal\Core\Hook\Attribute\Hook;

/**
 * Hook implementations for big_pipe_regression_test.
 */
class BigPipeRegressionTestHooks {

  /**
   * Implements hook_theme().
   *
   * @see \Drupal\Tests\big_pipe\FunctionalJavascript\BigPipeRegressionTest::testBigPipeLargeContent
   */
  #[Hook('theme')]
  public function theme() : array {
    return ['big_pipe_test_large_content' => ['variables' => []]];
  }

}
