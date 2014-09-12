<?php

/**
 * @file
 * Contains \Drupal\Core\Render\Element\RenderElement.
 */

namespace Drupal\Core\Render\Element;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\PluginBase;
use Drupal\Core\Render\Element;

/**
 * Provides a base class for render element plugins.
 *
 * @see \Drupal\Core\Render\Annotation\RenderElement
 * @see \Drupal\Core\Render\ElementInterface
 * @see \Drupal\Core\Render\ElementInfoManager
 * @see plugin_api
 *
 * @ingroup theme_render
 */
abstract class RenderElement extends PluginBase implements ElementInterface {

  /**
   * {@inheritdoc}
   */
  public static function setAttributes(&$element, $class = array()) {
    if (!empty($class)) {
      if (!isset($element['#attributes']['class'])) {
        $element['#attributes']['class'] = array();
      }
      $element['#attributes']['class'] = array_merge($element['#attributes']['class'], $class);
    }
    // This function is invoked from form element theme functions, but the
    // rendered form element may not necessarily have been processed by
    // form_builder().
    if (!empty($element['#required'])) {
      $element['#attributes']['class'][] = 'required';
      $element['#attributes']['required'] = 'required';
      $element['#attributes']['aria-required'] = 'true';
    }
    if (isset($element['#parents']) && isset($element['#errors']) && !empty($element['#validated'])) {
      $element['#attributes']['class'][] = 'error';
      $element['#attributes']['aria-invalid'] = 'true';
    }
  }

  /**
   * Adds members of this group as actual elements for rendering.
   *
   * @param array $element
   *   An associative array containing the properties and children of the
   *   element.
   *
   * @return array
   *   The modified element with all group members.
   */
  public static function preRenderGroup($element) {
    // The element may be rendered outside of a Form API context.
    if (!isset($element['#parents']) || !isset($element['#groups'])) {
      return $element;
    }

    // Inject group member elements belonging to this group.
    $parents = implode('][', $element['#parents']);
    $children = Element::children($element['#groups'][$parents]);
    if (!empty($children)) {
      foreach ($children as $key) {
        // Break references and indicate that the element should be rendered as
        // group member.
        $child = (array) $element['#groups'][$parents][$key];
        $child['#group_details'] = TRUE;
        // Inject the element as new child element.
        $element[] = $child;

        $sort = TRUE;
      }
      // Re-sort the element's children if we injected group member elements.
      if (isset($sort)) {
        $element['#sorted'] = FALSE;
      }
    }

    if (isset($element['#group'])) {
      // Contains form element summary functionalities.
      $element['#attached']['library'][] = 'core/drupal.form';

      $group = $element['#group'];
      // If this element belongs to a group, but the group-holding element does
      // not exist, we need to render it (at its original location).
      if (!isset($element['#groups'][$group]['#group_exists'])) {
        // Intentionally empty to clarify the flow; we simply return $element.
      }
      // If we injected this element into the group, then we want to render it.
      elseif (!empty($element['#group_details'])) {
        // Intentionally empty to clarify the flow; we simply return $element.
      }
      // Otherwise, this element belongs to a group and the group exists, so we do
      // not render it.
      elseif (Element::children($element['#groups'][$group])) {
        $element['#printed'] = TRUE;
      }
    }

    return $element;
  }

  /**
   * Form element processing handler for the #ajax form property.
   *
   * This method is useful for non-input elements that can be used in and
   * outside the context of a form.
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
      $form_state->setCached();
    }
    return $element;
  }

  /**
   * Arranges elements into groups.
   *
   * This method is useful for non-input elements that can be used in and
   * outside the context of a form.
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
    $groups = &$form_state->getGroups();
    $groups[$parents]['#group_exists'] = TRUE;
    $element['#groups'] = &$groups;

    // Process vertical tabs group member details elements.
    if (isset($element['#group'])) {
      // Add this details element to the defined group (by reference).
      $group = $element['#group'];
      $groups[$group][] = &$element;
    }

    return $element;
  }

}
