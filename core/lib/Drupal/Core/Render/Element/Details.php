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
 * - #summary_attributes: An array of attributes to apply to the <summary>
 *   element.
 *
 * Usage example:
 * @code
 * $form['author'] = array(
 *   '#type' => 'details',
 *   '#title' => $this->t('Author'),
 * );
 *
 * $form['author']['name'] = array(
 *   '#type' => 'textfield',
 *   '#title' => $this->t('Name'),
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
    return [
      '#open' => FALSE,
      '#summary_attributes' => [],
      '#value' => NULL,
      '#process' => [
        [$class, 'processGroup'],
        [$class, 'processAjaxForm'],
      ],
      '#pre_render' => [
        [$class, 'preRenderDetails'],
        [$class, 'preRenderGroup'],
      ],
      '#theme_wrappers' => ['details'],
    ];
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
    Element::setAttributes($element, ['id']);

    // The .js-form-wrapper class is required for #states to treat details like
    // containers.
    static::setAttributes($element, ['js-form-wrapper', 'form-wrapper']);

    // Collapsible details.
    $element['#attached']['library'][] = 'core/drupal.collapse';

    // Open the detail if specified or if a child has an error.
    if (!empty($element['#open']) || !empty($element['#children_errors'])) {
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
