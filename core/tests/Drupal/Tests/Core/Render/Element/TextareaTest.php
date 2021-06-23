<?php

namespace Drupal\Tests\Core\Render\Element;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element\Textarea;
use Drupal\Tests\UnitTestCase;

/**
 * @coversDefaultClass \Drupal\Core\Render\Element\Textarea
 * @group Render
 */
class TextareaTest extends UnitTestCase {

  /**
   * @covers ::valueCallback
   *
   * @dataProvider providerTestValueCallback
   */
  public function testValueCallback($expected, $input) {
    $element = [];
    $form_state = $this->prophesize(FormStateInterface::class)->reveal();
    $this->assertSame($expected, Textarea::valueCallback($element, $input, $form_state));
  }

  /**
   * Data provider for testValueCallback().
   */
  public function providerTestValueCallback() {
    $data = [];
    $data[] = [NULL, FALSE];
    $data[] = [NULL, NULL];
    $data[] = ['', ['test']];
    $data[] = ['test', 'test'];
    $data[] = ['123', 123];
    $data[] = ["some\r\ndifferent\rline\nendings", "some\r\ndifferent\rline\nendings"];

    return $data;
  }

  /**
   * @covers ::valueCallback
   */
  public function testNormalizeNewlines() {
    $element = ['#normalize_newlines' => TRUE];
    $form_state = $this->prophesize(FormStateInterface::class)->reveal();
    $input = "some\r\ndifferent\rline\nendings";
    $expected = "some\ndifferent\nline\nendings";
    $this->assertSame($expected, Textarea::valueCallback($element, $input, $form_state));
  }

}
