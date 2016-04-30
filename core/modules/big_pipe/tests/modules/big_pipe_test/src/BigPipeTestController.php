<?php

namespace Drupal\big_pipe_test;

use Drupal\big_pipe\Render\BigPipeMarkup;
use Drupal\big_pipe\Tests\BigPipePlaceholderTestCases;
use Drupal\big_pipe_test\EventSubscriber\BigPipeTestSubscriber;

class BigPipeTestController {

  /**
   * Returns a all BigPipe placeholder test case render arrays.
   *
   * @return array
   */
  public function test() {
    $build = [];

    $cases = BigPipePlaceholderTestCases::cases(\Drupal::getContainer());

    // 1. HTML placeholder: status messages. Drupal renders those automatically,
    // so all that we need to do in this controller is set a message.
    drupal_set_message('Hello from BigPipe!');
    $build['html'] = $cases['html']->renderArray;

    // 2. HTML attribute value placeholder: form action.
    $build['html_attribute_value'] = $cases['html_attribute_value']->renderArray;

    // 3. HTML attribute value subset placeholder: CSRF token in link.
    $build['html_attribute_value_subset'] = $cases['html_attribute_value_subset']->renderArray;

    // 4. Edge case: custom string to be considered as a placeholder that
    // happens to not be valid HTML.
    $build['edge_case__invalid_html'] = $cases['edge_case__invalid_html']->renderArray;

    // 5. Edge case: non-#lazy_builder placeholder.
    $build['edge_case__html_non_lazy_builder'] = $cases['edge_case__html_non_lazy_builder']->renderArray;

    // 6. Exception: #lazy_builder that throws an exception.
    $build['exception__lazy_builder'] = $cases['exception__lazy_builder']->renderArray;

    // 7. Exception: placeholder that causes response filter to throw exception.
    $build['exception__embedded_response'] = $cases['exception__embedded_response']->renderArray;

    return $build;
  }

  /**
   * @return array
   */
  public static function nope() {
    return ['#markup' => '<p>Nope.</p>'];
  }

  /**
   * #lazy_builder callback; builds <time> markup with current time.
   *
   * Note: does not actually use current time, that would complicate testing.
   *
   * @return array
   */
  public static function currentTime() {
    return [
      '#markup' => '<time datetime=' . date('Y-m-d', 668948400) . '"></time>',
      '#cache' => ['max-age' => 0]
    ];
  }

  /**
   * #lazy_builder callback; says "hello" or "yarhar".
   *
   * @return array
   */
  public static function helloOrYarhar() {
    return [
      '#markup' => BigPipeMarkup::create('<marquee>Yarhar llamas forever!</marquee>'),
      '#cache' => ['max-age' => 0],
    ];
  }

  /**
   * #lazy_builder callback; throws exception.
   *
   * @throws \Exception
   */
  public static function exception() {
    throw new \Exception('You are not allowed to say llamas are not cool!');
  }

  /**
   * #lazy_builder callback; returns content that will trigger an exception.
   *
   * @see \Drupal\big_pipe_test\EventSubscriber\BigPipeTestSubscriber::onRespondTriggerException()
   *
   * @return array
   */
  public static function responseException() {
    return ['#plain_text' => BigPipeTestSubscriber::CONTENT_TRIGGER_EXCEPTION];
  }

}
