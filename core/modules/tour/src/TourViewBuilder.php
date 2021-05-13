<?php

namespace Drupal\tour;

use Drupal\Core\Cache\Cache;
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

        $selector = NULL;
        $location = NULL;

        // @todo remove conditional in https://drupal.org/node/3195193, as all
        //   instances will already be instances of TourTipPluginInterface.
        if ($tip instanceof TourTipPluginInterface) {
          $body_render_array = $tip->getBody();
          $body = (string) \Drupal::service('renderer')->renderPlain($body_render_array);
          $output = [
            'body' => $body,
            'title' => Html::escape($tip->getLabel()),
          ];

          $location = $tip->getLocation();
          $selector = $tip->getSelector();
        }
        else {
          // This condition is met if the tip does not implement
          // TourTipPluginInterface. This means the tour tip must be constructed
          // with the deprecated getOutput() method. The resulting tour tip
          // should be largely identical, with the following exceptions:
          // 1 - If the tour tip `attributes` property included anything other
          //     than `data-class` or `data-id`, these additional attributes
          //     will not be available in the resulting tour tip. Note that such
          //     uses are uncommon.
          // 2 - Although the tour tip content is identical, the markup
          //     structure will be different due to being rendered by Shepherd
          //     instead of Joyride. Themes extending Stable or Stable 9 will
          //     not experience these changes as a script is provided that
          //     reconstructs each tip to match Joyride's markup structure.
          $tour_render_array = $tip->getOutput();
          if (!empty($tour_render_array)) {
            // The output render array intentionally omits title. The deprecated
            // getOutput() returns a render array with the title and main
            // content.
            $output = [
              'body' => (string) \Drupal::service('renderer')->renderPlain($tour_render_array),
            ];

            // Add a class so JavaScript in Stable themes can identify deprecated
            // tip plugins. The logic used to make markup backwards compatible
            // with Joyride is different depending on the type of
            // plugin used.
            $classes[] = 'tip-uses-getoutput';

            // `Selector` is a newer property that replaces `attributes`. It was
            // introduced alongside TourTipPluginInterface, but should be
            // present in most tip plugins, even those not implementing
            // TourTipPluginInterface, as tour_update_9200() converts this
            // property for all existing tours.
            // @see tour_update_9200()
            $selector = $tip->get('selector');

            // If a tour using the deprecated TipPluginInterface was installed
            // after tour_update_9200() ran, it may attributes instead of the
            // `selector` property to associate the tip with an element.
            // @see tour_update_9200()
            if (!$selector) {
              $attributes = $tip->get('attributes');
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
            $location = $tip->getLocation();

            // If $location is null, it's possible that a value is available
            // by directly accessing the `location` property. This can occur if
            // a tour with the deprecated `location` property was installed
            // after tour_update_9200() ran.
            // @see tour_update_9200()
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
              'counter' => $this->t('@tour_item of @total', [
                '@tour_item' => $index + 1,
                '@total' => $total_tips,
              ]),
              // Shepherd expects classes to be provided as a string.
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
