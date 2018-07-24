<?php

namespace Drupal\layout_builder\Entity;

use Drupal\Core\Entity\Display\EntityDisplayInterface;
use Drupal\layout_builder\LayoutBuilderEnabledInterface;
use Drupal\layout_builder\SectionListInterface;

/**
 * Provides an interface for entity displays that have layout.
 *
 * @internal
 *   Layout Builder is currently experimental and should only be leveraged by
 *   experimental modules and development releases of contributed modules.
 *   See https://www.drupal.org/core/experimental for more information.
 *
 * @todo Refactor this interface in https://www.drupal.org/node/2985362.
 */
interface LayoutEntityDisplayInterface extends EntityDisplayInterface, SectionListInterface, LayoutBuilderEnabledInterface {

  /**
   * Determines if the display allows custom overrides.
   *
   * @return bool
   *   TRUE if custom overrides are allowed, FALSE otherwise.
   */
  public function isOverridable();

  /**
   * Sets the display to allow or disallow overrides.
   *
   * @param bool $overridable
   *   TRUE if the display should allow overrides, FALSE otherwise.
   *
   * @return $this
   */
  public function setOverridable($overridable = TRUE);

}
