<?php

/**
 * @file
 * Contains \Drupal\Core\Entity\EntityFormController.
 */

namespace Drupal\Core\Entity;

use Drupal\Core\Entity\Display\EntityFormDisplayInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\entity\Entity\EntityFormDisplay;

/**
 * Base class for entity form controllers.
 */
class EntityFormController extends FormBase implements EntityFormControllerInterface {

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
    $entity_type = $this->entity->getEntityTypeId();
    $bundle = $this->entity->bundle();
    $form_id = $entity_type;
    if ($bundle != $entity_type) {
      $form_id = $bundle . '_' . $form_id;
    }
    if ($this->operation != 'default') {
      $form_id = $form_id . '_' . $this->operation;
    }
    return $form_id . '_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, array &$form_state) {
    // During the initial form build, add this controller to the form state and
    // allow for initial preparation before form building and processing.
    if (!isset($form_state['controller'])) {
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
   * {@inheritdoc}
   */
  public function submitForm(array &$form, array &$form_state) {
  }

  /**
   * Initialize the form state and the entity before the first form build.
   */
  protected function init(array &$form_state) {
    // Add the controller to the form state so it can be easily accessed by
    // module-provided form handlers there.
    $form_state['controller'] = $this;

    // Prepare the entity to be presented in the entity form.
    $this->prepareEntity();

    $form_display = EntityFormDisplay::collectRenderDisplay($this->entity, $this->getOperation());
    $this->setFormDisplay($form_display, $form_state);

    // Invoke the prepare form hooks.
    $this->prepareInvokeAll('entity_prepare_form', $form_state);
    $this->prepareInvokeAll($this->entity->getEntityTypeId() . '_prepare_form', $form_state);
  }

  /**
   * Returns the actual form array to be built.
   *
   * @see \Drupal\Core\Entity\EntityFormController::build()
   */
  public function form(array $form, array &$form_state) {
    $entity = $this->entity;
    // @todo Exploit the Field API to generate the default widgets for the
    // entity properties.
    if ($entity->getEntityType()->isFieldable()) {
      field_attach_form($entity, $form, $form_state, $this->getFormLangcode($form_state));
    }

    // Add a process callback so we can assign weights and hide extra fields.
    $form['#process'][] = array($this, 'processForm');

    if (!isset($form['langcode'])) {
      // If the form did not specify otherwise, default to keeping the existing
      // language of the entity or defaulting to the site default language for
      // new entities.
      $form['langcode'] = array(
        '#type' => 'value',
        '#value' => !$entity->isNew() ? $entity->language()->id : language_default()->id,
      );
    }
    return $form;
  }

  /**
   * Process callback: assigns weights and hides extra fields.
   *
   * @see \Drupal\Core\Entity\EntityFormController::form()
   */
  public function processForm($element, $form_state, $form) {
    // If the form is cached, process callbacks may not have a valid reference
    // to the entity object, hence we must restore it.
    $this->entity = $form_state['controller']->getEntity();

    // Assign the weights configured in the form display.
    foreach ($this->getFormDisplay($form_state)->getComponents() as $name => $options) {
      if (isset($element[$name])) {
        $element[$name]['#weight'] = $options['weight'];
      }
    }

    // Hide or assign weights for extra fields.
    $extra_fields = field_info_extra_fields($this->entity->getEntityTypeId(), $this->entity->bundle(), 'form');
    foreach ($extra_fields as $extra_field => $info) {
      $component = $this->getFormDisplay($form_state)->getComponent($extra_field);
      if (!$component) {
        $element[$extra_field]['#access'] = FALSE;
      }
      else {
        $element[$extra_field]['#weight'] = $component['weight'];
      }
    }

    return $element;
  }

  /**
   * Returns the action form element for the current entity form.
   */
  protected function actionsElement(array $form, array &$form_state) {
    $element = $this->actions($form, $form_state);

    // We cannot delete an entity that has not been created yet.
    if ($this->entity->isNew()) {
      unset($element['delete']);
    }
    elseif (isset($element['delete'])) {
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
    foreach (element_children($element) as $action) {
      $element[$action] += array(
        '#type' => 'submit',
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
   */
  protected function actions(array $form, array &$form_state) {
    return array(
      // @todo Rename the action key from submit to save.
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
      'delete' => array(
        '#value' => $this->t('Delete'),
        // No need to validate the form when deleting the entity.
        '#submit' => array(
          array($this, 'delete'),
        ),
      ),
      // @todo Consider introducing a 'preview' action here, since it is used by
      // many entity types.
    );
  }

  /**
   * {@inheritdoc}
   */
  public function validate(array $form, array &$form_state) {
    $this->updateFormLangcode($form_state);
    // @todo Remove this.
    // Execute legacy global validation handlers.
    unset($form_state['validate_handlers']);
    form_execute_handlers('validate', $form, $form_state);
  }

  /**
   * {@inheritdoc}
   *
   * This is the default entity object builder function. It is called before any
   * other submit handler to build the new entity object to be passed to the
   * following submit handlers. At this point of the form workflow the entity is
   * validated and the form state can be updated, this way the subsequently
   * invoked handlers can retrieve a regular entity object to act on.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param array $form_state
   *   A reference to a keyed array containing the current state of the form.
   */
  public function submit(array $form, array &$form_state) {
    // Remove button and internal Form API values from submitted values.
    form_state_values_clean($form_state);
    $this->entity = $this->buildEntity($form, $form_state);
    return $this->entity;
  }

  /**
   * Form submission handler for the 'save' action.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param array $form_state
   *   A reference to a keyed array containing the current state of the form.
   */
  public function save(array $form, array &$form_state) {
    // @todo Perform common save operations.
  }

  /**
   * Form submission handler for the 'delete' action.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param array $form_state
   *   A reference to a keyed array containing the current state of the form.
   */
  public function delete(array $form, array &$form_state) {
    if ($this->entity->hasLinkTemplate('delete-form')) {
      $form_state['redirect_route'] = $this->entity->urlInfo('delete-form');

      $query = $this->getRequest()->query;
      if ($query->has('destination')) {
        $form_state['redirect_route']['options']['query']['destination'] = $query->get('destination');
        $query->remove('destination');
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getFormLangcode(array &$form_state) {
    return $this->entity->language()->id;
  }

  /**
   * {@inheritdoc}
   */
  public function isDefaultFormLangcode(array $form_state) {
    // The entity is not translatable, this is always the default language.
    return TRUE;
  }

  /**
   * Updates the form language to reflect any change to the entity language.
   *
   * @param array $form_state
   *   A reference to a keyed array containing the current state of the form.
   */
  protected function updateFormLangcode(array &$form_state) {
    // Update the form language as it might have changed.
    if (isset($form_state['values']['langcode']) && $this->isDefaultFormLangcode($form_state)) {
      $form_state['langcode'] = $form_state['values']['langcode'];
    }
  }

  /**
   * {@inheritdoc}
   */
  public function buildEntity(array $form, array &$form_state) {
    $entity = clone $this->entity;
    // If you submit a form, the form state comes from caching, which forces
    // the controller to be the one before caching. Ensure to have the
    // controller of the current request.
    $form_state['controller'] = $this;

    $this->copyFormValuesToEntity($entity, $form_state);

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
   * @param array $form_state
   *   An associative array containing the current state of the form.
   */
  protected function copyFormValuesToEntity(EntityInterface $entity, array $form_state) {
    // @todo: This relies on a method that only exists for config and content
    //   entities, in a different way. Consider moving this logic to a config
    //   entity specific implementation.
    foreach ($form_state['values'] as $key => $value) {
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
   * @param array $form_state
   *   An associative array containing the current state of the form.
   */
  protected function prepareInvokeAll($hook, array &$form_state) {
    $implementations = $this->moduleHandler->getImplementations($hook);
    foreach ($implementations as $module) {
      $function = $module . '_' . $hook;
      if (function_exists($function)) {
        // Ensure we pass an updated translation object and form display at
        // each invocation, since they depend on form state which is alterable.
        $args = array($this->entity, $this->getFormDisplay($form_state), $this->operation, &$form_state);
        call_user_func_array($function, $args);
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getFormDisplay(array $form_state) {
    return isset($form_state['form_display']) ? $form_state['form_display'] : NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function setFormDisplay(EntityFormDisplayInterface $form_display, array &$form_state) {
    $form_state['form_display'] = $form_display;
    return $this;
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
