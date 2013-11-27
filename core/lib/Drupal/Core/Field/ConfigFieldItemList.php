<?php

/**
 * @file
 * Contains \Drupal\Core\Field\ConfigFieldItemList.
 */

namespace Drupal\Core\Field;

use Drupal\Core\Field\Plugin\DataType\FieldInstanceInterface;
use Drupal\Core\TypedData\TypedDataInterface;
use Drupal\field\Field;

/**
 * Represents a configurable entity field item list.
 */
class ConfigFieldItemList extends FieldItemList implements ConfigFieldItemListInterface {

  /**
   * The Field instance definition.
   *
   * @var \Drupal\field\Entity\FieldInstance
   */
  protected $instance;

  /**
   * {@inheritdoc}
   */
  public function __construct($definition, $name = NULL, TypedDataInterface $parent = NULL) {
    parent::__construct($definition, $name, $parent);
    // Definition can be the field config or field instance.
    if ($definition instanceof FieldInstanceInterface) {
      $this->instance = $definition;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getFieldDefinition() {
    // Configurable fields have the field_config entity injected as definition,
    // but we want to return the more specific field instance here.
    // @todo: Overhaul this once we have per-bundle field definitions injected,
    // see https://drupal.org/node/2114707.
    if (!isset($this->instance)) {
      $entity = $this->getEntity();
      $instances = Field::fieldInfo()->getBundleInstances($entity->entityType(), $entity->bundle());
      if (isset($instances[$this->getName()])) {
        $this->instance = $instances[$this->getName()];
      }
      else {
        // For base fields, fall back to return the general definition.
        return parent::getFieldDefinition();
      }
    }
    return $this->instance;
  }

  /**
   * {@inheritdoc}
   */
  public function getConstraints() {
    $constraints = array();
    // Check that the number of values doesn't exceed the field cardinality. For
    // form submitted values, this can only happen with 'multiple value'
    // widgets.
    $cardinality = $this->getFieldDefinition()->getFieldCardinality();
    if ($cardinality != FieldDefinitionInterface::CARDINALITY_UNLIMITED) {
      $constraints[] = \Drupal::typedData()
        ->getValidationConstraintManager()
        ->create('Count', array(
          'max' => $cardinality,
          'maxMessage' => t('%name: this field cannot hold more than @count values.', array('%name' => $this->getFieldDefinition()->getFieldLabel(), '@count' => $cardinality)),
        ));
    }

    return $constraints;
  }

  /**
   * {@inheritdoc}
   */
  public function defaultValuesForm(array &$form, array &$form_state) {
    if (empty($this->getFieldDefinition()->default_value_function)) {
      // Place the input in a separate place in the submitted values tree.
      $widget = $this->defaultValueWidget($form_state);
      $element = array('#parents' => array('default_value_input'));
      $element += $widget->form($this, $element, $form_state);

      return $element;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function defaultValuesFormValidate(array $element, array &$form, array &$form_state) {
    // Extract the submitted value, and validate it.
    $widget = $this->defaultValueWidget($form_state);
    $widget->extractFormValues($this, $element, $form_state);
    $violations = $this->validate();

    if (count($violations)) {
      // Store reported errors in $form_state.
      $field_name = $this->getFieldDefinition()->getFieldName();
      $field_state = field_form_get_state($element['#parents'], $field_name, $form_state);
      $field_state['constraint_violations'] = $violations;
      field_form_set_state($element['#parents'], $field_name, $form_state, $field_state);

      // Assign reported errors to the correct form element.
      $widget->flagErrors($this, $element, $form_state);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function defaultValuesFormSubmit(array $element, array &$form, array &$form_state) {
    // Extract the submitted value, and return it as an array.
    $widget = $this->defaultValueWidget($form_state);
    $widget->extractFormValues($this, $element, $form_state);
    return $this->getValue();
  }

  /**
   * Returns the widget object used in default value form.
   *
   * @param array $form_state
   *   The form state of the (entire) configuration form.
   *
   * @return \Drupal\Core\Field\WidgetInterface
   *   A Widget object.
   */
  protected function defaultValueWidget(array &$form_state) {
    if (!isset($form_state['default_value_widget'])) {
      $entity = $this->getEntity();

      // Force a non-required widget.
      $this->getFieldDefinition()->required = FALSE;
      $this->getFieldDefinition()->description = '';

      // Use the widget currently configured for the 'default' form mode, or
      // fallback to the default widget for the field type.
      $entity_form_display = entity_get_form_display($entity->entityType(), $entity->bundle(), 'default');
      $widget = $entity_form_display->getRenderer($this->getFieldDefinition()->getFieldName());
      if (!$widget) {
        $widget = \Drupal::service('plugin.manager.field.widget')->getInstance(array('field_definition' => $this->getFieldDefinition()));
      }

      $form_state['default_value_widget'] = $widget;
    }

    return $form_state['default_value_widget'];
  }

}
