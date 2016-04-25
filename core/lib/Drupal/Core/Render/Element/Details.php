<?php

namespace Drupal\Core\Render\Element;

use Drupal\Core\Render\Element;

/**
 * Provides a render element for a details element, similar to a fieldset.
 *
 * Fieldsets can only be used in forms, while details elements can be used
 * outside of forms. Users click on the title to open or close the details
 * element, showing or hiding the contained elements.
 *
 * Properties:
 * - #title: The title of the details container. Defaults to "Details".
 * - #open: Indicates whether the container should be open by default.
 *   Defaults to FALSE.
 *
 * Usage example:
 * @code
 * $form['author'] = array(
 *   '#type' => 'details',
 *   '#title' => 'Author',
 * );
 *
 * $form['author']['name'] = array(
 *   '#type' => 'textfield',
 *   '#title' => t('Name'),
 * );
 * @endcode
 *
 * @see \Drupal\Core\Render\Element\Fieldset
 * @see \Drupal]Core\Render\Element\VerticalTabs
 *
 * @RenderElement("details")
 */
class Details extends RenderElement {

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    $class = get_class($this);
    return array(
      '#open' => FALSE,
      '#value' => NULL,
      '#process' => array(
        array($class, 'processGroup'),
        array($class, 'processAjaxForm'),
      ),
      '#pre_render' => array(
        array($class, 'preRenderDetails'),
        array($class, 'preRenderGroup'),
      ),
      '#theme_wrappers' => array('details'),
    );
  }

  /**
   * Adds form element theming to details.
   *
   * @param $element
   *   An associative array containing the properties and children of the
   *   details.
   *
   * @return
   *   The modified element.
   */
  public static function preRenderDetails($element) {
    Element::setAttributes($element, array('id'));

    // The .js-form-wrapper class is required for #states to treat details like
    // containers.
    static::setAttributes($element, array('js-form-wrapper', 'form-wrapper'));

    // Collapsible details.
    $element['#attached']['library'][] = 'core/drupal.collapse';
    if (!empty($element['#open'])) {
      $element['#attributes']['open'] = 'open';
    }

    // Do not render optional details elements if there are no children.
    if (isset($element['#parents'])) {
      $group = implode('][', $element['#parents']);
      if (!empty($element['#optional']) && !Element::getVisibleChildren($element['#groups'][$group])) {
        $element['#printed'] = TRUE;
      }
    }

    return $element;
  }

}
