<?php

declare(strict_types=1);

namespace Drupal\views_entity_test\Hook;

use Drupal\Core\Access\AccessResultInterface;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Hook\Attribute\Hook;

/**
 * Hook implementations for views_entity_test.
 */
class ViewsEntityTestHooks {

  /**
   * Implements hook_entity_base_field_info().
   */
  #[Hook('entity_base_field_info')]
  public function entityBaseFieldInfo(EntityTypeInterface $entity_type): array {
    if ($entity_type->id() == 'entity_test') {
      $definitions['test_text_access'] = BaseFieldDefinition::create('string')
        ->setLabel('Test access')
        ->setTranslatable(FALSE)
        ->setSetting('max_length', 64)
        ->setDisplayOptions('form', [
          'type' => 'string_textfield',
          'weight' => 10,
        ]);
      return $definitions;
    }
    return [];
  }

  /**
   * Implements hook_entity_field_access().
   *
   * @see \Drupal\system\Tests\Entity\FieldAccessTest::testFieldAccess()
   */
  #[Hook('entity_field_access')]
  public function entityFieldAccess($operation, FieldDefinitionInterface $field_definition, AccountInterface $account, ?FieldItemListInterface $items = NULL): AccessResultInterface {
    if ($field_definition->getName() == 'test_text_access') {
      if ($items) {
        if ($items->value == 'no access value') {
          return AccessResult::forbidden()->addCacheableDependency($items->getEntity());
        }
      }
    }
    // No opinion.
    return AccessResult::neutral();
  }

  /**
   * Implements hook_entity_load().
   *
   * @see \Drupal\Tests\views\Kernel\Handler\FieldFieldTest::testSimpleExecute()
   */
  #[Hook('entity_load')]
  public function entityLoad(array $entities, $entity_type_id): void {
    if ($entity_type_id === 'entity_test') {
      // Cast the value of an entity field to be something else than a string so
      // we can check that
      // \Drupal\views\Tests\ViewResultAssertionTrait::assertIdenticalResultsetHelper()
      // takes care of converting all field values to strings.
      foreach ($entities as $entity) {
        $entity->user_id->target_id = (int) $entity->user_id->target_id;
      }
    }
  }

}
