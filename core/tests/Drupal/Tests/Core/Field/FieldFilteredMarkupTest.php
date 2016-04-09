<?php

namespace Drupal\Tests\Core\Field;

use Drupal\Tests\UnitTestCase;
use Drupal\Core\Field\FieldFilteredMarkup;
use Drupal\Component\Render\MarkupInterface;

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
  public function providerTestCreate() {
    $data = [];
    $data[] = ['', '', FALSE];
    // Certain tags are filtered.
    $data[] = ['<script>teststring</script>', 'teststring', TRUE];
    // Certain tags are not filtered.
    $data[] = ['<em>teststring</em>', '<em>teststring</em>', TRUE];
    // HTML will be normalized.
    $data[] = ['<em>teststring', '<em>teststring</em>', TRUE];

    // Even safe strings will be escaped.
    $safe_string = $this->prophesize(MarkupInterface::class);
    $safe_string->__toString()->willReturn('<script>teststring</script>');
    $data[] = [$safe_string->reveal(), 'teststring', TRUE];

    return $data;
  }

  /**
   * @covers: ::displayAllowedTags
   */
  public function testdisplayAllowedTags() {
    $expected = '<a> <b> <big> <code> <del> <em> <i> <ins> <pre> <q> <small> <span> <strong> <sub> <sup> <tt> <ol> <ul> <li> <p> <br> <img>';

    $this->assertSame($expected, FieldFilteredMarkup::displayAllowedTags());
  }

}
