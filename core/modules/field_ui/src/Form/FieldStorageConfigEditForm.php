<?php

/**
 * @file
 * Contains \Drupal\field_ui\Form\FieldStorageConfigEditForm.
 */

namespace Drupal\field_ui\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\field\Entity\FieldConfig;
use Drupal\field_ui\FieldUI;

/**
 * Provides a form for the "field storage" edit page.
 */
class FieldStorageConfigEditForm extends EntityForm {

  /**
   * The entity being used by this form.
   *
   * @var \Drupal\field\FieldStorageConfigInterface
   */
  protected $entity;

  /**
   * {@inheritdoc}
   */
  public function getEntityFromRouteMatch(RouteMatchInterface $route_match, $entity_type_id) {
    // The URL of this entity form contains only the ID of the field_config
    // but we are actually editing a field_storage_config entity.
    $field_config = FieldConfig::load($route_match->getRawParameter('field_config'));

    return $field_config->getFieldStorageDefinition();
  }

  /**
   * {@inheritdoc}
   *
   * @param string $field_config
   *   The ID of the field config whose field storage config is being edited.
   */
  public function buildForm(array $form, FormStateInterface $form_state, $field_config = NULL) {
    if ($field_config) {
      $field = FieldConfig::load($field_config);
      $form_state->set('field_config', $field);

      $form_state->set('entity_type_id', $field->getTargetEntityTypeId());
      $form_state->set('bundle', $field->getTargetBundle());
    }

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    $field_label = $form_state->get('field_config')->label();
    $form['#title'] = $field_label;
    $form['#prefix'] = '<p>' . $this->t('These settings apply to the %field field everywhere it is used. These settings impact the way that data is stored in the database and cannot be changed once data has been created.', array('%field' => $field_label)) . '</p>';

    // See if data already exists for this field.
    // If so, prevent changes to the field settings.
    if ($this->entity->hasData()) {
      $form['#prefix'] = '<div class="messages messages--error">' . $this->t('There is data for this field in the database. The field settings can no longer be changed.') . '</div>' . $form['#prefix'];
    }

    // Add settings provided by the field module. The field module is
    // responsible for not returning settings that cannot be changed if
    // the field already has data.
    $form['settings'] = array(
      '#weight' => -10,
      '#tree' => TRUE,
    );
    // Create an arbitrary entity object, so that we can have an instantiated
    // FieldItem.
    $ids = (object) array(
      'entity_type' => $form_state->get('entity_type_id'),
      'bundle' => $form_state->get('bundle'),
      'entity_id' => NULL
    );
    $entity = _field_create_entity_from_ids($ids);
    $items = $entity->get($this->entity->getName());
    $item = $items->first() ?: $items->appendItem();
    $form['settings'] += $item->storageSettingsForm($form, $form_state, $this->entity->hasData());

    // Build the configurable field values.
    $cardinality = $this->entity->getCardinality();
    $form['cardinality_container'] = array(
      // Reset #parents so the additional container does not appear.
      '#parents' => array(),
      '#type' => 'fieldset',
      '#title' => $this->t('Allowed number of values'),
      '#attributes' => array('class' => array(
        'container-inline',
        'fieldgroup',
        'form-composite'
      )),
    );
    $form['cardinality_container']['cardinality'] = array(
      '#type' => 'select',
      '#title' => $this->t('Allowed number of values'),
      '#title_display' => 'invisible',
      '#options' => array(
        'number' => $this->t('Limited'),
        FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED => $this->t('Unlimited'),
      ),
      '#default_value' => ($cardinality == FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED) ? FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED : 'number',
    );
    $form['cardinality_container']['cardinality_number'] = array(
      '#type' => 'number',
      '#default_value' => $cardinality != FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED ? $cardinality : 1,
      '#min' => 1,
      '#title' => $this->t('Limit'),
      '#title_display' => 'invisible',
      '#size' => 2,
      '#states' => array(
        'visible' => array(
         ':input[name="cardinality"]' => array('value' => 'number'),
        ),
        'disabled' => array(
         ':input[name="cardinality"]' => array('value' => FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED),
        ),
      ),
    );

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  protected function actions(array $form, FormStateInterface $form_state) {
    $elements = parent::actions($form, $form_state);
    $elements['submit']['#value'] = $this->t('Save field settings');

    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);

    // Validate field cardinality.
    if ($form_state->getValue('cardinality') === 'number' && !$form_state->getValue('cardinality_number')) {
      $form_state->setErrorByName('cardinality_number', $this->t('Number of values is required.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function buildEntity(array $form, FormStateInterface $form_state) {
    // Save field cardinality.
    if ($form_state->getValue('cardinality') === 'number' && $form_state->getValue('cardinality_number')) {
      $form_state->setValue('cardinality', $form_state->getValue('cardinality_number'));
    }

    return parent::buildEntity($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $field_label = $form_state->get('field_config')->label();
    try {
      $this->entity->save();
      drupal_set_message($this->t('Updated field %label field settings.', array('%label' => $field_label)));
      $request = $this->getRequest();
      if (($destinations = $request->query->get('destinations')) && $next_destination = FieldUI::getNextDestination($destinations)) {
        $request->query->remove('destinations');
        $form_state->setRedirectUrl($next_destination);
      }
      else {
        $form_state->setRedirectUrl(FieldUI::getOverviewRouteInfo($form_state->get('entity_type_id'), $form_state->get('bundle')));
      }
    }
    catch (\Exception $e) {
      drupal_set_message($this->t('Attempt to update field %label failed: %message.', array('%label' => $field_label, '%message' => $e->getMessage())), 'error');
    }
  }

}
