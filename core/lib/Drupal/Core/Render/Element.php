<?php

/**
 * @file
 * Contains \Drupal\Core\Render\Element.
 */

namespace Drupal\Core\Render;

use Drupal\Component\Utility\String;

/**
 * Provides helper methods for Drupal render elements.
 *
 * @see \Drupal\Core\Render\Element\ElementInterface
 *
 * @ingroup theme_render
 */
class Element {

  /**
   * Checks if the key is a property.
   *
   * @param string $key
   *   The key to check.
   *
   * @return bool
   *   TRUE of the key is a property, FALSE otherwise.
   */
  public static function property($key) {
    return $key[0] == '#';
  }

  /**
   * Gets properties of a structured array element (keys beginning with '#').
   *
   * @param array $element
   *   An element array to return properties for.
   *
   * @return array
   *   An array of property keys for the element.
   */
  public static function properties(array $element) {
    return array_filter(array_keys($element), 'static::property');
  }

  /**
   * Checks if the key is a child.
   *
   * @param string $key
   *   The key to check.
   *
   * @return bool
   *   TRUE if the element is a child, FALSE otherwise.
   */
  public static function child($key) {
    return !isset($key[0]) || $key[0] != '#';
  }

  /**
   * Identifies the children of an element array, optionally sorted by weight.
   *
   * The children of a element array are those key/value pairs whose key does
   * not start with a '#'. See drupal_render() for details.
   *
   * @param array $elements
   *   The element array whose children are to be identified. Passed by
   *   reference.
   * @param bool $sort
   *   Boolean to indicate whether the children should be sorted by weight.
   *
   * @return array
   *   The array keys of the element's children.
   */
  public static function children(array &$elements, $sort = FALSE) {
    // Do not attempt to sort elements which have already been sorted.
    $sort = isset($elements['#sorted']) ? !$elements['#sorted'] : $sort;

    // Filter out properties from the element, leaving only children.
    $children = array();
    $sortable = FALSE;
    foreach ($elements as $key => $value) {
      if ($key === '' || $key[0] !== '#') {
        if (is_array($value)) {
          $children[$key] = $value;
          if (isset($value['#weight'])) {
            $sortable = TRUE;
          }
        }
        // Only trigger an error if the value is not null.
        // @see http://drupal.org/node/1283892
        elseif (isset($value)) {
          trigger_error(String::format('"@key" is an invalid render array key', array('@key' => $key)), E_USER_ERROR);
        }
      }
    }
    // Sort the children if necessary.
    if ($sort && $sortable) {
      uasort($children, 'Drupal\Component\Utility\SortArray::sortByWeightProperty');
      // Put the sorted children back into $elements in the correct order, to
      // preserve sorting if the same element is passed through
      // \Drupal\Core\Render\Element::children() twice.
      foreach ($children as $key => $child) {
        unset($elements[$key]);
        $elements[$key] = $child;
      }
      $elements['#sorted'] = TRUE;
    }

    return array_keys($children);
  }

  /**
   * Returns the visible children of an element.
   *
   * @param array $elements
   *   The parent element.
   *
   * @return array
   *   The array keys of the element's visible children.
   */
  public static function getVisibleChildren(array $elements) {
    $visible_children = array();

    foreach (static::children($elements) as $key) {
      $child = $elements[$key];

      // Skip un-accessible children.
      if (isset($child['#access']) && !$child['#access']) {
        continue;
      }

      // Skip value and hidden elements, since they are not rendered.
      if (isset($child['#type']) && in_array($child['#type'], array('value', 'hidden'))) {
        continue;
      }

      $visible_children[$key] = $child;
    }

    return array_keys($visible_children);
  }

  /**
   * Sets HTML attributes based on element properties.
   *
   * @param array $element
   *   The renderable element to process. Passed by reference.
   * @param array $map
   *   An associative array whose keys are element property names and whose
   *   values are the HTML attribute names to set on the corresponding
   *   property; e.g., array('#propertyname' => 'attributename'). If both names
   *   are identical except for the leading '#', then an attribute name value is
   *   sufficient and no property name needs to be specified.
   */
  public static function setAttributes(array &$element, array $map) {
    foreach ($map as $property => $attribute) {
      // If the key is numeric, the attribute name needs to be taken over.
      if (is_int($property)) {
        $property = '#' . $attribute;
      }
      // Do not overwrite already existing attributes.
      if (isset($element[$property]) && !isset($element['#attributes'][$attribute])) {
        $element['#attributes'][$attribute] = $element[$property];
      }
    }
  }

}
