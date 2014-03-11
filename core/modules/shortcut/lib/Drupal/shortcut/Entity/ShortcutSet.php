<?php

/**
 * @file
 * Contains \Drupal\shortcut\Entity\ShortcutSet.
 */

namespace Drupal\shortcut\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\Core\Entity\EntityStorageControllerInterface;
use Drupal\shortcut\ShortcutSetInterface;

/**
 * Defines the Shortcut set configuration entity.
 *
 * @ConfigEntityType(
 *   id = "shortcut_set",
 *   label = @Translation("Shortcut set"),
 *   controllers = {
 *     "storage" = "Drupal\shortcut\ShortcutSetStorageController",
 *     "access" = "Drupal\shortcut\ShortcutSetAccessController",
 *     "list" = "Drupal\shortcut\ShortcutSetListController",
 *     "form" = {
 *       "default" = "Drupal\shortcut\ShortcutSetFormController",
 *       "add" = "Drupal\shortcut\ShortcutSetFormController",
 *       "edit" = "Drupal\shortcut\ShortcutSetFormController",
 *       "customize" = "Drupal\shortcut\Form\SetCustomize",
 *       "delete" = "Drupal\shortcut\Form\ShortcutSetDeleteForm"
 *     }
 *   },
 *   config_prefix = "set",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label"
 *   },
 *   links = {
 *     "customize-form" = "shortcut.set_customize",
 *     "delete-form" = "shortcut.set_delete",
 *     "edit-form" = "shortcut.set_edit"
 *   }
 * )
 */
class ShortcutSet extends ConfigEntityBase implements ShortcutSetInterface {

  /**
   * The machine name for the configuration entity.
   *
   * @var string
   */
  public $id;

  /**
   * The human-readable name of the configuration entity.
   *
   * @var string
   */
  public $label;

  /**
   * {@inheritdoc}
   */
  public function postCreate(EntityStorageControllerInterface $storage_controller) {
    parent::postCreate($storage_controller);

    // Generate menu-compatible set name.
    if (!$this->getOriginalId()) {
      // Save a new shortcut set with links copied from the user's default set.
      $default_set = shortcut_default_set();
      foreach ($default_set->getShortcuts() as $shortcut) {
        $shortcut = $shortcut->createDuplicate();
        $shortcut->enforceIsNew();
        $shortcut->shortcut_set->target_id = $this->id();
        $shortcut->save();
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function preDelete(EntityStorageControllerInterface $storage_controller, array $entities) {
    parent::preDelete($storage_controller, $entities);

    foreach ($entities as $entity) {
      $storage_controller->deleteAssignedShortcutSets($entity);

      // Next, delete the shortcuts for this set.
      $shortcut_ids = \Drupal::entityQuery('shortcut')
        ->condition('shortcut_set', $entity->id(), '=')
        ->execute();

      $controller = \Drupal::entityManager()->getStorageController('shortcut');
      $entities = $controller->loadMultiple($shortcut_ids);
      $controller->delete($entities);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function resetLinkWeights() {
    $weight = -50;
    foreach ($this->getShortcuts() as $shortcut) {
      $shortcut->setWeight(++$weight);
      $shortcut->save();
    }

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getShortcuts() {
    return \Drupal::entityManager()->getStorageController('shortcut')->loadByProperties(array('shortcut_set' => $this->id()));
  }

}
