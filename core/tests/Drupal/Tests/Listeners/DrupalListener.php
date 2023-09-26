<?php

namespace Drupal\Tests\Listeners;

use PHPUnit\Framework\TestListener;
use PHPUnit\Framework\TestListenerDefaultImplementation;
use PHPUnit\Framework\Test;
use PHPUnit\Framework\TestSuite;
use Symfony\Bridge\PhpUnit\SymfonyTestsListener;

/**
 * Listens to PHPUnit test runs.
 *
 * @internal
 */
class DrupalListener implements TestListener {

  use TestListenerDefaultImplementation;
  use DrupalComponentTestListenerTrait;

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
    $this->symfonyListener->startTest($test);
    // Check for incorrect visibility of the $modules property.
    $class = new \ReflectionClass($test);
    if ($class->hasProperty('modules') && !$class->getProperty('modules')->isProtected()) {
      @trigger_error('Declaring ' . get_class($test) . '::$modules with public visibility is deprecated in drupal:9.1.0 and must be declared protected in drupal:10.0.0. See https://www.drupal.org/node/2909426', E_USER_DEPRECATED);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function endTest(Test $test, float $time): void {
    $this->symfonyListener->endTest($test, $time);
    $this->componentEndTest($test, $time);
  }

}
