<?php

namespace Drupal\Core\Entity\Entity;

use Drupal\Core\Entity\EntityConstraintViolationListInterface;
use Drupal\Core\Entity\EntityDisplayPluginCollection;
use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\Core\Entity\Display\EntityFormDisplayInterface;
use Drupal\Core\Entity\EntityDisplayBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Component\Validator\ConstraintViolationListInterface;

/**
 * Configuration entity that contains widget options for all components of an
 * entity form in a given form mode.
 *
 * @ConfigEntityType(
 *   id = "entity_form_display",
 *   label = @Translation("Entity form display"),
 *   entity_keys = {
 *     "id" = "id",
 *     "status" = "status"
 *   },
 *   handlers = {
 *     "access" = "\Drupal\Core\Entity\Entity\Access\EntityFormDisplayAccessControlHandler",
 *   },
 *   config_export = {
 *     "id",
 *     "targetEntityType",
 *     "bundle",
 *     "mode",
 *     "content",
 *     "hidden",
 *   }
 * )
 */
class EntityFormDisplay extends EntityDisplayBase implements EntityFormDisplayInterface {

  /**
   * {@inheritdoc}
   */
  protected $displayContext = 'form';

  /**
   * Returns the entity_form_display object used to build an entity form.
   *
   * Depending on the configuration of the form mode for the entity bundle, this
   * can be either the display object associated with the form mode, or the
   * 'default' display.
   *
   * This method should only be used internally when rendering an entity form.
   * When assigning suggested display options for a component in a given form
   * mode, EntityDisplayRepositoryInterface::getFormDisplay() should be used
   * instead, in order to avoid inadvertently modifying the output of other form
   * modes that might happen to use the 'default' display too. Those options
   * will then be effectively applied only if the form mode is configured to use
   * them.
   *
   * hook_entity_form_display_alter() is invoked on each display, allowing 3rd
   * party code to alter the display options held in the display before they are
   * used to generate render arrays.
   *
   * @param \Drupal\Core\Entity\FieldableEntityInterface $entity
   *   The entity for which the form is being built.
   * @param string $form_mode
   *   The form mode.
   * @param bool $default_fallback
   *   (optional) Whether the default display should be used to initialize the
   *   form display in case the specified display does not exist. Defaults to
   *   TRUE.
   *
   * @return \Drupal\Core\Entity\Display\EntityFormDisplayInterface
   *   The display object that should be used to build the entity form.
   *
   * @see \Drupal\Core\Entity\EntityDisplayRepositoryInterface::getFormDisplay()
   * @see hook_entity_form_display_alter()
   */
  public static function collectRenderDisplay(FieldableEntityInterface $entity, $form_mode, $default_fallback = TRUE) {
    $entity_type = $entity->getEntityTypeId();
    $bundle = $entity->bundle();

    // Allow modules to change the form mode.
    \Drupal::moduleHandler()->alter('entity_form_mode', $form_mode, $entity);

    // Check the existence and status of:
    // - the display for the form mode,
    // - the 'default' display.
    if ($form_mode != 'default') {
      $candidate_ids[] = $entity_type . '.' . $bundle . '.' . $form_mode;
    }
    if ($default_fallback) {
      $candidate_ids[] = $entity_type . '.' . $bundle . '.default';
    }
    $results = \Drupal::entityQuery('entity_form_display')
      ->condition('id', $candidate_ids)
      ->condition('status', TRUE)
      ->execute();

    // Load the first valid candidate display, if any.
    $storage = \Drupal::entityTypeManager()->getStorage('entity_form_display');
    foreach ($candidate_ids as $candidate_id) {
      if (isset($results[$candidate_id])) {
        $display = $storage->load($candidate_id);
        break;
      }
    }
    // Else create a fresh runtime object.
    if (empty($display)) {
      $display = $storage->create([
        'targetEntityType' => $entity_type,
        'bundle' => $bundle,
        'mode' => $default_fallback ? $form_mode : static::CUSTOM_MODE,
        'status' => TRUE,
      ]);
    }

    // Let the display know which form mode was originally requested.
    $display->originalMode = $form_mode;

    // Let modules alter the display.
    $display_context = [
      'entity_type' => $entity_type,
      'bundle' => $bundle,
      'form_mode' => $form_mode,
    ];
    \Drupal::moduleHandler()->alter('entity_form_display', $display, $display_context);

    return $display;
  }

  /**
   * {@inheritdoc}
   */
  public function __construct(array $values, $entity_type) {
    $this->pluginManager = \Drupal::service('plugin.manager.field.widget');

    parent::__construct($values, $entity_type);
  }

