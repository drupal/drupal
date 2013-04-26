<?php

/**
 * @file
 * Contains \Drupal\views_ui\Form\Ajax\ReorderDisplays.
 */

namespace Drupal\views_ui\Form\Ajax;

use Drupal\views_ui\ViewUI;

/**
 * Displays the display reorder form.
 */
class ReorderDisplays extends ViewsFormBase {

  /**
   * Implements \Drupal\views_ui\Form\Ajax\ViewsFormInterface::getFormKey().
   */
  public function getFormKey() {
    return 'reorder-displays';
  }

  /**
   * Implements \Drupal\Core\Form\FormInterface::getFormID().
   */
  public function getFormID() {
    return 'views_ui_reorder_displays_form';
  }

  /**
   * Implements \Drupal\Core\Form\FormInterface::buildForm().
   */
  public function buildForm(array $form, array &$form_state) {
    $view = $form_state['view'];
    $display_id = $form_state['display_id'];

    $form['view'] = array('#type' => 'value', '#value' => $view);

    $form['#tree'] = TRUE;

    $count = count($view->get('display'));

    $displays = $view->get('display');
    foreach ($displays as $display) {
      $form[$display['id']] = array(
        'title'  => array('#markup' => $display['display_title']),
        'weight' => array(
          '#type' => 'weight',
          '#value' => $display['position'],
          '#delta' => $count,
          '#title' => t('Weight for @display', array('@display' => $display['display_title'])),
          '#title_display' => 'invisible',
        ),
        '#tree' => TRUE,
        '#display' => $display,
        'removed' => array(
          '#type' => 'checkbox',
          '#id' => 'display-removed-' . $display['id'],
          '#attributes' => array('class' => array('views-remove-checkbox')),
          '#default_value' => isset($display['deleted']),
        ),
      );

      if (isset($display['deleted']) && $display['deleted']) {
        $form[$display['id']]['deleted'] = array('#type' => 'value', '#value' => TRUE);
      }
      if ($display['id'] === 'default') {
        unset($form[$display['id']]['weight']);
        unset($form[$display['id']]['removed']);
      }

    }

    $form['#title'] = t('Displays Reorder');
    $form['#section'] = 'reorder';

    // Add javascript settings that will be added via $.extend for tabledragging
    $form['#js']['tableDrag']['reorder-displays']['weight'][0] = array(
      'target' => 'weight',
      'source' => NULL,
      'relationship' => 'sibling',
      'action' => 'order',
      'hidden' => TRUE,
      'limit' => 0,
    );

    $form['#action'] = url('admin/structure/views/nojs/reorder-displays/' . $view->id() . '/' . $display_id);

    $view->getStandardButtons($form, $form_state, 'views_ui_reorder_displays_form');

    return $form;
  }

  /**
   * Overrides \Drupal\views_ui\Form\Ajax\ViewsFormBase::submitForm().
   */
  public function submitForm(array &$form, array &$form_state) {
    $view = $form_state['view'];
    foreach ($form_state['input'] as $display => $info) {
      // add each value that is a field with a weight to our list, but only if
      // it has had its 'removed' checkbox checked.
      if (is_array($info) && isset($info['weight']) && empty($info['removed'])) {
        $order[$display] = $info['weight'];
      }
    }

    // Sort the order array
    asort($order);

    // Fixing up positions
    $position = 1;

    foreach (array_keys($order) as $display) {
      $order[$display] = $position++;
    }

    // Setting up position and removing deleted displays
    $displays = $view->get('display');
    foreach ($displays as $display_id => $display) {
      // Don't touch the default !!!
      if ($display_id === 'default') {
        $displays[$display_id]['position'] = 0;
        continue;
      }
      if (isset($order[$display_id])) {
        $displays[$display_id]['position'] = $order[$display_id];
      }
      else {
        $displays[$display_id]['deleted'] = TRUE;
      }
    }
    $view->set('display', $displays);

    // Store in cache
    views_ui_cache_set($view);
    $form_state['redirect'] = array('admin/structure/views/view/' . $view->id() . '/edit', array('fragment' => 'views-tab-default'));
  }

}
