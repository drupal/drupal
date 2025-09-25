<?php

declare(strict_types=1);

namespace Drupal\views_test_data\Hook;

use Drupal\Core\Hook\Attribute\Hook;

/**
 * Hook implementations for views_test_data.
 */
class ViewsTestDataThemeHooks {

  /**
   * Implements hook_preprocess_HOOK() for views table templates.
   */
  #[Hook('preprocess_views_view_table')]
  public function preprocessViewsViewTable(&$variables): void {
    if ($variables['view']->storage->id() == 'test_view_render') {
      $views_render_test = \Drupal::state()->get('views_render.test');
      $views_render_test++;
      \Drupal::state()->set('views_render.test', $views_render_test);
    }
  }

  /**
   * Prepares variables for the mapping row style test templates.
   *
   * Default template: views-view-mapping-test.html.twig.
   *
   * @param array $variables
   *   An associative array containing:
   *   - rows: A list of view rows.
   *   - options: Various view options, including the row style mapping.
   *   - view: The view object.
   */
  public function preprocessViewsViewMappingTest(array &$variables): void {
    $variables['element'] = [];

    foreach ($variables['rows'] as $delta => $row) {
      $fields = [];
      foreach ($variables['options']['mapping'] as $type => $field_names) {
        if (!is_array($field_names)) {
          $field_names = [$field_names];
        }
        foreach ($field_names as $field_name) {
          if ($value = $variables['view']->style_plugin->getField($delta, $field_name)) {
            $fields[$type . '-' . $field_name] = $type . ':' . $value;
          }
        }
      }

      // If there are no fields in this row, skip to the next one.
      if (empty($fields)) {
        continue;
      }

      // Build a container for the row.
      $variables['element'][$delta] = [
        '#type' => 'container',
        '#attributes' => [
          'class' => [
            'views-row-mapping-test',
          ],
        ],
      ];

      // Add each field to the row.
      foreach ($fields as $key => $render) {
        $variables['element'][$delta][$key] = [
          '#children' => $render,
          '#type' => 'container',
          '#attributes' => [
            'class' => [
              $key,
            ],
          ],
        ];
      }
    }
  }

}
