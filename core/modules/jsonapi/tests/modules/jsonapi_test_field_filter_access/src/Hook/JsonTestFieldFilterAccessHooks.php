<?php

declare(strict_types=1);

namespace Drupal\jsonapi_test_field_filter_access\Hook;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Access\AccessResultInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\Session\AccountInterface;

/**
 * Hook implementations for jsonapi_test_field_filter_access.
 */
class JsonTestFieldFilterAccessHooks {

  /**
   * Implements hook_jsonapi_entity_field_filter_access().
   */
  #[Hook('jsonapi_entity_field_filter_access')]
  public function jsonapiEntityFieldFilterAccess(FieldDefinitionInterface $field_definition, AccountInterface $account): AccessResultInterface {
    if ($field_definition->getName() === 'spotlight') {
      return AccessResult::forbiddenIf(!$account->hasPermission('filter by spotlight field'))->cachePerPermissions();
    }
    if ($field_definition->getName() === 'field_test_text') {
      return AccessResult::allowedIf($field_definition->getTargetEntityTypeId() === 'entity_test_with_bundle');
    }
    return AccessResult::neutral();
  }

}
