<?php

/**
 * @file
 * Contains \Drupal\views_ui\ViewAddForm.
 */

namespace Drupal\views_ui;

use Drupal\Core\Form\FormStateInterface;
use Drupal\views\Plugin\views\wizard\WizardPluginBase;
use Drupal\views\Plugin\views\wizard\WizardException;
use Drupal\views\Plugin\ViewsPluginManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Form controller for the Views edit form.
 */
class ViewAddForm extends ViewFormBase {

  /**
   * The wizard plugin manager.
   *
   * @var \Drupal\views\Plugin\ViewsPluginManager
   */
  protected $wizardManager;

  /**
   * Constructs a new ViewEditForm object.
   *
   * @param \Drupal\views\Plugin\ViewsPluginManager $wizard_manager
   *   The wizard plugin manager.
   */
  public function __construct(ViewsPluginManager $wizard_manager) {
    $this->wizardManager = $wizard_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('plugin.manager.views.wizard')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function prepareEntity() {
    // Do not prepare the entity while it is being added.
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form['#attached']['library'][] = 'views_ui/views_ui.admin';
    $form['#attributes']['class'] = array('views-admin');

    $form['name'] = array(
      '#type' => 'fieldset',
      '#title' => t('View basic information'),
      '#attributes' => array('class' => array('fieldset-no-legend')),
    );

    $form['name']['label'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('View name'),
      '#required' => TRUE,
      '#size' => 32,
      '#default_value' => '',
      '#maxlength' => 255,
    );
    $form['name']['id'] = array(
      '#type' => 'machine_name',
      '#maxlength' => 128,
      '#machine_name' => array(
        'exists' => '\Drupal\views\Views::getView',
        'source' => array('name', 'label'),
      ),
      '#description' => $this->t('A unique machine-readable name for this View. It must only contain lowercase letters, numbers, and underscores.'),
    );

    $form['name']['description_enable'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Description'),
    );
    $form['name']['description'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Provide description'),
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
      '#title' => t('View settings'),
      '#tree' => TRUE,
      '#attributes' => array('class' => array('container-inline')),
    );

    // Create the "Show" dropdown, which allows the base table of the view to be
    // selected.
    $wizard_plugins = $this->wizardManager->getDefinitions();
    $options = array();
    foreach ($wizard_plugins as $key => $wizard) {
      $options[$key] = $wizard['title'];
    }
    $form['displays']['show']['wizard_key'] = array(
      '#type' => 'select',
      '#title' => $this->t('Show'),
      '#options' => $options,
    );
    $show_form = &$form['displays']['show'];
    $default_value = \Drupal::moduleHandler()->moduleExists('node') ? 'node' : 'users';
    $show_form['wizard_key']['#default_value'] = WizardPluginBase::getSelected($form_state, array('show', 'wizard_key'), $default_value, $show_form['wizard_key']);
    // Changing this dropdown updates the entire content of $form['displays'] via
    // AJAX.
    views_ui_add_ajax_trigger($show_form, 'wizard_key', array('displays'));

    // Build the rest of the form based on the currently selected wizard plugin.
    $wizard_key = $show_form['wizard_key']['#default_value'];
    $wizard_instance = $this->wizardManager->createInstance($wizard_key);
    $form = $wizard_instance->buildForm($form, $form_state);

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  protected function actions(array $form, FormStateInterface $form_state) {
    $actions = parent::actions($form, $form_state);
    $actions['submit']['#value'] = $this->t('Save and edit');
    // Remove EntityFormController::save() form the submission handlers.
    $actions['submit']['#submit'] = array(array($this, 'submitForm'));
    $actions['cancel'] = array(
      '#type' => 'submit',
      '#value' => $this->t('Cancel'),
      '#submit' => array('::cancel'),
      '#limit_validation_errors' => array(),
    );
    return $actions;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $wizard_type = $form_state->getValue(array('show', 'wizard_key'));
    $wizard_instance = $this->wizardManager->createInstance($wizard_type);
    $form_state->set('wizard', $wizard_instance->getPluginDefinition());
    $form_state->set('wizard_instance', $wizard_instance);
    $errors = $wizard_instance->validateView($form, $form_state);

    foreach ($errors as $display_errors) {
      foreach ($display_errors as $name => $message) {
        $form_state->setErrorByName($name, $message);
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    try {
      /** @var $wizard \Drupal\views\Plugin\views\wizard\WizardInterface */
      $wizard = $form_state->get('wizard_instance');
      $this->entity = $wizard->createView($form, $form_state);
    }
    // @todo Figure out whether it really makes sense to throw and catch exceptions on the wizard.
    catch (WizardException $e) {
      drupal_set_message($e->getMessage(), 'error');
      $form_state->setRedirect('entity.view.collection');
      return;
    }
    $this->entity->save();

    $form_state->setRedirectUrl($this->entity->urlInfo('edit-form'));
  }

  /**
   * Form submission handler for the 'cancel' action.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public function cancel(array $form, FormStateInterface $form_state) {
    $form_state->setRedirect('entity.view.collection');
  }

}
