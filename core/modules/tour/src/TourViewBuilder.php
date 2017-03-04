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
    $build = [];
    foreach ($entities as $entity_id => $entity) {
      $tips = $entity->getTips();
      $count = count($tips);
      $list_items = [];
      foreach ($tips as $index => $tip) {
        if ($output = $tip->getOutput()) {
          $attributes = [
            'class' => [
              'tip-module-' . Html::cleanCssIdentifier($entity->getModule()),
              'tip-type-' . Html::cleanCssIdentifier($tip->getPluginId()),
              'tip-' . Html::cleanCssIdentifier($tip->id()),
            ],
          ];
          $list_items[] = [
            'output' => $output,
            'counter' => [
              '#type' => 'container',
              '#attributes' => [
                'class' => [
                  'tour-progress',
                ],
              ],
              '#children' => t('@tour_item of @total', ['@tour_item' => $index + 1, '@total' => $count]),
            ],
            '#wrapper_attributes' => $tip->getAttributes() + $attributes,
          ];
        }
      }
      // If there is at least one tour item, build the tour.
      if ($list_items) {
        end($list_items);
        $key = key($list_items);
        $list_items[$key]['#wrapper_attributes']['data-text'] = t('End tour');
        $build[$entity_id] = [
          '#theme' => 'item_list',
          '#items' => $list_items,
          '#list_type' => 'ol',
          '#attributes' => [
            'id' => 'tour',
            'class' => [
              'hidden',
            ],
          ],
          '#cache' => [
            'tags' => $entity->getCacheTags(),
          ],
        ];
      }
    }
    // If at least one tour was built, attach the tour library.
    if ($build) {
      $build['#attached']['library'][] = 'tour/tour';
    }
    return $build;
  }

}
