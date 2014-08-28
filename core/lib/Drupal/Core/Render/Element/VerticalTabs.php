<?php

/**
 * @file
 * Contains \Drupal\Core\Render\Element\VerticalTabs.
 */

namespace Drupal\Core\Render\Element;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;

/**
 * Provides a render element for vertical tabs in a form.
 *
 * Formats all child fieldsets and all non-child fieldsets whose #group is
 * assigned this element's name as vertical tabs.
 *
 * @FormElement("vertical_tabs")
 */
class VerticalTabs extends RenderElement {

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    $class = get_class($this);
    return array(
      '#default_tab' => '',
      '#process' => array(
        array($class, 'processVerticalTabs'),
      ),
      '#pre_render' => array(
        array($class, 'preRenderVerticalTabs'),
      ),
      '#theme_wrappers' => array('vertical_tabs', 'form_element'),
    );
  }

  /**
   * Prepares a vertical_tabs element for rendering.
   *
   * @param array $element
   *   An associative array containing the properties and children of the
   *   vertical tabs element.
   *
   * @return array
   *   The modified element.
   */
  public static function preRenderVerticalTabs($element) {
    // Do not render the vertical tabs element if it is empty.
    $group = implode('][', $element['#parents']);
    if (!Element::getVisibleChildren($element['group']['#groups'][$group])) {
      $element['#printed'] = TRUE;
    }
    return $element;
  }

  /**
   * Creates a group formatted as vertical tabs.
   *
   * @param array $element
   *   An associative array containing the properties and children of the
   *   details element.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   * @param array $complete_form
   *   The complete form structure.
   *
   * @return array
   *   The processed element.
   */
  public static function processVerticalTabs(&$element, FormStateInterface $form_state, &$complete_form) {
    // Inject a new details as child, so that form_process_details() processes
    // this details element like any other details.
    $element['group'] = array(
      '#type' => 'details',
      '#theme_wrappers' => array(),
      '#parents' => $element['#parents'],
    );

    // Add an invisible label for accessibility.
    if (!isset($element['#title'])) {
      $element['#title'] = t('Vertical Tabs');
      $element['#title_display'] = 'invisible';
    }

    $element['#attached']['library'][] = 'core/drupal.vertical-tabs';

    // The JavaScript stores the currently selected tab in this hidden
    // field so that the active tab can be restored the next time the
    // form is rendered, e.g. on preview pages or when form validation
    // fails.
    $name = implode('__', $element['#parents']);
    if ($form_state->hasValue($name . '__active_tab')){
      $element['#default_tab'] = $form_state->getValue($name . '__active_tab');
    }
    $element[$name . '__active_tab'] = array(
      '#type' => 'hidden',
      '#default_value' => $element['#default_tab'],
      '#attributes' => array('class' => array('vertical-tabs-active-tab')),
    );

    return $element;
  }

}
