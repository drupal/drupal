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
   * @see https://github.com/sebastianbergmann/phpunit/wiki/Release-Announcement-for-PHPUnit-5.4.0
   *
   * @return \PHPUnit\Framework\MockObject\MockObject
   *
   * @deprecated in drupal:8.5.0 and is removed from drupal:9.0.0.
   *   Use \Drupal\Tests\PhpunitCompatibilityTrait::createMock() instead.
   *
   * @see https://www.drupal.org/node/2907725
   */
  public function getMock($originalClassName, $methods = [], array $arguments = [], $mockClassName = '', $callOriginalConstructor = TRUE, $callOriginalClone = TRUE, $callAutoload = TRUE, $cloneArguments = FALSE, $callOriginalMethods = FALSE, $proxyTarget = NULL) {
    @trigger_error('\Drupal\Tests\PhpunitCompatibilityTrait::getMock() is deprecated in drupal:8.5.0 and is removed from drupal:9.0.0. Use \Drupal\Tests\PhpunitCompatibilityTrait::createMock() instead. See https://www.drupal.org/node/2907725', E_USER_DEPRECATED);
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

  /**
   * Compatibility layer for PHPUnit 6 to support PHPUnit 4 code.
   *
   * @param mixed $class
   *   The expected exception class.
   * @param string $message
   *   The expected exception message.
   * @param int $exception_code
   *   The expected exception code.
   *
   * @deprecated in drupal:8.8.0 and is removed from drupal:9.0.0.
   *   Backward compatibility for PHPUnit 4 will no longer be supported.
   *
   * @see https://www.drupal.org/node/3056869
   */
  public function setExpectedException($class, $message = '', $exception_code = NULL) {
    @trigger_error('\Drupal\Tests\PhpunitCompatibilityTrait:setExpectedException() is deprecated in drupal:8.8.0 and is removed from drupal:9.0.0. Backward compatibility for PHPUnit 4 will no longer be supported. See https://www.drupal.org/node/3056869', E_USER_DEPRECATED);
    $this->expectException($class);
    if (!empty($message)) {
      $this->expectExceptionMessage($message);
    }
    if ($exception_code !== NULL) {
      $this->expectExceptionCode($exception_code);
    }
  }

}
