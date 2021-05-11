<?php

namespace Drupal\tour;

use Drupal\Core\Entity\EntityViewBuilder;
use Drupal\Component\Utility\Html;

/**
 * Provides a Tour view builder.
 */
class TourViewBuilder extends EntityViewBuilder {

  /**
   * {@inheritdoc}
   */
  public function viewMultiple(array $entities = [], $view_mode = 'full', $langcode = NULL) {
    /** @var \Drupal\tour\TourInterface[] $entities */
    $tour = [];
    $cacheTags = [];
    $totalTips = 0;
    foreach ($entities as $entity_id => $entity) {
      $tour[$entity_id] = $entity->getTips();
      $totalTips += count($tour[$entity_id]);
      $cacheTags = array_merge($cacheTags, $entity->getCacheTags());
    }

    $items = [];
    foreach ($tour as $tour_id => $tips) {
      $tourEntity = $entities[$tour_id];

      foreach ($tips as $index => $tip) {
        $classes = [
          'tip-module-' . Html::cleanCssIdentifier($tourEntity->getModule()),
          'tip-type-' . Html::cleanCssIdentifier($tip->getPluginId()),
          'tip-' . Html::cleanCssIdentifier($tip->id()),
        ];

        $selector = NULL;
        $location = NULL;

        // @todo remove conditional in https://drupal.org/node/3195193, as all
        //   instances will already be instances of TourTipPluginInterface.
        if ($tip instanceof TourTipPluginInterface) {
          $body_render_array = $tip->getBody();
          $body = \Drupal::service('renderer')->renderPlain($body_render_array)->__toString();
          $output = [
            'body' => $body,
            'title' => $tip->getTitle(),
          ];

          $location = $tip->getLocation();
          $selector = $tip->getSelector();
        }
        else {
          $tour_render_array = $tip->getOutput();
          if (!empty($tour_render_array)) {
            $output = [
              'body' => \Drupal::service('renderer')->renderPlain($tour_render_array)->__toString(),
            ];
            // Add a class so JavaScript in Stable themes can identify deprecated
            // tip plugins. The logic used by Stable to make the markup backwards
            // compatible with Joyride is different depending on the type of
            // plugin used.
            $classes[] = 'tip-uses-getoutput';

            $selector = $tip->get('selector');

            // If a tour using the deprecated TipPluginInterface was installed
            // after tour_update_9200() ran, it may attributes instead of the
            // `selector` property to associate the tip with an element.
            // @see tour_update_9200()
            if (!$selector) {
              $attributes = $tip->getAttributes();
              if (!empty($attributes['data-class'])) {
                $selector = ".{$attributes['data-class']}";
              }
              elseif (!empty($attributes['data-id'])) {
                $selector = "#{$attributes['data-id']}";
              }
            }

            // If this tip uses the deprecated TipPluginInterface but installed
            // after If the tip has been updated with tour_update_9200(), the
            // value will still be provided by `location`. This should only be
            // checked for if `position` does not return a value.
            // @see tour_update_9200()
            $location = $tip->get('position');
            if (!$location && $location = $tip->get('location')) {
              // If the `location` property still has a value, this means the tip
              // is configured for Joyride. The position value must be inverted
              // to work with Shepherd.
              $location_swap = [
                'top' => 'bottom',
                'bottom' => 'top',
                'left' => 'right',
                'right' => 'left',
              ];
              $location = $location_swap[$location];
            }
          }

        }

        if ($output) {
          $items[] = [
              'id' => $tip->id(),
              'selector' => $selector,
              'location' => $location,
              'module' => $tourEntity->getModule(),
              'type' => $tip->getPluginId(),
              'counter' => t('@tour_item of @total', [
                '@tour_item' => $index + 1,
                '@total' => $totalTips,
              ]),
              'classes' => implode(' ', $classes),
            ] + $output;
        }
      }
    }

    // If there is at least one tour item, build the tour.
    if ($items) {
      end($items);
      $key = key($items);
      $items[$key]['cancelText'] = t('End tour');
    }

    $build = [
      '#cache' => [
        'tags' => $cacheTags,
      ],
    ];

    // If at least one tour was built, attach tips and the tour library.
    if ($items) {
      $build['#attached']['drupalSettings']['tourShepherdConfig'] = [
        'defaultStepOptions' => [
          'classes' => 'drupal-tour',
          'cancelIcon' => [
            'enabled' => TRUE,
            'label' => t('Close'),
          ],
          'modalOverlayOpeningPadding' => 3,
          'scrollTo' => [
            'behavior' => 'smooth',
            'block' => 'center',
          ],
          'popperOptions' => [
            'modifiers' => [
              [
                'name' => 'offset',
                'options' => [
                  'offset' => [10, 20],
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
      $build['#attached']['drupalSettings']['tour'] = $items;
      $build['#attached']['library'][] = 'tour/tour';
    }
    return $build;
  }

}
