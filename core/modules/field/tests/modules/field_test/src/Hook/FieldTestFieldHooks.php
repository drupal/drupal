<?php

declare(strict_types=1);

namespace Drupal\field_test\Hook;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Entity\Exception\FieldStorageDefinitionUpdateForbiddenException;
use Drupal\field\FieldStorageConfigInterface;
use Drupal\Core\Hook\Attribute\Hook;

/**
 * Hook implementations for field_test.
 */
class FieldTestFieldHooks {

  /**
   * Implements hook_field_widget_info_alter().
   */
  #[Hook('field_widget_info_alter')]
  public function fieldWidgetInfoAlter(&$info): void {
    $info['test_field_widget_multiple']['field_types'][] = 'test_field';
    $info['test_field_widget_multiple']['field_types'][] = 'test_field_with_preconfigured_options';
    // Add extra widget when needed for tests.
    // @see \Drupal\field\Tests\FormTest::widgetAlterTest().
    if ($alter_info = \Drupal::state()->get("field_test.widget_alter_test")) {
      if ($alter_info['widget'] === 'test_field_widget_multiple_single_value') {
        $info['test_field_widget_multiple_single_value']['field_types'][] = 'test_field';
      }
    }
  }

  /**
   * Implements hook_field_storage_config_update_forbid().
   */
  #[Hook('field_storage_config_update_forbid')]
  public function fieldStorageConfigUpdateForbid(FieldStorageConfigInterface $field_storage, FieldStorageConfigInterface $prior_field_storage) {
    if ($field_storage->getType() == 'test_field' && $field_storage->getSetting('unchangeable') != $prior_field_storage->getSetting('unchangeable')) {
      throw new FieldStorageDefinitionUpdateForbiddenException("field_test 'unchangeable' setting cannot be changed'");
    }
  }

  /**
   * Implements hook_entity_field_access().
   */
  #[Hook('entity_field_access')]
  public function entityFieldAccess($operation, FieldDefinitionInterface $field_definition, AccountInterface $account, ?FieldItemListInterface $items = NULL) {
    if ($field_definition->getName() == "field_no_{$operation}_access") {
      return AccessResult::forbidden();
    }
    // Only grant view access to test_view_field fields when the user has
    // 'view test_view_field content' permission.
    if ($field_definition->getName() == 'test_view_field' && $operation == 'view') {
      return AccessResult::forbiddenIf(!$account->hasPermission('view test_view_field content'))->cachePerPermissions();
    }
    return AccessResult::allowed();
  }

}
