<?php

namespace Drupal\content_translation\Hook;

use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\Render\Element;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Hook implementations for content_translation.
 */
class ContentTranslationThemeHooks {

  use StringTranslationTrait;

  public function __construct(
    protected readonly RendererInterface $renderer,
    protected readonly AccountInterface $currentUser,
  ) {}

  /**
   * Implements hook_preprocess_HOOK() for language-content-settings-table.html.twig.
   */
  #[Hook('preprocess_language_content_settings_table')]
  public function preprocessLanguageContentSettingsTable(&$variables): void {
    // Alter the 'build' variable injecting the translation settings if the user
    // has the required permission.
    if (!$this->currentUser->hasPermission('administer content translation')) {
      return;
    }

    $element = $variables['element'];
    $build = &$variables['build'];

    array_unshift($build['#header'], ['data' => $this->t('Translatable'), 'class' => ['translatable']]);
    $rows = [];

    foreach (Element::children($element) as $bundle) {
      $field_names = !empty($element[$bundle]['fields']) ? Element::children($element[$bundle]['fields']) : [];
      $checkbox_id = '';
      if (!empty($element[$bundle]['translatable'])) {
        $checkbox_id = $element[$bundle]['translatable']['#id'];
      }
      $rows[$bundle] = $build['#rows'][$bundle];

      if (!empty($element[$bundle]['translatable'])) {
        $translatable = [
          'data' => $element[$bundle]['translatable'],
          'class' => ['translatable'],
        ];
        array_unshift($rows[$bundle]['data'], $translatable);

        $rows[$bundle]['data'][1]['data']['#prefix'] = '<label for="' . $checkbox_id . '">';
      }
      else {
        $translatable = [
          'data' => $this->t('N/A'),
          'class' => ['untranslatable'],
        ];
        array_unshift($rows[$bundle]['data'], $translatable);
      }

      foreach ($field_names as $field_name) {
        $field_element = &$element[$bundle]['fields'][$field_name];
        $rows[] = [
          'data' => [
            [
              'data' => $this->renderer->render($field_element),
              'class' => ['translatable'],
            ],
            [
              'data' => [
                '#prefix' => '<label for="' . $field_element['#id'] . '">',
                '#suffix' => '</label>',
                'bundle' => [
                  '#prefix' => '<span class="visually-hidden">',
                  '#suffix' => '</span> ',
                  '#plain_text' => $element[$bundle]['settings']['#label'],
                ],
                'field' => [
                  '#plain_text' => $field_element['#label'],
                ],
              ],
              'class' => ['field'],
            ],
            [
              'data' => '',
              'class' => ['operations'],
            ],
          ],
          '#field_name' => $field_name,
          'class' => ['field-settings'],
        ];

        if (!empty($element[$bundle]['columns'][$field_name])) {
          $column_element = &$element[$bundle]['columns'][$field_name];
          foreach (Element::children($column_element) as $key) {
            $column_label = $column_element[$key]['#title'];
            unset($column_element[$key]['#title']);
            $rows[] = [
              'data' => [
                [
                  'data' => $this->renderer->render($column_element[$key]),
                  'class' => ['translatable'],
                ],
                [
                  'data' => [
                    '#prefix' => '<label for="' . $column_element[$key]['#id'] . '">',
                    '#suffix' => '</label>',
                    'bundle' => [
                      '#prefix' => '<span class="visually-hidden">',
                      '#suffix' => '</span> ',
                      '#plain_text' => $element[$bundle]['settings']['#label'],
                    ],
                    'field' => [
                      '#prefix' => '<span class="visually-hidden">',
                      '#suffix' => '</span> ',
                      '#plain_text' => $field_element['#label'],
                    ],
                    'columns' => [
                      '#plain_text' => $column_label,
                    ],
                  ],
                  'class' => ['column'],
                ],
                [
                  'data' => '',
                  'class' => ['operations'],
                ],
              ],
              'class' => ['column-settings'],
            ];
          }
        }
      }
    }

    $build['#rows'] = $rows;
  }

}
