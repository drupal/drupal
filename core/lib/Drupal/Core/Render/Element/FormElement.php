<?php

/**
 * @file
 * Contains \Drupal\Core\Render\Element\FormElement.
 */

namespace Drupal\Core\Render\Element;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\BubbleableMetadata;
use Drupal\Core\Url;

/**
 * Provides a base class for form element plugins.
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
   */
  public static function processPattern(&$element, FormStateInterface $form_state, &$complete_form) {
    if (isset($element['#pattern']) && !isset($element['#attributes']['pattern'])) {
      $element['#attributes']['pattern'] = $element['#pattern'];
      $element['#element_validate'][] = array(get_called_class(), 'validatePattern');
    }

    return $element;
  }

  /**
   * #element_validate callback for #pattern form element property.
   *
   * @param $element
   *   An associative array containing the properties and children of the
   *   generic form element.
   * @param $form_state
   *   The current state of the form.
   * @param array $complete_form
   *   The complete form structure.
   */
  public static function validatePattern(&$element, FormStateInterface $form_state, &$complete_form) {
    if ($element['#value'] !== '') {
      // The pattern must match the entire string and should have the same
      // behavior as the RegExp object in ECMA 262.
      // - Use bracket-style delimiters to avoid introducing a special delimiter
      //   character like '/' that would have to be escaped.
      // - Put in brackets so that the pattern can't interfere with what's
      //   prepended and appended.
      $pattern = '{^(?:' . $element['#pattern'] . ')$}';

      if (!preg_match($pattern, $element['#value'])) {
        $form_state->setError($element, t('%name field is not in the right format.', array('%name' => $element['#title'])));
      }
    }
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
    $url = NULL;
    $access = FALSE;

    if (!empty($element['#autocomplete_route_name'])) {
      $parameters = isset($element['#autocomplete_route_parameters']) ? $element['#autocomplete_route_parameters'] : array();
      $url = Url::fromRoute($element['#autocomplete_route_name'], $parameters)->toString(TRUE);
      /** @var \Drupal\Core\Access\AccessManagerInterface $access_manager */
      $access_manager = \Drupal::service('access_manager');
      $access = $access_manager->checkNamedRoute($element['#autocomplete_route_name'], $parameters, \Drupal::currentUser(), TRUE);
    }

    if ($access) {
      $metadata = BubbleableMetadata::createFromRenderArray($element);
      if ($access->isAllowed()) {
        $element['#attributes']['class'][] = 'form-autocomplete';
        $element['#attached']['library'][] = 'core/drupal.autocomplete';
        // Provide a data attribute for the JavaScript behavior to bind to.
        $element['#attributes']['data-autocomplete-path'] = $url->getGeneratedUrl();
        $metadata->merge($url);
      }
      $metadata
        ->merge(BubbleableMetadata::createFromObject($access))
        ->applyTo($element);
    }

    return $element;
  }

}
