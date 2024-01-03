<?php

declare(strict_types=1);

namespace Drupal\Tests\Core\Plugin;

use Drupal\Component\Plugin\PluginBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\PluginFormInterface;
use Drupal\Core\Plugin\PluginWithFormsInterface;
use Drupal\Core\Plugin\PluginWithFormsTrait;
use Drupal\Tests\UnitTestCase;

/**
 * @coversDefaultClass \Drupal\Core\Plugin\PluginWithFormsTrait
 * @group Plugin
 */
class PluginWithFormsTraitTest extends UnitTestCase {

  /**
   * @covers ::getFormClass
   * @covers ::hasFormClass
   * @dataProvider providerGetFormClass
   */
  public function testGetFormClass(PluginWithFormsInterface $block_plugin, $operation, $expected_class) {
    $this->assertSame($expected_class, $block_plugin->getFormClass($operation));
    $this->assertSame($expected_class !== NULL, $block_plugin->hasFormClass($operation));
  }

  /**
   * @return array
   */
  public function providerGetFormClass() {
    $block_plugin_without_forms = new TestClass([], 'block_plugin_without_forms', [
      'provider' => 'block_test',
    ]);
    // A block plugin that has a form defined for the 'poke' operation.
    $block_plugin_with_forms = new TestClass([], 'block_plugin_with_forms', [
      'provider' => 'block_test',
      'forms' => [
        'poke' => static::class,
      ],
    ]);
    return [
      'block plugin without forms, "configure" operation' => [$block_plugin_without_forms, 'configure', TestClass::class],
      'block plugin without forms, "tickle" operation'    => [$block_plugin_without_forms, 'tickle', NULL],
      'block plugin without forms, "poke" operation'      => [$block_plugin_without_forms, 'poke', NULL],
      'block plugin with forms, "configure" operation' => [$block_plugin_with_forms, 'configure', TestClass::class],
      'block plugin with forms, "tickle" operation'    => [$block_plugin_with_forms, 'tickle', NULL],
      'block plugin with forms, "poke" operation'      => [$block_plugin_with_forms, 'poke', static::class],
    ];
  }

}

class TestClass extends PluginBase implements PluginWithFormsInterface, PluginFormInterface {
  use PluginWithFormsTrait;

  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    return [];
  }

  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {
  }

  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
  }

}
