<?php

declare(strict_types=1);

namespace Drupal\views_ui_test_theme\Hook;

use Drupal\views_ui\ViewUI;
use Drupal\Core\Hook\Attribute\Hook;

/**
 * Hook implementations for views_ui_test_theme.
 */
class ViewsUiTestThemeHooks {

  /**
   * Implements hook_views_ui_display_tab_alter().
   */
  #[Hook('views_ui_display_tab_alter')]
  public function viewsUiDisplayTabAlter(&$build, ViewUI $view, $display_id): void {
    $build['details']['top']['display_title']['#description'] = 'This is text added to the display edit form';
  }

  /**
   * Implements hook_views_ui_display_top_alter().
   */
  #[Hook('views_ui_display_top_alter')]
  public function viewsUiDisplayTopAlter(&$build, ViewUI $view, $display_id): void {
    $build['tabs']['#suffix'] .= 'This is text added to the display tabs at the top';
  }

}
