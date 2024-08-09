<?php

namespace Drupal\big_pipe_test;

use Drupal\big_pipe\Render\BigPipeMarkup;
use Drupal\big_pipe_test\EventSubscriber\BigPipeTestSubscriber;
use Drupal\Core\Form\EnforcedResponseException;
use Drupal\Core\Security\TrustedCallbackInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Returns responses for Big Pipe routes.
 */
class BigPipeTestController implements TrustedCallbackInterface {

  /**
   * Returns all BigPipe placeholder test case render arrays.
   *
   * @return array
   */
  public function test() {
    $has_session = \Drupal::service('session_configuration')->hasSession(\Drupal::requestStack()->getMainRequest());

    $build = [];

    $cases = BigPipePlaceholderTestCases::cases(\Drupal::getContainer());

    // 1. HTML placeholder: status messages. Drupal renders those automatically,
    // so all that we need to do in this controller is set a message.
    if ($has_session) {
      // Only set a message if a session already exists, otherwise we always
      // trigger a session, which means we can't test no-session requests.
      \Drupal::messenger()->addStatus('Hello from BigPipe!');
    }
    $build['html'] = $cases['html']->renderArray;

    // 2. HTML attribute value placeholder: form action.
    $build['html_attribute_value'] = $cases['html_attribute_value']->renderArray;

    // 3. HTML attribute value subset placeholder: CSRF token in link.
    $build['html_attribute_value_subset'] = $cases['html_attribute_value_subset']->renderArray;

    // 4. Edge case: custom string to be considered as a placeholder that
    // happens to not be valid HTML.
    $build['edge_case__invalid_html'] = $cases['edge_case__invalid_html']->renderArray;

    // 5. Edge case: non-#lazy_builder placeholder that suspends.
    $build['edge_case__html_non_lazy_builder_suspend'] = $cases['edge_case__html_non_lazy_builder_suspend']->renderArray;

    // 6. Edge case: non-#lazy_builder placeholder.
    $build['edge_case__html_non_lazy_builder'] = $cases['edge_case__html_non_lazy_builder']->renderArray;

    // 7. Exception: #lazy_builder that throws an exception.
    $build['exception__lazy_builder'] = $cases['exception__lazy_builder']->renderArray;

    // 8. Exception: placeholder that causes response filter to throw exception.
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
   * A page with multiple occurrences of the same placeholder.
   *
   * @see \Drupal\Tests\big_pipe\Functional\BigPipeTest::testBigPipeMultiOccurrencePlaceholders()
   *
   * @return array
   */
  public function multiOccurrence() {
    return [
      'item1' => [
        '#lazy_builder' => [static::class . '::counter', []],
        '#create_placeholder' => TRUE,
      ],
      'item2' => [
        '#lazy_builder' => [static::class . '::counter', []],
        '#create_placeholder' => TRUE,
      ],
      'item3' => [
        '#lazy_builder' => [static::class . '::counter', []],
        '#create_placeholder' => TRUE,
      ],
    ];
  }

  /**
   * A page with placeholder preview.
   *
   * @return array[]
   */
  public function placeholderPreview() {
    return [
      'user_container' => [
        '#type' => 'container',
        '#attributes' => ['id' => 'placeholder-preview-twig-container'],
        'user' => [
          '#lazy_builder' => ['user.toolbar_link_builder:renderDisplayName', []],
          '#create_placeholder' => TRUE,
        ],
      ],
      'user_links_container' => [
        '#type' => 'container',
        '#attributes' => ['id' => 'placeholder-render-array-container'],
        'user_links' => [
          '#lazy_builder' => [static::class . '::helloOrHi', []],
          '#create_placeholder' => TRUE,
          '#lazy_builder_preview' => [
            '#attributes' => ['id' => 'render-array-preview'],
            '#type' => 'container',
            '#markup' => 'There is a lamb and there is a puppy',
          ],
        ],
      ],
    ];
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
      '#markup' => '<time datetime="' . date('Y-m-d', 668948400) . '"></time>',
      '#cache' => ['max-age' => 0],
    ];
  }

  /**
   * #lazy_builder callback; suspends its own execution then returns markup.
   *
   * @return array
   */
  public static function piggy(): array {
    // Immediately call Fiber::suspend(), so that other placeholders are
    // executed next. When this is resumed, it will immediately return the
    // render array.
    if (\Fiber::getCurrent() !== NULL) {
      \Fiber::suspend();
    }
    return [
      '#markup' => '<span>This 🐷 little 🐽 piggy 🐖 stayed 🐽 at 🐷 home.</span>',
      '#cache' => ['max-age' => 0],
    ];
  }

  /**
   * #lazy_builder callback; says "hello" or "hi".
   *
   * @return array
   */
  public static function helloOrHi() {
    return [
      '#markup' => BigPipeMarkup::create('<marquee>llamas forever!</marquee>'),
      '#cache' => [
        'max-age' => 0,
        'tags' => ['cache_tag_set_in_lazy_builder'],
      ],
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

  /**
   * #lazy_builder callback; returns the current count.
   *
   * @see \Drupal\Tests\big_pipe\Functional\BigPipeTest::testBigPipeMultiOccurrencePlaceholders()
   *
   * @return array
   *   The render array.
   */
  public static function counter() {
    // Lazy builders are not allowed to build their own state like this function
    // does, but in this case we're intentionally doing that for testing
    // purposes: so we can ensure that each lazy builder is only ever called
    // once with the same parameters.
    static $count;

    if (!isset($count)) {
      $count = 0;
    }

    $count++;

    return [
      '#markup' => BigPipeMarkup::create("<p>The count is $count.</p>"),
      '#cache' => ['max-age' => 0],
    ];
  }

  /**
   * Route callback to test a trusted lazy builder redirect response.
   *
   * @return array
   *   The lazy builder callback.
   */
  public function trustedRedirectLazyBuilder(): array {
    return [
      'redirect' => [
        '#lazy_builder' => [static::class . '::redirectTrusted', []],
        '#create_placeholder' => TRUE,
      ],
    ];
  }

  /**
   * Supports Big Pipe testing of the enforced redirect response.
   *
   * @throws \Drupal\Core\Form\EnforcedResponseException
   *   Trigger catch of Big Pipe enforced redirect response exception.
   */
  public static function redirectTrusted(): void {
    $response = new RedirectResponse('/big_pipe_test');
    throw new EnforcedResponseException($response);
  }

  /**
   * Route callback to test an untrusted lazy builder redirect response.
   *
   * @return array
   *   The lazy builder callback.
   */
  public function untrustedRedirectLazyBuilder(): array {
    return [
      'redirect' => [
        '#lazy_builder' => [static::class . '::redirectUntrusted', []],
        '#create_placeholder' => TRUE,
      ],
    ];
  }

  /**
   * Supports Big Pipe testing of an untrusted external URL.
   *
   * @throws \Drupal\Core\Form\EnforcedResponseException
   *   Trigger catch of Big Pipe enforced redirect response exception.
   */
  public static function redirectUntrusted(): void {
    $response = new RedirectResponse('https://example.com');
    throw new EnforcedResponseException($response);
  }

  /**
   * {@inheritdoc}
   */
  public static function trustedCallbacks() {
    return ['currentTime', 'piggy', 'helloOrHi', 'exception', 'responseException', 'counter', 'redirectTrusted', 'redirectUntrusted'];
  }

}
