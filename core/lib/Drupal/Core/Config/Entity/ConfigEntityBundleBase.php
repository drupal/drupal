<?php

/**
 * @file
 * Contains Drupal\Core\Config\Entity\ConfigEntityBundleBase.
 */

namespace Drupal\Core\Config\Entity;

use Drupal\Core\Entity\Entity\EntityFormDisplay;
use Drupal\Core\Entity\Entity\EntityViewDisplay;
use Drupal\Core\Entity\EntityStorageInterface;

/**
 * A base class for config entity types that act as bundles.
 *
 * Entity types that want to use this base class must use bundle_of in their
 * annotation to specify for which entity type they are providing bundles for.
 */
abstract class ConfigEntityBundleBase extends ConfigEntityBase {

  /**
   * Renames displays when a bundle is renamed.
   */
  protected function renameDisplays() {
    // Rename entity displays.
    if ($this->getOriginalId() !== $this->id()) {
      foreach ($this->loadDisplays('entity_view_display') as $display) {
        $new_id = $this->getEntityType()->getBundleOf() . '.' . $this->id() . '.' . $display->mode;
        $display->set('id', $new_id);
        $display->bundle = $this->id();
        $display->save();
      }
    }

    // Rename entity form displays.
    if ($this->getOriginalId() !== $this->id()) {
      foreach ($this->loadDisplays('entity_form_display') as $form_display) {
        $new_id = $this->getEntityType()->getBundleOf() . '.' . $this->id() . '.' . $form_display->mode;
        $form_display->set('id', $new_id);
        $form_display->bundle = $this->id();
        $form_display->save();
      }
    }
  }

  /**
   * Deletes display if a bundle is deleted.
   */
  protected function deleteDisplays() {
    // Remove entity displays of the deleted bundle.
    if ($displays = $this->loadDisplays('entity_view_display')) {
      $storage = $this->entityManager()->getStorage('entity_view_display');
      $storage->delete($displays);
    }

    // Remove entity form displays of the deleted bundle.
    if ($displays = $this->loadDisplays('entity_form_display')) {
      $storage = $this->entityManager()->getStorage('entity_form_display');
      $storage->delete($displays);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function postSave(EntityStorageInterface $storage, $update = TRUE) {
    parent::postSave($storage, $update);

    if (!$update) {
      $this->entityManager()->onBundleCreate($this->id(), $this->getEntityType()->getBundleOf());
    }
    elseif ($this->getOriginalId() != $this->id()) {
      $this->renameDisplays();
      $this->entityManager()->onBundleRename($this->getOriginalId(), $this->id(), $this->getEntityType()->getBundleOf());
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function postDelete(EntityStorageInterface $storage, array $entities) {
    parent::postDelete($storage, $entities);

    foreach ($entities as $entity) {
      $entity->deleteDisplays();
      \Drupal::entityManager()->onBundleDelete($entity->id(), $entity->getEntityType()->getBundleOf());
    }
  }

  /**
   * Returns view or form displays for this bundle.
   *
   * @param string $entity_type_id
   *   The entity type ID of the display type to load.
   *
   * @return \Drupal\Core\Entity\Display\EntityDisplayInterface[]
   *   A list of matching displays.
   */
  protected function loadDisplays($entity_type_id) {
    $ids = \Drupal::entityQuery($entity_type_id)
      ->condition('id', $this->getEntityType()->getBundleOf() . '.' . $this->getOriginalId() . '.', 'STARTS_WITH')
      ->execute();
    if ($ids) {
      $storage = $this->entityManager()->getStorage($entity_type_id);
      return $storage->loadMultiple($ids);
    }
    return array();
  }

}
