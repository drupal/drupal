<?php

namespace Drupal\Tests\Listeners;

use Drupal\Tests\Traits\ExpectDeprecationTrait;
use PHPUnit\Framework\TestCase;
use PHPUnit\Util\Test;

/**
 * Removes deprecations that we are yet to fix.
 *
 * @internal
 *   This class will be removed once all the deprecation notices have been
 *   fixed.
 */
trait DeprecationListenerTrait {

  use ExpectDeprecationTrait;

  /**
   * The previous error handler.
   *
   * @var callable
   */
  private $previousHandler;

  protected function deprecationStartTest($test) {
    if ($test instanceof TestCase) {
      if ('disabled' !== getenv('SYMFONY_DEPRECATIONS_HELPER')) {
        $this->registerErrorHandler($test);
      }
      if ($this->willBeIsolated($test)) {
        putenv('DRUPAL_EXPECTED_DEPRECATIONS_SERIALIZE=' . tempnam(sys_get_temp_dir(), 'exdep'));
      }
    }
  }

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
    if ($file = getenv('DRUPAL_EXPECTED_DEPRECATIONS_SERIALIZE')) {
      putenv('DRUPAL_EXPECTED_DEPRECATIONS_SERIALIZE');
      $expected_deprecations = file_get_contents($file);
      if ($expected_deprecations) {
        $test->expectedDeprecations(unserialize($expected_deprecations));
      }
    }
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
   * Determines if a test is isolated.
   *
   * @param \PHPUnit\Framework\TestCase $test
   *   The test to check.
   *
   * @return bool
   *   TRUE if the isolated, FALSE if not.
   */
  private function willBeIsolated($test) {
    if ($test->isInIsolation()) {
      return FALSE;
    }

    $r = new \ReflectionProperty($test, 'runTestInSeparateProcess');
    $r->setAccessible(TRUE);

    return $r->getValue($test);
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
      '%The "Symfony\\\\Component\\\\Routing\\\\(Compiled)?Route::(un)?serialize\(\)" method is considered (final|internal) since Symfony 4\.3\. It may change without further notice( as of its next major version)?\. You should not extend it from "[^"]+"\.%',
      '%The "Symfony\\\\Component\\\\Validator\\\\Context\\\\ExecutionContextInterface::.*\(\)" method is considered internal Used by the validator engine. Should not be called by user\s\*\s*code\. It may change without further notice\. You should not extend it from "[^"]+".%',
      '%Non-object services are deprecated since Symfony 4\.4, please fix the ".*" service which is of type ".*" right now\.%',
      '%Non-object services are deprecated since Symfony 4\.4, setting the ".*" service to a value of type ".*" should be avoided\.%',
      '%The ".*" service relies on the deprecated "Symfony\\\\Component\\\\Debug\\\\BufferingLogger" class\. It should either be deprecated or its implementation upgraded\.%',
      '%Method ".*::.*\(\)" will return ".*" as of its next major version\. Doing the same in child class ".*" will be required when upgrading\.%',
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
      'The Symfony\Component\ClassLoader\ApcClassLoader class is deprecated since Symfony 3.3 and will be removed in 4.0. Use `composer install --apcu-autoloader` instead.',
      // The following deprecation is not triggered by DrupalCI testing since it
      // is a Windows only deprecation. Remove when core no longer uses
      // WinCacheClassLoader in \Drupal\Core\DrupalKernel::initializeSettings().
      'The Symfony\Component\ClassLoader\WinCacheClassLoader class is deprecated since Symfony 3.3 and will be removed in 4.0. Use `composer install --apcu-autoloader` instead.',
      // The following deprecation message is skipped for testing purposes.
      '\Drupal\Tests\SkippedDeprecationTest deprecation',
      // These deprecations are triggered by symfony/psr-http-message-factory
      // 1.2, which can be installed if you update dependencies on php 7 or
      // higher.
      'The "Symfony\Bridge\PsrHttpMessage\Factory\DiactorosFactory" class is deprecated since symfony/psr-http-message-bridge 1.2, use PsrHttpFactory instead.',
      'The "psr7.http_message_factory" service relies on the deprecated "Symfony\Bridge\PsrHttpMessage\Factory\DiactorosFactory" class. It should either be deprecated or its implementation upgraded.',
      // This deprecation comes from behat/mink-browserkit-driver when updating
      // symfony/browser-kit to 4.3+.
      'The "Symfony\Component\BrowserKit\Response::getStatus()" method is deprecated since Symfony 4.3, use getStatusCode() instead.',
      // The following deprecations are introduced in by the new
      // DebugClassLoader in Symfony 4 we cannot immediately fix them without
      // breaking backwards compatibility.
      // @see https://www.drupal.org/project/drupal/issues/3030494
      // @see https://www.drupal.org/project/drupal/issues/3030474
      'The "Drupal\Core\Template\Loader\StringLoader" class implements "Twig\Loader\ExistsLoaderInterface" that is deprecated since 1.12 (to be removed in 3.0).',
      'The "Drupal\Core\Template\Loader\StringLoader" class implements "Twig\Loader\SourceContextLoaderInterface" that is deprecated since 1.27 (to be removed in 3.0).',
      // The following Symfony deprecations are introduced in the Symfony 4
      // development cycle. They will need to be resolved prior to Symfony 5
      // compatibility.
      'Support for mapping keys in multi-line blocks is deprecated since Symfony 4.3 and will throw a ParseException in 5.0.',
      'The "Symfony\Component\HttpFoundation\File\MimeType\MimeTypeGuesser" class is deprecated since Symfony 4.3, use "Symfony\Component\Mime\MimeTypes" instead.',
      'The "Drupal\Core\File\MimeType\MimeTypeGuesser" class implements "Symfony\Component\HttpFoundation\File\MimeType\MimeTypeGuesserInterface" that is deprecated since Symfony 4.3, use {@link MimeTypesInterface} instead.',
      'The "Symfony\Component\HttpFoundation\File\MimeType\FileBinaryMimeTypeGuesser" class is deprecated since Symfony 4.3, use "Symfony\Component\Mime\FileBinaryMimeTypeGuesser" instead.',
      'The "Symfony\Component\HttpFoundation\File\MimeType\FileinfoMimeTypeGuesser" class is deprecated since Symfony 4.3, use "Symfony\Component\Mime\FileinfoMimeTypeGuesser" instead.',
      'The signature of the "Drupal\Component\EventDispatcher\ContainerAwareEventDispatcher::dispatch()" method should be updated to "dispatch($event, string $eventName = null)", not doing so is deprecated since Symfony 4.3.',
      'Calling the "Symfony\Component\EventDispatcher\EventDispatcherInterface::dispatch()" method with the event name as the first argument is deprecated since Symfony 4.3, pass it as the second argument and provide the event object as the first argument instead.',
      'The "Drupal\Component\EventDispatcher\ContainerAwareEventDispatcher::dispatch()" method will require a new "string|null $eventName" argument in the next major version of its parent class "Symfony\Contracts\EventDispatcher\EventDispatcherInterface", not defining it is deprecated.',
      'The "Goutte\Client" class extends "Symfony\Component\BrowserKit\Client" that is deprecated since Symfony 4.3, use "\Symfony\Component\BrowserKit\AbstractBrowser" instead.',
      'Passing a command as string when creating a "Symfony\Component\Process\Process" instance is deprecated since Symfony 4.2, pass it as an array of its arguments instead, or use the "Process::fromShellCommandline()" constructor if you need features provided by the shell.',
      'Passing arguments to "Symfony\Component\HttpFoundation\Request::isMethodSafe()" has been deprecated since Symfony 4.4; use "Symfony\Component\HttpFoundation\Request::isMethodCacheable()" to check if the method is cacheable instead.',
      'The "Symfony\Component\Process\Process::inheritEnvironmentVariables()" method is deprecated since Symfony 4.4, env variables are always inherited.',
      'The "Symfony\Component\Debug\BufferingLogger" class is deprecated since Symfony 4.4, use "Symfony\Component\ErrorHandler\BufferingLogger" instead.',
      'Using the "Symfony\Component\Validator\Constraints\Length" constraint with the "min" option without setting the "allowEmptyString" one is deprecated and defaults to true. In 5.0, it will become optional and default to false.',
      'The "core/jquery.ui.checkboxradio" asset library is deprecated in drupal:8.8.0 and is removed from drupal:9.0.0. See https://www.drupal.org/node/3067969',
      'The "core/jquery.ui.controlgroup" asset library is deprecated in drupal:8.8.0 and is removed from drupal:9.0.0. See https://www.drupal.org/node/3067969',
      // The following deprecation is listed for Twig 2 compatibility when unit
      // testing using \Symfony\Component\ErrorHandler\DebugClassLoader.
      'The "Twig\Environment::getTemplateClass()" method is considered internal. It may change without further notice. You should not extend it from "Drupal\Core\Template\TwigEnvironment".',
      '"Symfony\Component\DomCrawler\Crawler::text()" will normalize whitespaces by default in Symfony 5.0, set the second "$normalizeWhitespace" argument to false to retrieve the non-normalized version of the text.',
    ];
  }

  /**
   * Registers an error handler that wraps Symfony's DeprecationErrorHandler.
   *
   * @see \Symfony\Bridge\PhpUnit\DeprecationErrorHandler
   * @see \Symfony\Bridge\PhpUnit\Legacy\SymfonyTestsListenerTrait
   */
  protected function registerErrorHandler($test) {
    $deprecation_handler = function ($type, $msg, $file, $line, $context = []) {
      // Skip listed deprecations.
      if ($type === E_USER_DEPRECATED && static::isDeprecationSkipped($msg)) {
        return;
      }
      return call_user_func($this->previousHandler, $type, $msg, $file, $line, $context);
    };

    if ($this->previousHandler) {
      set_error_handler($deprecation_handler);
      return;
    }
    $this->previousHandler = set_error_handler($deprecation_handler);

    // Register another listener so that we can remove the error handler before
    // Symfony's DeprecationErrorHandler checks that it is the currently
    // registered handler. Note this is done like this to ensure the error
    // handler is removed after SymfonyTestsListenerTrait::endTest() is called.
    // SymfonyTestsListenerTrait has its own error handler that needs to be
    // removed before this one.
    $test_result_object = $test->getTestResultObject();
    // It's possible that a test does not have a result object. This can happen
    // when a test class does not have any test methods.
    if ($test_result_object) {
      $reflection_class = new \ReflectionClass($test_result_object);
      $reflection_property = $reflection_class->getProperty('listeners');
      $reflection_property->setAccessible(TRUE);
      $listeners = $reflection_property->getValue($test_result_object);
      $listeners[] = new AfterSymfonyListener();
      $reflection_property->setValue($test_result_object, $listeners);
    }
  }

}
