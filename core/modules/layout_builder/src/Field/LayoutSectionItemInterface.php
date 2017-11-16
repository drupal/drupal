<?php

namespace Drupal\layout_builder\Field;

use Drupal\Core\Field\FieldItemInterface;
use Drupal\layout_builder\Section;

/**
 * Defines an interface for the layout section field item.
 *
 * @internal
 *   Layout Builder is currently experimental and should only be leveraged by
 *   experimental modules and development releases of contributed modules.
 *   See https://www.drupal.org/core/experimental for more information.
 *
 * @property string layout
 * @property array[] layout_settings
 * @property array[] section
 */
interface LayoutSectionItemInterface extends FieldItemInterface {

  /**
   * Gets a domain object for the layout section.
   *
   * @return \Drupal\layout_builder\Section
   *   The layout section.
   */
  public function getSection();

  /**
   * Updates the stored value based on the domain object.
   *
   * @param \Drupal\layout_builder\Section $section
   *   The layout section.
   *
   * @return $this
   */
  public function updateFromSection(Section $section);

}
