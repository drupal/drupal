<?php

namespace Drupal\Core\Render\Element;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;

/**
 * Provides a render element for vertical tabs in a form.
 *
 * Formats all child and non-child details elements whose #group is assigned
 * this element's name as vertical tabs.
 *
 * Properties:
 * - #default_tab: The HTML ID of the rendered details element to be used as
 *   the default tab. View the source of the rendered page to determine the ID.
 *
 * Usage example:
 * @code
 * $form['information'] = array(
 *   '#type' => 'vertical_tabs',
 *   '#default_tab' => 'edit-publication',
 * );
 *
 * $form['author'] = array(
 *   '#type' => 'details',
 *   '#title' => $this->t('Author'),
 *   '#group' => 'information',
 * );
 *
 * $form['author']['name'] = array(
 *   '#type' => 'textfield',
 *   '#title' => $this->t('Name'),
 * );
 *
 * $form['publication'] = array(
 *   '#type' => 'details',
 *   '#title' => $this->t('Publication'),
 *   '#group' => 'information',
 * );
 *
 * $form['publication']['publisher'] = array(
 *   '#type' => 'textfield',
 *   '#title' => $this->t('Publisher'),
 * );
 * @endcode
 *
 * @FormElement("vertical_tabs")
 */
class VerticalTabs extends RenderElement {

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    $class = static::class;
    return [
      '#default_tab' => '',
      '#process' => [
        [$class, 'processVerticalTabs'],
      ],
      '#pre_render' => [
        [$class, 'preRenderVerticalTabs'],
      ],
      '#theme_wrappers' => ['vertical_tabs', 'form_element'],
    ];
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
    if (isset($element['#access']) && !$element['#access']) {
      return $element;
    }

    // Inject a new details as child, so that form_process_details() processes
    // this details element like any other details.
    $element['group'] = [
      '#type' => 'details',
      '#theme_wrappers' => [],
      '#parents' => $element['#parents'],
    ];

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
    if ($form_state->hasValue($name . '__active_tab')) {
      $element['#default_tab'] = $form_state->getValue($name . '__active_tab');
    }
    $element[$name . '__active_tab'] = [
      '#type' => 'hidden',
      '#default_value' => $element['#default_tab'],
      '#attributes' => ['class' => ['vertical-tabs__active-tab']],
    ];
    // Clean up the active tab value so it's not accidentally stored in
    // settings forms.
    $form_state->addCleanValueKey($name . '__active_tab');

    return $element;
  }

}
