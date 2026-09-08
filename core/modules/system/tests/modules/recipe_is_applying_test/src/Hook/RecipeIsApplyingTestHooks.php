<?php

declare(strict_types=1);

namespace Drupal\recipe_is_applying_test\Hook;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\KeyValueStore\KeyValueFactoryInterface;
use Drupal\Core\Recipe\RecipeRunner;

/**
 * Hook implementations for recipe_is_applying_test.
 */
class RecipeIsApplyingTestHooks {

  public function __construct(private KeyValueFactoryInterface $keyValueFactory) {
  }

  /**
   * Implements hook_modules_installed().
   */
  #[Hook('modules_installed')]
  public function modulesInstalled(array $modules, bool $is_syncing): void {
    $this->keyValueFactory->get('recipe_is_applying_test')->set('modules_installed', RecipeRunner::isApplying());
  }

  /**
   * Implements hook_themes_installed().
   */
  #[Hook('themes_installed')]
  public function themesInstalled(array $theme_list): void {
    $this->keyValueFactory->get('recipe_is_applying_test')->set('themes_installed', RecipeRunner::isApplying());
  }

  /**
   * Implements hook_ENTITY_TYPE_insert() for entity_test entities.
   */
  #[Hook('entity_test_insert')]
  public function entityTestInsert(EntityInterface $entity): void {
    $this->keyValueFactory->get('recipe_is_applying_test')->set('entity_test_insert', RecipeRunner::isApplying());
  }

}
