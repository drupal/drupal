<?php

/**
 * @file
 * Contains \Drupal\system\Plugin\views\field\BulkFormBase.
 */

namespace Drupal\system\Plugin\views\field;

use Drupal\Core\Annotation\Plugin;
use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\views\Plugin\views\style\Table;

/**
 * Defines a generic bulk operation form element.
 */
abstract class BulkFormBase extends FieldPluginBase {

  /**
   * Overrides \Drupal\views\Plugin\views\Plugin\field\FieldPluginBase::render().
   */
  public function render($values) {
    return '<!--form-item-' . $this->options['id'] . '--' . $this->view->row_index . '-->';
  }

  /**
   * Overrides \Drupal\views\Plugin\views\Plugin\field\FieldPluginBase::pre_render().
   */
  public function pre_render(&$values) {
    parent::pre_render($values);

    // If the view is using a table style, provide a placeholder for a
    // "select all" checkbox.
    if (!empty($this->view->style_plugin) && $this->view->style_plugin instanceof Table) {
      // Add the tableselect css classes.
      $this->options['element_label_class'] .= 'select-all';
      // Hide the actual label of the field on the table header.
      $this->options['label'] = '';
    }
  }

  /**
   * Form constructor for the bulk form.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param array $form_state
   *   An associative array containing the current state of the form.
   */
  public function views_form(&$form, &$form_state) {
    // Add the tableselect javascript.
    $form['#attached']['library'][] = array('system', 'drupal.tableselect');

    // Render checkboxes for all rows.
    $form[$this->options['id']]['#tree'] = TRUE;
    foreach ($this->view->result as $row_index => $row) {
      $form[$this->options['id']][$row_index] = array(
        '#type' => 'checkbox',
        // We are not able to determine a main "title" for each row, so we can
        // only output a generic label.
        '#title' => t('Update this item'),
        '#title_display' => 'invisible',
        '#default_value' => !empty($form_state['values'][$this->options['id']][$row_index]) ? 1 : NULL,
      );
    }

    // Replace the form submit button label.
    $form['actions']['submit']['#value'] = t('Apply');

    // Ensure a consistent container for filters/operations in the view header.
    $form['header'] = array(
      '#type' => 'container',
      '#weight' => -100,
    );

    // Build the bulk operations action widget for the header.
    // Allow themes to apply .container-inline on this separate container.
    $form['header'][$this->options['id']] = array(
      '#type' => 'container',
    );
    $form['header'][$this->options['id']]['action'] = array(
      '#type' => 'select',
      '#title' => t('With selection'),
      '#options' => $this->getBulkOptions(),
    );

    // Duplicate the form actions into the action container in the header.
    $form['header'][$this->options['id']]['actions'] = $form['actions'];
  }

  /**
   * Returns the available operations for this form.
   *
   * @return array
   *   An associative array of operations, suitable for a select element.
   */
  abstract protected function getBulkOptions();

  /**
   * Submit handler for the bulk form.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param array $form_state
   *   An associative array containing the current state of the form.
   */
  abstract public function views_form_submit(&$form, &$form_state);

  /**
   * Overrides \Drupal\views\Plugin\views\Plugin\field\FieldPluginBase::query().
   */
  public function query() {
  }

}
