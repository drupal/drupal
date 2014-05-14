<?php

/**
 * @file
 * Contains Drupal\Core\Config\Entity\ConfigEntityBundleBase.
 */

namespace Drupal\Core\Config\Entity;

use Drupal\Core\Entity\EntityStorageInterface;

/**
 * A base class for config entity types that act as bundles.
 *
 * Entity types that want to use this base class must use bundle_of in their
 * annotation to specify for which entity type they are providing bundles for.
 */
abstract class ConfigEntityBundleBase extends ConfigEntityBase {

  /**
   * {@inheritdoc}
   */
  public function postSave(EntityStorageInterface $storage, $update = TRUE) {
    parent::postSave($storage, $update);

    if (!$update) {
      entity_invoke_bundle_hook('create', $this->getEntityType()->getBundleOf(), $this->id());
    }
    elseif ($this->getOriginalId() != $this->id()) {
      entity_invoke_bundle_hook('rename', $this->getEntityType()->getBundleOf(), $this->getOriginalId(), $this->id());
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function postDelete(EntityStorageInterface $storage, array $entities) {
    parent::postDelete($storage, $entities);

    foreach ($entities as $entity) {
      entity_invoke_bundle_hook('delete', $entity->getEntityType()->getBundleOf(), $entity->id());
    }
  }

}
