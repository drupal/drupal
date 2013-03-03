<?php

/**
 * @file
 * Contains \Drupal\views_ui\Form\Ajax\Rearrange.
 */

namespace Drupal\views_ui\Form\Ajax;

use Drupal\views\ViewStorageInterface;
use Drupal\views\ViewExecutable;

/**
 * Provides a rearrange form for Views handlers.
 */
class Rearrange extends ViewsFormBase {

  /**
   * Constucts a new Rearrange object.
   */
  public function __construct($type = NULL) {
    $this->setType($type);
  }

  /**
   * Implements \Drupal\views_ui\Form\Ajax\ViewsFormInterface::getFormKey().
   */
  public function getFormKey() {
    return 'rearrange';
  }

  /**
   * Overrides \Drupal\views_ui\Form\Ajax\ViewsFormBase::getForm().
   */
  public function getForm(ViewStorageInterface $view, $display_id, $js, $type = NULL) {
    $this->setType($type);
    return parent::getForm($view, $display_id, $js);
  }

  /**
   * Implements \Drupal\Core\Form\FormInterface::getFormID().
   */
  public function getFormID() {
    return 'views_ui_rearrange_form';
  }

  /**
   * Implements \Drupal\Core\Form\FormInterface::buildForm().
   */
  public function buildForm(array $form, array &$form_state) {
    $view = &$form_state['view'];
    $display_id = $form_state['display_id'];
    $type = $form_state['type'];

    $types = ViewExecutable::viewsHandlerTypes();
    $executable = $view->get('executable');
    if (!$executable->setDisplay($display_id)) {
      views_ajax_error(t('Invalid display id @display', array('@display' => $display_id)));
    }
    $display = &$executable->displayHandlers->get($display_id);
    $form['#title'] = t('Rearrange @type', array('@type' => $types[$type]['ltitle']));
    $form['#section'] = $display_id . 'rearrange-item';

    if ($display->defaultableSections($types[$type]['plural'])) {
      $form_state['section'] = $types[$type]['plural'];
      views_ui_standard_display_dropdown($form, $form_state, $form_state['section']);
    }

    $count = 0;

    // Get relationship labels
    $relationships = array();
    foreach ($display->getHandlers('relationship') as $id => $handler) {
      $relationships[$id] = $handler->label();
    }

    // Filters can now be grouped so we do a little bit extra:
    $groups = array();
    $grouping = FALSE;
    if ($type == 'filter') {
      $group_info = $executable->display_handler->getOption('filter_groups');
      if (!empty($group_info['groups']) && count($group_info['groups']) > 1) {
        $grouping = TRUE;
        $groups = array(0 => array());
      }
    }

    foreach ($display->getOption($types[$type]['plural']) as $id => $field) {
      $form['fields'][$id] = array('#tree' => TRUE);
      $form['fields'][$id]['weight'] = array(
        '#type' => 'textfield',
        '#default_value' => ++$count,
      );
      $handler = $display->getHandler($type, $id);
      if ($handler) {
        $name = $handler->adminLabel() . ' ' . $handler->adminSummary();
        if (!empty($field['relationship']) && !empty($relationships[$field['relationship']])) {
          $name = '(' . $relationships[$field['relationship']] . ') ' . $name;
        }

        $form['fields'][$id]['name'] = array(
          '#markup' => $name,
        );
      }
      else {
        $form['fields'][$id]['name'] = array('#markup' => t('Broken field @id', array('@id' => $id)));
      }
      $form['fields'][$id]['removed'] = array(
        '#type' => 'checkbox',
        '#id' => 'views-removed-' . $id,
        '#attributes' => array('class' => array('views-remove-checkbox')),
        '#default_value' => 0,
      );
    }

    // Add javascript settings that will be added via $.extend for tabledragging
    $form['#js']['tableDrag']['arrange']['weight'][0] = array(
      'target' => 'weight',
      'source' => NULL,
      'relationship' => 'sibling',
      'action' => 'order',
      'hidden' => TRUE,
      'limit' => 0,
    );

    $name = NULL;
    if (isset($form_state['update_name'])) {
      $name = $form_state['update_name'];
    }

    $view->getStandardButtons($form, $form_state, 'views_ui_rearrange_form');
    return $form;
  }

  /**
   * Overrides \Drupal\views_ui\Form\Ajax\ViewsFormBase::submitForm().
   */
  public function submitForm(array &$form, array &$form_state) {
    $types = ViewExecutable::viewsHandlerTypes();
    $display = &$form_state['view']->get('executable')->displayHandlers->get($form_state['display_id']);

    $old_fields = $display->getOption($types[$form_state['type']]['plural']);
    $new_fields = $order = array();

    // Make an array with the weights
    foreach ($form_state['values'] as $field => $info) {
      // add each value that is a field with a weight to our list, but only if
      // it has had its 'removed' checkbox checked.
      if (is_array($info) && isset($info['weight']) && empty($info['removed'])) {
        $order[$field] = $info['weight'];
      }
    }

    // Sort the array
    asort($order);

    // Create a new list of fields in the new order.
    foreach (array_keys($order) as $field) {
      $new_fields[$field] = $old_fields[$field];
    }
    $display->setOption($types[$form_state['type']]['plural'], $new_fields);

    // Store in cache
    views_ui_cache_set($form_state['view']);
  }

}
