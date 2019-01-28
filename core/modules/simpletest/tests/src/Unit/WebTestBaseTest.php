<?php

namespace Drupal\Tests\simpletest\Unit;

use Drupal\Tests\UnitTestCase;

/**
 * @requires extension curl
 * @coversDefaultClass \Drupal\simpletest\WebTestBase
 * @group simpletest
 * @group WebTestBase
 */
class WebTestBaseTest extends UnitTestCase {

  /**
   * Provides data for testing the assertFieldByName() helper.
   *
   * @return array
   *   An array of values passed to the test method.
   */
  public function providerAssertFieldByName() {
    $data = [];
    $data[] = ['select_2nd_selected', 'test', '1', FALSE];
    $data[] = ['select_2nd_selected', 'test', '2', TRUE];
    $data[] = ['select_none_selected', 'test', '', FALSE];
    $data[] = ['select_none_selected', 'test', '1', TRUE];
    $data[] = ['select_none_selected', 'test', NULL, TRUE];

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
   * @covers ::assertFieldByName
   */
  public function testAssertFieldByName($filename, $name, $value, $expected) {
    $content = file_get_contents(__DIR__ . '/../../fixtures/' . $filename . '.html');

    $web_test = $this->getMockBuilder('Drupal\simpletest\WebTestBase')
      ->disableOriginalConstructor()
      ->setMethods(['getRawContent', 'assertTrue', 'pass'])
      ->getMock();

    $web_test->expects($this->any())
      ->method('getRawContent')
      ->will($this->returnValue($content));

    $web_test->expects($this->once())
      ->method('assertTrue')
      ->with($this->identicalTo($expected),
             $this->identicalTo('message'),
             $this->identicalTo('Browser'));

    $test_method = new \ReflectionMethod('Drupal\simpletest\WebTestBase', 'assertFieldByName');
    $test_method->setAccessible(TRUE);
    $test_method->invokeArgs($web_test, [$name, $value, 'message']);
  }

  /**
   * Data provider for testClickLink().
   *
   * In the test method, we mock drupalGet() to return a known string:
   * 'This Text Returned By drupalGet()'. Since clickLink() can only return
   * either the value of drupalGet() or FALSE, our expected return value is the
   * same as this mocked return value when we expect a link to be found.
   *
   * @see https://www.drupal.org/node/1452896
   *
   * @return array
   *   Array of arrays of test data. Test data is structured as follows:
   *   - Expected return value of clickLink().
   *   - Parameter $label to clickLink().
   *   - Parameter $index to clickLink().
   *   - Test data to be returned by mocked xpath(). Return an empty array here
   *     to mock no link found on the page.
   */
  public function providerTestClickLink() {
    return [
      // Test for a non-existent label.
      [
        FALSE,
        'does_not_exist',
        0,
        [],
      ],
      // Test for an existing label.
      [
        'This Text Returned By drupalGet()',
        'exists',
        0,
        [0 => ['href' => 'this_is_a_url']],
      ],
      // Test for an existing label that isn't the first one.
      [
        'This Text Returned By drupalGet()',
        'exists',
        1,
        [
          0 => ['href' => 'this_is_a_url'],
          1 => ['href' => 'this_is_another_url'],
        ],
      ],
    ];
  }

  /**
   * Test WebTestBase::clickLink().
   *
   * @param mixed $expected
   *   Expected return value of clickLink().
   * @param string $label
   *   Parameter $label to clickLink().
   * @param int $index
   *   Parameter $index to clickLink().
   * @param array $xpath_data
   *   Test data to be returned by mocked xpath().
   *
   * @dataProvider providerTestClickLink
   * @covers ::clickLink
   */
  public function testClickLink($expected, $label, $index, $xpath_data) {
    // Mock a WebTestBase object and some of its methods.
    $web_test = $this->getMockBuilder('Drupal\simpletest\WebTestBase')
      ->disableOriginalConstructor()
      ->setMethods([
        'pass',
        'fail',
        'getUrl',
        'xpath',
        'drupalGet',
        'getAbsoluteUrl',
      ])
      ->getMock();

    // Mocked getUrl() is only used for reporting so we just return a string.
    $web_test->expects($this->any())
      ->method('getUrl')
      ->will($this->returnValue('url_before'));

    // Mocked xpath() should return our test data.
    $web_test->expects($this->any())
      ->method('xpath')
      ->will($this->returnValue($xpath_data));

    if ($expected === FALSE) {
      // If link does not exist clickLink() will not try to do a drupalGet() or
      // a getAbsoluteUrl()
      $web_test->expects($this->never())
        ->method('drupalGet');
      $web_test->expects($this->never())
        ->method('getAbsoluteUrl');
      // The test should fail and not pass.
      $web_test->expects($this->never())
        ->method('pass');
      $web_test->expects($this->once())
        ->method('fail')
        ->will($this->returnValue(NULL));
    }
    else {
      // Mocked getAbsoluteUrl() should return whatever comes in.
      $web_test->expects($this->once())
        ->method('getAbsoluteUrl')
        ->with($xpath_data[$index]['href'])
        ->will($this->returnArgument(0));
      // We're only testing clickLink(), so drupalGet() always returns a string.
      $web_test->expects($this->once())
        ->method('drupalGet')
        ->with($xpath_data[$index]['href'])
        ->will($this->returnValue('This Text Returned By drupalGet()'));
      // The test should pass and not fail.
      $web_test->expects($this->never())
        ->method('fail');
      $web_test->expects($this->once())
        ->method('pass')
        ->will($this->returnValue(NULL));
    }

    // Set the clickLink() method to public so we can test it.
    $clicklink_method = new \ReflectionMethod($web_test, 'clickLink');
    $clicklink_method->setAccessible(TRUE);

    $this->assertSame($expected, $clicklink_method->invoke($web_test, $label, $index));
  }

  /**
   * @dataProvider providerTestGetAbsoluteUrl
   */
  public function testGetAbsoluteUrl($href, $expected_absolute_path) {
    $web_test = $this->getMockBuilder('Drupal\simpletest\WebTestBase')
      ->disableOriginalConstructor()
      ->setMethods(['getUrl'])
      ->getMock();

    $web_test->expects($this->any())
      ->method('getUrl')
      ->willReturn('http://example.com/drupal/current-path?foo=baz');

    $GLOBALS['base_url'] = 'http://example.com';
    $GLOBALS['base_path'] = 'drupal';

    $get_absolute_url_method = new \ReflectionMethod($web_test, 'getAbsoluteUrl');
    $get_absolute_url_method->setAccessible(TRUE);

    $this->assertSame($expected_absolute_path, $get_absolute_url_method->invoke($web_test, $href));
    unset($GLOBALS['base_url'], $GLOBALS['base_path']);
  }

  /**
   * Provides test data for testGetAbsoluteUrl.
   *
   * @return array
   */
  public function providerTestGetAbsoluteUrl() {
    $data = [];
    $data['host'] = ['http://example.com/drupal/test-example', 'http://example.com/drupal/test-example'];
    $data['path'] = ['/drupal/test-example', 'http://example.com/drupal/test-example'];
    $data['path-with-query'] = ['/drupal/test-example?foo=bar', 'http://example.com/drupal/test-example?foo=bar'];
    $data['just-query'] = ['?foo=bar', 'http://example.com/drupal/current-path?foo=bar'];

    return $data;
  }

}
