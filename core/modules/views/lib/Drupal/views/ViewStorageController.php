<?php

/**
 * @file
 * Definition of Drupal\views\ViewStorageController.
 */

namespace Drupal\views;

use Drupal\Core\Config\Entity\ConfigStorageController;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Component\Uuid\Uuid;

/**
 * Defines the storage controller class for View entities.
 */
class ViewStorageController extends ConfigStorageController {

  /**
   * Holds a UUID factory instance.
   *
   * @var Drupal\Component\Uuid\Uuid
   */
  protected $uuidFactory = NULL;

  /**
   * Overrides Drupal\config\ConfigStorageController::load();
   */
  public function load(array $ids = NULL) {
    $entities = parent::load($ids);

    // Only return views for enabled modules.
    return array_filter($entities, function ($entity) {
      if (module_exists($entity->getModule())) {
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
      // Create a uuid if we don't have one.
      if (empty($entity->{$this->uuidKey})) {
        // Only get an instance of uuid once.
        if (!isset($this->uuidFactory)) {
          $this->uuidFactory = new Uuid();
        }
        $entity->{$this->uuidKey} = $this->uuidFactory->generate();
      }
      $this->attachDisplays($entity);
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
        ),
      )
    );

    $entity = parent::create($values);

    $this->attachDisplays($entity);
    return $entity;
  }

  /**
   * Add defaults to the display options.
   *
   * @param Drupal\Core\Entity\EntityInterface $entity
   */
  protected function attachDisplays(EntityInterface $entity) {
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
