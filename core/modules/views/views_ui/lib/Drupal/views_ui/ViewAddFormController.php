<?php

/**
 * @file
 * Contains Drupal\views_ui\ViewAddFormController.
 */

namespace Drupal\views_ui;

use Drupal\Core\Entity\EntityInterface;
use Drupal\views\Plugin\views\wizard\WizardPluginBase;

/**
 * Form controller for the Views edit form.
 */
class ViewAddFormController extends ViewFormControllerBase {

  /**
   * Overrides Drupal\Core\Entity\EntityFormController::prepareForm().
   */
  protected function prepareEntity(EntityInterface $view) {
    // Do not prepare the entity while it is being added.
  }

  /**
   * Overrides Drupal\Core\Entity\EntityFormController::form().
   */
  public function form(array $form, array &$form_state, EntityInterface $view) {
    $form['#attached']['css'] = static::getAdminCSS();
    $form['#attached']['js'][] = drupal_get_path('module', 'views_ui') . '/js/views-admin.js';
    $form['#attributes']['class'] = array('views-admin');

    $form['human_name'] = array(
      '#type' => 'textfield',
      '#title' => t('View name'),
      '#required' => TRUE,
      '#size' => 32,
      '#default_value' => '',
      '#maxlength' => 255,
    );
    $form['name'] = array(
      '#type' => 'machine_name',
      '#maxlength' => 128,
      '#machine_name' => array(
        'exists' => 'views_get_view',
        'source' => array('human_name'),
      ),
      '#description' => t('A unique machine-readable name for this View. It must only contain lowercase letters, numbers, and underscores.'),
    );

    $form['description_enable'] = array(
      '#type' => 'checkbox',
      '#title' => t('Description'),
    );
    $form['description'] = array(
      '#type' => 'textfield',
      '#title' => t('Provide description'),
      '#title_display' => 'invisible',
      '#size' => 64,
      '#default_value' => '',
      '#states' => array(
        'visible' => array(
          ':input[name="description_enable"]' => array('checked' => TRUE),
        ),
      ),
    );

    // Create a wrapper for the entire dynamic portion of the form. Everything
    // that can be updated by AJAX goes somewhere inside here. For example, this
    // is needed by "Show" dropdown (below); it changes the base table of the
    // view and therefore potentially requires all options on the form to be
    // dynamically updated.
    $form['displays'] = array();

    // Create the part of the form that allows the user to select the basic
    // properties of what the view will display.
    $form['displays']['show'] = array(
      '#type' => 'fieldset',
      '#tree' => TRUE,
      '#attributes' => array('class' => array('container-inline')),
    );

    // Create the "Show" dropdown, which allows the base table of the view to be
    // selected.
    $wizard_plugins = views_ui_get_wizards();
    $options = array();
    foreach ($wizard_plugins as $key => $wizard) {
      $options[$key] = $wizard['title'];
    }
    $form['displays']['show']['wizard_key'] = array(
      '#type' => 'select',
      '#title' => t('Show'),
      '#options' => $options,
    );
    $show_form = &$form['displays']['show'];
    $default_value = module_exists('node') ? 'node' : 'users';
    $show_form['wizard_key']['#default_value'] = WizardPluginBase::getSelected($form_state, array('show', 'wizard_key'), $default_value, $show_form['wizard_key']);
    // Changing this dropdown updates the entire content of $form['displays'] via
    // AJAX.
    views_ui_add_ajax_trigger($show_form, 'wizard_key', array('displays'));

    // Build the rest of the form based on the currently selected wizard plugin.
    $wizard_key = $show_form['wizard_key']['#default_value'];
    $wizard_instance = views_get_plugin('wizard', $wizard_key);
    $form = $wizard_instance->build_form($form, $form_state);

    return $form;
  }

  /**
   * Overrides Drupal\Core\Entity\EntityFormController::actions().
   */
  protected function actions(array $form, array &$form_state) {
    $actions = parent::actions($form, $form_state);
    $actions['submit']['#value'] = t('Save & exit');
    $actions['continueAndEdit'] = array(
      '#value' => t('Continue & edit'),
      '#validate' => array(
        array($this, 'validate'),
      ),
      '#submit' => array(
        array($this, 'continueAndEdit'),
      ),
    );

    $actions['cancel'] = array(
      '#value' => t('Cancel'),
      '#submit' => array(
        array($this, 'cancel'),
      ),
      '#limit_validation_errors' => array(),
    );
    return $actions;
  }

  /**
   * Overrides Drupal\Core\Entity\EntityFormController::validate().
   */
  public function validate(array $form, array &$form_state) {
    $wizard = views_ui_get_wizard($form_state['values']['show']['wizard_key']);
    $form_state['wizard'] = $wizard;
    $form_state['wizard_instance'] = views_get_plugin('wizard', $wizard['id']);
    $errors = $form_state['wizard_instance']->validateView($form, $form_state);
    foreach ($errors as $name => $message) {
      form_set_error($name, $message);
    }
  }

  /**
   * Overrides Drupal\Core\Entity\EntityFormController::submit().
   */
  public function submit(array $form, array &$form_state) {
    try {
      $view = $form_state['wizard_instance']->create_view($form, $form_state);
    }
    catch (WizardException $e) {
      drupal_set_message($e->getMessage(), 'error');
      $form_state['redirect'] = 'admin/structure/views';
      return;
    }
    $view->save();

    $form_state['redirect'] = 'admin/structure/views';
    if (!empty($view->get('executable')->displayHandlers['page_1'])) {
      $display = $view->get('executable')->displayHandlers['page_1'];
      if ($display->hasPath()) {
        $one_path = $display->getOption('path');
        if (strpos($one_path, '%') === FALSE) {
          $form_state['redirect'] = $one_path;  // PATH TO THE VIEW IF IT HAS ONE
          return;
        }
      }
    }
    drupal_set_message(t('Your view was saved. You may edit it from the list below.'));
  }

  /**
   * Form submission handler for the 'continue' action.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param array $form_state
   *   A reference to a keyed array containing the current state of the form.
   */
  public function continueAndEdit(array $form, array &$form_state) {
    try {
      $view = $form_state['wizard_instance']->create_view($form, $form_state);
    }
    catch (WizardException $e) {
      drupal_set_message($e->getMessage(), 'error');
      $form_state['redirect'] = 'admin/structure/views';
      return;
    }
    // Just cache it temporarily to edit it.
    views_ui_cache_set($view);

    // If there is a destination query, ensure we still redirect the user to the
    // edit view page, and then redirect the user to the destination.
    // @todo: Revisit this when http://drupal.org/node/1668866 is in.
    $destination = array();
    $query = drupal_container()->get('request')->query;
    if ($query->has('destination')) {
      $destination = drupal_get_destination();
      $query->remove('destination');
    }
    $form_state['redirect'] = array('admin/structure/views/view/' . $view->get('name'), array('query' => $destination));
  }

  /**
   * Form submission handler for the 'cancel' action.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param array $form_state
   *   A reference to a keyed array containing the current state of the form.
   */
  public function cancel(array $form, array &$form_state) {
    $form_state['redirect'] = 'admin/structure/views';
  }

}
