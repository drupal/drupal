<?php

/**
 * @file
 * Contains \Drupal\Core\Entity\FieldableEntityStorageInterface.
 */

namespace Drupal\Core\Entity;

use Drupal\field\FieldConfigInterface;
use Drupal\field\FieldInstanceConfigInterface;

interface FieldableEntityStorageInterface extends EntityStorageInterface {

  /**
   * Allows reaction to the creation of a configurable field.
   *
   * @param \Drupal\field\FieldConfigInterface $field
   *   The field being created.
   */
  public function onFieldCreate(FieldConfigInterface $field);

  /**
   * Allows reaction to the update of a configurable field.
   *
   * @param \Drupal\field\FieldConfigInterface $field
   *   The field being updated.
   */
  public function onFieldUpdate(FieldConfigInterface $field);

  /**
   * Allows reaction to the deletion of a configurable field.
   *
   * Stored values should not be wiped at once, but marked as 'deleted' so that
   * they can go through a proper purge process later on.
   *
   * @param \Drupal\field\FieldConfigInterface $field
   *   The field being deleted.
   *
   * @see fieldPurgeData()
   */
  public function onFieldDelete(FieldConfigInterface $field);

  /**
   * Allows reaction to the creation of a configurable field instance.
   *
   * @param \Drupal\field\FieldInstanceConfigInterface $instance
   *   The instance being created.
   */
  public function onInstanceCreate(FieldInstanceConfigInterface $instance);

  /**
   * Allows reaction to the update of a configurable field instance.
   *
   * @param \Drupal\field\FieldInstanceConfigInterface $instance
   *   The instance being updated.
   */
  public function onInstanceUpdate(FieldInstanceConfigInterface $instance);

  /**
   * Allows reaction to the deletion of a configurable field instance.
   *
   * Stored values should not be wiped at once, but marked as 'deleted' so that
   * they can go through a proper purge process later on.
   *
   * @param \Drupal\field\FieldInstanceConfigInterface $instance
   *   The instance being deleted.
   *
   * @see fieldPurgeData()
   */
  public function onInstanceDelete(FieldInstanceConfigInterface $instance);

  /**
   * Allows reaction to a bundle being created.
   *
   * @param string $bundle
   *   The name of the bundle created.
   */
  public function onBundleCreate($bundle);

  /**
   * Allows reaction to a bundle being renamed.
   *
   * This method runs before field instance definitions are updated with the new
   * bundle name.
   *
   * @param string $bundle
   *   The name of the bundle being renamed.
   * @param string $bundle_new
   *   The new name of the bundle.
   */
  public function onBundleRename($bundle, $bundle_new);

  /**
   * Allows reaction to a bundle being deleted.
   *
   * This method runs before field and instance definitions are deleted.
   *
   * @param string $bundle
   *   The name of the bundle being deleted.
   */
  public function onBundleDelete($bundle);

  /**
   * Purges the field data for a single field on a single entity.
   *
   * The entity itself is not being deleted, and it is quite possible that
   * other field data will remain attached to it.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity whose field data is being purged.
   * @param \Drupal\field\FieldInstanceConfigInterface $instance
   *   The deleted field instance whose data is being purged.
   */
  public function onFieldItemsPurge(EntityInterface $entity, FieldInstanceConfigInterface $instance);

  /**
   * Performs final cleanup after all data on all instances has been purged.
   *
   * @param \Drupal\field\FieldConfigInterface $instance
   *   The field being purged.
   */
  public function onFieldPurge(FieldConfigInterface $field);

}
