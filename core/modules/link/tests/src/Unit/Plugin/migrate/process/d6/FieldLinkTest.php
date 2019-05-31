<?php

namespace Drupal\Tests\link\Unit\Plugin\migrate\process\d6;

use Drupal\link\Plugin\migrate\process\d6\FieldLink;
use Drupal\Tests\UnitTestCase;

/**
 * @group Link
 * @group legacy
 */
class FieldLinkTest extends UnitTestCase {

  /**
   * Test the url transformations in the FieldLink process plugin.
   *
   * @dataProvider canonicalizeUriDataProvider
   */
  public function testCanonicalizeUri($url, $expected) {
    $link_plugin = new FieldLink([], '', [], $this->createMock('\Drupal\migrate\Plugin\MigrationInterface'));
    $transformed = $link_plugin->transform([
      'url' => $url,
      'title' => '',
      'attributes' => serialize([]),
    ], $this->createMock('\Drupal\migrate\MigrateExecutableInterface'), $this->getMockBuilder('\Drupal\migrate\Row')->disableOriginalConstructor()->getMock(), NULL);
    $this->assertEquals($expected, $transformed['uri']);
  }

  /**
   * Data provider for testCanonicalizeUri.
   */
  public function canonicalizeUriDataProvider() {
    return [
      'Simple front-page' => [
        '<front>',
        'internal:/',
      ],
      'Front page with query' => [
        '<front>?query=1',
        'internal:/?query=1',
      ],
      'No leading forward slash' => [
        'node/10',
        'internal:/node/10',
      ],
      'Leading forward slash' => [
        '/node/10',
        'internal:/node/10',
      ],
      'Existing scheme' => [
        'scheme:test',
        'scheme:test',
      ],
    ];
  }

}
