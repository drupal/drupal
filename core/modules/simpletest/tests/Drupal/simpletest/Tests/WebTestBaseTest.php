<?php

/**
 * @file
 * Contains \Drupal\simpletest\Tests\WebTestBaseTest.
 */

namespace Drupal\simpletest\Tests;

use Drupal\Tests\UnitTestCase;

/**
 * Tests helper methods provided by the abstract WebTestBase class.
 *
 * @group Drupal
 * @group Simpletest
 */
class WebTestBaseTest extends UnitTestCase {

  /**
   * {@inheritdoc}
   */
  public static function getInfo() {
    return array(
      'name' => 'WebTestBase helper functions test',
      'description' => 'Test helper functions provided by the WebTestBase abstract class.',
      'group' => 'Simpletest',
    );
  }

  /**
   * Provides data for testing the assertFieldByName() helper.
   *
   * @return array
   *   An array of values passed to the test method.
   */
  public function providerAssertFieldByName() {
    $data = array();
    $data[] = array('select_2nd_selected', 'test', '1', FALSE);
    $data[] = array('select_2nd_selected', 'test', '2', TRUE);
    $data[] = array('select_none_selected', 'test', '', FALSE);
    $data[] = array('select_none_selected', 'test', '1', TRUE);
    $data[] = array('select_none_selected', 'test', NULL, TRUE);

    return $data;
  }

  /**
   * Tests the assertFieldByName() helper.
   *
   * @param string $filename
   *   Name of file containing the output to test.
   * @param string $name
   *   Name of field to assert.
   * @param string $value
   *   Value of the field to assert.
   * @param bool $expected
   *   The expected result of the assert.
   *
   * @see \Drupal\simpletest\WebTestBase::assertFieldByName()
   *
   * @dataProvider providerAssertFieldByName
   */
  public function testAssertFieldByName($filename, $name, $value, $expected) {
    $content = file_get_contents(__DIR__ . '/Fixtures/' . $filename . '.html');

    $web_test = $this->getMockBuilder('Drupal\simpletest\WebTestBase')
      ->disableOriginalConstructor()
      ->setMethods(array('assertTrue', 'drupalGetContent', 'pass'))
      ->getMock();

    $web_test->expects($this->any())
      ->method('drupalGetContent')
      ->will($this->returnValue($content));

    $web_test->expects($this->once())
      ->method('assertTrue')
      ->with($this->identicalTo($expected),
             $this->identicalTo('message'),
             $this->identicalTo('Browser'));

    $test_method = new \ReflectionMethod('Drupal\simpletest\WebTestBase', 'assertFieldByName');
    $test_method->setAccessible(TRUE);
    $test_method->invokeArgs($web_test, array($name, $value, 'message'));
  }

}
