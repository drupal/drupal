<?php

/**
 * @file
 * Contains \Drupal\action\Plugin\views\field\BulkForm.
 */

namespace Drupal\action\Plugin\views\field;

use Drupal\Core\Annotation\Plugin;
use Drupal\views\Plugin\views\field\FieldPluginBase;

/**
 * Defines a simple bulk operation form element.
 *
 * @Plugin(
 *   id = "action_bulk_form",
 *   module = "action"
 * )
 */
class BulkForm extends FieldPluginBase {

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
    if (!empty($this->view->style_plugin) && $this->view->style_plugin instanceof \Drupal\views\Plugin\views\style\Table) {
      // Add the tableselect css classes.
      $this->options['element_label_class'] .= 'select-all';
      // Hide the actual label of the field on the table header.
      $this->options['label'] = '';
    }
  }

  /**
   * Implements \Drupal\views\Plugin\views\Plugin\field\FieldPluginBase::views_form().
   */
  public function views_form(&$form, &$form_state) {
    // Add the tableselect javascript.
    $form['#attached']['library'][] = array('system', 'drupal.tableselect');

    // Render checkboxes for all rows.
    foreach ($this->view->result as $row_index => $row) {
      $entity_id = $this->get_value($row);

      $form[$this->options['id']][$row_index] = array(
        '#type' => 'checkbox',
        '#default_value' => FALSE,
      );
    }

    $form[$this->options['id']]['#tree'] = TRUE;

    // Get all available actions.
    $actions = action_get_all_actions();
    $entity_type = $this->getEntityType();
    // Filter actions by the entity type and build options for the form.
    $actions = array_filter($actions, function($action) use ($entity_type) {
      return $action['type'] == $entity_type && empty($action['configurable']);
    });
    $options = array_map(function($action) {
      return $action['label'];
    }, $actions);

    $form['action'] = array(
      '#type' => 'select',
      '#title' => t('Action'),
      '#options' => $options,
      '#description' => t('Select the action you want to execute on the content entitites.'),
    );

    // Move the submit button beside the selection.
    $form['actions']['#weight'] = 1;

      // Replace the text with Update.
    $form['actions']['submit']['#value'] = t('Update');

    // Put the submit button both at the top and bottom.
    $form['actions_bottom'] = $form['actions'];
    $form['actions_bottom']['#weight'] = 100;
  }

  /**
   * Implements \Drupal\views\Plugin\views\Plugin\field\FieldPluginBase::views_form_submit().
   */
  public function views_form_submit(&$form, &$form_state) {
    if ($form_state['step'] == 'views_form_views_form') {
      $action = $form_state['values']['action'];
      $action = action_load($action);
      $count = 0;

      // Filter only selected checkboxes.
      $selected = array_filter($form_state['values'][$this->options['id']]);

      if (!empty($selected)) {
        foreach (array_keys($selected) as $row_index) {
          $entity = $this->get_entity($this->view->result[$row_index]);
          actions_do($action->aid, $entity);
          $entity->save();
          $count++;
        }
      }

      if ($count) {
        drupal_set_message(t('%action action performed on %count item(s).', array('%action' => $action->label, '%count' => $count)));
      }
    }
  }

  /**
   * Overrides \Drupal\views\Plugin\views\Plugin\field\FieldPluginBase::query().
   */
  public function query() {
  }

}
