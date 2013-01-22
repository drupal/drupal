<?php

/**
 * @file
 * Contains \Drupal\action\Plugin\views\field\BulkForm.
 */

namespace Drupal\action\Plugin\views\field;

use Drupal\Core\Annotation\Plugin;
use Drupal\system\Plugin\views\field\BulkFormBase;

/**
 * Defines a actions-based bulk operation form element.
 *
 * @Plugin(
 *   id = "action_bulk_form",
 *   module = "action"
 * )
 */
class BulkForm extends BulkFormBase {

  /**
   * Implements \Drupal\system\Plugin\views\field\BulkFormBase::getBulkOptions().
   */
  protected function getBulkOptions() {
    // Get all available actions.
    $actions = action_get_all_actions();
    $entity_type = $this->getEntityType();
    // Filter actions by entity type and build select options.
    $actions = array_filter($actions, function($action) use ($entity_type) {
      return $action['type'] == $entity_type && empty($action['configurable']);
    });
    return array_map(function($action) {
      return $action['label'];
    }, $actions);
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
