<?php

declare(strict_types=1);

namespace Drupal\Tests\menu_link_content\Kernel\Plugin\migrate\process;

use Drupal\KernelTests\KernelTestBase;
use Drupal\menu_link_content\Plugin\migrate\process\LinkUri;
use Drupal\migrate\MigrateException;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\Row;
use Drupal\node\Entity\Node;
use Drupal\Tests\user\Traits\UserCreationTrait;

/**
 * Tests \Drupal\menu_link_content\Plugin\migrate\process\LinkUri.
 *
 * @group menu_link_content
 *
 * @coversDefaultClass \Drupal\menu_link_content\Plugin\migrate\process\LinkUri
 */
class LinkUriTest extends KernelTestBase {

  use UserCreationTrait;

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = ['node', 'user'];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->setUpCurrentUser();
    $this->installEntitySchema('node');
  }

  /**
   * Tests LinkUri::transform().
   *
   * @param string $value
   *   The value to pass to LinkUri::transform().
   * @param string $expected
   *   The expected return value of LinkUri::transform().
   *
   * @dataProvider providerTestRouted
   *
   * @covers ::transform
   */
  public function testRouted($value, $expected): void {
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
  public static function providerTestRouted() {
    $tests = [];

    $value = 'http://example.com';
    $expected = 'http://example.com';
    $tests['with_scheme'] = [$value, $expected];

    $value = '<front>';
    $expected = 'internal:/';
    $tests['front'] = [$value, $expected];

    $value = '<nolink>';
    $expected = 'route:<nolink>';
    $tests['nolink'] = [$value, $expected];

    return $tests;
  }

  /**
   * Tests that Non routed URLs throws an exception.
   *
   * @param string $value
   *   The value to pass to LinkUri::transform().
   * @param string $exception_message
   *   The expected exception message.
   *
   * @dataProvider providerTestNotRouted
   */
  public function testNotRouted($value, $exception_message): void {
    $this->expectException(MigrateException::class);
    $this->expectExceptionMessage($exception_message);
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
  public static function providerTestNotRouted() {
    $tests = [];

    $message = 'The path "%s" failed validation.';

    $value = '/test';
    $expected = 'internal:/test';
    $exception_message = sprintf($message, $expected);
    $tests['leading_slash'] = [$value, $exception_message];

    $value = 'test';
    $expected = 'internal:/test';
    $exception_message = sprintf($message, $expected);
    $tests['without_scheme'] = [$value, $exception_message];

    return $tests;
  }

  /**
   * Tests disabling route validation in LinkUri::transform().
   *
   * @param string $value
   *   The value to pass to LinkUri::transform().
   * @param string $expected
   *   The expected return value of LinkUri::transform().
   *
   * @dataProvider providerTestDisablingRouteValidation
   *
   * @covers ::transform
   */
  public function testDisablingRouteValidation($value, $expected): void {
    // Create a node so we have a valid route.
    Node::create([
      'nid' => 1,
      'title' => 'test',
      'type' => 'page',
    ])->save();

    $actual = $this->doTransform($value, ['validate_route' => FALSE]);
    $this->assertSame($expected, $actual);
  }

  /**
   * Provides test cases for LinkUriTest::testDisablingRouteValidation().
   *
   * @return array
   *   An array of test cases, each which the following values:
   *   - The value array to pass to LinkUri::transform().
   *   - The expected path returned by LinkUri::transform().
   */
  public static function providerTestDisablingRouteValidation() {
    $tests = [];

    $value = 'node/1';
    $expected = 'entity:node/1';
    $tests['routed'] = [$value, $expected];

    $value = 'node/2';
    $expected = 'base:node/2';
    $tests['unrouted'] = [$value, $expected];

    return $tests;
  }

  /**
   * Transforms a link path into an 'internal:' or 'entity:' URI.
   *
   * @param string $value
   *   The value to pass to LinkUri::transform().
   * @param array $configuration
   *   The plugin configuration.
   *
   * @return string
   *   The transformed link.
   */
  public function doTransform($value, $configuration = []) {
    $entityTypeManager = $this->container->get('entity_type.manager');
    $row = new Row();
    $executable = $this->prophesize(MigrateExecutableInterface::class)->reveal();

    $plugin = new LinkUri($configuration, 'link_uri', [], $entityTypeManager);
    $actual = $plugin->transform($value, $executable, $row, 'destination_property');

    return $actual;
  }

}
