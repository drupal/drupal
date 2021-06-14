<?php

/**
 * @file
 * Describes API functions for tour module.
 */

/**
 * @addtogroup hooks
 * @{
 */

/**
 * Allow modules to alter tour items before render.
 *
 * @param array $tour_tips
 *   Array of \Drupal\tour\TipPluginInterface items.
 * @param \Drupal\Core\Entity\EntityInterface $entity
 *   The tour which contains the $tour_tips.
 */
function hook_tour_tips_alter(array &$tour_tips, \Drupal\Core\Entity\EntityInterface $entity) {
  foreach ($tour_tips as $tour_tip) {
    if ($tour_tip->get('id') == 'tour-code-test-1') {
      $tour_tip->set('body', 'Altered by hook_tour_tips_alter');
    }
  }
}

/**
 * Allow modules to alter tip plugin definitions.
 *
 * @param array $info
 *   The array of tip plugin definitions, keyed by plugin ID.
 *
 * @see \Drupal\tour\Annotation\Tip
 */
function hook_tour_tips_info_alter(&$info) {
  // Swap out the class used for this tip plugin.
  if (isset($info['text'])) {
    $info['class'] = 'Drupal\mymodule\Plugin\tour\tip\MyCustomTipPlugin';
  }
}

/**
 * @} End of "addtogroup hooks".
 */
