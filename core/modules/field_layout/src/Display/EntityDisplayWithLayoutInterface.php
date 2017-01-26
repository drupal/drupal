<?php

namespace Drupal\field_layout\Display;

use Drupal\Core\Entity\Display\EntityDisplayInterface;
use Drupal\Core\Layout\LayoutInterface;

/**
 * Provides a common interface for entity displays that have layout.
 */
interface EntityDisplayWithLayoutInterface extends EntityDisplayInterface {

  /**
   * Gets the default region.
   *
   * @return string
   *   The default region for this display.
   */
  public function getDefaultRegion();

  /**
   * Gets the layout plugin ID for this display.
   *
   * @return string
   *   The layout plugin ID.
   */
  public function getLayoutId();

  /**
   * Gets the layout plugin settings for this display.
   *
   * @return mixed[]
   *   The layout plugin settings.
   */
  public function getLayoutSettings();

  /**
   * Sets the layout plugin ID for this display.
   *
   * @param string|null $layout_id
   *   Either a valid layout plugin ID, or NULL to remove the layout setting.
   * @param array $layout_settings
   *   (optional) An array of settings for this layout.
   *
   * @return $this
   */
  public function setLayoutId($layout_id, array $layout_settings = []);

  /**
   * Sets the layout plugin for this display.
   *
   * @param \Drupal\Core\Layout\LayoutInterface $layout
   *   A layout plugin.
   *
   * @return $this
   */
  public function setLayout(LayoutInterface $layout);

  /**
   * Gets the layout plugin for this display.
   *
   * @return \Drupal\Core\Layout\LayoutInterface
   *   The layout plugin.
   */
  public function getLayout();

  /**
   * Ensures this entity has a layout.
   *
   * @param string $default_layout_id
   *   (optional) The layout ID to use as a default. Defaults to
   *   'layout_onecol'.
   *
   * @return $this
   */
  public function ensureLayout($default_layout_id = 'layout_onecol');

}
