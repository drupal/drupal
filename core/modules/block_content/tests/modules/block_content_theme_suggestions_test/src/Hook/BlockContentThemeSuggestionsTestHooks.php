<?php

declare(strict_types=1);

namespace Drupal\block_content_theme_suggestions_test\Hook;

use Drupal\Core\Entity\Display\EntityViewDisplayInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Hook implementations for block_content_theme_suggestions_test.
 */
class BlockContentThemeSuggestionsTestHooks {

  use StringTranslationTrait;

  /**
   * Implements hook_entity_extra_field_info().
   */
  #[Hook('entity_extra_field_info')]
  public function entityExtraFieldInfo(): array {
    // Add an extra field to the test bundle.
    $extra['node']['bundle_with_extra_field']['display']['block_content_extra_field_test'] = [
      'label' => $this->t('Extra field'),
      'description' => $this->t('Extra field description'),
      'weight' => 0,
    ];
    return $extra;
  }

  /**
   * Implements hook_node_view().
   */
  #[Hook('node_view')]
  public function nodeView(array &$build, EntityInterface $entity, EntityViewDisplayInterface $display, string $view_mode): void {
    // Provide content for the extra field in the form of a content block.
    if ($display->getComponent('block_content_extra_field_test')) {
      $entity_type_manager = \Drupal::entityTypeManager();
      // Load a block content entity with a known UUID created by test setup.
      // @see \Drupal\Tests\block_content\Functional\BlockContentThemeSuggestionsTest::setUp()
      $block_content = $entity_type_manager->getStorage('block_content')->loadByProperties([
        'uuid' => 'b22c881a-bcfd-4d0c-a41d-3573327705df',
      ]);
      $block_content = reset($block_content);
      $build['block_content_extra_field_test'] = $entity_type_manager->getViewBuilder('block_content')->view($block_content);
    }
  }

  /**
   * Implements hook_theme().
   */
  #[Hook('theme')]
  public function theme(): array {
    // It is necessary to explicitly register the template via hook_theme()
    // because it is added via a module, not a theme.
    return [
      'block__block_content__view_type__basic__full' => [
        'base hook' => 'block',
      ],
    ];
  }

}
