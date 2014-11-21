<?php

/**
 * @file
 * Provides views data and hooks for views_test_data module.
 */

use Drupal\field\FieldStorageConfigInterface;
use Drupal\views\ViewExecutable;
use Drupal\views\ViewStorageInterface;

/**
 * Implements hook_views_query_substitutions().
 */
function views_test_data_views_query_substitutions(ViewExecutable $view) {
  \Drupal::state()->set('views_hook_test_views_query_substitutions', TRUE);
}

/**
 * Implements hook_views_form_substitutions().
 */
function views_test_data_views_form_substitutions(ViewExecutable $view) {
  \Drupal::state()->set('views_hook_test_views_form_substitutions', TRUE);
}

/**
 * Implements hook_field_views_data().
 */
function views_test_data_field_views_data(FieldStorageConfigInterface $field_storage) {
  \Drupal::state()->set('views_hook_test_field_views_data', TRUE);
}

/**
 * Implements hook_field_views_data_alter().
 */
function views_test_data_field_views_data_alter(&$data, FieldStorageConfigInterface $field_storage, $module) {
  \Drupal::state()->set('views_hook_test_field_views_data_alter', TRUE);
}

/**
 * Implements hook_views_pre_render().
 *
 * @see \Drupal\views\Tests\Plugin\CacheTest
 * @see \Drupal\views\Tests\Plugin\RenderTest
 */
function views_test_data_views_pre_render(ViewExecutable $view) {
  \Drupal::state()->set('views_hook_test_views_pre_render', TRUE);

  if (isset($view) && ($view->storage->id() == 'test_cache_header_storage')) {
    $path = drupal_get_path('module', 'views_test_data');
    $view->element['#attached']['library'][] = 'views_test_data/test';
    $view->element['#attached']['js'][] = "$path/views_cache.test.js";
    $view->element['#attached']['css'][] = "$path/views_cache.test.css";
    $view->element['#cache']['tags'][] = 'views_test_data:1';
    $view->element['#post_render_cache']['views_test_data_post_render_cache'][] = ['foo' => 'bar'];
    $view->build_info['pre_render_called'] = TRUE;
  }

}

/**
 * #post_render_cache callback; for testing purposes only.
 */
function views_test_data_post_render_cache(array $element, array $context) {
  // No-op.
  return $element;
}

/**
 * Implements hook_views_post_render().
 */
function views_test_data_views_post_render(ViewExecutable $view) {
  \Drupal::state()->set('views_hook_test_views_post_render', TRUE);
}

/**
 * Implements hook_views_pre_build().
 */
function views_test_data_views_pre_build(ViewExecutable $view) {
  \Drupal::state()->set('views_hook_test_views_pre_build', TRUE);
}

/**
 * Implements hook_views_post_build().
 */
function views_test_data_views_post_build(ViewExecutable $view) {
  \Drupal::state()->set('views_hook_test_views_post_build', TRUE);

  if (isset($view) && ($view->storage->id() == 'test_page_display')) {
    if ($view->current_display == 'page_1') {
      $view->build_info['denied'] = TRUE;
    }
    elseif ($view->current_display == 'page_2') {
      $view->build_info['fail'] = TRUE;
    }
  }
}

/**
 * Implements hook_views_pre_view().
 */
function views_test_data_views_pre_view(ViewExecutable $view) {
  \Drupal::state()->set('views_hook_test_views_pre_view', TRUE);
}

/**
 * Implements hook_views_pre_execute().
 */
function views_test_data_views_pre_execute(ViewExecutable $view) {
  \Drupal::state()->set('views_hook_test_views_pre_execute', TRUE);
}

/**
 * Implements hook_views_post_execute().
 */
function views_test_data_views_post_execute(ViewExecutable $view) {
  \Drupal::state()->set('views_hook_test_views_post_execute', TRUE);
}

/**
 * Implements hook_views_query_alter().
 */
function views_test_data_views_query_alter(ViewExecutable $view) {
  \Drupal::state()->set('views_hook_test_views_query_alter', TRUE);
}
