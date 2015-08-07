<?php

/**
 * @file
 * Contains \Drupal\Tests\simpletest\Unit\TestBaseTest.
 */

namespace Drupal\Tests\simpletest\Unit;

use Drupal\Tests\UnitTestCase;

/**
 * @coversDefaultClass \Drupal\simpletest\TestBase
 * @group simpletest
 */
class TestBaseTest extends UnitTestCase {

  /**
   * Helper method for constructing a mock TestBase object.
   *
   * TestBase is abstract, so we have to mock it. We'll also
   * mock the storeAssertion() method so we don't need the database.
   *
   * @param string $test_id
   *   An identifying name for the mocked test.
   *
   * @return object
   *   Mock of Drupal\simpletest\TestBase.
   */
  public function getTestBaseForAssertionTests($test_id) {
    $mock_test_base = $this->getMockBuilder('Drupal\simpletest\TestBase')
        ->setConstructorArgs(array($test_id))
        ->setMethods(array('storeAssertion'))
        ->getMockForAbstractClass();
    // Override storeAssertion() so we don't need a database.
    $mock_test_base->expects($this->any())
        ->method('storeAssertion')
        ->will($this->returnValue(NULL));
    return $mock_test_base;
  }

  /**
   * Invoke methods that are protected or private.
   *
   * @param object $object
   *   Object on which to invoke the method.
   * @param string $method_name
   *   Name of the method to invoke.
   * @param array $arguments
   *   Array of arguments to be passed to the method.
   *
   * @return mixed
   *   Value returned by the invoked method.
   */
  public function invokeProtectedMethod($object, $method_name, array $arguments) {
    $ref_method = new \ReflectionMethod($object, $method_name);
    $ref_method->setAccessible(TRUE);
    return $ref_method->invokeArgs($object, $arguments);
  }

  /**
   * Provides data for the random string validation test.
   *
   * @return array
   *   - The expected result of the validation.
   *   - The string to validate.
   */
  public function providerRandomStringValidate() {
    return array(
      array(FALSE, ' curry paste'),
      array(FALSE, 'curry paste '),
      array(FALSE, 'curry  paste'),
      array(FALSE, 'curry   paste'),
      array(TRUE, 'curry paste'),
      array(TRUE, 'thai green curry paste'),
      array(TRUE, '@startswithat'),
      array(TRUE, 'contains@at'),
    );
  }

  /**
   * @covers ::randomStringValidate
   * @dataProvider providerRandomStringValidate
   */
  public function testRandomStringValidate($expected, $string) {
    $mock_test_base = $this->getMockForAbstractClass('Drupal\simpletest\TestBase');
    $actual = $mock_test_base->randomStringValidate($string);
    $this->assertEquals($expected, $actual);
  }

  /**
   * Provides data for testRandomString() and others.
   *
   * @return array
   *   - The number of items (characters, object properties) we expect any of
   *     the random functions to give us.
   */
  public function providerRandomItems() {
    return [
      [NULL],
      [0],
      [1],
      [2],
      [3],
      [4],
      [7],
    ];
  }

  /**
   * @covers ::randomString
   * @dataProvider providerRandomItems
   */
  public function testRandomString($length) {
    $mock_test_base = $this->getMockForAbstractClass('Drupal\simpletest\TestBase');
    $string = $mock_test_base->randomString($length);
    $this->assertEquals($length, strlen($string));
    // randomString() should always include an ampersand ('&')  and a
    // greater than ('>') if $length is greater than 3.
    if ($length > 3) {
      $this->assertContains('&', $string);
      $this->assertContains('>', $string);
    }
  }

  /**
   * @covers ::randomObject
   * @dataProvider providerRandomItems
   */
  public function testRandomObject($size) {
    $test_base = $this->getTestBaseForAssertionTests('test_id');
    // Note: count((array)object) works for now, maybe not later.
    $this->assertEquals($size, count((array) $test_base->randomObject($size)));
  }

  /**
   * @covers ::checkRequirements
   */
  public function testCheckRequirements() {
    $test_base = $this->getTestBaseForAssertionTests('test_id');
    $this->assertInternalType(
        'array',
        $this->invokeProtectedMethod($test_base, 'checkRequirements', array())
    );
  }

  /**
   * Data provider for testAssert().
   *
   * @return array
   *   Standard dataProvider array of arrays:
   *   - Expected result from assert().
   *   - Expected status stored in TestBase->assertions.
   *   - Status, passed to assert().
   *   - Message, passed to assert().
   *   - Group, passed to assert().
   *   - Caller, passed to assert().
   */
  public function providerAssert() {
    return array(
      array(TRUE, 'pass', TRUE, 'Yay pass', 'test', array()),
      array(FALSE, 'fail', FALSE, 'Boo fail', 'test', array()),
      array(TRUE, 'pass', 'pass', 'Yay pass', 'test', array()),
      array(FALSE, 'fail', 'fail', 'Boo fail', 'test', array()),
      array(FALSE, 'exception', 'exception', 'Boo fail', 'test', array()),
      array(FALSE, 'debug', 'debug', 'Boo fail', 'test', array()),
    );
  }

