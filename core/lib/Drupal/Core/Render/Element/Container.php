<?php

namespace Drupal\Core\Render\Element;

use Drupal\Component\Utility\Html as HtmlUtility;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;

/**
 * Provides a render element that wraps child elements in a container.
 *
 * Surrounds child elements with a <div> and adds attributes such as classes or
 * an HTML ID.
 *
 * Properties:
 * - #optional: Indicates whether the container should render when it has no
 *   visible children. Defaults to FALSE.
 *
 * Usage example:
 * @code
 * $form['needs_accommodation'] = [
 *   '#type' => 'checkbox',
 *   '#title' => $this->t('Need Special Accommodations?'),
 * ];
 *
 * $form['accommodation'] = [
 *   '#type' => 'container',
 *   '#attributes' => [
 *     'class' => ['accommodation'],
 *   ],
 *   '#states' => [
 *     'invisible' => [
 *       'input[name="needs_accommodation"]' => ['checked' => FALSE],
 *     ],
 *   ],
 * ];
 *
 * $form['accommodation']['diet'] = [
 *   '#type' => 'textfield',
 *   '#title' => $this->t('Dietary Restrictions'),
 * ];
 * @endcode
 *
 * @RenderElement("container")
 */
class Container extends RenderElement {

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    $class = static::class;
    return [
      '#optional' => FALSE,
      '#process' => [
        [$class, 'processGroup'],
        [$class, 'processContainer'],
      ],
      '#pre_render' => [
        [$class, 'preRenderGroup'],
        [$class, 'preRenderContainer'],
      ],
      '#theme_wrappers' => ['container'],
    ];
  }

  /**
   * Processes a container element.
   *
   * @param array $element
   *   An associative array containing the properties and children of the
   *   container.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   * @param array $complete_form
   *   The complete form structure.
   *
   * @return array
   *   The processed element.
   */
  public static function processContainer(&$element, FormStateInterface $form_state, &$complete_form) {
    // Generate the ID of the element if it's not explicitly given.
    if (!isset($element['#id'])) {
      $element['#id'] = HtmlUtility::getUniqueId(implode('-', $element['#parents']) . '-wrapper');
    }
    return $element;
  }

  /**
   * Prevents optional containers from rendering if they have no children.
   *
   * @param array $element
   *   An associative array containing the properties and children of the
   *   container.
   *
   * @return array
   *   The modified element.
   */
  public static function preRenderContainer($element) {
    // Do not render optional container elements if there are no children.
    if (empty($element['#printed']) && !empty($element['#optional']) && !Element::getVisibleChildren($element)) {
      $element['#printed'] = TRUE;
    }
    return $element;
  }

}
