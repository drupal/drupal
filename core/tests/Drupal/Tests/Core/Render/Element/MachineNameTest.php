<?php

/**
 * @file
 * Contains \Drupal\Tests\Core\Render\Element\MachineNameTest.
 */

namespace Drupal\Tests\Core\Render\Element;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element\MachineName;
use Drupal\Tests\UnitTestCase;

/**
 * @coversDefaultClass \Drupal\Core\Render\Element\MachineName
 * @group Render
 */
class MachineNameTest extends UnitTestCase {

  /**
   * @covers ::valueCallback
   *
   * @dataProvider providerTestValueCallback
   */
  public function testValueCallback($expected, $input) {
    $element = [];
    $form_state = $this->prophesize(FormStateInterface::class)->reveal();
    $this->assertSame($expected, MachineName::valueCallback($element, $input, $form_state));
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

    return $data;
  }

}
