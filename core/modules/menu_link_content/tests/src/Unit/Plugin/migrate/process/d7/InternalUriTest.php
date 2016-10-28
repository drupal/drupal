<?php

namespace Drupal\Tests\menu_link_content\Unit\Plugin\migrate\process\d7;

use Drupal\menu_link_content\Plugin\migrate\process\d7\InternalUri;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\Row;
use Drupal\Tests\UnitTestCase;

/**
 * Tests \Drupal\menu_link_content\Plugin\migrate\process\d7\InternalUri.
 *
 * @group menu_link_content
 *
 * @coversDefaultClass \Drupal\menu_link_content\Plugin\migrate\process\d7\InternalUri
 */
class InternalUriTest extends UnitTestCase {

  /**
   * The 'd7_internal_uri' process plugin being tested.
   *
   * @var \Drupal\menu_link_content\Plugin\migrate\process\d7\InternalUri
   */
  protected $processPlugin;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->processPlugin = new InternalUri([], 'd7_internal_uri', []);
  }

  /**
   * Tests InternalUri::transform().
   *
   * @param array $value
   *   The value to pass to InternalUri::transform().
   * @param string $expected
   *   The expected return value of InternalUri::transform().
   *
   * @dataProvider providerTestTransform
   *
   * @covers ::transform
   */
  public function testTransform(array $value, $expected) {
    $migrate_executable = $this->prophesize(MigrateExecutableInterface::class);
    $row = $this->prophesize(Row::class);

    $actual = $this->processPlugin->transform($value, $migrate_executable->reveal(), $row->reveal(), 'link/uri');
    $this->assertEquals($expected, $actual);
  }

  /**
   * Provides test cases for InternalUriTest::testTransform().
   *
   * @return array
   *   An array of test cases, each which the following values:
   *   - The value array to pass to InternalUri::transform().
   *   - The expected path returned by InternalUri::transform().
   */
  public function providerTestTransform() {
    $tests = [];
    $tests['with_scheme'] = [['http://example.com'], 'http://example.com'];
    $tests['leading_slash'] = [['/test'], 'internal:/test'];
    $tests['without_scheme'] = [['test'], 'internal:/test'];
    $tests['front'] = [['<front>'], 'internal:/'];
    $tests['node'] = [['node/27'], 'entity:node/27'];
    return $tests;
  }

}
