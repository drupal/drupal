<?php

declare(strict_types=1);

namespace Drupal\editor_test\Hook;

use Drupal\file\FileInterface;
use Drupal\filter\FilterFormatInterface;
use Drupal\node\NodeInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Hook\Attribute\Hook;

/**
 * Hook implementations for editor_test.
 */
class EditorTestHooks {

  /**
   * Implements hook_entity_update().
   *
   * @see \Drupal\Tests\editor\Kernel\EntityUpdateTest
   */
  #[Hook('entity_update')]
  public function entityUpdate(EntityInterface $entity): void {
    // Only act on nodes.
    if (!$entity instanceof NodeInterface) {
      return;
    }
    // Avoid infinite loop by only going through our post save logic once.
    if (!empty($entity->editor_test_updating)) {
      return;
    }
    // Set flag for whether or not the entity needs to be resaved.
    $needs_update = FALSE;
    // Perform our post save logic.
    if ($entity->title->value == 'test updated') {
      // Change the node title.
      $entity->title->value = 'test updated 2';
      $needs_update = TRUE;
    }
    if ($needs_update) {
      // Set flag on entity that our logic was already executed.
      $entity->editor_test_updating = TRUE;
      // And resave entity.
      $entity->save();
    }
  }

  /**
   * Implements hook_editor_js_settings_alter().
   */
  #[Hook('editor_js_settings_alter')]
  public function editorJsSettingsAlter(&$settings): void {
    // Allow tests to enable or disable this alter hook.
    if (!\Drupal::state()->get('editor_test_js_settings_alter_enabled', FALSE)) {
      return;
    }
    if (isset($settings['editor']['formats']['full_html'])) {
      $settings['editor']['formats']['full_html']['editorSettings']['ponyModeEnabled'] = FALSE;
    }
  }

  /**
   * Implements hook_editor_xss_filter_alter().
   */
  #[Hook('editor_xss_filter_alter')]
  public function editorXssFilterAlter(&$editor_xss_filter_class, FilterFormatInterface $format, ?FilterFormatInterface $original_format = NULL): void {
    // Allow tests to enable or disable this alter hook.
    if (!\Drupal::keyValue('editor_test')->get('editor_xss_filter_alter_enabled', FALSE)) {
      return;
    }
    $filters = $format->filters()->getAll();
    if (isset($filters['filter_html']) && $filters['filter_html']->status) {
      $editor_xss_filter_class = '\Drupal\editor_test\EditorXssFilter\Insecure';
    }
  }

  /**
   * Implements hook_editor_info_alter().
   */
  #[Hook('editor_info_alter')]
  public function editorInfoAlter(&$items): void {
    if (!\Drupal::state()->get('editor_test_give_me_a_trex_thanks', FALSE)) {
      unset($items['trex']);
    }
  }

  /**
   * Implements hook_ENTITY_TYPE_presave() for file entities.
   */
  #[Hook('file_presave')]
  public function filePresave(FileInterface $file): void {
    // Use state to keep track of how many times a file is saved.
    $file_save_count = \Drupal::state()->get('editor_test.file_save_count', []);
    $file_save_count[$file->getFilename()] = isset($file_save_count[$file->getFilename()]) ? $file_save_count[$file->getFilename()] + 1 : 1;
    \Drupal::state()->set('editor_test.file_save_count', $file_save_count);
  }

}
