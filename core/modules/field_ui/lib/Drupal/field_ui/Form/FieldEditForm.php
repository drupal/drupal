<?php

/**
 * @file
 * Contains \Drupal\field_ui\Form\FieldEditForm.
 */

namespace Drupal\field_ui\Form;

use Drupal\Core\Controller\ControllerInterface;
use Drupal\Core\Entity\EntityManager;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Form\FormInterface;
use Drupal\field\FieldInstanceInterface;
use Drupal\field\Field;
use Drupal\field_ui\FieldUI;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a form for the field settings edit page.
 */
class FieldEditForm implements FormInterface, ControllerInterface {

  /**
   * The field instance being edited.
   *
   * @var \Drupal\field\FieldInstanceInterface
   */
  protected $instance;

  /**
   * The entity manager.
   *
   * @var \Drupal\Core\Entity\EntityManager
   */
  protected $entityManager;

  /**
   * {@inheritdoc}
   */
  public function getFormID() {
    return 'field_ui_field_edit_form';
  }

  /**
   * Constructs a new FieldEditForm object.
   *
   * @param \Drupal\Core\Entity\EntityManager $entity_manager
   *   The entity manager.
   */
  public function __construct(EntityManager $entity_manager) {
    $this->entityManager = $entity_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('plugin.manager.entity')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, array &$form_state, FieldInstanceInterface $field_instance = NULL) {
    $this->instance = $form_state['instance'] = $field_instance;

    $field = $this->instance->getField();
    $form['#field'] = $field;

    drupal_set_title($this->instance->label());

    $description = '<p>' . t('These settings apply to the %field field everywhere it is used. These settings impact the way that data is stored in the database and cannot be changed once data has been created.', array('%field' => $this->instance->label())) . '</p>';

    // Create a form structure for the field values.
    $form['field'] = array(
      '#prefix' => $description,
      '#tree' => TRUE,
    );

    // See if data already exists for this field.
    // If so, prevent changes to the field settings.
    if ($field->hasData()) {
      $form['field']['#prefix'] = '<div class="messages messages--error">' . t('There is data for this field in the database. The field settings can no longer be changed.') . '</div>' . $form['field']['#prefix'];
    }

    // Build the configurable field values.
    $cardinality = $field['cardinality'];
    $form['field']['cardinality_container'] = array(
      // We can't use the container element because it doesn't support the title
      // or description properties.
      '#type' => 'item',
      // Reset #parents to 'field', so the additional container does not appear.
      '#parents' => array('field'),
      '#field_prefix' => '<div class="container-inline">',
      '#field_suffix' => '</div>',
      '#title' => t('Allowed number of values'),
    );
    $form['field']['cardinality_container']['cardinality'] = array(
      '#type' => 'select',
      '#title' => t('Allowed number of values'),
      '#title_display' => 'invisible',
      '#options' => array(
        'number' => t('Limited'),
        FIELD_CARDINALITY_UNLIMITED => t('Unlimited'),
      ),
      '#default_value' => ($cardinality == FIELD_CARDINALITY_UNLIMITED) ? FIELD_CARDINALITY_UNLIMITED : 'number',
    );
    $form['field']['cardinality_container']['cardinality_number'] = array(
      '#type' => 'number',
      '#default_value' => $cardinality != FIELD_CARDINALITY_UNLIMITED ? $cardinality : 1,
      '#min' => 1,
      '#title' => t('Limit'),
      '#title_display' => 'invisible',
      '#size' => 2,
      '#states' => array(
        'visible' => array(
         ':input[name="field[cardinality]"]' => array('value' => 'number'),
        ),
      ),
    );

    // Build the non-configurable field values.
    $form['field']['field_name'] = array('#type' => 'value', '#value' => $field['field_name']);
    $form['field']['type'] = array('#type' => 'value', '#value' => $field['type']);
    $form['field']['module'] = array('#type' => 'value', '#value' => $field['module']);
    $form['field']['active'] = array('#type' => 'value', '#value' => $field['active']);

    // Add settings provided by the field module. The field module is
    // responsible for not returning settings that cannot be changed if
    // the field already has data.
    $form['field']['settings'] = array(
      '#weight' => 10,
    );
    // Create an arbitrary entity object, so that we can have an instantiated
    // FieldItem.
    $ids = (object) array('entity_type' => $this->instance['entity_type'], 'bundle' => $this->instance['bundle'], 'entity_id' => NULL);
    $entity = _field_create_entity_from_ids($ids);
    $form['field']['settings'] += $this->getFieldItem($entity, $field['field_name'])->settingsForm($form, $form_state);

    $form['actions'] = array('#type' => 'actions');
    $form['actions']['submit'] = array('#type' => 'submit', '#value' => t('Save field settings'));
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
      form_error($form['field']['cardinality_container']['cardinality_number'], t('Number of values is required.'));
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
    $field = Field::fieldInfo()->getField($field_values['field_name']);
    foreach ($field_values as $key => $value) {
      $field[$key] = $value;
    }

    // Update the field.
    try {
      $field->save();
      drupal_set_message(t('Updated field %label field settings.', array('%label' => $this->instance->label())));
      $next_destination = FieldUI::getNextDestination();
      if (empty($next_destination)) {
        $next_destination = $this->entityManager->getAdminPath($this->instance->entity_type, $this->instance->bundle) . '/fields';
      }
      $form_state['redirect'] = $next_destination;
    }
    catch (\Exception $e) {
      drupal_set_message(t('Attempt to update field %label failed: %message.', array('%label' => $this->instance->label(), '%message' => $e->getMessage())), 'error');
    }
  }

  /**
   * Returns a FieldItem object for an entity.
   *
   * @todo Remove when all entity types extend EntityNG.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   An entity.
   * @param string $field_name
   *   The field name.
   *
   * @return \Drupal\field\Plugin\Type\FieldType\ConfigFieldItemInterface
   *   The field item object.
   */
  protected function getFieldItem(EntityInterface $entity, $field_name) {
    if ($entity instanceof \Drupal\Core\Entity\EntityNG) {
      $item = $entity->get($field_name)->offsetGet(0);
    }
    else {
      $definitions = \Drupal::entityManager()->getFieldDefinitions($entity->entityType(), $entity->bundle());
      $item = \Drupal::typedData()->create($definitions[$field_name], array(), $field_name, $entity)->offsetGet(0);
    }
    return $item;
  }

}
