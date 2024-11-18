<?php

declare(strict_types=1);

namespace Drupal\config_schema_test\Hook;

use Drupal\Core\Hook\Attribute\Hook;

/**
 * Hook implementations for config_schema_test.
 */
class ConfigSchemaTestHooks {

  /**
   * Implements hook_config_schema_info_alter().
   */
  #[Hook('config_schema_info_alter')]
  public function configSchemaInfoAlter(&$definitions): void {
    if (\Drupal::state()->get('config_schema_test_exception_add')) {
      $definitions['config_schema_test.hook_added_definition'] = $definitions['config_schema_test.hook'];
    }
    if (\Drupal::state()->get('config_schema_test_exception_remove')) {
      unset($definitions['config_schema_test.hook']);
    }
    // Since code can not be unloaded only alter the definition if it exists.
    if (isset($definitions['config_schema_test.hook'])) {
      $definitions['config_schema_test.hook']['additional_metadata'] = 'new schema info';
    }
    // @see \Drupal\KernelTests\Core\TypedData\ValidKeysConstraintValidatorTest
    if (\Drupal::state()->get('config_schema_test_block_fully_validatable')) {
      $definitions['block.block.*']['constraints']['FullyValidatable'] = NULL;
    }
    else {
      unset($definitions['block.block.*']['constraints']);
    }
    // @see \Drupal\Tests\node\Kernel\NodeTypeValidationTest::testThirdPartySettingsMenuUi()
    if (\Drupal::state()->get('config_schema_test_menu_ui_third_party_settings_fully_validatable')) {
      $definitions['node.type.*.third_party.menu_ui']['constraints']['FullyValidatable'] = NULL;
    }
    else {
      unset($definitions['node.type.*.third_party.menu_ui']['constraints']);
    }
  }

}
