<?php

namespace Drupal\Tests\settings_tray\Unit\Access;

use Drupal\block\BlockInterface;
use Drupal\Core\Access\AccessResultAllowed;
use Drupal\Core\Access\AccessResultInterface;
use Drupal\Core\Access\AccessResultNeutral;
use Drupal\Core\Block\BlockPluginInterface;
use Drupal\Core\Plugin\PluginWithFormsInterface;
use Drupal\settings_tray\Access\BlockPluginHasSettingsTrayFormAccessCheck;
use Drupal\Tests\UnitTestCase;
use Prophecy\Argument;

/**
 * @coversDefaultClass \Drupal\settings_tray\Access\BlockPluginHasSettingsTrayFormAccessCheck
 * @group settings_tray
 */
class BlockPluginHasSettingsTrayFormAccessCheckTest extends UnitTestCase {

  /**
   * @covers ::access
   * @covers ::accessBlockPlugin
   * @dataProvider providerTestAccess
   */
  public function testAccess($with_forms, array $plugin_definition, AccessResultInterface $expected_access_result) {
    $block_plugin = $this->prophesize()->willImplement(BlockPluginInterface::class);

    if ($with_forms) {
      $block_plugin->willImplement(PluginWithFormsInterface::class);
      $block_plugin->hasFormClass(Argument::type('string'))->will(function ($arguments) use ($plugin_definition) {
        return !empty($plugin_definition['forms'][$arguments[0]]);
      });
    }

    $block = $this->prophesize(BlockInterface::class);
    $block->getPlugin()->willReturn($block_plugin->reveal());

    $access_check = new BlockPluginHasSettingsTrayFormAccessCheck();
    $this->assertEquals($expected_access_result, $access_check->access($block->reveal()));
    $this->assertEquals($expected_access_result, $access_check->accessBlockPlugin($block_plugin->reveal()));
  }

  /**
   * Provides test data for ::testAccess().
   */
  public function providerTestAccess() {
    $annotation_forms_settings_tray_class = [
      'forms' => [
        'settings_tray' => $this->randomMachineName(),
      ],
    ];
    $annotation_forms_settings_tray_not_set = [];
    $annotation_forms_settings_tray_false = [
      'forms' => [
        'settings_tray' => FALSE,
      ],
    ];
    return [
      'block plugin with forms, forms[settings_tray] set to class' => [
        TRUE,
        $annotation_forms_settings_tray_class,
        new AccessResultAllowed(),
      ],
      'block plugin with forms, forms[settings_tray] not set' => [
        TRUE,
        $annotation_forms_settings_tray_not_set,
        new AccessResultNeutral(),
      ],
      'block plugin with forms, forms[settings_tray] set to FALSE' => [
        TRUE,
        $annotation_forms_settings_tray_false,
        new AccessResultNeutral(),
      ],
      // In practice, all block plugins extend BlockBase, which means they all
      // implement PluginWithFormsInterface, but this may change in the future.
      // This ensures Settings Tray will continue to work correctly.
      'block plugin without forms, forms[settings_tray] set to class' => [
        FALSE,
        $annotation_forms_settings_tray_class,
        new AccessResultNeutral(),
      ],
      'block plugin without forms, forms[settings_tray] not set' => [
        FALSE,
        $annotation_forms_settings_tray_not_set,
        new AccessResultNeutral(),
      ],
      'block plugin without forms, forms[settings_tray] set to FALSE' => [
        FALSE,
        $annotation_forms_settings_tray_false,
        new AccessResultNeutral(),
      ],
    ];
  }

}
