<?php

namespace Drupal\Core\EventSubscriber;

use Drupal\Core\Utility\Error;
use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;

/**
 * Custom handling of errors when in a system-under-test.
 */
class ExceptionTestSiteSubscriber extends HttpExceptionSubscriberBase {

  /**
   * {@inheritdoc}
   */
  protected static function getPriority() {
    return 3;
  }

  /**
   * {@inheritdoc}
   */
  protected function getHandledFormats() {
    return ['html'];
  }

  /**
   * Checks for special handling of errors inside Simpletest.
   *
   * @todo The $headers array appears to not actually get used at all in the
   *   original code. It's quite possible that this entire method is now
   *   vestigial and can be removed.
   *
   * @param \Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent $event
   */
  public function on500(GetResponseForExceptionEvent $event) {
    $exception = $event->getException();
    $error = Error::decodeException($exception);

    $headers = array();

    // When running inside the testing framework, we relay the errors
    // to the tested site by the way of HTTP headers.
    if (DRUPAL_TEST_IN_CHILD_SITE && !headers_sent() && (!defined('SIMPLETEST_COLLECT_ERRORS') || SIMPLETEST_COLLECT_ERRORS)) {
      // $number does not use drupal_static as it should not be reset
      // as it uniquely identifies each PHP error.
      static $number = 0;
      $assertion = array(
        $error['@message'],
        $error['%type'],
        array(
          'function' => $error['%function'],
          'file' => $error['%file'],
          'line' => $error['%line'],
        ),
      );
      $headers['X-Drupal-Assertion-' . $number] = rawurlencode(serialize($assertion));
      $number++;
    }
  }

}
