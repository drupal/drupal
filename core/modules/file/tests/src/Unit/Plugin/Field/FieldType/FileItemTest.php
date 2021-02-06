<?php

namespace Drupal\Tests\file\Unit\Plugin\Field\FieldType;

use Drupal\Core\Form\FormStateInterface;
use Drupal\file\Plugin\Field\FieldType\FileItem;
use Drupal\Tests\UnitTestCase;

/**
 * @coversDefaultClass \Drupal\file\Plugin\Field\FieldType\FileItem
 *
 * @group file
 */
class FileItemTest extends UnitTestCase {

  /**
   * Data provider for ::testValidateMaxFilesize.
   */
  public function providerTestValidateMaxFilesize() {
    return [
      // String not starting with a number.
      ['foo', FALSE],
      ['fifty megabytes', FALSE],
      ['five', FALSE],
      // Test spaces and capital combinations.
      [5, TRUE],
      ['5M', TRUE],
      ['5m', TRUE],
      ['5 M', TRUE],
      ['5 m', TRUE],
      ['5Mb', TRUE],
      ['5mb', TRUE],
      ['5 Mb', TRUE],
      ['5 mb', TRUE],
      ['5Gb', TRUE],
      ['5gb', TRUE],
      ['5 Gb', TRUE],
      ['5 gb', TRUE],
      // Test all allowed suffixes.
      ['5', TRUE],
      ['5 b', TRUE],
      ['5 byte', TRUE],
      ['5 bytes', TRUE],
      ['5 k', TRUE],
      ['5 kb', TRUE],
      ['5 kilobyte', TRUE],
      ['5 kilobytes', TRUE],
      ['5 m', TRUE],
      ['5 mb', TRUE],
      ['5 megabyte', TRUE],
      ['5 megabytes', TRUE],
      ['5 g', TRUE],
      ['5 gb', TRUE],
      ['5 gigabyte', TRUE],
      ['5 gigabytes', TRUE],
      ['5 t', TRUE],
      ['5 tb', TRUE],
      ['5 terabyte', TRUE],
      ['5 terabytes', TRUE],
      ['5 p', TRUE],
      ['5 pb', TRUE],
      ['5 petabyte', TRUE],
      ['5 petabytes', TRUE],
      ['5 e', TRUE],
      ['5 eb', TRUE],
      ['5 exabyte', TRUE],
      ['5 exabytes', TRUE],
      ['5 z', TRUE],
      ['5 zb', TRUE],
      ['5 zettabyte', TRUE],
      ['5 zettabytes', TRUE],
      ['5 y', TRUE],
      ['5 yb', TRUE],
      ['5 yottabyte', TRUE],
      ['5 yottabytes', TRUE],
      // Test with an unauthorized string.
      ['1five', FALSE],
    ];
  }

  /**
   * @covers ::validateMaxFilesize
   *
   * @dataProvider providerTestValidateMaxFilesize
   */
  public function testValidateMaxFilesize($filesize, $valid) {
    // If this is valid, then setError should not be called.
    $invocation_count = $valid ? $this->never() : $this->once();

    $element['#value'] = $filesize;
    $element['#title'] = 'title';

    $form_state = $this->createMock(FormStateInterface::class);
    $form_state->expects($invocation_count)
      ->method('setError');

    FileItem::validateMaxFilesize($element, $form_state);
  }

}
