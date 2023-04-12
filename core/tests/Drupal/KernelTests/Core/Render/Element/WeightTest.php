<?php

namespace Drupal\KernelTests\Core\Render\Element;

use Drupal\Core\Form\FormState;
use Drupal\Core\Render\Element\Number;
use Drupal\Core\Render\Element\Select;
use Drupal\Core\Render\Element\Weight;
use Drupal\element_info_test\ElementInfoTestNumberBuilder;
use Drupal\KernelTests\KernelTestBase;

/**
 * @coversDefaultClass \Drupal\Core\Render\Element\Weight
 * @group Render
 */
class WeightTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['system', 'element_info_test'];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->installConfig(['system']);
  }

  /**
   * Tests existing #default_value value in #options list.
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

  /**
   * Tests transformation from "select" to "number" for MAX_DELTA + 1.
   *
   * @throws \Exception
   *
   * @covers ::processWeight
   */
  public function testProcessWeightSelectMax() {
    $form_state = new FormState();
    $definition = [
      '#type' => 'weight',
      '#delta' => $this->container
        ->get('config.factory')
        ->get('system.site')
        ->get('weight_select_max'),
      // Expected by the "doBuildForm()" method of "form_builder" service.
      '#parents' => [],
    ];

    $assert = function ($type, array $element, array $expected) use ($form_state) {
      // Pretend we have a form to trigger the "#process" callbacks.
      $element = $this->container
        ->get('form_builder')
        ->doBuildForm(__FUNCTION__, $element, $form_state);

      $expected['#type'] = $type;

      foreach ($expected as $property => $value) {
        static::assertSame($value, $element[$property]);
      }

      return $element;
    };

    // When the "#delta" is less or equal to maximum the "weight" must be
    // rendered as a "select".
    $select = $definition;
    $assert('select', $select, [
      '#process' => [
        [Select::class, 'processSelect'],
        [Select::class, 'processAjaxForm'],
      ],
      '#pre_render' => [
        [Select::class, 'preRenderSelect'],
      ],
    ]);

    $number = $definition;
    // Increase "#delta" in order to start rendering "number" elements
    // instead of "select".
    $number['#delta']++;
    // The "number" element definition has the "#pre_render" declaration by
    // default. The "hook_element_info_alter()" allows to modify the definition
    // of an element. We must be sure the standard "#pre_render" callbacks
    // are presented (unless explicitly removed) even in a case when the array
    // is modified by the alter hook.
    $assert('number', $number, [
      '#process' => [
        [Number::class, 'processAjaxForm'],
      ],
      '#element_validate' => [
        [Number::class, 'validateNumber'],
      ],
      '#pre_render' => [
        [Number::class, 'preRenderNumber'],
        // The custom callback is appended.
        /* @see \Drupal\element_info_test\ElementInfoTestNumberBuilder::preRender */
        [ElementInfoTestNumberBuilder::class, 'preRender'],
      ],
    ]);
  }

}
