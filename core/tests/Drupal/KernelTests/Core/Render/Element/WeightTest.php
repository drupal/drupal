<?php

namespace Drupal\KernelTests\Core\Render\Element;

use Drupal\Core\Form\FormState;
use Drupal\Core\Render\Element\Weight;
use Drupal\KernelTests\KernelTestBase;

/**
 * @coversDefaultClass \Drupal\Core\Render\Element\Weight
 * @group Render
 */
class WeightTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['system'];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->installConfig(['system']);
  }

  /**
   * Test existing #default_value value in #options list.
   *
   * @covers ::processWeight
   */
  public function testProcessWeight() {
    $element = [];
    $form_state = new FormState();
    $complete_form = [];

    $element_object = new Weight([], 'weight', []);
    $info = $element_object->getInfo();
    $element += $info;

    $element['#default_value'] = $element['#delta'] + 5;

    Weight::processWeight($element, $form_state, $complete_form);

    $this->assertTrue(
      isset($element['#options'][$element['#default_value']]),
      'Default value exists in the #options list'
    );
  }

}
