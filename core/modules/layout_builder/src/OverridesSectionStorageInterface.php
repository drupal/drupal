<?php

namespace Drupal\layout_builder;

/**
 * Defines an interface for an object that stores layout sections for overrides.
 *
 * @internal
 *   Layout Builder is currently experimental and should only be leveraged by
 *   experimental modules and development releases of contributed modules.
 *   See https://www.drupal.org/core/experimental for more information.
 */
interface OverridesSectionStorageInterface extends SectionStorageInterface {

  /**
   * Returns the corresponding defaults section storage for this override.
   *
   * @return \Drupal\layout_builder\DefaultsSectionStorageInterface
   *   The defaults section storage.
   *
   * @todo Determine if this method needs a parameter in
   *   https://www.drupal.org/project/drupal/issues/2936507.
   */
  public function getDefaultSectionStorage();

  /**
   * Indicates if overrides are in use.
   *
   * @return bool
   *   TRUE if this overrides section storage is in use, otherwise FALSE.
   */
  public function isOverridden();

}
