<?php

namespace Drupal\Core\Render;

use Drupal\Core\Access\AccessResultInterface;

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
    return is_string($key) && $key[0] == '#';
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
    return array_filter(array_keys($element), [static::class, 'property']);
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
   * The children of an element array are those key/value pairs whose key does
   * not start with a '#'. See \Drupal\Core\Render\RendererInterface::render()
   * for details.
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
    $count = count($elements);
    $child_weights = [];
    $i = 0;
    $sortable = FALSE;
    foreach ($elements as $key => $value) {
      if (is_int($key) || $key === '' || $key[0] !== '#') {
        if (is_array($value)) {
          if (isset($value['#weight'])) {
            $weight = $value['#weight'];
            $sortable = TRUE;
          }
          else {
            $weight = 0;
          }
          // Supports weight with up to three digit precision and conserve
          // the insertion order.
          $child_weights[$key] = floor($weight * 1000) + $i / $count;
        }
        // Only trigger an exception if the value is not null.
        // @see https://www.drupal.org/node/1283892
        elseif (isset($value)) {
          throw new \InvalidArgumentException(sprintf('"%s" is an invalid render array key. Value should be an array but got a %s.', $key, gettype($value)));
        }
      }
      $i++;
    }

    // Sort the children if necessary.
    if ($sort && $sortable) {
      asort($child_weights);
      // Put the sorted children back into $elements in the correct order, to
      // preserve sorting if the same element is passed through
      // \Drupal\Core\Render\Element::children() twice.
      foreach ($child_weights as $key => $weight) {
        $value = $elements[$key];
        unset($elements[$key]);
        $elements[$key] = $value;
      }
      $elements['#sorted'] = TRUE;
    }

    return array_keys($child_weights);
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
    $visible_children = [];

    foreach (static::children($elements) as $key) {
      $child = $elements[$key];

      // Skip value and hidden elements, since they are not rendered.
      if (!static::isVisibleElement($child)) {
        continue;
      }

      $visible_children[$key] = $child;
    }

    return array_keys($visible_children);
  }

  /**
   * Determines if an element is visible.
   *
   * @param array $element
   *   The element to check for visibility.
   *
   * @return bool
   *   TRUE if the element is visible, otherwise FALSE.
   */
  public static function isVisibleElement($element) {
    return (!isset($element['#type']) || !in_array($element['#type'], ['value', 'hidden', 'token']))
      && (!isset($element['#access'])
      || (($element['#access'] instanceof AccessResultInterface && $element['#access']->isAllowed()) || ($element['#access'] === TRUE)));
  }

  /**
   * Sets HTML attributes based on element properties.
   *
   * @param array $element
   *   The renderable element to process. Passed by reference.
   * @param array $map
   *   An associative array whose keys are element property names and whose
   *   values are the HTML attribute names to set on the corresponding property.
   *   for example, ['#property_name' => 'attribute_name']. If both names are
   *   identical except for the leading '#', then an attribute name value is
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

  /**
   * Indicates whether the given element is empty.
   *
   * An element that only has #cache or #weight set is considered
   * empty, because it will render to the empty string.
   *
   * @param array $elements
   *   The element.
   *
   * @return bool
   *   Whether the given element is empty.
   */
  public static function isEmpty(array $elements) {
    return \array_diff(\array_keys($elements), ['#cache', '#weight']) === [];
  }

  /**
   * Checks if a candidate is a render array.
   *
   * @param mixed $candidate
   *   The candidate.
   *
   * @return bool
   *   TRUE if it's a render array. FALSE otherwise.
   */
  public static function isRenderArray($candidate): bool {
    if (!is_array($candidate)) {
      return FALSE;
    }
    if (empty($candidate)) {
      return FALSE;
    }
    foreach ($candidate as $key => $value) {
      if (!is_int($key) && $key !== '' && $key[0] === '#') {
        continue;
      }
      if (!is_array($value)) {
        return FALSE;
      }
      if (!static::isRenderArray($value)) {
        return FALSE;
      }
    }
    return TRUE;
  }

}
