<?php

namespace Drupal\tour;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Entity\EntityViewBuilder;
use Drupal\Component\Utility\Html;

/**
 * Provides a Tour view builder.
 *
 * Note: Does not invoke any alter hooks. In other view
 * builders, the view alter hooks are run later in the process
 */
class TourViewBuilder extends EntityViewBuilder {

  /**
   * {@inheritdoc}
   */
  public function viewMultiple(array $entities = [], $view_mode = 'full', $langcode = NULL) {
    /** @var \Drupal\tour\TourInterface[] $entities */
    $tour = [];
    $cache_tags = [];
    $total_tips = 0;
    foreach ($entities as $entity_id => $entity) {
      $tour[$entity_id] = $entity->getTips();
      $total_tips += count($tour[$entity_id]);
      $cache_tags = Cache::mergeTags($cache_tags, $entity->getCacheTags());
    }

    $items = [];
    foreach ($tour as $tour_id => $tips) {
      $tourEntity = $entities[$tour_id];

      foreach ($tips as $index => $tip) {
        $classes = [
          'tip-module-' . Html::getClass($tourEntity->getModule()),
          'tip-type-' . Html::getClass($tip->getPluginId()),
          'tip-' . Html::getClass($tip->id()),
        ];

        $selector = $tip->getSelector();
        $location = $tip->getLocation();

        $body_render_array = $tip->getBody();
        $body = (string) \Drupal::service('renderer')->renderInIsolation($body_render_array);
        $output = [
          'body' => $body,
          'title' => $tip->getLabel(),
        ];

        $selector = $tip->getSelector();

        if ($output) {
          $items[] = [
            'id' => $tip->id(),
            'selector' => $selector,
            'module' => $tourEntity->getModule(),
            'type' => $tip->getPluginId(),
            'counter' => $this->t('@tour_item of @total', [
              '@tour_item' => $index + 1,
              '@total' => $total_tips,
            ]),
            'attachTo' => [
              'element' => $selector,
              'on' => $location ?? 'bottom-start',
            ],
            // Shepherd expects classes to be provided as a string.
            'classes' => implode(' ', $classes),
          ] + $output;
        }
      }
    }

    // If there is at least one tour item, build the tour.
    if ($items) {
      $key = array_key_last($items);
      $items[$key]['cancelText'] = t('End tour');
    }

    $build = [
      '#cache' => [
        'tags' => $cache_tags,
      ],
    ];

    // If at least one tour was built, attach tips and the tour library.
    if ($items) {
      $build['#attached']['drupalSettings']['tourShepherdConfig'] = [
        'defaultStepOptions' => [
          'classes' => 'drupal-tour',
          'cancelIcon' => [
            'enabled' => TRUE,
            'label' => $this->t('Close'),
          ],
          'modalOverlayOpeningPadding' => 3,
          'scrollTo' => [
            'behavior' => 'smooth',
            'block' => 'center',
          ],
          'popperOptions' => [
            'modifiers' => [
              // Prevent overlap with the element being highlighted.
              [
                'name' => 'offset',
                'options' => [
                  'offset' => [-10, 20],
                ],
              ],
              // Pad the arrows so they don't hit the edge of rounded corners.
              [
                'name' => 'arrow',
                'options' => [
                  'padding' => 12,
                ],
              ],
              // Disable Shepherd's focusAfterRender modifier, which results in
              // the tour item container being focused on any scroll or resize
              // event.
              [
                'name' => 'focusAfterRender',
                'enabled' => FALSE,
              ],

            ],
          ],
        ],
        'useModalOverlay' => TRUE,
      ];
      // This property is used for storing the tour items. It may change without
      // notice and should not be extended or modified in contrib.
      // see: https://www.drupal.org/project/drupal/issues/3214593
      $build['#attached']['drupalSettings']['_tour_internal'] = $items;
      $build['#attached']['library'][] = 'tour/tour';
    }
    return $build;
  }

}