  /**
   * {@inheritdoc}
   */
  public function getRenderer($field_name) {
    if (isset($this->plugins[$field_name])) {
      return $this->plugins[$field_name];
    }

    // Instantiate the widget object from the stored display properties.
    if (($configuration = $this->getComponent($field_name)) && isset($configuration['type']) && ($definition = $this->getFieldDefinition($field_name))) {
      $widget = $this->pluginManager->getInstance([
        'field_definition' => $definition,
        'form_mode' => $this->originalMode,
        // No need to prepare, defaults have been merged in setComponent().
        'prepare' => FALSE,
        'configuration' => $configuration,
      ]);
    }
    else {
      $widget = NULL;
    }

    // Persist the widget object.
    $this->plugins[$field_name] = $widget;
    return $widget;
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(FieldableEntityInterface $entity, array &$form, FormStateInterface $form_state) {
    // Set #parents to 'top-level' by default.
    $form += ['#parents' => []];

    // Let each widget generate the form elements.
    foreach ($this->getComponents() as $name => $options) {
      if ($widget = $this->getRenderer($name)) {
        $items = $entity->get($name);
        $items->filterEmptyItems();
        $form[$name] = $widget->form($items, $form, $form_state);
        $form[$name]['#access'] = $items->access('edit');

        // Assign the correct weight. This duplicates the reordering done in
        // processForm(), but is needed for other forms calling this method
        // directly.
        $form[$name]['#weight'] = $options['weight'];

        // Associate the cache tags for the field definition & field storage
        // definition.
        $field_definition = $this->getFieldDefinition($name);
        $this->renderer->addCacheableDependency($form[$name], $field_definition);
        $this->renderer->addCacheableDependency($form[$name], $field_definition->getFieldStorageDefinition());
      }
    }

    // Associate the cache tags for the form display.
    $this->renderer->addCacheableDependency($form, $this);

    // Add a process callback so we can assign weights and hide extra fields.
    $form['#process'][] = [$this, 'processForm'];
  }

  /**
   * Process callback: assigns weights and hides extra fields.
   *
   * @see \Drupal\Core\Entity\Entity\EntityFormDisplay::buildForm()
   */
  public function processForm($element, FormStateInterface $form_state, $form) {
    // Assign the weights configured in the form display.
    foreach ($this->getComponents() as $name => $options) {
      if (isset($element[$name])) {
        $element[$name]['#weight'] = $options['weight'];
      }
    }

    // Hide extra fields.
    $extra_fields = \Drupal::service('entity_field.manager')->getExtraFields($this->targetEntityType, $this->bundle);
    $extra_fields = $extra_fields['form'] ?? [];
    foreach ($extra_fields as $extra_field => $info) {
      if (!$this->getComponent($extra_field)) {
        $element[$extra_field]['#access'] = FALSE;
      }
    }
    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function extractFormValues(FieldableEntityInterface $entity, array &$form, FormStateInterface $form_state) {
    $extracted = [];
    foreach ($entity as $name => $items) {
      if ($widget = $this->getRenderer($name)) {
        $widget->extractFormValues($items, $form, $form_state);
        $extracted[$name] = $name;
      }
    }
    return $extracted;
  }

  /**
   * {@inheritdoc}
   */
  public function validateFormValues(FieldableEntityInterface $entity, array &$form, FormStateInterface $form_state) {
    $violations = $entity->validate();
    $violations->filterByFieldAccess();

    // Flag entity level violations.
    foreach ($violations->getEntityViolations() as $violation) {
      /** @var \Symfony\Component\Validator\ConstraintViolationInterface $violation */
      $form_state->setError($form, $violation->getMessage());
    }

    $this->flagWidgetsErrorsFromViolations($violations, $form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function flagWidgetsErrorsFromViolations(EntityConstraintViolationListInterface $violations, array &$form, FormStateInterface $form_state) {
    $entity = $violations->getEntity();
    foreach ($violations->getFieldNames() as $field_name) {
      // Only show violations for fields that actually appear in the form, and
      // let the widget assign the violations to the correct form elements.
      if ($widget = $this->getRenderer($field_name)) {
        $field_violations = $this->movePropertyPathViolationsRelativeToField($field_name, $violations->getByField($field_name));
        $widget->flagErrors($entity->get($field_name), $field_violations, $form, $form_state);
      }
    }
  }

  /**
   * Moves the property path to be relative to field level.
   *
   * @param string $field_name
   *   The field name.
   * @param \Symfony\Component\Validator\ConstraintViolationListInterface $violations
   *   The violations.
   *
   * @return \Symfony\Component\Validator\ConstraintViolationList
   *   A new constraint violation list with the changed property path.
   */
  protected function movePropertyPathViolationsRelativeToField($field_name, ConstraintViolationListInterface $violations) {
    $new_violations = new ConstraintViolationList();
    foreach ($violations as $violation) {
      // All the logic below is necessary to change the property path of the
      // violations to be relative to the item list, so like title.0.value gets
      // changed to 0.value. Sadly constraints in Symfony don't have setters so
      // we have to create new objects.
      /** @var \Symfony\Component\Validator\ConstraintViolationInterface $violation */
      // Create a new violation object with just a different property path.
      $violation_path = $violation->getPropertyPath();
      $path_parts = explode('.', $violation_path);
      if ($path_parts[0] === $field_name) {
        unset($path_parts[0]);
      }
      $new_path = implode('.', $path_parts);

      $constraint = NULL;
      $cause = NULL;
      $parameters = [];
      $plural = NULL;
      if ($violation instanceof ConstraintViolation) {
        $constraint = $violation->getConstraint();
        $cause = $violation->getCause();
        $parameters = $violation->getParameters();
        $plural = $violation->getPlural();
      }

      $new_violation = new ConstraintViolation(
        $violation->getMessage(),
        $violation->getMessageTemplate(),
        $parameters,
        $violation->getRoot(),
        $new_path,
        $violation->getInvalidValue(),
        $plural,
        $violation->getCode(),
        $constraint,
        $cause
      );
      $new_violations->add($new_violation);
    }
    return $new_violations;
  }

  /**
   * {@inheritdoc}
   */
  public function getPluginCollections() {
    $configurations = [];
    foreach ($this->getComponents() as $field_name => $configuration) {
      if (!empty($configuration['type']) && ($field_definition = $this->getFieldDefinition($field_name))) {
        $configurations[$configuration['type']] = $configuration + [
          'field_definition' => $field_definition,
          'form_mode' => $this->mode,
        ];
      }
    }

    return [
      'widgets' => new EntityDisplayPluginCollection($this->pluginManager, $configurations),
    ];
  }

}
