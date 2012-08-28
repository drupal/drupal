<?php

/**
 * @file
 * Definition of Drupal\views\ViewStorageController.
 */

namespace Drupal\views;

use Drupal\config\ConfigStorageController;
use Drupal\entity\StorableInterface;

/**
 * Defines the storage controller class for ViewStorage entities.
 */
class ViewStorageController extends ConfigStorageController {

  /**
   * Overrides Drupal\config\ConfigStorageController::attachLoad();
   */
  protected function attachLoad(&$queried_objects, $revision_id = FALSE) {
    foreach ($queried_objects as $id => $configurable) {
      // @todo This property is left in for CTools export UI.
      $configurable->type = t('Normal');
      $this->attachDisplays($configurable);
    }
  }

  /**
   * Overrides Drupal\config\ConfigStorageController::save().
   *
   * This currently replaces the reflection code with a static array of
   * properties to be set on the config object. This can be removed when the
   * view storage is isolated so the ReflectionClass can work.
   */
  public function save(StorableInterface $configurable) {
    $prefix = $this->entityInfo['config prefix'] . '.';

    // Load the stored configurable, if any, and rename it.
    if ($configurable->getOriginalID()) {
      $id = $configurable->getOriginalID();
    }
    else {
      $id = $configurable->id();
    }
    $config = config($prefix . $id);
    $config->setName($prefix . $configurable->id());

    if (!$config->isNew() && !isset($configurable->original)) {
      $configurable->original = entity_load_unchanged($this->entityType, $id);
    }

    $this->preSave($configurable);
    $this->invokeHook('presave', $configurable);

    // @todo This temp measure will be removed once we have a better way or
    //   separation of storage and the executed view.
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
        foreach ($configurable->display as $key => $display) {
          $displays[$key] = array(
            'display_options' => $display->display_options,
            'display_plugin' => $display->display_plugin,
            'id' => $display->id,
            'display_title' => $display->display_title,
            'position' => isset($display->position) ? $display->position : 0,
          );
        }
        $config->set('display', $displays);
      }
      else {
        $config->set($property, $configurable->$property);
      }
    }

    if (!$config->isNew()) {
      $return = SAVED_NEW;
      $config->save();
      $this->postSave($configurable, TRUE);
      $this->invokeHook('update', $configurable);
    }
    else {
      $return = SAVED_UPDATED;
      $config->save();
      $configurable->enforceIsNew(FALSE);
      $this->postSave($configurable, FALSE);
      $this->invokeHook('insert', $configurable);
    }

    // Clear caches.
    views_invalidate_cache();

    unset($configurable->original);

    return $return;
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

    $configurable = parent::create($values);

    $this->attachDisplays($configurable);
    return $configurable;
  }

  /**
   * Attaches an array of ViewDisplay objects to the view display property.
   *
   * @param Drupal\entity\StorableInterface $configurable
   */
  protected function attachDisplays(StorableInterface $configurable) {
    if (isset($configurable->display) && is_array($configurable->display)) {
      $displays = array();

      foreach ($configurable->get('display') as $key => $options) {
        $options += array(
          'display_options' => array(),
          'display_plugin' => NULL,
          'id' => NULL,
          'display_title' => '',
          'position' => NULL,
        );
        // Create a ViewDisplay object using the display options.
        $displays[$key] = new ViewDisplay($options);
      }

      $configurable->set('display', $displays);
    }
  }

}
