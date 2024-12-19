<?php

declare(strict_types=1);

namespace Drupal\views_test_data\Hook;

use Drupal\views\Plugin\views\cache\CachePluginBase;
use Drupal\field\FieldStorageConfigInterface;
use Drupal\views\ViewExecutable;
use Drupal\Core\Hook\Attribute\Hook;

/**
 * Hook implementations for views_test_data.
 */
class ViewsTestDataViewsExecutionHooks {

  /**
   * Implements hook_views_query_substitutions().
   */
  #[Hook('views_query_substitutions')]
  public function viewsQuerySubstitutions(ViewExecutable $view): array {
    \Drupal::state()->set('views_hook_test_views_query_substitutions', TRUE);
    return [];
  }

  /**
   * Implements hook_views_form_substitutions().
   */
  #[Hook('views_form_substitutions')]
  public function viewsFormSubstitutions(): array {
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
  #[Hook('field_views_data')]
  public function fieldViewsData(FieldStorageConfigInterface $field_storage): array {
    \Drupal::state()->set('views_hook_test_field_views_data', TRUE);
    return [];
  }

  /**
   * Implements hook_field_views_data_alter().
   */
  #[Hook('field_views_data_alter')]
  public function fieldViewsDataAlter(&$data, FieldStorageConfigInterface $field_storage, $module): void {
    \Drupal::state()->set('views_hook_test_field_views_data_alter', TRUE);
  }

  /**
   * Implements hook_views_pre_render().
   *
   * @see \Drupal\views\Tests\Plugin\CacheTest
   * @see \Drupal\views\Tests\Plugin\RenderTest
   */
  #[Hook('views_pre_render')]
  public function viewsPreRender(ViewExecutable $view): void {
    \Drupal::state()->set('views_hook_test_views_pre_render', TRUE);
    if (isset($view) && $view->storage->id() == 'test_cache_header_storage') {
      $view->element['#attached']['library'][] = 'views_test_data/test';
      $view->element['#attached']['drupalSettings']['foo'] = 'bar';
      $view->element['#attached']['placeholders']['non-existing-placeholder-just-for-testing-purposes']['#lazy_builder'] = [
        'Drupal\views_test_data\Controller\ViewsTestDataController::placeholderLazyBuilder',
            [
              'bar',
            ],
      ];
      $view->element['#cache']['tags'][] = 'views_test_data:1';
      $view->build_info['pre_render_called'] = TRUE;
    }
  }

  /**
   * Implements hook_views_post_render().
   */
  #[Hook('views_post_render')]
  public function viewsPostRender(ViewExecutable $view, &$output, CachePluginBase $cache): void {
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
  #[Hook('views_pre_build')]
  public function viewsPreBuild(ViewExecutable $view): void {
    \Drupal::state()->set('views_hook_test_views_pre_build', TRUE);
  }

  /**
   * Implements hook_views_post_build().
   */
  #[Hook('views_post_build')]
  public function viewsPostBuild(ViewExecutable $view): void {
    \Drupal::state()->set('views_hook_test_views_post_build', TRUE);
    if (isset($view) && $view->storage->id() == 'test_page_display') {
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
  #[Hook('views_pre_view')]
  public function viewsPreView(ViewExecutable $view): void {
    \Drupal::state()->set('views_hook_test_views_pre_view', TRUE);
  }

  /**
   * Implements hook_views_pre_execute().
   */
  #[Hook('views_pre_execute')]
  public function viewsPreExecute(ViewExecutable $view): void {
    \Drupal::state()->set('views_hook_test_views_pre_execute', TRUE);
  }

  /**
   * Implements hook_views_post_execute().
   */
  #[Hook('views_post_execute')]
  public function viewsPostExecute(ViewExecutable $view): void {
    \Drupal::state()->set('views_hook_test_views_post_execute', TRUE);
  }

  /**
   * Implements hook_views_query_alter().
   */
  #[Hook('views_query_alter')]
  public function viewsQueryAlter(ViewExecutable $view): void {
    \Drupal::state()->set('views_hook_test_views_query_alter', TRUE);
  }

}
