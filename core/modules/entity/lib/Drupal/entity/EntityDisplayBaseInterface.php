<?php

/**
 * @file
 * Contains \Drupal\entity\EntityDisplayBaseInterface.
 */

namespace Drupal\entity;

use Drupal\Core\Config\Entity\ConfigEntityInterface;

/**
 * Provides an interface defining an entity display entity.
 */
interface EntityDisplayBaseInterface extends ConfigEntityInterface {

  /**
   * Creates a duplicate of the EntityDisplay object on a different view mode.
   *
   * The new object necessarily has the same $targetEntityType and $bundle
   * properties than the original one.
   *
   * @param $view_mode
   *   The view mode for the new object.
   *
   * @return \Drupal\entity\Entity\EntityDisplay
   *   The new object.
   */
  public function createCopy($view_mode);

  /**
   * Gets the display options for all components.
   *
   * @return array
   *   The array of display options, keyed by component name.
   */
  public function getComponents();

  /**
   * Gets the display options set for a component.
   *
   * @param string $name
   *   The name of the component.
   *
   * @return array|null
   *   The display options for the component, or NULL if the component is not
   *   displayed.
   */
  public function getComponent($name);

  /**
   * Sets the display options for a component.
   *
   * @param string $name
   *   The name of the component.
   * @param array $options
   *   The display options.
   *
   * @return \Drupal\entity\Entity\EntityDisplay
   *   The EntityDisplay object.
   */
  public function setComponent($name, array $options = array());

  /**
   * Sets a component to be hidden.
   *
   * @param string $name
   *   The name of the component.
   *
   * @return \Drupal\entity\Entity\EntityDisplay
   *   The EntityDisplay object.
   */
  public function removeComponent($name);

  /**
   * Returns the highest weight of the components in the display.
   *
   * @return int|null
   *   The highest weight of the components in the display, or NULL if the
   *   display is empty.
   */
  public function getHighestWeight();

  /**
   * Returns the renderer plugin for a field (e.g. widget, formatter).
   *
   * @param string $field_name
   *   The field name.
   *
   * @return \Drupal\field\Plugin\PluginSettingsInterface|null
   *   A widget or formatter plugin or NULL if the field does not exist.
   */
  public function getRenderer($field_name);

}
