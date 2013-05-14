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
   * {@inheritdoc}
   */
  public function load(array $ids = NULL) {
    $entities = parent::load($ids);
    // Only blocks with a valid plugin should be loaded.
    return array_filter($entities, function ($entity) {
      return $entity->getPlugin();
    });
  }

  /**
   * {@inheritdoc}
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

  /**
   * {@inheritdoc}
   */
  protected function preSave(EntityInterface $entity) {
    parent::preSave($entity);

    $entity->set('settings', $entity->getPlugin()->getConfig());
  }

}
