<?php

/**
 * @file
 * Contains \Drupal\tour\TourViewBuilder.
 */

namespace Drupal\tour;

use Drupal\Core\Entity\EntityViewBuilder;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Component\Utility\Html;

/**
 * Provides a Tour view builder.
 */
class TourViewBuilder extends EntityViewBuilder {

  /**
   * {@inheritdoc}
   */
  public function viewMultiple(array $entities = array(), $view_mode = 'full', $langcode = NULL) {
    /** @var \Drupal\tour\TourInterface[] $entities */
    $build = array();
    foreach ($entities as $entity_id => $entity) {
      $tips = $entity->getTips();
      $count = count($tips);
      $list_items = array();
      foreach ($tips as $index => $tip) {
        if ($output = $tip->getOutput()) {
          $attributes = array(
            'class' => array(
              'tip-module-' . Html::cleanCssIdentifier($entity->getModule()),
              'tip-type-' . Html::cleanCssIdentifier($tip->getPluginId()),
              'tip-' . Html::cleanCssIdentifier($tip->id()),
            ),
          );
          $list_items[] = array(
            'output' => $output,
            'counter' => array(
              '#type' => 'container',
              '#attributes' => array(
                'class' => array(
                  'tour-progress',
                ),
              ),
              '#children' => t('!tour_item of !total', array('!tour_item' => $index + 1, '!total' => $count)),
            ),
            '#wrapper_attributes' => $tip->getAttributes() + $attributes,
          );
        }
      }
      // If there is at least one tour item, build the tour.
      if ($list_items) {
        end($list_items);
        $key = key($list_items);
        $list_items[$key]['#wrapper_attributes']['data-text'] = t('End tour');
        $build[$entity_id] = array(
          '#theme' => 'item_list',
          '#items' => $list_items,
          '#list_type' => 'ol',
          '#attributes' => array(
            'id' => 'tour',
            'class' => array(
              'hidden',
            ),
          ),
          '#cache' => [
            'tags' => $entity->getCacheTags(),
          ],
        );
      }
    }
    // If at least one tour was built, attach the tour library.
    if ($build) {
      $build['#attached']['library'][] = 'tour/tour';
    }
    return $build;
  }

}
