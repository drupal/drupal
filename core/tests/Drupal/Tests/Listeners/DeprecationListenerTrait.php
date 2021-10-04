<?php

namespace Drupal\Tests\Listeners;

use PHPUnit\Util\Test;

/**
 * Removes deprecations that we are yet to fix.
 *
 * @internal
 *   This class will be removed once all the deprecation notices have been
 *   fixed.
 */
trait DeprecationListenerTrait {

  /**
   * The previous error handler.
   *
   * @var callable
   */
  private $previousHandler;

  /**
   * Reacts to the end of a test.
   *
   * @param \PHPUnit\Framework\Test $test
   *   The test object that has ended its test run.
   * @param float $time
   *   The time the test took.
   */
  protected function deprecationEndTest($test, $time) {
    /** @var \PHPUnit\Framework\Test $test */
    if ($file = getenv('SYMFONY_DEPRECATIONS_SERIALIZE')) {
      $method = $test->getName(FALSE);
      if (strpos($method, 'testLegacy') === 0
        || strpos($method, 'provideLegacy') === 0
        || strpos($method, 'getLegacy') === 0
        || strpos(get_class($test), '\Legacy')
        || in_array('legacy', Test::getGroups(get_class($test), $method), TRUE)) {
        // This is a legacy test don't skip deprecations.
        return;
      }

      // Need to edit the file of deprecations to remove any skipped
      // deprecations.
      $deprecations = file_get_contents($file);
      $deprecations = $deprecations ? unserialize($deprecations) : [];
      $resave = FALSE;
      foreach ($deprecations as $key => $deprecation) {
        if (static::isDeprecationSkipped($deprecation[1])) {
          unset($deprecations[$key]);
          $resave = TRUE;
        }
      }
      if ($resave) {
        file_put_contents($file, serialize($deprecations));
      }
    }
  }

  /**
   * Determines if a deprecation error should be skipped.
   *
   * @return bool
   *   TRUE if the deprecation error should be skipped, FALSE if not.
   */
  public static function isDeprecationSkipped($message) {
    if (in_array($message, static::getSkippedDeprecations(), TRUE)) {
      return TRUE;
    }
    $dynamic_skipped_deprecations = [
      '%The "[^"]+" class extends "Symfony\\\\Component\\\\EventDispatcher\\\\Event" that is deprecated since Symfony 4\.3, use "Symfony\\\\Contracts\\\\EventDispatcher\\\\Event" instead\.$%',
      '%The "Symfony\\\\Component\\\\Validator\\\\Context\\\\ExecutionContextInterface::.*\(\)" method is considered internal Used by the validator engine. Should not be called by user\s\*\s*code\. It may change without further notice\. You should not extend it from "[^"]+".%',
      '%The "PHPUnit\\\\Framework\\\\TestCase::addWarning\(\)" method is considered internal%',
      // The following deprecations were not added as part of the original
      // issues and thus were not addressed in time for the 9.0.0 release.
      '%The entity link url update for the "\w+" view is deprecated in drupal:9.0.0 and is removed from drupal:10.0.0. Module-provided Views configuration should be updated to accommodate the changes described at https://www.drupal.org/node/2857891.%',
      '%The operator defaults update for the "\w+" view is deprecated in drupal:9.0.0 and is removed from drupal:10.0.0. Module-provided Views configuration should be updated to accommodate the changes described at https://www.drupal.org/node/2869168.%',
      // Guzzle 6 will not be updated for full PHP 8.1 compatibility, see
      // https://github.com/guzzle/guzzle/pull/2918.
      '%Return type of GuzzleHttp\\\\.* should either be compatible with .*, or the #\[\\\\ReturnTypeWillChange\] attribute should be used to temporarily suppress the notice%',
      // Skip EasyRdf deprecations for PHP 8.1 - fixed by
      // https://github.com/easyrdf/easyrdf/pull/384.
      '%Return type of EasyRdf\\\\.* should either be compatible with .*, or the #\[\\\\ReturnTypeWillChange\] attribute should be used to temporarily suppress the notice%',
    ];
    return (bool) preg_filter($dynamic_skipped_deprecations, '$0', $message);
  }

