<?php

/**
 * @file
 * Contains \Drupal\field_ui\Form\FieldEditForm.
 */

namespace Drupal\field_ui\Form;

use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\TypedData\TypedDataManager;
use Drupal\field\FieldInfo;
use Drupal\field\FieldInstanceInterface;
use Drupal\field_ui\FieldUI;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a form for the field settings edit page.
 */
class FieldEditForm extends FormBase {

  /**
   * The field instance being edited.
   *
   * @var \Drupal\field\FieldInstanceInterface
   */
  protected $instance;

  /**
   * The entity manager.
   *
   * @var \Drupal\Core\Entity\EntityManagerInterface
   */
  protected $entityManager;

  /**
   * The field info service.
   *
   * @var \Drupal\field\FieldInfo
   */
  protected $fieldInfo;

  /**
   * The typed data manager.
   *
   * @var \Drupal\Core\TypedData\TypedDataManager
   */
  protected $typedData;

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'field_ui_field_edit_form';
  }

  /**
   * Constructs a new FieldEditForm object.
   *
   * @param \Drupal\Core\Entity\EntityManagerInterface $entity_manager
   *   The entity manager.
   * @param \Drupal\field\FieldInfo $field_info
   *   The field info service.
   * @param \Drupal\Core\TypedData\TypedDataManager $typed_data
   *   The typed data manager.
   */
  public function __construct(EntityManagerInterface $entity_manager, FieldInfo $field_info, TypedDataManager $typed_data) {
    $this->entityManager = $entity_manager;
    $this->fieldInfo = $field_info;
    $this->typedData = $typed_data;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity.manager'),
      $container->get('field.info'),
      $container->get('typed_data')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, array &$form_state, FieldInstanceInterface $field_instance = NULL) {
    $this->instance = $form_state['instance'] = $field_instance;
    $form['#title'] = $this->instance->label();

    $field = $this->instance->getField();
    $form['#field'] = $field;

    $description = '<p>' . $this->t('These settings apply to the %field field everywhere it is used. These settings impact the way that data is stored in the database and cannot be changed once data has been created.', array('%field' => $this->instance->label())) . '</p>';

    // Create a form structure for the field values.
    $form['field'] = array(
      '#prefix' => $description,
      '#tree' => TRUE,
    );

    // See if data already exists for this field.
    // If so, prevent changes to the field settings.
    if ($field->hasData()) {
      $form['field']['#prefix'] = '<div class="messages messages--error">' . $this->t('There is data for this field in the database. The field settings can no longer be changed.') . '</div>' . $form['field']['#prefix'];
    }

    // Build the configurable field values.
    $cardinality = $field->getCardinality();
    $form['field']['cardinality_container'] = array(
      // We can't use the container element because it doesn't support the title
      // or description properties.
      '#type' => 'item',
      // Reset #parents to 'field', so the additional container does not appear.
      '#parents' => array('field'),
      '#field_prefix' => '<div class="container-inline">',
      '#field_suffix' => '</div>',
      '#title' => $this->t('Allowed number of values'),
    );
    $form['field']['cardinality_container']['cardinality'] = array(
      '#type' => 'select',
      '#title' => $this->t('Allowed number of values'),
      '#title_display' => 'invisible',
      '#options' => array(
        'number' => $this->t('Limited'),
        FieldInstanceInterface::CARDINALITY_UNLIMITED => $this->t('Unlimited'),
      ),
      '#default_value' => ($cardinality == FieldInstanceInterface::CARDINALITY_UNLIMITED) ? FieldInstanceInterface::CARDINALITY_UNLIMITED : 'number',
    );
    $form['field']['cardinality_container']['cardinality_number'] = array(
      '#type' => 'number',
      '#default_value' => $cardinality != FieldInstanceInterface::CARDINALITY_UNLIMITED ? $cardinality : 1,
      '#min' => 1,
      '#title' => $this->t('Limit'),
      '#title_display' => 'invisible',
      '#size' => 2,
      '#states' => array(
        'visible' => array(
         ':input[name="field[cardinality]"]' => array('value' => 'number'),
        ),
      ),
    );

    // Build the non-configurable field values.
    $form['field']['field_name'] = array('#type' => 'value', '#value' => $field->getName());
    $form['field']['type'] = array('#type' => 'value', '#value' => $field->getType());
    $form['field']['module'] = array('#type' => 'value', '#value' => $field->module);

    // Add settings provided by the field module. The field module is
    // responsible for not returning settings that cannot be changed if
    // the field already has data.
    $form['field']['settings'] = array(
      '#weight' => 10,
    );
    // Create an arbitrary entity object, so that we can have an instantiated
    // FieldItem.
    $ids = (object) array('entity_type' => $this->instance->entity_type, 'bundle' => $this->instance->bundle, 'entity_id' => NULL);
    $entity = _field_create_entity_from_ids($ids);
    $form['field']['settings'] += $entity->get($field->getName())->offsetGet(0)->settingsForm($form, $form_state, $field->hasData());

    $form['actions'] = array('#type' => 'actions');
    $form['actions']['submit'] = array('#type' => 'submit', '#value' => $this->t('Save field settings'));
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, array &$form_state) {
    // Validate field cardinality.
    $cardinality = $form_state['values']['field']['cardinality'];
    $cardinality_number = $form_state['values']['field']['cardinality_number'];
    if ($cardinality === 'number' && empty($cardinality_number)) {
      $this->setFormError('field][cardinality_number', $form_state, $this->t('Number of values is required.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, array &$form_state) {
    $form_values = $form_state['values'];
    $field_values = $form_values['field'];

    // Save field cardinality.
    $cardinality = $field_values['cardinality'];
    $cardinality_number = $field_values['cardinality_number'];
    if ($cardinality === 'number') {
      $cardinality = $cardinality_number;
    }
    $field_values['cardinality'] = $cardinality;
    unset($field_values['container']);

    // Merge incoming form values into the existing field.
    $field = $this->instance->getField();
    foreach ($field_values as $key => $value) {
      $field->{$key} = $value;
    }

    // Update the field.
    try {
      $field->save();
      drupal_set_message($this->t('Updated field %label field settings.', array('%label' => $this->instance->label())));
      if ($next_destination = FieldUI::getNextDestination($this->getRequest())) {
        $form_state['redirect'] = $next_destination;
      }
      else {
        $form_state['redirect_route'] = $this->entityManager->getAdminRouteInfo($this->instance->entity_type, $this->instance->bundle);
      }
    }
    catch (\Exception $e) {
      drupal_set_message($this->t('Attempt to update field %label failed: %message.', array('%label' => $this->instance->label(), '%message' => $e->getMessage())), 'error');
    }
  }

}
