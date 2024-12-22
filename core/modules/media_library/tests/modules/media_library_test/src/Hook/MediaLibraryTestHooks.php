<?php

declare(strict_types=1);

namespace Drupal\media_library_test\Hook;

use Drupal\Core\Access\AccessResultInterface;
use Drupal\media_library_test\Form\TestNodeFormOverride;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Hook\Attribute\Hook;

/**
 * Hook implementations for media_library_test.
 */
class MediaLibraryTestHooks {

  /**
   * Implements hook_ENTITY_TYPE_create_access().
   */
  #[Hook('media_create_access')]
  public function mediaCreateAccess(AccountInterface $account, array $context, $entity_bundle): AccessResultInterface {
    if (isset($context['media_library_state'])) {
      /** @var \Drupal\media_library\MediaLibraryState $state */
      $state = $context['media_library_state'];
      return AccessResult::forbiddenIf($state->getSelectedTypeId() === 'deny_access');
    }
    return AccessResult::neutral();
  }

  /**
   * Implements hook_entity_field_access().
   */
  #[Hook('entity_field_access')]
  public function entityFieldAccess($operation, FieldDefinitionInterface $field_definition, AccountInterface $account, ?FieldItemListInterface $items = NULL): AccessResultInterface {
    $deny_fields = \Drupal::state()->get('media_library_test_entity_field_access_deny_fields', []);
    // Always deny the field_media_no_access field.
    $deny_fields[] = 'field_media_no_access';
    return AccessResult::forbiddenIf(in_array($field_definition->getName(), $deny_fields, TRUE), 'Field access denied by test module');
  }

  /**
   * Implements hook_entity_type_alter().
   */
  #[Hook('entity_type_alter')]
  public function entityTypeAlter(array &$entity_types) : void {
    if (isset($entity_types['node'])) {
      $entity_types['node']->setFormClass('default', TestNodeFormOverride::class);
      $entity_types['node']->setFormClass('edit', TestNodeFormOverride::class);
    }
  }

  /**
   * Implements hook_field_widget_info_alter().
   */
  #[Hook('field_widget_info_alter')]
  public function fieldWidgetInfoAlter(array &$info): void {
    $info['media_library_widget']['field_types'][] = 'entity_reference_subclass';
  }

}
