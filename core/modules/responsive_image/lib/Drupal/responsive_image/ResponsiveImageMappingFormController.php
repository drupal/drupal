<?php

/**
 * @file
 * Contains Drupal\responsive_image\ResponsiveImageFormController.
 */

namespace Drupal\responsive_image;

use Drupal\Core\Entity\EntityFormController;

/**
 * Form controller for the responsive image edit/add forms.
 */
class ResponsiveImageMappingFormController extends EntityFormController {

  /**
   * Overrides Drupal\Core\Entity\EntityFormController::form().
   *
   * @param array $form
   *   A nested array form elements comprising the form.
   * @param array $form_state
   *   An associative array containing the current state of the form.
   * @param \Drupal\responsive_image\ResponsiveImageMappingInterface $responsive_image_mapping
   *   The entity being edited.
   *
   * @return array
   *   The array containing the complete form.
   */
  public function form(array $form, array &$form_state) {
    if ($this->operation == 'duplicate') {
      $form['#title'] = $this->t('<em>Duplicate responsive image mapping</em> @label', array('@label' => $this->entity->label()));
      $this->entity = $this->entity->createDuplicate();
    }
    if ($this->operation == 'edit') {
      $form['#title'] = $this->t('<em>Edit responsive image mapping</em> @label', array('@label' => $this->entity->label()));
    }

    $responsive_image_mapping = $this->entity;
    $form['label'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#maxlength' => 255,
      '#default_value' => $responsive_image_mapping->label(),
      '#description' => $this->t("Example: 'Hero image' or 'Author image'."),
      '#required' => TRUE,
    );
    $form['id'] = array(
      '#type' => 'machine_name',
      '#default_value' => $responsive_image_mapping->id(),
      '#machine_name' => array(
        'exists' => 'responsive_image_mapping_load',
        'source' => array('label'),
      ),
      '#disabled' => (bool) $responsive_image_mapping->id() && $this->operation != 'duplicate',
    );

    if ((bool) $responsive_image_mapping->id() && $this->operation != 'duplicate') {
      $description = $this->t('Select a breakpoint group from the enabled themes.') . ' ' . $this->t("Warning: if you change the breakpoint group you lose all your selected mappings.");
    }
    else {
      $description = $this->t('Select a breakpoint group from the enabled themes.');
    }
    $form['breakpointGroup'] = array(
      '#type' => 'select',
      '#title' => $this->t('Breakpoint group'),
      '#default_value' => !empty($responsive_image_mapping->breakpointGroup) ? $responsive_image_mapping->breakpointGroup->id() : '',
      '#options' => breakpoint_group_select_options(),
      '#required' => TRUE,
      '#description' => $description,
    );

    $image_styles = image_style_options(TRUE);
    foreach ($responsive_image_mapping->mappings as $breakpoint_id => $mapping) {
      foreach ($mapping as $multiplier => $image_style) {
        $breakpoint = $responsive_image_mapping->breakpointGroup->getBreakpointById($breakpoint_id);
        $label = $multiplier . ' ' . $breakpoint->name . ' [' . $breakpoint->mediaQuery . ']';
        $form['mappings'][$breakpoint_id][$multiplier] = array(
          '#type' => 'select',
          '#title' => check_plain($label),
          '#options' => $image_styles,
          '#default_value' => $image_style,
          '#description' => $this->t('Select an image style for this breakpoint.'),
        );
      }
    }

    $form['#tree'] = TRUE;

    return parent::form($form, $form_state, $responsive_image_mapping);
  }

  /**
   * Overrides Drupal\Core\Entity\EntityFormController::actions().
   */
  protected function actions(array $form, array &$form_state) {
    // Only includes a Save action for the entity, no direct Delete button.
    return array(
      'submit' => array(
        '#value' => $this->t('Save'),
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
    $responsive_image_mapping = $this->entity;

    // Only validate on edit.
    if (isset($form_state['values']['mappings'])) {
      $responsive_image_mapping->mappings = $form_state['values']['mappings'];

      // Check if another breakpoint group is selected.
      if ($form_state['values']['breakpointGroup'] != $form_state['complete_form']['breakpointGroup']['#default_value']) {
        // Remove the mappings.
        unset($form_state['values']['mappings']);
      }
      // Make sure at least one mapping is defined.
      elseif (!$responsive_image_mapping->isNew() && !$responsive_image_mapping->hasMappings()) {
        $this->setFormError('mappings', $form_state, $this->t('Please select at least one mapping.'));
      }
    }
  }

  /**
   * Overrides Drupal\Core\Entity\EntityFormController::save().
   */
  public function save(array $form, array &$form_state) {
    $responsive_image_mapping = $this->entity;
    $responsive_image_mapping->save();

    watchdog('responsive_image', 'Responsive image mapping @label saved.', array('@label' => $responsive_image_mapping->label()), WATCHDOG_NOTICE);
    drupal_set_message($this->t('Responsive image mapping %label saved.', array('%label' => $responsive_image_mapping->label())));

    // Redirect to edit form after creating a new mapping or after selecting
    // another breakpoint group.
    if (!$responsive_image_mapping->hasMappings()) {
      $form_state['redirect_route'] = array(
        'route_name' => 'responsive_image.mapping_page_edit',
        'route_parameters' => array(
          'responsive_image_mapping' => $responsive_image_mapping->id(),
        ),
      );
    }
    else {
      $form_state['redirect_route']['route_name'] = 'responsive_image.mapping_page';
    }
  }

}
