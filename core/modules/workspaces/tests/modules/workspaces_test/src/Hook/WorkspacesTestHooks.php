<?php

declare(strict_types=1);

namespace Drupal\workspaces_test\Hook;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\KeyValueStore\KeyValueFactoryInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * Hook implementations for workspaces_test.
 */
class WorkspacesTestHooks {

  public function __construct(
    #[Autowire(service: 'keyvalue')]
    protected readonly KeyValueFactoryInterface $keyValueFactory,
  ) {}

  /**
   * Implements hook_entity_type_alter().
   */
  #[Hook('entity_type_alter')]
  public function entityTypeAlter(array &$entity_types) : void {
    $state = \Drupal::state();
    // Allow all entity types to have their definition changed dynamically for
    // testing purposes.
    foreach ($entity_types as $entity_type_id => $entity_type) {
      $entity_types[$entity_type_id] = $state->get("{$entity_type_id}.entity_type", $entity_types[$entity_type_id]);
    }
  }

  /**
   * Implements hook_ENTITY_TYPE_translation_create() for 'entity_test_mulrevpub'.
   */
  #[Hook('entity_test_mulrevpub_translation_create')]
  public function entityTranslationCreate(): void {
    /** @var \Drupal\workspaces\WorkspaceManagerInterface $workspace_manager */
    $workspace_manager = \Drupal::service('workspaces.manager');
    $this->keyValueFactory->get('ws_test')->set('workspace_was_active', $workspace_manager->hasActiveWorkspace());
  }

  /**
   * Implements hook_entity_create().
   */
  #[Hook('entity_create')]
  public function entityCreate(EntityInterface $entity): void {
    $this->incrementHookCount('hook_entity_create', $entity);
  }

  /**
   * Implements hook_entity_presave().
   */
  #[Hook('entity_presave')]
  public function entityPresave(EntityInterface $entity): void {
    $this->incrementHookCount('hook_entity_presave', $entity);
  }

  /**
   * Implements hook_entity_insert().
   */
  #[Hook('entity_insert')]
  public function entityInsert(EntityInterface $entity): void {
    $this->incrementHookCount('hook_entity_insert', $entity);
  }

  /**
   * Implements hook_entity_update().
   */
  #[Hook('entity_update')]
  public function entityUpdate(EntityInterface $entity): void {
    $this->incrementHookCount('hook_entity_update', $entity);
  }

  /**
   * Increments the invocation count for a specific entity hook.
   *
   * @param string $hook_name
   *   The name of the hook being invoked (e.g., 'hook_entity_create').
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity object involved in the hook.
   */
  protected function incrementHookCount(string $hook_name, EntityInterface $entity): void {
    $key = $entity->getEntityTypeId() . '.' . $hook_name . '.count';
    $count = $this->keyValueFactory->get('ws_test')->get($key, 0);
    $this->keyValueFactory->get('ws_test')->set($key, $count + 1);
  }

}
