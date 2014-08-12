<?php

/**
 * @file
 * Contains \Drupal\Core\Render\Element\FormElement.
 */

namespace Drupal\Core\Render\Element;

use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a base class for form render plugins.
 *
 * @see \Drupal\Core\Render\Annotation\FormElement
 * @see \Drupal\Core\Render\Element\FormElementInterface
 * @see \Drupal\Core\Render\ElementInfoManager
 * @see plugin_api
 *
 * @ingroup theme_render
 */
abstract class FormElement extends RenderElement implements FormElementInterface {

  /**
   * {@inheritdoc}
   */
  public static function valueCallback(&$element, $input, FormStateInterface $form_state) {
    return NULL;
  }

  /**
   * Form element processing handler for the #ajax form property.
   *
   * @param array $element
   *   An associative array containing the properties of the element.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   * @param array $complete_form
   *   The complete form structure.
   *
   * @return array
   *   The processed element.
   *
   * @see ajax_pre_render_element()
   */
  public static function processAjaxForm(&$element, FormStateInterface $form_state, &$complete_form) {
    $element = ajax_pre_render_element($element);
    if (!empty($element['#ajax_processed'])) {
      $form_state['cache'] = TRUE;
    }
    return $element;
  }

  /**
   * Arranges elements into groups.
   *
   * @param array $element
   *   An associative array containing the properties and children of the
   *   element. Note that $element must be taken by reference here, so processed
   *   child elements are taken over into $form_state.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   * @param array $complete_form
   *   The complete form structure.
   *
   * @return array
   *   The processed element.
   */
  public static function processGroup(&$element, FormStateInterface $form_state, &$complete_form) {
    $parents = implode('][', $element['#parents']);

    // Each details element forms a new group. The #type 'vertical_tabs' basically
    // only injects a new details element.
    $form_state['groups'][$parents]['#group_exists'] = TRUE;
    $element['#groups'] = &$form_state['groups'];

    // Process vertical tabs group member details elements.
    if (isset($element['#group'])) {
      // Add this details element to the defined group (by reference).
      $group = $element['#group'];
      $form_state['groups'][$group][] = &$element;
    }

    return $element;
  }

  /**
   * #process callback for #pattern form element property.
   *
   * @param array $element
   *   An associative array containing the properties and children of the
   *   generic input element.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   * @param array $complete_form
   *   The complete form structure.
   *
   * @return array
   *   The processed element.
   *
   * @see form_validate_pattern()
   */
  public static function processPattern(&$element, FormStateInterface $form_state, &$complete_form) {
    if (isset($element['#pattern']) && !isset($element['#attributes']['pattern'])) {
      $element['#attributes']['pattern'] = $element['#pattern'];
      $element['#element_validate'][] = 'form_validate_pattern';
    }

    return $element;
  }

  /**
   * Adds autocomplete functionality to elements.
   *
   * This sets up autocomplete functionality for elements with an
   * #autocomplete_route_name property, using the #autocomplete_route_parameters
   * property if present.
   *
   * For example, suppose your autocomplete route name is
   * 'mymodule.autocomplete' and its path is
   * '/mymodule/autocomplete/{a}/{b}'. In a form array, you would create a text
   * field with properties:
   * @code
   * '#autocomplete_route_name' => 'mymodule.autocomplete',
   * '#autocomplete_route_parameters' => array('a' => $some_key, 'b' => $some_id),
   * @endcode
   * If the user types "keywords" in that field, the full path called would be:
   * 'mymodule_autocomplete/$some_key/$some_id?q=keywords'
   *
   * @param array $element
   *   The form element to process. Properties used:
   *   - #autocomplete_route_name: A route to be used as callback URL by the
   *     autocomplete JavaScript library.
   *   - #autocomplete_route_parameters: The parameters to be used in
   *     conjunction with the route name.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   * @param array $complete_form
   *   The complete form structure.
   *
   * @return array
   *   The form element.
   */
  public static function processAutocomplete(&$element, FormStateInterface $form_state, &$complete_form) {
    $access = FALSE;
    if (!empty($element['#autocomplete_route_name'])) {
      $parameters = isset($element['#autocomplete_route_parameters']) ? $element['#autocomplete_route_parameters'] : array();

      $path = \Drupal::urlGenerator()->generate($element['#autocomplete_route_name'], $parameters);
      $access = \Drupal::service('access_manager')->checkNamedRoute($element['#autocomplete_route_name'], $parameters, \Drupal::currentUser());
    }
    if ($access) {
      $element['#attributes']['class'][] = 'form-autocomplete';
      $element['#attached']['library'][] = 'core/drupal.autocomplete';
      // Provide a data attribute for the JavaScript behavior to bind to.
      $element['#attributes']['data-autocomplete-path'] = $path;
    }

    return $element;
  }

}
