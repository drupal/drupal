<?php

/**
 * @file
 * Contains \Drupal\field_ui\Form\FieldStorageEditForm.
 */

namespace Drupal\field_ui\Form;

use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\TypedData\TypedDataManager;
use Drupal\field\FieldInstanceConfigInterface;
use Drupal\field_ui\FieldUI;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a form for the "field storage" edit page.
 */
class FieldStorageEditForm extends FormBase {

  /**
   * The field instance being edited.
   *
   * @var \Drupal\field\FieldInstanceConfigInterface
   */
  protected $instance;

  /**
   * The entity manager.
   *
   * @var \Drupal\Core\Entity\EntityManagerInterface
   */
  protected $entityManager;

  /**
   * The typed data manager.
   *
   * @var \Drupal\Core\TypedData\TypedDataManager
   */
  protected $typedDataManager;

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'field_ui_field_storage_edit_form';
  }

  /**
   * Constructs a new FieldStorageEditForm object.
   *
   * @param \Drupal\Core\Entity\EntityManagerInterface $entity_manager
   *   The entity manager.
   * @param \Drupal\Core\TypedData\TypedDataManager $typed_data_manager
   *   The typed data manager.
   */
  public function __construct(EntityManagerInterface $entity_manager, TypedDataManager $typed_data_manager) {
    $this->entityManager = $entity_manager;
    $this->typedDataManager = $typed_data_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity.manager'),
      $container->get('typed_data_manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, FieldInstanceConfigInterface $field_instance_config = NULL) {
    $this->instance = $field_instance_config;
    $form_state->set('instance', $field_instance_config);
    $form['#title'] = $this->instance->label();

    $field_storage = $this->instance->getFieldStorageDefinition();
    $form['#field'] = $field_storage;
    $form['#bundle'] = $this->instance->bundle;

    $description = '<p>' . $this->t('These settings apply to the %field field everywhere it is used. These settings impact the way that data is stored in the database and cannot be changed once data has been created.', array('%field' => $this->instance->label())) . '</p>';

    // Create a form structure for the field values.
    $form['field'] = array(
      '#prefix' => $description,
      '#tree' => TRUE,
    );

    // See if data already exists for this field.
    // If so, prevent changes to the field settings.
    if ($field_storage->hasData()) {
      $form['field']['#prefix'] = '<div class="messages messages--error">' . $this->t('There is data for this field in the database. The field settings can no longer be changed.') . '</div>' . $form['field']['#prefix'];
    }

    // Build the configurable field values.
    $cardinality = $field_storage->getCardinality();
    $form['field']['cardinality_container'] = array(
      // Reset #parents to 'field', so the additional container does not appear.
      '#parents' => array('field'),
      '#type' => 'fieldset',
      '#title' => $this->t('Allowed number of values'),
      '#attributes' => array('class' => array(
        'container-inline',
        'fieldgroup',
        'form-composite'
      )),
    );
    $form['field']['cardinality_container']['cardinality'] = array(
      '#type' => 'select',
      '#title' => $this->t('Allowed number of values'),
      '#title_display' => 'invisible',
      '#options' => array(
        'number' => $this->t('Limited'),
        FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED => $this->t('Unlimited'),
      ),
      '#default_value' => ($cardinality == FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED) ? FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED : 'number',
    );
    $form['field']['cardinality_container']['cardinality_number'] = array(
      '#type' => 'number',
      '#default_value' => $cardinality != FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED ? $cardinality : 1,
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
    $form['field']['field_name'] = array('#type' => 'value', '#value' => $field_storage->getName());
    $form['field']['type'] = array('#type' => 'value', '#value' => $field_storage->getType());
    $form['field']['module'] = array('#type' => 'value', '#value' => $field_storage->module);
    $form['field']['translatable'] = array('#type' => 'value', '#value' => $field_storage->isTranslatable());

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
    $form['field']['settings'] += $entity->get($field_storage->getName())->first()->settingsForm($form, $form_state, $field_storage->hasData());

    $form['actions'] = array('#type' => 'actions');
    $form['actions']['submit'] = array('#type' => 'submit', '#value' => $this->t('Save field settings'));
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    // Validate field cardinality.
    $field_values = $form_state->getValue('field');
    $cardinality = $field_values['cardinality'];
    $cardinality_number = $field_values['cardinality_number'];
    if ($cardinality === 'number' && empty($cardinality_number)) {
      $form_state->setErrorByName('field][cardinality_number', $this->t('Number of values is required.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $form_values = $form_state->getValues();
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
    $field_storage = $this->instance->getFieldStorageDefinition();
    foreach ($field_values as $key => $value) {
      $field_storage->{$key} = $value;
    }

    // Update the field.
    try {
      $field_storage->save();
      drupal_set_message($this->t('Updated field %label field settings.', array('%label' => $this->instance->label())));
      $request = $this->getRequest();
      if (($destinations = $request->query->get('destinations')) && $next_destination = FieldUI::getNextDestination($destinations)) {
        $request->query->remove('destinations');
        $form_state->setRedirectUrl($next_destination);
      }
      else {
        $form_state->setRedirectUrl(FieldUI::getOverviewRouteInfo($this->instance->entity_type, $this->instance->bundle));
      }
    }
    catch (\Exception $e) {
      drupal_set_message($this->t('Attempt to update field %label failed: %message.', array('%label' => $this->instance->label(), '%message' => $e->getMessage())), 'error');
    }
  }

}
