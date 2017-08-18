<?php

namespace Drupal\Core\Render;

use Drupal\Component\Plugin\Discovery\DiscoveryInterface;

/**
 * Collects available render array element types.
 */
interface ElementInfoManagerInterface extends DiscoveryInterface {

  /**
   * Retrieves the default properties for the defined element type.
   *
   * Each of the element types defined by this hook is assumed to have a
   * matching theme hook, which should be registered with hook_theme() as
   * normal.
   *
   * For more information about custom element types see the explanation at
   * https://www.drupal.org/node/169815.
   *
   * @param string $type
   *   The machine name of an element type plugin.
   *
   * @return array
   *   An associative array describing the element types being defined. The
   *   array contains a sub-array for each element type, with the
   *   machine-readable type name as the key. Each sub-array has a number of
   *   possible attributes:
   *   - #input: boolean indicating whether or not this element carries a value
   *     (even if it's hidden).
   *   - #process: array of callback functions taking $element, $form_state,
   *     and $complete_form.
   *   - #after_build: array of callables taking $element and $form_state.
   *   - #validate: array of callback functions taking $form and $form_state.
   *   - #element_validate: array of callback functions taking $element and
   *     $form_state.
   *   - #pre_render: array of callables taking $element.
   *   - #post_render: array of callables taking $children and $element.
   *   - #submit: array of callback functions taking $form and $form_state.
   *   - #title_display: optional string indicating if and how #title should be
   *     displayed (see form-element.html.twig).
   *
   * @see \Drupal\Core\Render\Element\ElementInterface
   * @see \Drupal\Core\Render\Element\ElementInterface::getInfo()
   */
  public function getInfo($type);

  /**
   * Retrieves a single property for the defined element type.
   *
   * @param string $type
   *   An element type as defined by an element plugin.
   * @param string $property_name
   *   The property within the element type that should be returned.
   * @param $default
   *   (Optional) The value to return if the element type does not specify a
   *   value for the property. Defaults to NULL.
   *
   * @return string
   *   The property value of the defined element type. Or the provided
   *   default value, which can be NULL.
   */
  public function getInfoProperty($type, $property_name, $default = NULL);

}
