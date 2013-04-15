<?php

/**
 * @file
 * Contains \Drupal\action\Plugin\views\field\BulkForm.
 */

namespace Drupal\action\Plugin\views\field;

use Drupal\Component\Annotation\PluginID;
use Drupal\system\Plugin\views\field\BulkFormBase;

/**
 * Defines a actions-based bulk operation form element.
 *
 * @PluginID("action_bulk_form")
 */
class BulkForm extends BulkFormBase {

  /**
   * Overrides \Drupal\views\Plugin\views\field\FieldPluginBase::defineOptions().
   */
  protected function defineOptions() {
    $options = parent::defineOptions();
    $options['include_exclude'] = array(
      'default' => 'exclude',
    );
    $options['selected_actions'] = array(
      'default' => array(),
    );
    return $options;
  }

  /**
   * Overrides \Drupal\views\Plugin\views\field\FieldPluginBase::buildOptionsForm().
   */
  public function buildOptionsForm(&$form, &$form_state) {
    parent::buildOptionsForm($form, $form_state);

    $form['include_exclude'] = array(
      '#type' => 'radios',
      '#title' => t('Available actions'),
      '#options' => array(
        'exclude' => t('All actions, except selected'),
        'include' => t('Only selected actions'),
      ),
      '#default_value' => $this->options['include_exclude'],
    );
    $form['selected_actions'] = array(
      '#type' => 'checkboxes',
      '#title' => t('Selected actions'),
      '#options' => $this->getBulkOptions(FALSE),
      '#default_value' => $this->options['selected_actions'],
    );
  }

  /**
   * Overrides \Drupal\views\Plugin\views\PluginBase::buildOptionsForm().
   */
  public function validateOptionsForm(&$form, &$form_state) {
    parent::validateOptionsForm($form, $form_state);

    $form_state['values']['options']['selected_actions'] = array_filter($form_state['values']['options']['selected_actions']);
  }

  /**
   * Implements \Drupal\system\Plugin\views\field\BulkFormBase::getBulkOptions().
   *
   * @param bool $filtered
   *   (optional) Whether to filter actions to selected actions.
   */
  protected function getBulkOptions($filtered = TRUE) {
    // Get all available actions.
    $actions = action_get_all_actions();
    $entity_type = $this->getEntityType();
    $options = array();
    // Filter the action list.
    foreach ($actions as $id => $action) {
      if ($filtered) {
        $in_selected = in_array($id, $this->options['selected_actions']);
        // If the field is configured to include only the selected actions,
        // skip actions that were not selected.
        if (($this->options['include_exclude'] == 'include') && !$in_selected) {
          continue;
        }
        // Otherwise, if the field is configured to exclude the selected
        // actions, skip actions that were selected.
        elseif (($this->options['include_exclude'] == 'exclude') && $in_selected) {
          continue;
        }
      }
      // Only allow actions that are valid for this entity type.
      if (($action['type'] == $entity_type) && empty($action['configurable'])) {
        $options[$id] = $action['label'];
      }
    }

    return $options;
  }

  /**
   * Implements \Drupal\system\Plugin\views\field\BulkFormBase::views_form_submit().
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
        drupal_set_message(format_plural($count, '%action was applied to @count item.', '%action was applied to @count items.', array(
          '%action' => $action->label,
        )));
      }
    }
  }

}
