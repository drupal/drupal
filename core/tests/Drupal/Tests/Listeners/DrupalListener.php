<?php

namespace Drupal\Tests\Listeners;

use PHPUnit\Framework\TestListener;
use PHPUnit\Framework\TestListenerDefaultImplementation;
use PHPUnit\Framework\Test;

/**
 * Listens to PHPUnit test runs.
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
   * {@inheritdoc}
   */
  public function startTest(Test $test): void {
    $this->deprecationStartTest($test);

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
  }

  /**
   * {@inheritdoc}
   */
  public function endTest(Test $test, float $time): void {
    $this->deprecationEndTest($test, $time);
    $this->componentEndTest($test, $time);
    $this->standardsEndTest($test, $time);
  }

}