  /**
   * @covers ::assert
   * @dataProvider providerAssert
   */
  public function testAssert($expected, $assertion_status, $status, $message, $group, $caller) {
    $test_id = 'luke_i_am_your_' . $assertion_status;
    $test_base = $this->getTestBaseForAssertionTests($test_id);

    // Verify some startup values.
    $this->assertAttributeEmpty('assertions', $test_base);
    if (is_string($status)) {
      $this->assertEquals(0, $test_base->results['#' . $status]);
    }

    // assert() is protected so we have to make it accessible.
    $ref_assert = new \ReflectionMethod($test_base, 'assert');
    $ref_assert->setAccessible(TRUE);

    // Call assert() from within our hall of mirrors.
    $this->assertEquals(
        $expected,
        $ref_assert->invokeArgs($test_base,
          array($status, $message, $group, $caller)
        )
    );

    // Check the side-effects of assert().
    if (is_string($status)) {
      $this->assertEquals(1, $test_base->results['#' . $status]);
    }
    $this->assertAttributeNotEmpty('assertions', $test_base);
    // Make a ReflectionProperty for the assertions property,
    // since it's protected.
    $ref_assertions = new \ReflectionProperty($test_base, 'assertions');
    $ref_assertions->setAccessible(TRUE);
    $assertions = $ref_assertions->getValue($test_base);
    $assertion = reset($assertions);
    $this->assertEquals($assertion_status, $assertion['status']);
    $this->assertEquals($test_id, $assertion['test_id']);
    $this->assertEquals(get_class($test_base), $assertion['test_class']);
    $this->assertEquals($message, $assertion['message']);
    $this->assertEquals($group, $assertion['message_group']);
  }

  /**
   * Data provider for assertTrue().
   */
  public function providerAssertTrue() {
    return array(
      array(TRUE, TRUE),
      array(FALSE, FALSE),
    );
  }

  /**
   * @covers ::assertTrue
   * @dataProvider providerAssertTrue
   */
  public function testAssertTrue($expected, $value) {
    $test_base = $this->getTestBaseForAssertionTests('test_id');
    $this->assertEquals(
        $expected,
        $this->invokeProtectedMethod($test_base, 'assertTrue', array($value))
    );
  }

  /**
   * @covers ::assertFalse
   * @dataProvider providerAssertTrue
   */
  public function testAssertFalse($expected, $value) {
    $test_base = $this->getTestBaseForAssertionTests('test_id');
    $this->assertEquals(
        (!$expected),
        $this->invokeProtectedMethod($test_base, 'assertFalse', array($value))
    );
  }

  /**
   * Data provider for assertNull().
   */
  public function providerAssertNull() {
    return array(
      array(TRUE, NULL),
      array(FALSE, ''),
    );
  }

  /**
   * @covers ::assertNull
   * @dataProvider providerAssertNull
   */
  public function testAssertNull($expected, $value) {
    $test_base = $this->getTestBaseForAssertionTests('test_id');
    $this->assertEquals(
        $expected,
        $this->invokeProtectedMethod($test_base, 'assertNull', array($value))
    );
  }

  /**
   * @covers ::assertNotNull
   * @dataProvider providerAssertNull
   */
  public function testAssertNotNull($expected, $value) {
    $test_base = $this->getTestBaseForAssertionTests('test_id');
    $this->assertEquals(
        (!$expected),
        $this->invokeProtectedMethod($test_base, 'assertNotNull', array($value))
    );
  }

  /**
   * Data provider for tests of equality assertions.
   *
   * Used by testAssertIdentical(), testAssertEqual(), testAssertNotIdentical(),
   * and testAssertNotEqual().
   *
   * @return
   *   Array of test data.
   *   - Expected assertion value for identical comparison.
   *   - Expected assertion value for equal comparison.
   *   - First value to compare.
   *   - Second value to compare.
   */
  public function providerEqualityAssertions() {
    return [
      // Integers and floats.
      [TRUE, TRUE, 0, 0],
      [FALSE, TRUE, 0, 0.0],
      [FALSE, TRUE, '0', 0],
      [FALSE, TRUE, '0.0', 0.0],
      [FALSE, FALSE, 23, 77],
      [TRUE, TRUE, 23.0, 23.0],
      // Strings.
      [FALSE, FALSE, 'foof', 'yay'],
      [TRUE, TRUE, 'yay', 'yay'],
      // Bools with type conversion.
      [TRUE, TRUE, TRUE, TRUE],
      [TRUE, TRUE, FALSE, FALSE],
      [FALSE, TRUE, NULL, FALSE],
      [FALSE, TRUE, 'TRUE', TRUE],
      [FALSE, FALSE, 'FALSE', FALSE],
      [FALSE, TRUE, 0, FALSE],
      [FALSE, TRUE, 1, TRUE],
      [FALSE, TRUE, -1, TRUE],
      [FALSE, TRUE, '1', TRUE],
      [FALSE, TRUE, '1.3', TRUE],
      // Null.
      [FALSE, FALSE, 'NULL', NULL],
      [TRUE, TRUE, NULL, NULL],
    ];
  }

