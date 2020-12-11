<?php

/**
 * @file
 * Contains \Drupal\Tests\file\Unit\Plugin\Field\FieldType\FileItemTest.
 */

namespace Drupal\Tests\file\Unit\Plugin\Field\FieldType {

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
      ['five', FALSE],
      ['fifty megabytes', FALSE],
    ];
  }

  /**
   * @covers ::validateMaxFilesize
   *
   * @dataProvider providerTestValidateMaxFilesize
   */
  public function testValidateMaxFilesize($filesize, $valid) {
    $form_state = $this->getMock('\Drupal\Core\Form\FormStateInterface');
    // If this is valid, then setError should not be called.
    $form_state->expects($valid ? $this->never() : $this->once())
      ->method('setError');
    $element['#value'] = $filesize;
    $element['#title'] = 'title';
    FileItem::validateMaxFilesize($element, $form_state);
  }

}

}

namespace {
  use Drupal\Component\Render\FormattableMarkup;

  if (!function_exists('t')) {
    function t($string, array $args = []) {
      return new FormattableMarkup($string, $args);
    }
  }
}
