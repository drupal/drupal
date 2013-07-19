<?php

/**
 * @file
 * Contains Drupal\picture\PictureFormController.
 */

namespace Drupal\picture;

use Drupal\Core\Entity\EntityFormController;

/**
 * Form controller for the picture edit/add forms.
 */
class PictureMappingFormController extends EntityFormController {

  /**
   * Overrides Drupal\Core\Entity\EntityFormController::form().
   *
   * @param array $form
   *   A nested array form elements comprising the form.
   * @param array $form_state
   *   An associative array containing the current state of the form.
   * @param \Drupal\picture\PictureMappingInterface $picture_mapping
   *   The entity being edited.
   *
   * @return array
   *   The array containing the complete form.
   */
  public function form(array $form, array &$form_state) {
    if ($this->operation == 'duplicate') {
      drupal_set_title(t('<em>Duplicate picture mapping</em> @label', array('@label' => $this->entity->label())), PASS_THROUGH);
      $this->entity = $this->entity->createDuplicate();
    }
    if ($this->operation == 'edit') {
      drupal_set_title(t('<em>Edit picture mapping</em> @label', array('@label' => $this->entity->label())), PASS_THROUGH);
    }

    $picture_mapping = $this->entity;
    $form['label'] = array(
      '#type' => 'textfield',
      '#title' => t('Label'),
      '#maxlength' => 255,
      '#default_value' => $picture_mapping->label(),
      '#description' => t("Example: 'Hero image' or 'Author image'."),
      '#required' => TRUE,
    );
    $form['id'] = array(
      '#type' => 'machine_name',
      '#default_value' => $picture_mapping->id(),
      '#machine_name' => array(
        'exists' => 'picture_mapping_load',
        'source' => array('label'),
      ),
      '#disabled' => (bool) $picture_mapping->id() && $this->operation != 'duplicate',
    );

    if ((bool) $picture_mapping->id() && $this->operation != 'duplicate') {
      $description = t('Select a breakpoint group from the enabled themes.') . ' ' . t("Warning: if you change the breakpoint group you lose all your selected mappings.");
    }
    else {
      $description = t('Select a breakpoint group from the enabled themes.');
    }
    $form['breakpointGroup'] = array(
      '#type' => 'select',
      '#title' => t('Breakpoint group'),
      '#default_value' => !empty($picture_mapping->breakpointGroup) ? $picture_mapping->breakpointGroup->id() : '',
      '#options' => breakpoint_group_select_options(),
      '#required' => TRUE,
      '#description' => $description,
    );

    $image_styles = image_style_options(TRUE);
    foreach ($picture_mapping->mappings as $breakpoint_id => $mapping) {
      foreach ($mapping as $multiplier => $image_style) {
        $label = $multiplier . ' ' . $picture_mapping->breakpointGroup->breakpoints[$breakpoint_id]->name . ' [' . $picture_mapping->breakpointGroup->breakpoints[$breakpoint_id]->mediaQuery . ']';
        $form['mappings'][$breakpoint_id][$multiplier] = array(
          '#type' => 'select',
          '#title' => check_plain($label),
          '#options' => $image_styles,
          '#default_value' => $image_style,
          '#description' => t('Select an image style for this breakpoint.'),
        );
      }
    }

    $form['#tree'] = TRUE;

    return parent::form($form, $form_state, $picture_mapping);
  }

  /**
   * Overrides Drupal\Core\Entity\EntityFormController::actions().
   */
  protected function actions(array $form, array &$form_state) {
    // Only includes a Save action for the entity, no direct Delete button.
    return array(
      'submit' => array(
        '#value' => t('Save'),
        '#validate' => array(
          array($this, 'validate'),
        ),
        '#submit' => array(
          array($this, 'submit'),
          array($this, 'save'),
        ),
      ),
    );
  }

  /**
   * Overrides Drupal\Core\Entity\EntityFormController::validate().
   */
  public function validate(array $form, array &$form_state) {
    $picture_mapping = $this->entity;

    // Only validate on edit.
    if (isset($form_state['values']['mappings'])) {
      $picture_mapping->mappings = $form_state['values']['mappings'];

      // Check if another breakpoint group is selected.
      if ($form_state['values']['breakpointGroup'] != $form_state['complete_form']['breakpointGroup']['#default_value']) {
        // Remove the mappings.
        unset($form_state['values']['mappings']);
      }
      // Make sure at least one mapping is defined.
      elseif (!$picture_mapping->isNew() && !$picture_mapping->hasMappings()) {
        form_set_error('mappings', t('Please select at least one mapping.'));
      }
    }
  }

  /**
   * Overrides Drupal\Core\Entity\EntityFormController::save().
   */
  public function save(array $form, array &$form_state) {
    $picture_mapping = $this->entity;
    $picture_mapping->save();

    watchdog('picture', 'Picture mapping @label saved.', array('@label' => $picture_mapping->label()), WATCHDOG_NOTICE);
    drupal_set_message(t('Picture mapping %label saved.', array('%label' => $picture_mapping->label())));

    // Redirect to edit form after creating a new mapping or after selecting
    // another breakpoint group.
    if (!$picture_mapping->hasMappings()) {
      $uri = $picture_mapping->uri();
      $form_state['redirect'] = $uri['path'];
    }
    else {
      $form_state['redirect'] = 'admin/config/media/picturemapping';
    }
  }

}
