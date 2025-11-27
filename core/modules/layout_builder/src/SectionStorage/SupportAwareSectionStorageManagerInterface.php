<?php

declare(strict_types=1);

namespace Drupal\layout_builder\SectionStorage;

/**
 * Interface for section manager that knows whether it supports view modes.
 */
interface SupportAwareSectionStorageManagerInterface extends SectionStorageManagerInterface {

  /**
   * Determines whether a view mode is not supported by any storage.
   *
   * @param string $entity_type_id
   *   The entity type id.
   * @param string $bundle
   *   The bundle.
   * @param string $view_mode
   *   The view mode.
   *
   * @return bool
   *   TRUE if the view mode is not supported, otherwise FALSE.
   */
  public function notSupported(string $entity_type_id, string $bundle, string $view_mode): bool;

}
