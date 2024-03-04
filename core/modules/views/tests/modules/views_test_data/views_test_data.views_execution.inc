<?php

/**
 * @file
 * Provides views data and hooks for views_test_data module.
 */

use Drupal\field\FieldStorageConfigInterface;
use Drupal\views\Plugin\views\cache\CachePluginBase;
use Drupal\views\ViewExecutable;

/**
 * Implements hook_views_query_substitutions().
 */
function views_test_data_views_query_substitutions(ViewExecutable $view) {
  \Drupal::state()->set('views_hook_test_views_query_substitutions', TRUE);
}

/**
 * Implements hook_views_form_substitutions().
 */
function views_test_data_views_form_substitutions() {
  \Drupal::state()->set('views_hook_test_views_form_substitutions', TRUE);
  $render = ['#markup' => '<em>unescaped</em>'];
  return [
    '<!--will-be-escaped-->' => '<em>escaped</em>',
    '<!--will-be-not-escaped-->' => \Drupal::service('renderer')->renderInIsolation($render),
  ];
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
    $view->element['#attached']['library'][] = 'views_test_data/test';
    $view->element['#attached']['drupalSettings']['foo'] = 'bar';
    $view->element['#attached']['placeholders']['non-existing-placeholder-just-for-testing-purposes']['#lazy_builder'] = ['Drupal\views_test_data\Controller\ViewsTestDataController::placeholderLazyBuilder', ['bar']];
    $view->element['#cache']['tags'][] = 'views_test_data:1';
    $view->build_info['pre_render_called'] = TRUE;
  }

}

/**
 * Implements hook_views_post_render().
 */
function views_test_data_views_post_render(ViewExecutable $view, &$output, CachePluginBase $cache) {
  \Drupal::state()->set('views_hook_test_views_post_render', TRUE);
  if ($view->storage->id() === 'test_page_display' && $view->current_display === 'empty_row') {
    for ($i = 0; $i < 5; $i++) {
      $output['#rows'][0]['#rows'][] = [];
    }
  }
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
