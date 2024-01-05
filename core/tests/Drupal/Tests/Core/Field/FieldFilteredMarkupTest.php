<?php

declare(strict_types=1);

namespace Drupal\Tests\Core\Field;

use Drupal\Tests\UnitTestCase;
use Drupal\Core\Field\FieldFilteredMarkup;
use Drupal\Component\Render\MarkupInterface;
use Prophecy\Prophet;

/**
 * @coversDefaultClass \Drupal\Core\Field\FieldFilteredMarkup
 * @group Field
 */
class FieldFilteredMarkupTest extends UnitTestCase {

  /**
   * @covers ::create
   * @dataProvider providerTestCreate
   */
  public function testCreate($string, $expected, $instance_of_check) {
    $filtered_string = FieldFilteredMarkup::create($string);

    if ($instance_of_check) {
      $this->assertInstanceOf(FieldFilteredMarkup::class, $filtered_string);
    }
    $this->assertSame($expected, (string) $filtered_string);
  }

  /**
   * Provides data for testCreate().
   */
  public static function providerTestCreate() {
    $data = [];
    $data[] = ['', '', FALSE];
    // Certain tags are filtered.
    $data[] = ['<script>test string</script>', 'test string', TRUE];
    // Certain tags are not filtered.
    $data[] = ['<em>test string</em>', '<em>test string</em>', TRUE];
    // HTML will be normalized.
    $data[] = ['<em>test string', '<em>test string</em>', TRUE];

    // Even safe strings will be escaped.
    $safe_string = (new Prophet())->prophesize(MarkupInterface::class);
    $safe_string->__toString()->willReturn('<script>test string</script>');
    $data[] = [$safe_string->reveal(), 'test string', TRUE];

    return $data;
  }

  /**
   * @covers ::displayAllowedTags
   */
  public function testDisplayAllowedTags() {
    $expected = '<a> <b> <big> <code> <del> <em> <i> <ins> <pre> <q> <small> <span> <strong> <sub> <sup> <tt> <ol> <ul> <li> <p> <br> <img>';

    $this->assertSame($expected, FieldFilteredMarkup::displayAllowedTags());
  }

}
