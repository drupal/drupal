<?php

namespace Drupal\Tests\Core\Render\Element;

use Drupal\Core\Access\CsrfTokenGenerator;
use Drupal\Core\Form\FormState;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Render\Element\MachineName;
use Drupal\Tests\UnitTestCase;
use Symfony\Component\DependencyInjection\ContainerInterface;

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

  /**
   * @covers ::processMachineName
   */
  public function testProcessMachineName() {
    $form_state = new FormState();

    $element = [
      '#id' => 'test',
      '#field_suffix' => 'test_suffix',
      '#field_prefix' => 'test_prefix',
      '#machine_name' => [
        'source' => [
          'test_source',
        ],
        'maxlength' => 32,
        'additional_property' => TRUE,
        '#additional_property_with_hash' => TRUE,
      ],
      // The process function requires these to be set. During regular form
      // building they are always set.
      '#name' => 'test_machine_name',
      '#default_value' => NULL,
    ];

    $complete_form = [
      'test_source' => [
        '#type' => 'textfield',
        '#id' => 'source',
      ],
      'test_machine_name' => $element,
    ];

    $form_state->setCompleteForm($complete_form);

    $language = $this->prophesize(LanguageInterface::class);
    $language->getId()->willReturn('xx-lolspeak');

    $language_manager = $this->prophesize(LanguageManagerInterface::class);
    $language_manager->getCurrentLanguage()->willReturn($language);

    $csrf_token = $this->prophesize(CsrfTokenGenerator::class);
    $csrf_token->get('[^a-z0-9_]+')->willReturn('tis-a-fine-token');

    $container = $this->prophesize(ContainerInterface::class);
    $container->get('language_manager')->willReturn($language_manager->reveal());
    $container->get('csrf_token')->willReturn($csrf_token->reveal());
    \Drupal::setContainer($container->reveal());

    $element = MachineName::processMachineName($element, $form_state, $complete_form);
    $settings = $element['#attached']['drupalSettings']['machineName']['#source'];

    $allowed_options = [
      'replace_pattern',
      'replace',
      'maxlength',
      'target',
      'label',
      'field_prefix',
      'field_suffix',
      'suffix',
      'replace_token',
    ];
    $this->assertEmpty(array_diff_key($settings, array_flip($allowed_options)));
    foreach ($allowed_options as $key) {
      $this->assertArrayHasKey($key, $settings);
    }
  }

}

namespace Drupal\Core\Render\Element;

if (!function_exists('t')) {

  function t($string, array $args = []) {
    return strtr($string, $args);
  }

}
