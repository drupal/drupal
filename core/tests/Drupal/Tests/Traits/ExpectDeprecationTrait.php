<?php

namespace Drupal\Tests\Traits;

use Symfony\Bridge\PhpUnit\Legacy\SymfonyTestsListenerTrait;

/**
 * Adds the ability to dynamically set expected deprecation messages in tests.
 *
 * @internal
 *
 * @todo https://www.drupal.org/project/drupal/issues/3157434 Deprecate the
 *   trait and its methods.
 */
trait ExpectDeprecationTrait {

  /**
   * Sets an expected deprecation message.
   *
   * @param string $message
   *   The expected deprecation message.
   */
  protected function addExpectedDeprecationMessage($message) {
    $this->expectedDeprecations([$message]);
  }

  /**
   * Sets expected deprecation messages.
   *
   * @param string[] $messages
   *   The expected deprecation messages.
   *
   * @see \Symfony\Bridge\PhpUnit\Legacy\ExpectDeprecationTraitForV8_4::expectDeprecation()
   */
  public function expectedDeprecations(array $messages) {
    $this->getTestResultObject()->beStrictAboutTestsThatDoNotTestAnything(FALSE);

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
    }
    else {
      // Copy code from ExpectDeprecationTraitForV8_4::expectDeprecation().
      if (!SymfonyTestsListenerTrait::$previousErrorHandler) {
        SymfonyTestsListenerTrait::$previousErrorHandler = set_error_handler([SymfonyTestsListenerTrait::class, 'handleError']);
      }

      foreach ($messages as $message) {
        SymfonyTestsListenerTrait::$expectedDeprecations[] = $message;
      }
    }
  }

}
