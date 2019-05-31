<?php

namespace Drupal\Tests;

/**
 * Makes Drupal's test API forward compatible with multiple versions of PHPUnit.
 */
trait PhpunitCompatibilityTrait {

  /**
   * Returns a mock object for the specified class using the available method.
   *
   * The getMock method does not exist in PHPUnit 6. To provide backward
   * compatibility this trait provides the getMock method and uses createMock if
   * this method is available on the parent class.
   *
   * @param string $originalClassName
   *   Name of the class to mock.
   * @param array|null $methods
   *   When provided, only methods whose names are in the array are replaced
   *   with a configurable test double. The behavior of the other methods is not
   *   changed. Providing null means that no methods will be replaced.
   * @param array $arguments
   *   Parameters to pass to the original class' constructor.
   * @param string $mockClassName
   *   Class name for the generated test double class.
   * @param bool $callOriginalConstructor
   *   Can be used to disable the call to the original class' constructor.
   * @param bool $callOriginalClone
   *   Can be used to disable the call to the original class' clone constructor.
   * @param bool $callAutoload
   *   Can be used to disable __autoload() during the generation of the test
   *   double class.
   * @param bool $cloneArguments
   *   Enables the cloning of arguments passed to mocked methods.
   * @param bool $callOriginalMethods
   *   Enables the invocation of the original methods.
   * @param object $proxyTarget
   *   Sets the proxy target.
   *
   * @see \PHPUnit_Framework_TestCase::getMock
   * @see https://github.com/sebastianbergmann/phpunit/wiki/Release-Announcement-for-PHPUnit-5.4.0
   *
   * @return \PHPUnit_Framework_MockObject_MockObject
   *
   * @deprecated in drupal:8.5.0 and is removed from drupal:9.0.0.
   *   Use \Drupal\Tests\PhpunitCompatibilityTrait::createMock() instead.
   *
   * @see https://www.drupal.org/node/2907725
   */
  public function getMock($originalClassName, $methods = [], array $arguments = [], $mockClassName = '', $callOriginalConstructor = TRUE, $callOriginalClone = TRUE, $callAutoload = TRUE, $cloneArguments = FALSE, $callOriginalMethods = FALSE, $proxyTarget = NULL) {
    @trigger_error('\Drupal\Tests\PhpunitCompatibilityTrait::getMock() is deprecated in drupal:8.5.0 and is removed from drupal:9.0.0. Use \Drupal\Tests\PhpunitCompatibilityTrait::createMock() instead. See https://www.drupal.org/node/2907725', E_USER_DEPRECATED);
    if (!$this->supports('getMock')) {
      $mock = $this->getMockBuilder($originalClassName)
        ->setMethods($methods)
        ->setConstructorArgs($arguments)
        ->setMockClassName($mockClassName)
        ->setProxyTarget($proxyTarget);
      if ($callOriginalConstructor) {
        $mock->enableOriginalConstructor();
      }
      else {
        $mock->disableOriginalConstructor();
      }
      if ($callOriginalClone) {
        $mock->enableOriginalClone();
      }
      else {
        $mock->disableOriginalClone();
      }
      if ($callAutoload) {
        $mock->enableAutoload();
      }
      else {
        $mock->disableAutoload();
      }
      if ($cloneArguments) {
        $mock->enableArgumentCloning();
      }
      else {
        $mock->disableArgumentCloning();
      }
      if ($callOriginalMethods) {
        $mock->enableProxyingToOriginalMethods();
      }
      else {
        $mock->disableProxyingToOriginalMethods();
      }
      return $mock->getMock();
    }
    else {
      return parent::getMock($originalClassName, $methods, $arguments, $mockClassName, $callOriginalConstructor, $callOriginalClone, $callAutoload, $cloneArguments, $callOriginalMethods, $proxyTarget);
    }
  }

  /**
   * Returns a mock object for the specified class using the available method.
   *
   * The createMock method does not exist in PHPUnit 4. To provide forward
   * compatibility this trait provides the createMock method and uses createMock
   * if this method is available on the parent class or falls back to getMock if
   * it isn't.
   *
   * @param string $originalClassName
   *   Name of the class to mock.
   *
   * @see \PHPUnit_Framework_TestCase::getMock
   *
   * @return \PHPUnit_Framework_MockObject_MockObject
   */
  public function createMock($originalClassName) {
    if ($this->supports('createMock')) {
      return parent::createMock($originalClassName);
    }
    else {
      return $this->getMock($originalClassName, [], [], '', FALSE, FALSE);
    }
  }

  /**
   * Compatibility layer for PHPUnit 6 to support PHPUnit 4 code.
   *
   * @param mixed $class
   *   The expected exception class.
   * @param string $message
   *   The expected exception message.
   * @param int $exception_code
   *   The expected exception code.
   */
  public function setExpectedException($class, $message = '', $exception_code = NULL) {
    if (method_exists($this, 'expectException')) {
      $this->expectException($class);
      if (!empty($message)) {
        $this->expectExceptionMessage($message);
      }
      if ($exception_code !== NULL) {
        $this->expectExceptionCode($exception_code);
      }
    }
    else {
      parent::setExpectedException($class, $message, $exception_code);
    }
  }

  /**
   * Checks if the trait is used in a class that has a method.
   *
   * @param string $method
   *   Method to check.
   *
   * @return bool
   *   TRUE if the method is supported, FALSE if not.
   */
  private function supports($method) {
    // Get the parent class of the currently running test class.
    $parent = get_parent_class($this);
    // Ensure that the method_exists() check on the createMock method is carried
    // out on the first parent of $this that does not have access to this
    // trait's methods. This is because the trait also has a method called
    // createMock(). Most often the check will be made on
    // \PHPUnit\Framework\TestCase.
    while (method_exists($parent, 'supports')) {
      $parent = get_parent_class($parent);
    }
    return method_exists($parent, $method);
  }

}
