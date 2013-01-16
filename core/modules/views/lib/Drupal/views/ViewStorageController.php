<?php

/**
 * @file
 * Definition of Drupal\views\ViewStorageController.
 */

namespace Drupal\views;

use Drupal\Core\Config\Entity\ConfigStorageController;
use Drupal\Core\Entity\EntityInterface;

/**
 * Defines the storage controller class for View entities.
 */
class ViewStorageController extends ConfigStorageController {

  /**
   * Overrides Drupal\config\ConfigStorageController::load();
   */
  public function load(array $ids = NULL) {
    $entities = parent::load($ids);

    // Only return views for enabled modules.
    return array_filter($entities, function ($entity) {
      if (module_exists($entity->get('module'))) {
        return TRUE;
      }
      return FALSE;
    });
  }

  /**
   * Overrides Drupal\config\ConfigStorageController::attachLoad();
   */
  protected function attachLoad(&$queried_entities, $revision_id = FALSE) {
    foreach ($queried_entities as $id => $entity) {
      $this->mergeDefaultDisplaysOptions($entity);
    }

    parent::attachLoad($queried_entities, $revision_id);
  }

  /**
   * Overrides Drupal\config\ConfigStorageController::postSave().
   */
  public function postSave(EntityInterface $entity, $update) {
    parent::postSave($entity, $update);
    // Clear caches.
    views_invalidate_cache();
  }

  /**
   * Overrides Drupal\config\ConfigStorageController::create().
   */
  public function create(array $values) {
    // If there is no information about displays available add at least the
    // default display.
    $values += array(
      'display' => array(
        'default' => array(
          'display_plugin' => 'default',
          'id' => 'default',
          'display_title' => 'Master',
          'position' => 0,
          'display_options' => array(),
        ),
      )
    );

    $entity = parent::create($values);

    $this->mergeDefaultDisplaysOptions($entity);
    return $entity;
  }

  /**
   * Add defaults to the display options.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The view entity to attach default displays options.
   */
  protected function mergeDefaultDisplaysOptions(EntityInterface $entity) {
    if (isset($entity->display) && is_array($entity->display)) {
      $displays = array();

      foreach ($entity->get('display') as $key => $options) {
        $options += array(
          'display_options' => array(),
          'display_plugin' => NULL,
          'id' => NULL,
          'display_title' => '',
          'position' => NULL,
        );
        // Add the defaults for the display.
        $displays[$key] = $options;
      }

      $entity->set('display', $displays);
    }
  }

}
