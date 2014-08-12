<?php

/**
 * @file
 * Contains \Drupal\Core\Render\ElementInfoManagerInterface.
 */

namespace Drupal\Core\Render;

/**
 * Collects available render array element types.
 */
interface ElementInfoManagerInterface {

  /**
   * Retrieves the default properties for the defined element type.
   *
   * Each of the form element types defined by this hook is assumed to have
   * a matching theme hook, which should be registered with hook_theme() as
   * normal.
   *
   * For more information about custom element types see the explanation at
   * http://drupal.org/node/169815.
   *
   * @param string $type
   *   An element type as defined by hook_element_info() or the machine name
   *   of an element type plugin.
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
   * @see hook_element_info()
   * @see hook_element_info_alter()
   * @see \Drupal\Core\Render\Element\ElementInterface
   * @see \Drupal\Core\Render\Element\ElementInterface::getInfo()
   */
  public function getInfo($type);

}
