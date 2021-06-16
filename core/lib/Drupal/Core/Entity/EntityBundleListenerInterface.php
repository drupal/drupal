<?php

namespace Drupal\Core\Entity;

/**
 * An interface for reacting to entity bundle creation and deletion.
 */
interface EntityBundleListenerInterface {

  /**
   * Reacts to a bundle being created.
   *
   * @param string $bundle
   *   The name of the bundle created.
   * @param string $entity_type_id
   *   The entity type to which the bundle is bound; e.g. 'node' or 'user'.
   */
  public function onBundleCreate($bundle, $entity_type_id);

  /**
   * Reacts to a bundle being deleted.
   *
   * This method runs before fields are deleted.
   *
   * @param string $bundle
   *   The name of the bundle being deleted.
   * @param string $entity_type_id
   *   The entity type to which the bundle is bound; e.g. 'node' or 'user'.
   */
  public function onBundleDelete($bundle, $entity_type_id);

}
