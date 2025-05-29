<?php

declare(strict_types=1);

namespace Drupal\block_test\Hook;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Block\BlockPluginInterface;
use Drupal\Core\Hook\Attribute\Hook;

/**
 * Hook implementations for block_test.
 */
class BlockTestHooks {

  /**
   * Implements hook_block_alter().
   */
  #[Hook('block_alter')]
  public function blockAlter(&$block_info): void {
    if (\Drupal::state()->get('block_test_info_alter') && isset($block_info['test_block_instantiation'])) {
      $block_info['test_block_instantiation']['category'] = 'Custom category';
    }
  }

  /**
   * Implements hook_block_view_BASE_BLOCK_ID_alter().
   */
  #[Hook('block_view_test_cache_alter')]
  public function blockViewTestCacheAlter(array &$build, BlockPluginInterface $block): void {
    if (\Drupal::state()->get('block_test_view_alter_suffix') !== NULL) {
      $build['#attributes']['foo'] = 'bar';
    }
    if (\Drupal::state()->get('block_test_view_alter_append_pre_render_prefix') !== NULL) {
      $build['#pre_render'][] = '\Drupal\block_test\BlockRenderAlterContent::preRender';
    }
  }

  /**
   * Implements hook_block_view_BASE_BLOCK_ID_alter().
   *
   * @see \Drupal\Tests\block\Kernel\BlockViewBuilderTest::testBlockViewBuilderCacheTitleBlock()
   */
  #[Hook('block_view_page_title_block_alter')]
  public function blockViewPageTitleBlockAlter(array &$build, BlockPluginInterface $block): void {
    $build['#cache']['tags'][] = 'custom_cache_tag';
  }

  /**
   * Implements hook_block_build_BASE_BLOCK_ID_alter().
   */
  #[Hook('block_build_test_cache_alter')]
  public function blockBuildTestCacheAlter(array &$build, BlockPluginInterface $block): void {
    // Test altering cache keys, contexts, tags and max-age.
    if (\Drupal::state()->get('block_test_block_alter_cache_key') !== NULL) {
      $build['#cache']['keys'][] = \Drupal::state()->get('block_test_block_alter_cache_key');
    }
    if (\Drupal::state()->get('block_test_block_alter_cache_context') !== NULL) {
      $build['#cache']['contexts'][] = \Drupal::state()->get('block_test_block_alter_cache_context');
    }
    if (\Drupal::state()->get('block_test_block_alter_cache_tag') !== NULL) {
      $build['#cache']['tags'] = Cache::mergeTags($build['#cache']['tags'], [\Drupal::state()->get('block_test_block_alter_cache_tag')]);
    }
    if (\Drupal::state()->get('block_test_block_alter_cache_max_age') !== NULL) {
      $build['#cache']['max-age'] = \Drupal::state()->get('block_test_block_alter_cache_max_age');
    }
    // Test setting #create_placeholder.
    if (\Drupal::state()->get('block_test_block_alter_create_placeholder') !== NULL) {
      $build['#create_placeholder'] = \Drupal::state()->get('block_test_block_alter_create_placeholder');
    }
  }

}
