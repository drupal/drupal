<?php

/**
 * @file
 * Describes API functions for tour module.
 */

/**
 * Allow modules to alter tour items before render.
 *
 * @param array $tour_items
 *   Array of \Drupal\tour\TipPluginInterface items.
 * @param string $path
 *   The path for which the tour is valid.
 */
function hook_tour_tips_alter(array &$tour_tips, $path) {
  foreach ($tour_tips as $tour_tip) {
    if ($tour_tip->get('id') == 'tour-code-test-1') {
      $tour_tip->set('body', 'Altered by hook_tour_tips_alter');
    }
  }
}

/**
 * Act on tour objects when loaded.
 *
 * @param array $entities
 *   An array of \Drupal\tour\Plugin\Core\Entity\Tour objects, indexed by id.
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
 * @param \Drupal\tour\Plugin\Core\Entity\Tour $entity
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
 * @param \Drupal\tour\Plugin\Core\Entity\Tour $entity
 *   The tour object being inserted.
 */
function hook_tour_insert($entity) {
  drupal_container()->get('plugin.manager.tour')->clearCachedDefinitions();
  cache('cache_tour')->deleteTags(array('tour_items'));
}

/**
 * Respond to updates to a tour object.
 *
 * @param \Drupal\tour\Plugin\Core\Entity\Tour $entity
 *   The tour object being updated.
 */
function hook_tour_update($entity) {
  drupal_container()->get('plugin.manager.tour')->clearCachedDefinitions();
  cache('cache_tour')->deleteTags(array('tour_items'));
}
