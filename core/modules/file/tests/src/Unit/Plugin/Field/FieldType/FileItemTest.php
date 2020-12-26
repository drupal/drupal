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
   * Data provider for ::testValidateMaxFilesize
   */
  public function providerTestValidateMaxFilesize() {
    return [
      // Valid.
      [5, TRUE],
      ['5M', TRUE],
      ['5Mb', TRUE],
      ['5Gb', TRUE],
      // Invalid.
      ['foo', FALSE],
      ['fifty megabytes', FALSE],
      ['five', FALSE],
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
