<?php

namespace Drupal\Tests\Theme;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Form\FormState;
use Drupal\Core\Render\Element\Radio;
use Drupal\form_test\Form\FormTestLabelForm;
use Drupal\Tests\UnitTestCase;

/**
 * Tests the Olivero theme's olivero_preprocess_input.
 *
 * @group olivero
 * @covers olivero_preprocess_input
 */
final class OliveroPreprocessInputTest extends UnitTestCase {

  /**
   * {@inheritdoc}
   */
  public function setUp(): void {
    parent::setUp();
    require_once __DIR__ . '/../../../../themes/olivero/olivero.theme';

    $container = new ContainerBuilder();
    $container->set('string_translation', $this->getStringTranslationStub());
    \Drupal::setContainer($container);
  }

  /**
   * Tests the olivero_preprocess_input adjustments to title attribute.
   */
  public function testPreprocessInputTitleAttribute() {
    $form = (new FormTestLabelForm())->buildForm([], new FormState());
    $element = Radio::preRenderRadio($form['form_radios_title_attribute']);
    $variables = [
      'attributes' => $element['#attributes'],
      'element' => $element,
    ];
    olivero_preprocess_input($variables);
    $this->assertArrayHasKey('title', $variables['attributes']);
    $this->assertContains('form-boolean--type-radio', $variables['attributes']['class']);
  }

  /**
   * Tests the olivero_preprocess_input adjustments to type attribute.
   *
   * @dataProvider preprocessInputDataProvider()
   */
  public function testPreprocessInputTypeAttribute($expected, $element) {
    $variables = [
      'element' => $element,
      'attributes' => $element['#attributes'],
    ];
    olivero_preprocess_input($variables);

    $this->assertEquals($expected, $variables['attributes']['class']);
  }

  /**
   * Tests the olivero_preprocess_input adjustments to autocomplete message.
   */
  public function testPreprocessInputAutocompleteMessage() {
    $variables = [
      'element' => [
        '#autocomplete_route_name' => 'mock',
        '#type' => 'text',
      ],
      'attributes' => [
        'type' => 'text',
      ],
    ];
    olivero_preprocess_input($variables);
    $loading_message = t('Loading…');
    $this->assertEquals($loading_message, $variables['autocomplete_message']);
  }

  /**
   * Data provider to test different types.
   */
  public function preprocessInputDataProvider() {
    $tests = [];
    $types = [
      'text' => 'textfield',
      'email' => 'email',
      'tel' => 'tel',
      'number' => 'number',
      'search' => 'search',
      'password' => 'password',
      'date' => 'date',
      'time' => 'date',
      'file' => 'file',
      'color' => 'color',
      'datetime-local' => 'date',
      'url' => 'url',
      'month' => 'date',
      'week' => 'date',
    ];

    $tests = [];
    foreach ($types as $html_type => $api_type) {
      $tests[] = [
        [
          'form-element',
          'form-element--type-' . $html_type,
          'form-element--api-' . $api_type,
        ],
        [
          '#type' => $api_type,
          '#title' => 'Field test ' . $html_type,
          '#attributes' => [
            'type' => $html_type,
            'title' => 'Field test ' . $html_type,
          ],
        ],
      ];
    }
    return $tests;
  }

}
