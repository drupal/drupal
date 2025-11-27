<?php

declare(strict_types=1);

namespace Drupal\layout_builder;

/**
 * Interface for section storage that knows whether it supports a view mode.
 */
interface SupportAwareSectionStorageInterface extends SectionStorageInterface {

  /**
   * Determines if layout builder is supported by a view mode.
   *
   * @param string $entity_type_id
   *   The entity type id.
   * @param string $bundle
   *   The bundle.
   * @param string $view_mode
   *   The view mode.
   *
   * @return bool
   *   TRUE if it is supported, otherwise FALSE.
   */
  public function isSupported(string $entity_type_id, string $bundle, string $view_mode): bool;

}
