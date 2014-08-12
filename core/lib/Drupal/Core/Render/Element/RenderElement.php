<?php

/**
 * @file
 * Contains \Drupal\Core\Render\Element\RenderElement.
 */

namespace Drupal\Core\Render\Element;

use Drupal\Core\Plugin\PluginBase;
use Drupal\Core\Render\Element;

/**
 * Provides a base class for element render plugins.
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

}
