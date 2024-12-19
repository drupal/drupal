<?php

namespace Drupal\views\Hook;

use Drupal\views\Plugin\views\PluginBase;
use Drupal\views\ViewExecutable;
use Drupal\Core\Hook\Attribute\Hook;

/**
 * Hook implementations for views.
 */
class ViewsViewsExecutionHooks {

  /**
   * Implements hook_views_query_substitutions().
   *
   * Makes the following substitutions:
   * - Current time.
   * - Drupal version.
   * - Special language codes; see
   *   \Drupal\views\Plugin\views\PluginBase::listLanguages().
   */
  #[Hook('views_query_substitutions')]
  public function viewsQuerySubstitutions(ViewExecutable $view): array {
    $substitutions = [
      '***CURRENT_VERSION***' => \Drupal::VERSION,
      '***CURRENT_TIME***' => \Drupal::time()->getRequestTime(),
    ] + PluginBase::queryLanguageSubstitutions();
    return $substitutions;
  }

  /**
   * Implements hook_views_form_substitutions().
   */
  #[Hook('views_form_substitutions')]
  public function viewsFormSubstitutions(): array {
    $select_all = [
      '#type' => 'checkbox',
      '#default_value' => FALSE,
      '#attributes' => [
        'class' => [
          'action-table-select-all',
        ],
      ],
    ];
    return [
      '<!--action-bulk-form-select-all-->' => \Drupal::service('renderer')->render($select_all),
    ];
  }

}
