<?php

/**
 * @file
 * Contains \Drupal\shortcut\Entity\ShortcutSet.
 */

namespace Drupal\shortcut\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\shortcut\ShortcutSetInterface;

/**
 * Defines the Shortcut set configuration entity.
 *
 * @ConfigEntityType(
 *   id = "shortcut_set",
 *   label = @Translation("Shortcut set"),
 *   handlers = {
 *     "storage" = "Drupal\shortcut\ShortcutSetStorage",
 *     "access" = "Drupal\shortcut\ShortcutSetAccessControlHandler",
 *     "list_builder" = "Drupal\shortcut\ShortcutSetListBuilder",
 *     "form" = {
 *       "default" = "Drupal\shortcut\ShortcutSetForm",
 *       "add" = "Drupal\shortcut\ShortcutSetForm",
 *       "edit" = "Drupal\shortcut\ShortcutSetForm",
 *       "customize" = "Drupal\shortcut\Form\SetCustomize",
 *       "delete" = "Drupal\shortcut\Form\ShortcutSetDeleteForm"
 *     }
 *   },
 *   config_prefix = "set",
 *   bundle_of = "shortcut",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label"
 *   },
 *   links = {
 *     "customize-form" = "/admin/config/user-interface/shortcut/manage/{shortcut_set}/customize",
 *     "delete-form" = "/admin/config/user-interface/shortcut/manage/{shortcut_set}/delete",
 *     "edit-form" = "/admin/config/user-interface/shortcut/manage/{shortcut_set}",
 *     "collection" = "/admin/config/user-interface/shortcut",
 *   }
 * )
 */
class ShortcutSet extends ConfigEntityBase implements ShortcutSetInterface {

  /**
   * The machine name for the configuration entity.
   *
   * @var string
   */
  protected $id;

  /**
   * The human-readable name of the configuration entity.
   *
   * @var string
   */
  protected $label;

  /**
   * {@inheritdoc}
   */
  public function postSave(EntityStorageInterface $storage, $update = TRUE) {
    parent::postSave($storage, $update);

    if (!$update && !$this->isSyncing()) {
      // Save a new shortcut set with links copied from the user's default set.
      $default_set = shortcut_default_set();
      // This is the default set, do not copy shortcuts.
      if ($default_set->id() != $this->id()) {
        foreach ($default_set->getShortcuts() as $shortcut) {
          $shortcut = $shortcut->createDuplicate();
          $shortcut->enforceIsNew();
          $shortcut->shortcut_set->target_id = $this->id();
          $shortcut->save();
        }
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function preDelete(EntityStorageInterface $storage, array $entities) {
    parent::preDelete($storage, $entities);

    foreach ($entities as $entity) {
      $storage->deleteAssignedShortcutSets($entity);

      // Next, delete the shortcuts for this set.
      $shortcut_ids = \Drupal::entityQuery('shortcut')
        ->condition('shortcut_set', $entity->id(), '=')
        ->execute();

      $controller = \Drupal::entityManager()->getStorage('shortcut');
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
    return \Drupal::entityManager()->getStorage('shortcut')->loadByProperties(array('shortcut_set' => $this->id()));
  }

}
