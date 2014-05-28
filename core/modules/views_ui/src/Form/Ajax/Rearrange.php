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
   * {@inheritdoc}
   */
  public function getFormKey() {
    return 'rearrange';
  }

  /**
   * {@inheritdoc}
   */
  public function getForm(ViewStorageInterface $view, $display_id, $js, $type = NULL) {
    $this->setType($type);
    return parent::getForm($view, $display_id, $js);
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'views_ui_rearrange_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, array &$form_state) {
    $view = $form_state['view'];
    $display_id = $form_state['display_id'];
    $type = $form_state['type'];

    $types = ViewExecutable::getHandlerTypes();
    $executable = $view->getExecutable();
    $executable->setDisplay($display_id);
    $display = &$executable->displayHandlers->get($display_id);
    $form['#title'] = $this->t('Rearrange @type', array('@type' => $types[$type]['ltitle']));
    $form['#section'] = $display_id . 'rearrange-item';

    if ($display->defaultableSections($types[$type]['plural'])) {
      $form_state['section'] = $types[$type]['plural'];
      views_ui_standard_display_dropdown($form, $form_state, $form_state['section']);
    }

    $count = 0;

    // Get relationship labels
    $relationships = array();
    foreach ($display->getHandlers('relationship') as $id => $handler) {
      $relationships[$id] = $handler->adminLabel();
    }

    $form['fields'] = array(
      '#type' => 'table',
      '#header' => array('', $this->t('Weight'), $this->t('Remove')),
      '#empty' => $this->t('No fields available.'),
      '#tabledrag' => array(
        array(
          'action' => 'order',
          'relationship' => 'sibling',
          'group' => 'weight',
        )
      ),
      '#tree' => TRUE,
      '#prefix' => '<div class="scroll" data-drupal-views-scroll>',
      '#suffix' => '</div>',
    );

    foreach ($display->getOption($types[$type]['plural']) as $id => $field) {
      $form['fields'][$id] = array();

      $form['fields'][$id]['#attributes'] = array('class' => array('draggable'), 'id' => 'views-row-' . $id);

      $handler = $display->getHandler($type, $id);
      if ($handler) {
        $name = $handler->adminLabel() . ' ' . $handler->adminSummary();
        if (!empty($field['relationship']) && !empty($relationships[$field['relationship']])) {
          $name = '(' . $relationships[$field['relationship']] . ') ' . $name;
        }
        $markup = $name;
      }
      else {
        $name = $id;
        $markup = $this->t('Broken field @id', array('@id' => $id));
      }
      $form['fields'][$id]['name'] = array('#markup' => $markup);

      $form['fields'][$id]['weight'] = array(
        '#type' => 'textfield',
        '#default_value' => ++$count,
        '#attributes' => array('class' => array('weight')),
        '#title' => t('Weight for @title', array('@title' => $name)),
        '#title_display' => 'invisible',
      );

      $form['fields'][$id]['removed'] = array(
        '#type' => 'checkbox',
        '#title' => t('Remove @title', array('@title' => $name)),
        '#title_display' => 'invisible',
        '#id' => 'views-removed-' . $id,
        '#attributes' => array('class' => array('views-remove-checkbox')),
        '#default_value' => 0,
        '#suffix' => l('<span>' . $this->t('Remove') . '</span>', 'javascript:void()', array('attributes' => array('id' => 'views-remove-link-' . $id, 'class' => array('views-hidden', 'views-button-remove', 'views-remove-link'), 'alt' => $this->t('Remove this item'), 'title' => $this->t('Remove this item')), 'html' => TRUE)),
      );
    }

    $view->getStandardButtons($form, $form_state, 'views_ui_rearrange_form');

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, array &$form_state) {
    $types = ViewExecutable::getHandlerTypes();
    $display = &$form_state['view']->getExecutable()->displayHandlers->get($form_state['display_id']);

    $old_fields = $display->getOption($types[$form_state['type']]['plural']);
    $new_fields = $order = array();

    // Make an array with the weights
    foreach ($form_state['values']['fields'] as $field => $info) {
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
    $form_state['view']->cacheSet();
  }

}
