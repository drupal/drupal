<?php

namespace Drupal\Tests\menu_link_content\Kernel\Plugin\migrate\process;

use Drupal\menu_link_content\Plugin\migrate\process\LinkUri;
use Drupal\migrate\MigrateException;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\Row;
use Drupal\KernelTests\KernelTestBase;

/**
 * Tests \Drupal\menu_link_content\Plugin\migrate\process\LinkUri.
 *
 * @group menu_link_content
 *
 * @coversDefaultClass \Drupal\menu_link_content\Plugin\migrate\process\LinkUri
 */
class LinkUriTest extends KernelTestBase {

  /**
   * Tests LinkUri::transform().
   *
   * @param array $value
   *   The value to pass to LinkUri::transform().
   * @param string $expected
   *   The expected return value of LinkUri::transform().
   *
   * @dataProvider providerTestRouted
   *
   * @covers ::transform
   */
  public function testRouted(array $value, $expected) {
    $actual = $this->doTransform($value);
    $this->assertSame($expected, $actual);
  }

  /**
   * Provides test cases for LinkUriTest::testTransform().
   *
   * @return array
   *   An array of test cases, each which the following values:
   *   - The value array to pass to LinkUri::transform().
   *   - The expected path returned by LinkUri::transform().
   */
  public function providerTestRouted() {
    $tests = [];

    $value = ['http://example.com'];
    $expected = 'http://example.com';
    $tests['with_scheme'] = [$value, $expected];

    $value = ['<front>'];
    $expected = 'internal:/';
    $tests['front'] = [$value, $expected];

    return $tests;
  }

  /**
   * Tests that Non routed URLs throws an exception.
   *
   * @param array $value
   *   The value to pass to LinkUri::transform().
   * @param string $exception_message
   *   The expected exception message.
   *
   * @dataProvider providerTestNotRouted
   */
  public function testNotRouted(array $value, $exception_message) {
    $this->setExpectedException(MigrateException::class, $exception_message);
    $this->doTransform($value);
  }

  /**
   * Provides test cases for LinkUriTest::testNotRouted().
   *
   * @return array
   *   An array of test cases, each which the following values:
   *   - The value array to pass to LinkUri::transform().
   *   - The expected path returned by LinkUri::transform().
   *   - (optional) A URL object that the path validator prophecy will return.
   */
  public function providerTestNotRouted() {
    $tests = [];

    $message = 'The path "%s" failed validation.';

    $value = ['/test'];
    $expected = 'internal:/test';
    $exception_message = sprintf($message, $expected);
    $tests['leading_slash'] = [$value, $exception_message];

    $value = ['test'];
    $expected = 'internal:/test';
    $exception_message = sprintf($message, $expected);
    $tests['without_scheme'] = [$value, $exception_message];

    return $tests;
  }

  /**
   * Transforms a link path into an 'internal:' or 'entity:' URI.
   *
   * @param array $value
   *   The value to pass to LinkUri::transform().
   *
   * @return string
   *   The transformed link.
   */
  public function doTransform(array $value) {
    $entityTypeManager = $this->container->get('entity_type.manager');
    $routeBuilder = $this->container->get('router.builder');
    $row = new Row();
    $executable = $this->prophesize(MigrateExecutableInterface::class)->reveal();

    $plugin = new LinkUri([], 'link_uri', [], $entityTypeManager, $routeBuilder);
    $actual = $plugin->transform($value, $executable, $row, 'destinationproperty');

    return $actual;
  }

}
