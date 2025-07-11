<?php

namespace Drupal\views_ui\Hook;

use Drupal\Component\Utility\Xss;
use Drupal\Core\Hook\Attribute\Hook;

/**
 * Hook implementations for views_ui.
 */
class ViewsUiThemeHooks {

  /**
   * Implements hook_preprocess_HOOK() for views templates.
   */
  #[Hook('preprocess_views_view')]
  public function preprocessViewsView(&$variables): void {
    $view = $variables['view'];
    // Render title for the admin preview.
    if (!empty($view->live_preview)) {
      $variables['title'] = [
        '#markup' => $view->getTitle(),
        '#allowed_tags' => Xss::getHtmlTagList(),
      ];
    }
    if (!empty($view->live_preview) && \Drupal::moduleHandler()->moduleExists('contextual')) {
      $view->setShowAdminLinks(FALSE);
      foreach ([
        'title',
        'header',
        'exposed',
        'rows',
        'pager',
        'more',
        'footer',
        'empty',
        'attachment_after',
        'attachment_before',
      ] as $section) {
        if (!empty($variables[$section])) {
          $variables[$section] = [
            '#theme' => 'views_ui_view_preview_section',
            '#view' => $view,
            '#section' => $section,
            '#content' => $variables[$section],
            '#theme_wrappers' => [
              'views_ui_container',
            ],
            '#attributes' => [
              'class' => [
                'contextual-region',
              ],
            ],
          ];
        }
      }
    }
  }

  /**
   * Implements hook_theme_suggestions_HOOK().
   */
  #[Hook('theme_suggestions_views_ui_view_preview_section')]
  public function themeSuggestionsViewsUiViewPreviewSection(array $variables): array {
    return [
      'views_ui_view_preview_section__' . $variables['section'],
    ];
  }

}
