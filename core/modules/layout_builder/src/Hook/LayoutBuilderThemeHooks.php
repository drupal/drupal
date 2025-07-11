<?php

namespace Drupal\layout_builder\Hook;

use Drupal\layout_builder\Plugin\SectionStorage\OverridesSectionStorage;
use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Hook implementations for layout_builder.
 */
class LayoutBuilderThemeHooks {
  use StringTranslationTrait;

  /**
   * Implements hook_preprocess_HOOK() for language-content-settings-table.html.twig.
   */
  #[Hook('preprocess_language_content_settings_table')]
  public function preprocessLanguageContentSettingsTable(&$variables): void {
    foreach ($variables['build']['#rows'] as &$row) {
      if (isset($row['#field_name']) && $row['#field_name'] === OverridesSectionStorage::FIELD_NAME) {
        // Rebuild the label to include a warning about using translations with
        // layouts.
        $row['data'][1]['data']['field'] = [
          'label' => $row['data'][1]['data']['field'],
          'description' => [
            '#type' => 'container',
            '#markup' => $this->t('<strong>Warning</strong>: Layout Builder does not support translating layouts. (<a href="https://www.drupal.org/docs/8/core/modules/layout-builder/layout-builder-and-content-translation">online documentation</a>)'),
            '#attributes' => [
              'class' => [
                'layout-builder-translation-warning',
              ],
            ],
          ],
        ];
      }
    }
  }

}
