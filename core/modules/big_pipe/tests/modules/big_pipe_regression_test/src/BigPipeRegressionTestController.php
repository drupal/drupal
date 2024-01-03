<?php

namespace Drupal\big_pipe_regression_test;

use Drupal\big_pipe\Render\BigPipeMarkup;
use Drupal\Component\Utility\Random;
use Drupal\Core\Security\TrustedCallbackInterface;

class BigPipeRegressionTestController implements TrustedCallbackInterface {

  const MARKER_2678662 = '<script>var hitsTheFloor = "</body>";</script>';

  const PLACEHOLDER_COUNT = 3000;

  /**
   * @see \Drupal\Tests\big_pipe\FunctionalJavascript\BigPipeRegressionTest::testMultipleBodies_2678662()
   */
  public function regression2678662() {
    return [
      '#markup' => BigPipeMarkup::create(self::MARKER_2678662),
    ];
  }

  /**
   * @see \Drupal\Tests\big_pipe\FunctionalJavascript\BigPipeRegressionTest::testMultipleBodies_2678662()
   */
  public function regression2802923() {
    return [
      '#prefix' => BigPipeMarkup::create('<p>Hi, my train will arrive at '),
      'time' => [
        '#lazy_builder' => [static::class . '::currentTime', []],
        '#create_placeholder' => TRUE,
      ],
      '#suffix' => BigPipeMarkup::create(' — will I still be able to catch the connection to the center?</p>'),
    ];
  }

  /**
   * A page with large content.
   *
   * @see \Drupal\Tests\big_pipe\FunctionalJavascript\BigPipeRegressionTest::testBigPipeLargeContent
   */
  public function largeContent() {
    return [
      'item1' => [
        '#lazy_builder' => [static::class . '::largeContentBuilder', []],
        '#create_placeholder' => TRUE,
      ],
    ];
  }

  /**
   * A page with multiple nodes.
   *
   * @see \Drupal\Tests\big_pipe\FunctionalJavascript\BigPipeRegressionTest::testMultipleReplacements
   */
  public function multipleReplacements() {
    $build = [];
    foreach (range(1, self::PLACEHOLDER_COUNT) as $length) {
      $build[] = [
        '#lazy_builder' => [static::class . '::renderRandomSentence', [$length]],
        '#create_placeholder' => TRUE,
      ];
    }

    return $build;
  }

  /**
   * Renders large content.
   *
   * @see \Drupal\Tests\big_pipe\FunctionalJavascript\BigPipeRegressionTest::testBigPipeLargeContent
   */
  public static function largeContentBuilder() {
    return [
      '#theme' => 'big_pipe_test_large_content',
      '#cache' => ['max-age' => 0],
    ];
  }

  /**
   * #lazy_builder callback; builds <time> markup with current time.
   *
   * @return array
   */
  public static function currentTime() {
    return [
      '#markup' => '<time datetime="' . date('Y-m-d', time()) . '"></time>',
      '#cache' => ['max-age' => 0],
    ];
  }

  /**
   * Renders a random length sentence.
   *
   * @param int $length
   *   The sentence length.
   *
   * @return array
   *   Render array.
   */
  public static function renderRandomSentence(int $length): array {
    return ['#cache' => ['max-age' => 0], '#markup' => (new Random())->sentences($length)];
  }

  /**
   * {@inheritdoc}
   */
  public static function trustedCallbacks() {
    return ['currentTime', 'largeContentBuilder', 'renderRandomSentence'];
  }

}
