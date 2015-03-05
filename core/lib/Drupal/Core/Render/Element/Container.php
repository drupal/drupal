<?php

/**
 * @file
 * Contains \Drupal\Core\Render\Element\Container.
 */

namespace Drupal\Core\Render\Element;

use Drupal\Component\Utility\Html as HtmlUtility;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a render element that wraps child elements in a container.
 *
 * Surrounds child elements with a <div> and adds attributes such as classes or
 * an HTML ID.
 *
 * @RenderElement("container")
 */
class Container extends RenderElement {

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    $class = get_class($this);
    return array(
      '#process' => array(
        array($class, 'processGroup'),
        array($class, 'processContainer'),
      ),
      '#pre_render' => array(
        array($class, 'preRenderGroup'),
      ),
      '#theme_wrappers' => array('container'),
    );
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

}
