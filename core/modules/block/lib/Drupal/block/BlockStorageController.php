<?php

/**
 * @file
 * Contains \Drupal\block\BlockStorageController.
 */

namespace Drupal\block;

use Drupal\Core\Config\Entity\ConfigStorageController;
use Drupal\Core\Entity\EntityInterface;

/**
 * Defines the storage controller class for Block entities.
 */
class BlockStorageController extends ConfigStorageController {

  /**
   * Overrides \Drupal\Core\Config\Entity\ConfigStorageController::create().
   */
  public function create(array $values) {
    $entity = parent::create($values);

    if (!$entity->get('module')) {
      $definition = $entity->getPlugin()->getDefinition();
      $entity->set('module', $definition['module']);
    }

    return $entity;
  }

  /**
   * Overrides \Drupal\Core\Config\Entity\ConfigStorageController::load().
   */
  public function load(array $ids = NULL) {
    $entities = parent::load($ids);
    // Only blocks with a valid plugin should be loaded.
    return array_filter($entities, function ($entity) {
      return $entity->getPlugin();
    });
  }

  /**
   * Overrides \Drupal\Core\Config\Entity\ConfigStorageController::loadByProperties().
   */
  public function loadByProperties(array $values = array()) {
    $blocks = $this->load();
    foreach ($values as $key => $value) {
      $blocks = array_filter($blocks, function($block) use ($key, $value) {
        return $value === $block->get($key);
      });
    }
    return $blocks;
  }

}