  /**
   * @covers ::assertIdentical
   * @dataProvider providerEqualityAssertions
   */
  public function testAssertIdentical($expected_identical, $expected_equal, $first, $second) {
    $test_base = $this->getTestBaseForAssertionTests('test_id');
    $this->assertEquals(
        $expected_identical,
        $this->invokeProtectedMethod($test_base, 'assertIdentical', array($first, $second))
    );
  }

  /**
   * @covers ::assertNotIdentical
   * @dataProvider providerEqualityAssertions
   */
  public function testAssertNotIdentical($expected_identical, $expected_equal, $first, $second) {
    $test_base = $this->getTestBaseForAssertionTests('test_id');
    $this->assertEquals(
        (!$expected_identical),
        $this->invokeProtectedMethod($test_base, 'assertNotIdentical', array($first, $second))
    );
  }

  /**
   * @covers ::assertEqual
   * @dataProvider providerEqualityAssertions
   */
  public function testAssertEqual($expected_identical, $expected_equal, $first, $second) {
    $test_base = $this->getTestBaseForAssertionTests('test_id');
    $this->assertEquals(
        $expected_equal,
        $this->invokeProtectedMethod($test_base, 'assertEqual', array($first, $second))
    );
  }

  /**
   * @covers ::assertNotEqual
   * @dataProvider providerEqualityAssertions
   */
  public function testAssertNotEqual($expected_identical, $expected_equal, $first, $second) {
    $test_base = $this->getTestBaseForAssertionTests('test_id');
    $this->assertEquals(
        (!$expected_equal),
        $this->invokeProtectedMethod($test_base, 'assertNotEqual', array($first, $second))
    );
  }

  /**
   * Data provider for testAssertIdenticalObject().
   */
  public function providerAssertIdenticalObject() {
    $obj1 = new \stdClass();
    $obj1->foof = 'yay';
    $obj2 = $obj1;
    $obj3 = clone $obj1;
    $obj4 = new \stdClass();
    return array(
      array(TRUE, $obj1, $obj2),
      array(TRUE, $obj1, $obj3),
      array(FALSE, $obj1, $obj4),
    );
  }

  /**
   * @covers ::assertIdenticalObject
   * @dataProvider providerAssertIdenticalObject
   */
  public function testAssertIdenticalObject($expected, $first, $second) {
    $test_base = $this->getTestBaseForAssertionTests('test_id');
    $this->assertEquals(
      $expected,
      $this->invokeProtectedMethod($test_base, 'assertIdenticalObject', array($first, $second))
    );
  }

  /**
   * @covers ::pass
   */
  public function testPass() {
    $test_base = $this->getTestBaseForAssertionTests('test_id');
    $this->assertEquals(
        TRUE,
        $this->invokeProtectedMethod($test_base, 'pass', array())
    );
  }

  /**
   * @covers ::fail
   */
  public function testFail() {
    $test_base = $this->getTestBaseForAssertionTests('test_id');
    $this->assertEquals(
        FALSE,
        $this->invokeProtectedMethod($test_base, 'fail', array())
    );
  }

  /**
   * Data provider for testError().
   *
   * @return array
   *   - Expected status for assertion.
   *   - Group for use in assert().
   */
  public function providerError() {
    return array(
      array('debug', 'User notice'),
      array('exception', 'Not User notice'),
    );
  }

  /**
   * @covers ::error
   * @dataProvider providerError
   */
  public function testError($status, $group) {
    // Mock up a TestBase object.
    $mock_test_base = $this->getMockBuilder('Drupal\simpletest\TestBase')
      ->setMethods(array('assert'))
      ->getMockForAbstractClass();

    // Set expectations for assert().
    $mock_test_base->expects($this->once())
      ->method('assert')
      // The first argument to assert() should be the expected $status. This is
      // the most important expectation of this test.
      ->with($status)
      // Arbitrary return value.
      ->willReturn("$status:$group");

    // Invoke error().
    $this->assertEquals(
        "$status:$group",
        $this->invokeProtectedMethod($mock_test_base, 'error', array('msg', $group))
    );
  }

  /**
   * @covers ::getRandomGenerator
   */
  public function testGetRandomGenerator() {
    $test_base = $this->getTestBaseForAssertionTests('test_id');
    $this->assertInstanceOf(
        'Drupal\Component\Utility\Random',
        $this->invokeProtectedMethod($test_base, 'getRandomGenerator', array())
    );
  }

}
