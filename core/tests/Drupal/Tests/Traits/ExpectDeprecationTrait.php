<?php

namespace Drupal\Tests\Traits;

use Symfony\Bridge\PhpUnit\Legacy\SymfonyTestsListener as LegacySymfonyTestsListener;
use Symfony\Bridge\PhpUnit\SymfonyTestsListener;
use PHPUnit\Framework\TestCase;

/**
 * Adds the ability to dynamically set expected deprecation messages in tests.
 *
 * @internal
 *   This class should only be used by Drupal core and will be removed once
 *   https://github.com/symfony/symfony/pull/25757 is resolved.
 *
 * @todo Remove once https://github.com/symfony/symfony/pull/25757 is resolved.
 */
trait ExpectDeprecationTrait {

  /**
   * Sets an expected deprecation message.
   *
   * @param string $message
   *   The expected deprecation message.
   */
  protected function expectDeprecation($message) {
    $this->expectedDeprecations([$message]);
  }

  /**
   * Sets expected deprecation messages.
   *
   * @param string[] $messages
   *   The expected deprecation messages.
   */
  public function expectedDeprecations(array $messages) {
    if (class_exists('PHPUnit_Util_Test', FALSE)) {
      $test_util = 'PHPUnit_Util_Test';
      $assertion_failed_error = 'PHPUnit_Framework_AssertionFailedError';
    }
    else {
      $test_util = 'PHPUnit\Util\Test';
      $assertion_failed_error = 'PHPUnit\Framework\AssertionFailedError';
    }
    if ($this instanceof \PHPUnit_Framework_TestCase || $this instanceof TestCase) {
      // Ensure the class or method is in the legacy group.
      $groups = $test_util::getGroups(get_class($this), $this->getName(FALSE));
      if (!in_array('legacy', $groups, TRUE)) {
        throw new $assertion_failed_error('Only tests with the `@group legacy` annotation can call `setExpectedDeprecation()`.');
      }

      // If setting an expected deprecation there is no need to be strict about
      // testing nothing as this is an assertion.
      $this->getTestResultObject()
        ->beStrictAboutTestsThatDoNotTestAnything(FALSE);

      if ($trait = $this->getSymfonyTestListenerTrait()) {
        // Add the expected deprecation message to the class property.
        $reflection_class = new \ReflectionClass($trait);
        $expected_deprecations_property = $reflection_class->getProperty('expectedDeprecations');
        $expected_deprecations_property->setAccessible(TRUE);
        $expected_deprecations = $expected_deprecations_property->getValue($trait);
        $expected_deprecations = array_merge($expected_deprecations, $messages);
        $expected_deprecations_property->setValue($trait, $expected_deprecations);

        // Register the error handler if necessary.
        $previous_error_handler_property = $reflection_class->getProperty('previousErrorHandler');
        $previous_error_handler_property->setAccessible(TRUE);
        $previous_error_handler = $previous_error_handler_property->getValue($trait);
        if (!$previous_error_handler) {
          $previous_error_handler = set_error_handler([$trait, 'handleError']);
          $previous_error_handler_property->setValue($trait, $previous_error_handler);
        }
        return;
      }
    }

    // Expected deprecations set by isolated tests need to be written to a file
    // so that the test running process can take account of them.
    if ($file = getenv('DRUPAL_EXPECTED_DEPRECATIONS_SERIALIZE')) {
      $expected_deprecations = file_get_contents($file);
      if ($expected_deprecations) {
        $expected_deprecations = array_merge(unserialize($expected_deprecations), $messages);
      }
      else {
        $expected_deprecations = $messages;
      }
      file_put_contents($file, serialize($expected_deprecations));
      return;
    }

    throw new $assertion_failed_error('Can not set an expected deprecation message because the Symfony\Bridge\PhpUnit\SymfonyTestsListener is not registered as a PHPUnit test listener.');
  }

  /**
   * Gets the SymfonyTestsListenerTrait.
   *
   * @return \Symfony\Bridge\PhpUnit\Legacy\SymfonyTestsListenerTrait|null
   *   The SymfonyTestsListenerTrait object or NULL is a Symfony test listener
   *   is not present.
   */
  private function getSymfonyTestListenerTrait() {
    $test_result_object = $this->getTestResultObject();
    $reflection_class = new \ReflectionClass($test_result_object);
    $reflection_property = $reflection_class->getProperty('listeners');
    $reflection_property->setAccessible(TRUE);
    $listeners = $reflection_property->getValue($test_result_object);
    foreach ($listeners as $listener) {
      if ($listener instanceof SymfonyTestsListener || $listener instanceof LegacySymfonyTestsListener) {
        $reflection_class = new \ReflectionClass($listener);
        $reflection_property = $reflection_class->getProperty('trait');
        $reflection_property->setAccessible(TRUE);
        return $reflection_property->getValue($listener);
      }
    }
  }

}
