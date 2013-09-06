<?php

/**
 * @file
 * Contains \Drupal\Core\Entity\ExtensibleEntityStorageControllerInterface.
 */

namespace Drupal\Core\Entity;

use Drupal\field\FieldInterface;
use Drupal\field\FieldInstanceInterface;

interface FieldableEntityStorageControllerInterface extends EntityStorageControllerInterface {

  /**
   * Allows reaction to the creation of a configurable field.
   *
   * @param \Drupal\field\FieldInterface $field
   *   The field being created.
   */
  public function onFieldCreate(FieldInterface $field);

  /**
   * Allows reaction to the update of a configurable field.
   *
   * @param \Drupal\field\FieldInterface $field
   *   The field being updated.
   */
  public function onFieldUpdate(FieldInterface $field);

  /**
   * Allows reaction to the deletion of a configurable field.
   *
   * Stored values should not be wiped at once, but marked as 'deleted' so that
   * they can go through a proper purge process later on.
   *
   * @param \Drupal\field\FieldInterface $field
   *   The field being deleted.
   *
   * @see fieldPurgeData()
   */
  public function onFieldDelete(FieldInterface $field);

  /**
   * Allows reaction to the creation of a configurable field instance.
   *
   * @param \Drupal\field\FieldInstanceInterface $instance
   *   The instance being created.
   */
  public function onInstanceCreate(FieldInstanceInterface $instance);

  /**
   * Allows reaction to the update of a configurable field instance.
   *
   * @param \Drupal\field\FieldInstanceInterface $instance
   *   The instance being updated.
   */
  public function onInstanceUpdate(FieldInstanceInterface $instance);

  /**
   * Allows reaction to the deletion of a configurable field instance.
   *
   * Stored values should not be wiped at once, but marked as 'deleted' so that
   * they can go through a proper purge process later on.
   *
   * @param \Drupal\field\FieldInstanceInterface $instance
   *   The instance being deleted.
   *
   * @see fieldPurgeData()
   */
  public function onInstanceDelete(FieldInstanceInterface $instance);

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
   * @param string $bundle
   *   The name of the bundle being renamed.
   * @param string $bundle_new
   *   The new name of the bundle.
   */
  public function onBundleRename($bundle, $bundle_new);

  /**
   * Allows reaction to a bundle being deleted.
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
   * @param \Drupal\field\FieldInstanceInterface $instance
   *   The deleted field instance whose data is being purged.
   */
  public function onFieldItemsPurge(EntityInterface $entity, FieldInstanceInterface $instance);

  /**
   * Performs final cleanup after all data on all instances has been purged.
   *
   * @param \Drupal\field\FieldInterface $instance
   *   The field being purged.
   */
  public function onFieldPurge(FieldInterface $field);

}
