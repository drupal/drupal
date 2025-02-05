<?php

declare(strict_types=1);

namespace Drupal\views_test_data\Hook;

use Drupal\views\Analyzer;
use Drupal\views\ViewExecutable;
use Drupal\Core\Hook\Attribute\Hook;

/**
 * Hook implementations for views_test_data.
 */
class ViewsTestDataViewsHooks {

  /**
   * Implements hook_views_data().
   */
  #[Hook('views_data')]
  public function viewsData(): array {
    $state = \Drupal::service('state');
    $state->set('views_hook_test_views_data', TRUE);
    // We use a state variable to keep track of how many times this function is
    // called so we can assert that calls to
    // \Drupal\views\ViewsData::delete() trigger a rebuild of views data.
    if (!($count = $state->get('views_test_data_views_data_count'))) {
      $count = 0;
    }
    $count++;
    $state->set('views_test_data_views_data_count', $count);
    return $state->get('views_test_data_views_data', []);
  }

  /**
   * Implements hook_views_data_alter().
   */
  #[Hook('views_data_alter')]
  public function viewsDataAlter(array &$data): void {
    \Drupal::state()->set('views_hook_test_views_data_alter', TRUE);
    \Drupal::state()->set('views_hook_test_views_data_alter_data', $data);
  }

  /**
   * Implements hook_views_analyze().
   */
  #[Hook('views_analyze')]
  public function viewsAnalyze(ViewExecutable $view): array {
    \Drupal::state()->set('views_hook_test_views_analyze', TRUE);
    $ret = [];
    $ret[] = Analyzer::formatMessage('Test ok message', 'ok');
    $ret[] = Analyzer::formatMessage('Test warning message', 'warning');
    $ret[] = Analyzer::formatMessage('Test error message', 'error');
    return $ret;
  }

  /**
   * Implements hook_views_invalidate_cache().
   */
  #[Hook('views_invalidate_cache')]
  public function viewsInvalidateCache(): void {
    \Drupal::state()->set('views_hook_test_views_invalidate_cache', TRUE);
  }

}
