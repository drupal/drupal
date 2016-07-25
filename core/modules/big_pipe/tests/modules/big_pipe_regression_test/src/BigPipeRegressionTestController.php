<?php

namespace Drupal\big_pipe_regression_test;

use Drupal\big_pipe\Render\BigPipeMarkup;

class BigPipeRegressionTestController {

  const MARKER_2678662 = '<script>var hitsTheFloor = "</body>";</script>';

  /**
   * @see \Drupal\Tests\big_pipe\FunctionalJavascript\BigPipeRegressionTest::testMultipleBodies_2678662()
   */
  public function regression2678662() {
    return [
      '#markup' => BigPipeMarkup::create(self::MARKER_2678662),
    ];
  }

}