  /**
   * A list of deprecations to ignore whilst fixes are put in place.
   *
   * Do not add any new deprecations to this list. All deprecation errors will
   * eventually be removed from this list.
   *
   * @return string[]
   *   A list of deprecations to ignore.
   *
   * @internal
   *
   * @todo Fix all these deprecations and remove them from this list.
   *   https://www.drupal.org/project/drupal/issues/2959269
   *
   * @see https://www.drupal.org/node/2811561
   */
  public static function getSkippedDeprecations() {
    return [
      // The following deprecation messages are skipped for testing purposes.
      '\Drupal\Tests\SkippedDeprecationTest deprecation',
      'Return type of PhpDeprecation::getIterator() should either be compatible with IteratorAggregate::getIterator(): Traversable, or the #[\ReturnTypeWillChange] attribute should be used to temporarily suppress the notice',
      // The following Symfony deprecations are introduced in the Symfony 4
      // development cycle. They will need to be resolved prior to Symfony 5
      // compatibility.
      'The "Symfony\Component\HttpFoundation\File\MimeType\MimeTypeGuesser" class is deprecated since Symfony 4.3, use "Symfony\Component\Mime\MimeTypes" instead.',
      'The "Drupal\Core\File\MimeType\MimeTypeGuesser" class implements "Symfony\Component\HttpFoundation\File\MimeType\MimeTypeGuesserInterface" that is deprecated since Symfony 4.3, use {@link MimeTypesInterface} instead.',
      'The "Symfony\Component\HttpFoundation\File\MimeType\FileBinaryMimeTypeGuesser" class is deprecated since Symfony 4.3, use "Symfony\Component\Mime\FileBinaryMimeTypeGuesser" instead.',
      'The "Symfony\Component\HttpFoundation\File\MimeType\FileinfoMimeTypeGuesser" class is deprecated since Symfony 4.3, use "Symfony\Component\Mime\FileinfoMimeTypeGuesser" instead.',
      'The "Drupal\Tests\Core\DependencyInjection\Compiler\LegacyMimeTypeGuesser" class implements "Symfony\Component\HttpFoundation\File\MimeType\MimeTypeGuesserInterface" that is deprecated since Symfony 4.3, use {@link MimeTypesInterface} instead.',
      'The "Drupal\Component\EventDispatcher\ContainerAwareEventDispatcher::dispatch()" method will require a new "string|null $eventName" argument in the next major version of its interface "Symfony\Contracts\EventDispatcher\EventDispatcherInterface", not defining it is deprecated.',
      'The "Drupal\Component\EventDispatcher\ContainerAwareEventDispatcher::dispatch()" method will require a new "string|null $eventName" argument in the next major version of its parent class "Symfony\Contracts\EventDispatcher\EventDispatcherInterface", not defining it is deprecated.',
      // The following deprecation is listed for Twig 2 compatibility when unit
      // testing using \Symfony\Component\ErrorHandler\DebugClassLoader.
      'The "Twig\Environment::getTemplateClass()" method is considered internal. It may change without further notice. You should not extend it from "Drupal\Core\Template\TwigEnvironment".',
      '"Symfony\Component\DomCrawler\Crawler::text()" will normalize whitespaces by default in Symfony 5.0, set the second "$normalizeWhitespace" argument to false to retrieve the non-normalized version of the text.',
      // PHPUnit 8.
      "The \"PHPUnit\TextUI\ResultPrinter\" class is considered internal This class is not covered by the backward compatibility promise for PHPUnit. It may change without further notice. You should not use it from \"Drupal\Tests\Listeners\HtmlOutputPrinter\".",
      "The \"Drupal\Tests\Listeners\DrupalListener\" class implements \"PHPUnit\Framework\TestListener\" that is deprecated Use the `TestHook` interfaces instead.",
      "The \"Drupal\Tests\Listeners\DrupalListener\" class uses \"PHPUnit\Framework\TestListenerDefaultImplementation\" that is deprecated The `TestListener` interface is deprecated.",
      "The \"PHPUnit\Framework\TestSuite\" class is considered internal This class is not covered by the backward compatibility promise for PHPUnit. It may change without further notice. You should not use it from \"Drupal\Tests\TestSuites\TestSuiteBase\".",
      // PHPUnit 9.
      "The \"PHPUnit\TextUI\DefaultResultPrinter\" class is considered internal This class is not covered by the backward compatibility promise for PHPUnit. It may change without further notice. You should not use it from \"Drupal\Tests\Listeners\HtmlOutputPrinter\".",
    ];
  }

  /**
   * Registers an error handler that wraps Symfony's DeprecationErrorHandler.
   *
   * @see \Symfony\Bridge\PhpUnit\DeprecationErrorHandler
   * @see \Symfony\Bridge\PhpUnit\Legacy\SymfonyTestsListenerTrait
   */
  protected function registerErrorHandler() {
    if ($this->previousHandler || 'disabled' === getenv('SYMFONY_DEPRECATIONS_HELPER')) {
      return;
    }
    $deprecation_handler = function ($type, $msg, $file, $line, $context = []) {
      // Skip listed deprecations.
      if (($type === E_USER_DEPRECATED || $type === E_DEPRECATED) && static::isDeprecationSkipped($msg)) {
        return;
      }
      return call_user_func($this->previousHandler, $type, $msg, $file, $line, $context);
    };

    $this->previousHandler = set_error_handler($deprecation_handler);
  }

  /**
   * Removes the error handler if registered.
   *
   * @see \Drupal\Tests\Listeners\DeprecationListenerTrait::registerErrorHandler()
   */
  protected function removeErrorHandler(): void {
    if ($this->previousHandler) {
      $this->previousHandler = NULL;
      restore_error_handler();
    }
  }

}
