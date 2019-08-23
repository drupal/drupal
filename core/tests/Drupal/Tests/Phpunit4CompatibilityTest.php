<?php

namespace Drupal\Tests;

/**
 * Tests existence of the PHPUnit4 backward compatibility classes.
 *
 * @group Tests
 */
class Phpunit4CompatibilityTest extends UnitTestCase {

  /**
   * Tests existence of \PHPUnit_Framework_AssertionFailedError.
   */
  public function testFrameworkAssertionFailedError() {
    $this->assertTrue(class_exists('\PHPUnit_Framework_AssertionFailedError'));
  }

  /**
   * Tests existence of \PHPUnit_Framework_Constraint_Count.
   */
  public function testFrameworkConstraintCount() {
    $this->assertTrue(class_exists('\PHPUnit_Framework_Constraint_Count'));
  }

  /**
   * Tests existence of \PHPUnit_Framework_Error.
   */
  public function testFrameworkError() {
    $this->assertTrue(class_exists('\PHPUnit_Framework_Error'));
  }

  /**
   * Tests existence of \PHPUnit_Framework_Error_Warning.
   */
  public function FrameworkErrorWarning() {
    $this->assertTrue(class_exists('\PHPUnit_Framework_Error_Warning'));
  }

  /**
   * Tests existence of \PHPUnit_Framework_Exception.
   */
  public function testFrameworkException() {
    $this->assertTrue(class_exists('\PHPUnit_Framework_Exception'));
  }

  /**
   * Tests existence of \PHPUnit_Framework_ExpectationFailedException.
   */
  public function testFrameworkExpectationFailedException() {
    $this->assertTrue(class_exists('\PHPUnit_Framework_ExpectationFailedException'));
  }

  /**
   * Tests existence of \PHPUnit_Framework_MockObject_Matcher_InvokedRecorder.
   */
  public function testFrameworkMockObjectMatcherInvokedRecorder() {
    $this->assertTrue(class_exists('\PHPUnit_Framework_MockObject_Matcher_InvokedRecorder'));
  }

  /**
   * Tests existence of \PHPUnit_Framework_SkippedTestError.
   */
  public function testFrameworkSkippedTestError() {
    $this->assertTrue(class_exists('\PHPUnit_Framework_SkippedTestError'));
  }

  /**
   * Tests existence of \PHPUnit_Framework_TestCase.
   */
  public function testFrameworkTestCase() {
    $this->assertTrue(class_exists('\PHPUnit_Framework_TestCase'));
  }

  /**
   * Tests existence of \PHPUnit_Util_Test.
   */
  public function testUtilTest() {
    $this->assertTrue(class_exists('\PHPUnit_Util_Test'));
  }

  /**
   * Tests existence of \PHPUnit_Util_XML.
   */
  public function testUtilXml() {
    $this->assertTrue(class_exists('\PHPUnit_Util_XML'));
  }

}
