<?php

declare(strict_types=1);

namespace Drupal\big_pipe_regression_test;

use Drupal\big_pipe\Render\BigPipeMarkup;
use Drupal\Component\Utility\Random;
use Drupal\Core\Security\TrustedCallbackInterface;

/**
 * Controller for BigPipe regression tests.
 */
class BigPipeRegressionTestController implements TrustedCallbackInterface {

  const MARKER_2678662 = '<script>var hitsTheFloor = "</body>";</script>';

  const PLACEHOLDER_COUNT = 2000;

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
   * A page with an inline script.
   */
  public function inlineScriptContent(): array {
    return [
      '#lazy_builder' => [static::class . '::inlineScript', []],
      '#create_placeholder' => TRUE,
    ];
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
   * Render API callback: Builds <time> markup with current time.
   *
   * This function is assigned as a #lazy_builder callback.
   *
   * @return array
   *   Render array with a <time> markup with current time and cache settings.
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
   * Renders an inline script element between other markup tags.
   *
   * @return array
   *   Render array.
   */
  public static function inlineScript(): array {
    return [
      '#cache' => ['max-age' => 0],
      '#markup' => BigPipeMarkup::create(
        '<div class="container-before">First</div><script>document.body.classList.add("inline-script-fires");</script><div class="container-after">Second</div>'
      ),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public static function trustedCallbacks() {
    return ['currentTime', 'largeContentBuilder', 'renderRandomSentence', 'inlineScript'];
  }

}
