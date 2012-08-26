<?php

/**
 * @file
 * Definition of Drupal\views\ViewStorageController.
 */

namespace Drupal\views;

use Drupal\config\ConfigStorageController;
use Drupal\entity\StorableInterface;

class ViewStorageController extends ConfigStorageController {

  /**
   * Overrides Drupal\config\ConfigStorageController::attachLoad();
   */
  protected function attachLoad(&$queried_entities, $revision_id = FALSE) {
    foreach ($queried_entities as $id => $entity) {
      foreach ($entity->display as $key => $options) {
        // Create a ViewsDisplay object using the display options.
        $entity->display[$key] = new ViewsDisplay($options);
      }
    }
  }

  /**
   * Overrides Drupal\config\ConfigStorageController::save().
   *
   * This currently replaces the reflection code with a static array of
   * properties to be set on the config object. This can be removed
   * when the view storage is isolated so the ReflectionClass can work.
   */
  public function save(StorableInterface $entity) {
    $prefix = $this->entityInfo['config prefix'] . '.';

    // Load the stored entity, if any.
    if ($entity->getOriginalID()) {
      $id = $entity->getOriginalID();
    }
    else {
      $id = $entity->id();
    }
    $config = config($prefix . $id);
    $config->setName($prefix . $entity->id());

    if (!$config->isNew() && !isset($entity->original)) {
      $entity->original = entity_load_unchanged($this->entityType, $id);
    }

    $this->preSave($entity);
    $this->invokeHook('presave', $entity);

    // @todo: This temp measure will be removed once we have a better way or
    // separation of storage and the executed view.
    $config_properties = array (
      'disabled',
      'api_version',
      'name',
      'description',
      'tag',
      'base_table',
      'human_name',
      'core',
      'display',
    );

    foreach ($config_properties as $property) {
      if ($property == 'display') {
        $displays = array();
        foreach ($entity->display as $key => $display) {
          $displays[$key] = $display->display_options;
        }
        $config->set('display', $displays);
      }
      else {
        $config->set($property, $entity->$property);
      }
    }

    if (!$config->isNew()) {
      $return = SAVED_NEW;
      $config->save();
      $this->postSave($entity, TRUE);
      $this->invokeHook('update', $entity);
    }
    else {
      $return = SAVED_UPDATED;
      $config->save();
      $entity->enforceIsNew(FALSE);
      $this->postSave($entity, FALSE);
      $this->invokeHook('insert', $entity);
    }

    unset($entity->original);

    return $return;
  }

}