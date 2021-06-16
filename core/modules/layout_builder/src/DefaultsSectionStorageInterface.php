<?php

namespace Drupal\layout_builder;

use Drupal\Core\Config\Entity\ThirdPartySettingsInterface;

/**
 * Defines an interface for an object that stores layout sections for defaults.
 *
 * @todo Refactor this interface in https://www.drupal.org/node/2985362.
 */
interface DefaultsSectionStorageInterface extends SectionStorageInterface, ThirdPartySettingsInterface, LayoutBuilderEnabledInterface {

  /**
   * Determines if the defaults allow custom overrides.
   *
   * @return bool
   *   TRUE if custom overrides are allowed, FALSE otherwise.
   */
  public function isOverridable();

  /**
   * Sets the defaults to allow or disallow overrides.
   *
   * @param bool $overridable
   *   TRUE if the display should allow overrides, FALSE otherwise.
   *
   * @return $this
   */
  public function setOverridable($overridable = TRUE);

}
