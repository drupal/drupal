<?php

/**
 * @file
 * Contains \Drupal\edit\Form\EditFieldForm.
 */

namespace Drupal\edit\Form;

use Drupal\Core\Entity\EntityInterface;
use Drupal\user\TempStoreFactory;

/**
 * Builds and process a form for editing a single entity field.
 */
class EditFieldForm {

  /**
   * Stores the tempstore factory.
   *
   * @var \Drupal\user\TempStoreFactory
   */
  protected $tempStoreFactory;

  /**
   * Builds a form for a single entity field.
   */
  public function build(array $form, array &$form_state, EntityInterface $entity, $field_name, TempStoreFactory $temp_store_factory) {
    if (!isset($form_state['entity'])) {
      $this->init($form_state, $entity, $field_name);
    }
    $this->tempStoreFactory = $temp_store_factory;

    // Add the field form.
    field_attach_form($form_state['entity'], $form, $form_state, $form_state['langcode'], array('field_name' =>  $form_state['field_name']));

    // Add a submit button. Give it a class for easy JavaScript targeting.
    $form['actions'] = array('#type' => 'actions');
    $form['actions']['submit'] = array(
      '#type' => 'submit',
      '#value' => t('Save'),
      '#attributes' => array('class' => array('edit-form-submit')),
    );

    // Add validation and submission handlers.
    $form['#validate'][] = array($this, 'validate');
    $form['#submit'][] = array($this, 'submit');

    // Simplify it for optimal in-place use.
    $this->simplify($form, $form_state);

    return $form;
  }

  /**
   * Initialize the form state and the entity before the first form build.
   */
  protected function init(array &$form_state, EntityInterface $entity, $field_name) {
    // @todo Rather than special-casing $node->revision, invoke prepareEdit()
    //   once http://drupal.org/node/1863258 lands.
    if ($entity->entityType() == 'node') {
      $entity->setNewRevision(in_array('revision', variable_get('node_options_' . $entity->bundle(), array())));
      $entity->log = NULL;
    }

    $form_state['entity'] = $entity;
    $form_state['field_name'] = $field_name;

    // @todo Allow the usage of different form modes by exposing a hook and the
    // UI for them.
    $form_display = entity_get_render_form_display($entity, 'default');

    // Let modules alter the form display.
    $form_display_context = array(
      'entity_type' => $entity->entityType(),
      'bundle' => $entity->bundle(),
      'form_mode' => 'default',
    );
    \Drupal::moduleHandler()->alter('entity_form_display', $form_display, $form_display_context);

    $form_state['form_display'] = $form_display;
  }

  /**
   * Validates the form.
   */
  public function validate(array $form, array &$form_state) {
    $entity = $this->buildEntity($form, $form_state);
    field_attach_form_validate($entity, $form, $form_state, array('field_name' =>  $form_state['field_name']));
  }

  /**
   * Saves the entity with updated values for the edited field.
   */
  public function submit(array $form, array &$form_state) {
    $form_state['entity'] = $this->buildEntity($form, $form_state);

    // Store entity in tempstore with its UUID as tempstore key.
    $this->tempStoreFactory->get('edit')->set($form_state['entity']->uuid(), $form_state['entity']);
  }

  /**
   * Returns a cloned entity containing updated field values.
   *
   * Calling code may then validate the returned entity, and if valid, transfer
   * it back to the form state and save it.
   */
  protected function buildEntity(array $form, array &$form_state) {
    $entity = clone $form_state['entity'];

    field_attach_extract_form_values($entity, $form, $form_state, array('field_name' =>  $form_state['field_name']));

    // @todo Refine automated log messages and abstract them to all entity
    //   types: http://drupal.org/node/1678002.
    if ($entity->entityType() == 'node' && $entity->isNewRevision() && !isset($entity->log)) {
      $instance = field_info_instance($entity->entityType(), $form_state['field_name'], $entity->bundle());
      $entity->log = t('Updated the %field-name field through in-place editing.', array('%field-name' => $instance['label']));
    }

    return $entity;
  }

  /**
   * Simplifies the field edit form for in-place editing.
   *
   * This function:
   * - Hides the field label inside the form, because JavaScript displays it
   *   outside the form.
   * - Adjusts textarea elements to fit their content.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   */
  protected function simplify(array &$form, array &$form_state) {
    $field_name = $form_state['field_name'];
    $widget_element =& $form[$field_name]['widget'];

    // Hide the field label from displaying within the form, because JavaScript
    // displays the equivalent label that was provided within an HTML data
    // attribute of the field's display element outside of the form. Do this for
    // widgets without child elements (like Option widgets) as well as for ones
    // with per-delta elements. Skip single checkboxes, because their title is
    // key to their UI. Also skip widgets with multiple subelements, because in
    // that case, per-element labeling is informative.
    $num_children = count(element_children($widget_element));
    if ($num_children == 0 && $widget_element['#type'] != 'checkbox') {
      $widget_element['#title_display'] = 'invisible';
    }
    if ($num_children == 1 && isset($widget_element[0]['value'])) {
      // @todo While most widgets name their primary element 'value', not all
      //   do, so generalize this.
      $widget_element[0]['value']['#title_display'] = 'invisible';
    }

    // Adjust textarea elements to fit their content.
    if (isset($widget_element[0]['value']['#type']) && $widget_element[0]['value']['#type'] == 'textarea') {
      $lines = count(explode("\n", $widget_element[0]['value']['#default_value']));
      $widget_element[0]['value']['#rows'] = $lines + 1;
    }
  }

}
