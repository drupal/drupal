<?php

namespace Drupal\Tests\Listeners;

use PHPUnit\Framework\TestListener;
use PHPUnit\Framework\TestListenerDefaultImplementation;
use PHPUnit\Framework\Test;
use PHPUnit\Framework\TestSuite;
use PHPUnit\Util\Test as UtilTest;
use Symfony\Bridge\PhpUnit\Legacy\SymfonyTestsListenerTrait;
use Symfony\Bridge\PhpUnit\SymfonyTestsListener;

/**
 * Listens to PHPUnit test runs.
 *
 * This listener orchestrates error handlers to ensure deprecations are skipped
 * when \Drupal\Tests\Listeners\DeprecationListenerTrait::isDeprecationSkipped()
 * returns TRUE. It removes test listeners to ensure that when
 * \Symfony\Bridge\PhpUnit\DeprecationErrorHandler::shutdown() is run the error
 * handler is in the expected state.
 *
 * @internal
 */
class DrupalListener implements TestListener {

  use TestListenerDefaultImplementation;
  use DeprecationListenerTrait;
  use DrupalComponentTestListenerTrait;
  use DrupalStandardsListenerTrait;

  /**
   * A list of methods to be checked for void return typehint.
   *
   * @var string[]
   */
  protected $methodsWithVoidReturn = [
    'setUpBeforeClass',
    'setUp',
    'assertPreConditions',
    'assertPostConditions',
    'tearDown',
    'tearDownAfterClass',
    'onNotSuccessfulTest',
  ];

  /**
   * The wrapped Symfony test listener.
   *
   * @var \Symfony\Bridge\PhpUnit\SymfonyTestsListener
   */
  private $symfonyListener;

  /**
   * Constructs the DrupalListener object.
   */
  public function __construct() {
    $this->symfonyListener = new SymfonyTestsListener();
  }

  /**
   * {@inheritdoc}
   */
  public function startTestSuite(TestSuite $suite): void {
    $this->symfonyListener->startTestSuite($suite);
    $this->registerErrorHandler();
  }

  /**
   * {@inheritdoc}
   */
  public function addSkippedTest(Test $test, \Throwable $t, float $time): void {
    $this->symfonyListener->addSkippedTest($test, $t, $time);
  }

  /**
   * {@inheritdoc}
   */
  public function startTest(Test $test): void {
    // Check for deprecated @expectedDeprecation annotations before the
    // Symfony error handler has a chance to swallow this deprecation notice.
    $annotations = UtilTest::parseTestMethodAnnotations(get_class($test), $test->getName(FALSE));
    if (isset($annotations['method']['expectedDeprecation'])) {
      @trigger_error('The @expectedDeprecation annotation on ' . get_class($test) . '::' . $test->getName(FALSE) . '() is deprecated in drupal:9.1.0 and is removed from drupal:10.0.0. Use the expectDeprecation() method instead. See https://www.drupal.org/node/3176667', E_USER_DEPRECATED);
    }
    // The Drupal error handler has to be registered prior to the Symfony error
    // handler that is registered in
    // \Symfony\Bridge\PhpUnit\Legacy\SymfonyTestsListenerTrait::startTest()
    // that handles expected deprecations.
    $this->registerErrorHandler();
    $this->symfonyListener->startTest($test);
    // Check for missing void return typehints in concrete test classes'
    // methods. If the method is inherited from a base test class, do
    // nothing.
    $class = new \ReflectionClass($test);
    foreach ($this->methodsWithVoidReturn as $method) {
      if ($class->hasMethod($method)) {
        $reflected_method = $class->getMethod($method);
        if ($reflected_method->getDeclaringClass()->getName() === get_class($test)) {
          if (!$reflected_method->hasReturnType() || $reflected_method->getReturnType()->getName() !== 'void') {
            @trigger_error("Declaring ::$method without a void return typehint in " . get_class($test) . " is deprecated in drupal:9.0.0. Typehinting will be required before drupal:10.0.0. See https://www.drupal.org/node/3114724", E_USER_DEPRECATED);
          }
        }
      }
    }
    // Check for incorrect visibility of the $modules property.
    if ($class->hasProperty('modules') && !$class->getProperty('modules')->isProtected()) {
      @trigger_error('The ' . get_class($test) . '::$modules property must be declared protected. See https://www.drupal.org/node/2909426', E_USER_DEPRECATED);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function endTest(Test $test, float $time): void {
    if (!SymfonyTestsListenerTrait::$previousErrorHandler) {
      $className = get_class($test);
      $groups = UtilTest::getGroups($className, $test->getName(FALSE));
      if (in_array('legacy', $groups, TRUE)) {
        // If the Symfony listener is not registered for legacy tests then
        // deprecations triggered by the DebugClassloader in
        // \Symfony\Bridge\PhpUnit\Legacy\SymfonyTestsListenerTrait::endTest()
        // are not correctly identified as occurring in legacy tests.
        $symfony_error_handler = set_error_handler([SymfonyTestsListenerTrait::class, 'handleError']);
      }
    }
    $this->deprecationEndTest($test, $time);
    $this->symfonyListener->endTest($test, $time);
    $this->componentEndTest($test, $time);
    $this->standardsEndTest($test, $time);
    if (isset($symfony_error_handler)) {
      // If this test listener has added the Symfony error handler then it needs
      // to be removed.
      restore_error_handler();
    }
    // The Drupal error handler has to be removed after the Symfony error
    // handler is potentially removed in
    // \Symfony\Bridge\PhpUnit\Legacy\SymfonyTestsListenerTrait::endTest().
    $this->removeErrorHandler();
  }

}
