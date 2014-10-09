<?php

/**
 * @file
 * Contains \Drupal\Core\Entity\EntityForm.
 */

namespace Drupal\Core\Entity;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;

/**
 * Base class for entity forms.
 *
 * @ingroup entity_api
 */
class EntityForm extends FormBase implements EntityFormInterface {

  /**
   * The name of the current operation.
   *
   * Subclasses may use this to implement different behaviors depending on its
   * value.
   *
   * @var string
   */
  protected $operation;

  /**
   * The module handler service.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * The entity being used by this form.
   *
   * @var \Drupal\Core\Entity\EntityInterface
   */
  protected $entity;

  /**
   * {@inheritdoc}
   */
  public function setOperation($operation) {
    // If NULL is passed, do not overwrite the operation.
    if ($operation) {
      $this->operation = $operation;
    }
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getBaseFormID() {
    // Assign ENTITYTYPE_form as base form ID to invoke corresponding
    // hook_form_alter(), #validate, #submit, and #theme callbacks, but only if
    // it is different from the actual form ID, since callbacks would be invoked
    // twice otherwise.
    $base_form_id = $this->entity->getEntityTypeId() . '_form';
    if ($base_form_id == $this->getFormId()) {
      $base_form_id = NULL;
    }
    return $base_form_id;
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    $form_id = $this->entity->getEntityTypeId();
    if ($this->entity->getEntityType()->hasKey('bundle')) {
      $form_id = $this->entity->bundle() . '_' . $form_id;
    }
    if ($this->operation != 'default') {
      $form_id = $form_id . '_' . $this->operation;
    }
    return $form_id . '_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    // During the initial form build, add this form object to the form state and
    // allow for initial preparation before form building and processing.
    if (!$form_state->has('entity_form_initialized')) {
      $this->init($form_state);
    }

    // Retrieve the form array using the possibly updated entity in form state.
    $form = $this->form($form, $form_state);

    // Retrieve and add the form actions array.
    $actions = $this->actionsElement($form, $form_state);
    if (!empty($actions)) {
      $form['actions'] = $actions;
    }

    return $form;
  }

  /**
   * Initialize the form state and the entity before the first form build.
   */
  protected function init(FormStateInterface $form_state) {
    // Flag that this form has been initialized.
    $form_state->set('entity_form_initialized', TRUE);

    // Prepare the entity to be presented in the entity form.
    $this->prepareEntity();

    // Invoke the prepare form hooks.
    $this->prepareInvokeAll('entity_prepare_form', $form_state);
    $this->prepareInvokeAll($this->entity->getEntityTypeId() . '_prepare_form', $form_state);
  }

  /**
   * Returns the actual form array to be built.
   *
   * @see \Drupal\Core\Entity\EntityForm::build()
   */
  public function form(array $form, FormStateInterface $form_state) {
    $entity = $this->entity;

    // Add a process callback.
    $form['#process'][] = '::processForm';

    return $form;
  }

  /**
   * Process callback: assigns weights and hides extra fields.
   *
   * @see \Drupal\Core\Entity\EntityForm::form()
   */
  public function processForm($element, FormStateInterface $form_state, $form) {
    // If the form is cached, process callbacks may not have a valid reference
    // to the entity object, hence we must restore it.
    $this->entity = $form_state->getFormObject()->getEntity();

    return $element;
  }

  /**
   * Returns the action form element for the current entity form.
   */
  protected function actionsElement(array $form, FormStateInterface $form_state) {
    $element = $this->actions($form, $form_state);

    if (isset($element['delete'])) {
      // Move the delete action as last one, unless weights are explicitly
      // provided.
      $delete = $element['delete'];
      unset($element['delete']);
      $element['delete'] = $delete;
      $element['delete']['#button_type'] = 'danger';
    }

    if (isset($element['submit'])) {
      // Give the primary submit button a #button_type of primary.
      $element['submit']['#button_type'] = 'primary';
    }

    $count = 0;
    foreach (Element::children($element) as $action) {
      $element[$action] += array(
        '#weight' => ++$count * 5,
      );
    }

    if (!empty($element)) {
      $element['#type'] = 'actions';
    }

    return $element;
  }

  /**
   * Returns an array of supported actions for the current entity form.
   *
   * @todo Consider introducing a 'preview' action here, since it is used by
   *   many entity types.
   */
  protected function actions(array $form, FormStateInterface $form_state) {
    // @todo Consider renaming the action key from submit to save. The impacts
    //   are hard to predict. For example, see
    //   \Drupal\language\Element\LanguageConfiguration::processLanguageConfiguration().
    $actions['submit'] = array(
      '#type' => 'submit',
      '#value' => $this->t('Save'),
      '#validate' => array('::validate'),
      '#submit' => array('::submitForm', '::save'),
    );

    if (!$this->entity->isNew() && $this->entity->hasLinkTemplate('delete-form')) {
      $route_info = $this->entity->urlInfo('delete-form');
      if ($this->getRequest()->query->has('destination')) {
        $query = $route_info->getOption('query');
        $query['destination'] = $this->getRequest()->query->get('destination');
        $route_info->setOption('query', $query);
      }
      $actions['delete'] = array(
        '#type' => 'link',
        '#title' => $this->t('Delete'),
        '#access' => $this->entity->access('delete'),
        '#attributes' => array(
          'class' => array('button', 'button--danger'),
        ),
      );
      $actions['delete']['#url'] = $route_info;
    }

    return $actions;
  }

  /**
   * {@inheritdoc}
   */
  public function validate(array $form, FormStateInterface $form_state) {
    // @todo Remove this.
    // Execute legacy global validation handlers.
    $form_state->setValidateHandlers([]);
    form_execute_handlers('validate', $form, $form_state);
  }

  /**
   * {@inheritdoc}
   *
   * This is the default entity object builder function. It is called before any
   * other submit handler to build the new entity object to be used by the
   * following submit handlers. At this point of the form workflow the entity is
   * validated and the form state can be updated, this way the subsequently
   * invoked handlers can retrieve a regular entity object to act on. Generally
   * this method should not be overridden unless the entity requires the same
   * preparation for two actions, see \Drupal\comment\CommentForm for an example
   * with the save and preview actions.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Remove button and internal Form API values from submitted values.
    $form_state->cleanValues();
    $this->entity = $this->buildEntity($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    return $this->entity->save();
  }

  /**
   * {@inheritdoc}
   */
  public function buildEntity(array $form, FormStateInterface $form_state) {
    $entity = clone $this->entity;
    $this->copyFormValuesToEntity($entity, $form, $form_state);

    // Invoke all specified builders for copying form values to entity
    // properties.
    if (isset($form['#entity_builders'])) {
      foreach ($form['#entity_builders'] as $function) {
        call_user_func_array($function, array($entity->getEntityTypeId(), $entity, &$form, &$form_state));
      }
    }

    return $entity;
  }

  /**
   * Copies top-level form values to entity properties
   *
   * This should not change existing entity properties that are not being edited
   * by this form.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity the current form should operate upon.
   * @param array $form
   *   A nested array of form elements comprising the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  protected function copyFormValuesToEntity(EntityInterface $entity, array $form, FormStateInterface $form_state) {
    // @todo: This relies on a method that only exists for config and content
    //   entities, in a different way. Consider moving this logic to a config
    //   entity specific implementation.
    foreach ($form_state->getValues() as $key => $value) {
      $entity->set($key, $value);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getEntity() {
    return $this->entity;
  }

  /**
   * {@inheritdoc}
   */
  public function setEntity(EntityInterface $entity) {
    $this->entity = $entity;
    return $this;
  }

  /**
   * Prepares the entity object before the form is built first.
   */
  protected function prepareEntity() {}

  /**
   * Invokes the specified prepare hook variant.
   *
   * @param string $hook
   *   The hook variant name.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  protected function prepareInvokeAll($hook, FormStateInterface $form_state) {
    $implementations = $this->moduleHandler->getImplementations($hook);
    foreach ($implementations as $module) {
      $function = $module . '_' . $hook;
      if (function_exists($function)) {
        // Ensure we pass an updated translation object and form display at
        // each invocation, since they depend on form state which is alterable.
        $args = array($this->entity, $this->operation, &$form_state);
        call_user_func_array($function, $args);
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getOperation() {
    return $this->operation;
  }

  /**
   * {@inheritdoc}
   */
  public function setModuleHandler(ModuleHandlerInterface $module_handler) {
    $this->moduleHandler = $module_handler;
    return $this;
  }

}
