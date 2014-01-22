<?php

/**
 * @file
 * Describes API functions for tour module.
 */

/**
 * Allow modules to alter tour items before render.
 *
 * @param array $tour_tips
 *   Array of \Drupal\tour\TipPluginInterface items.
 * @param \Drupal\Core\Entity\EntityInterface $entity
 *   The tour which contains the $tour_tips.
 */
function hook_tour_tips_alter(array &$tour_tips, Drupal\Core\Entity\EntityInterface $entity) {
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
 * Act on tour objects when loaded.
 *
 * @param array $entities
 *   An array of \Drupal\tour\Entity\Tour objects, indexed by id.
 */
function hook_tour_load($entities) {
  if (isset($entities['tour-entity-create-test-en'])) {
    $entities['tour-entity-create-test-en']->loaded = 'Load hooks work';
  }
}

/**
 * Act on a tour being inserted or updated.
 *
 * This hook is invoked before the tour object is saved to configuration.
 *
 * @param \Drupal\tour\Entity\Tour $entity
 *   The tour object.
 *
 * @see hook_tour_insert()
 * @see hook_tour_update()
 */
function hook_tour_presave($entity) {
  if ($entity->id() == 'tour-entity-create-test-en') {
    $entity->set('label', $entity->label() . ' alter');
  }
}

/**
 * Respond to creation of a new tour.
 *
 * @param \Drupal\tour\Entity\Tour $entity
 *   The tour object being inserted.
 */
function hook_tour_insert($entity) {
  \Drupal::service('plugin.manager.tour.tip')->clearCachedDefinitions();
  \Drupal\Core\Cache\Cache::deleteTags(array('tour_items'));
}

/**
 * Respond to updates to a tour object.
 *
 * @param \Drupal\tour\Entity\Tour $entity
 *   The tour object being updated.
 */
function hook_tour_update($entity) {
  \Drupal::service('plugin.manager.tour.tip')->clearCachedDefinitions();
  \Drupal\Core\Cache\Cache::deleteTags(array('tour_items'));
}
