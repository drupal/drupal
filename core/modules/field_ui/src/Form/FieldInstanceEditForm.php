<?php

/**
 * @file
 * Contains \Drupal\field_ui\Form\FieldInstanceEditForm.
 */

namespace Drupal\field_ui\Form;

use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Component\Utility\String;
use Drupal\Core\Form\FormStateInterface;
use Drupal\field\FieldInstanceConfigInterface;
use Drupal\field_ui\FieldUI;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a form for the field instance settings form.
 */
class FieldInstanceEditForm extends FormBase {

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
   * Constructs a new field instance form.
   *
   * @param \Drupal\Core\Entity\EntityManagerInterface $entity_manager
   *   The entity manager.
   */
  public function __construct(EntityManagerInterface $entity_manager) {
    $this->entityManager = $entity_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'field_ui_field_instance_edit_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, FieldInstanceConfigInterface $field_instance_config = NULL) {
    $this->instance = $form_state['instance'] = $field_instance_config;

    $bundle = $this->instance->bundle;
    $entity_type = $this->instance->entity_type;
    $field_storage = $this->instance->getFieldStorageDefinition();
    $bundles = entity_get_bundles();

    $form_title = $this->t('%instance settings for %bundle', array(
      '%instance' => $this->instance->getLabel(),
      '%bundle' => $bundles[$entity_type][$bundle]['label'],
    ));
    $form['#title'] = $form_title;

    $form['#field'] = $field_storage;
    // Create an arbitrary entity object (used by the 'default value' widget).
    $ids = (object) array('entity_type' => $this->instance->entity_type, 'bundle' => $this->instance->bundle, 'entity_id' => NULL);
    $form['#entity'] = _field_create_entity_from_ids($ids);
    $items = $form['#entity']->get($this->instance->getName());

    if (!empty($field_storage->locked)) {
      $form['locked'] = array(
        '#markup' => $this->t('The field %field is locked and cannot be edited.', array('%field' => $this->instance->getLabel())),
      );
      return $form;
    }

    // Create a form structure for the instance values.
    $form['instance'] = array(
      '#tree' => TRUE,
    );

    // Build the non-configurable instance values.
    $form['instance']['field_name'] = array(
      '#type' => 'value',
      '#value' => $this->instance->getName(),
    );
    $form['instance']['entity_type'] = array(
      '#type' => 'value',
      '#value' => $entity_type,
    );
    $form['instance']['bundle'] = array(
      '#type' => 'value',
      '#value' => $bundle,
    );

    // Build the configurable instance values.
    $form['instance']['label'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#default_value' => $this->instance->getLabel() ?: $field_storage->getName(),
      '#required' => TRUE,
      '#weight' => -20,
    );

    $form['instance']['description'] = array(
      '#type' => 'textarea',
      '#title' => $this->t('Help text'),
      '#default_value' => $this->instance->getDescription(),
      '#rows' => 5,
      '#description' => $this->t('Instructions to present to the user below this field on the editing form.<br />Allowed HTML tags: @tags', array('@tags' => _field_filter_xss_display_allowed_tags())) . '<br />' . $this->t('This field supports tokens.'),
      '#weight' => -10,
    );

    $form['instance']['required'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Required field'),
      '#default_value' => $this->instance->isRequired(),
      '#weight' => -5,
    );

    // Add instance settings for the field type.
    $form['instance']['settings'] = $items[0]->instanceSettingsForm($form, $form_state);
    $form['instance']['settings']['#weight'] = 10;

    // Add handling for default value.
    if ($element = $items->defaultValuesForm($form, $form_state)) {
      $element += array(
        '#type' => 'details',
        '#title' => $this->t('Default value'),
        '#open' => TRUE,
        '#description' => $this->t('The default value for this field, used when creating new content.'),
      );
      $form['instance']['default_value'] = $element;
    }

    $form['actions'] = array('#type' => 'actions');
    $form['actions']['submit'] = array(
      '#type' => 'submit',
      '#value' => $this->t('Save settings')
    );
    $form['actions']['delete'] = array(
      '#type' => 'submit',
      '#value' => $this->t('Delete field'),
      '#submit' => array(array($this, 'delete')),
    );
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    if (isset($form['instance']['default_value'])) {
      $items = $form['#entity']->get($this->instance->getName());
      $items->defaultValuesFormValidate($form['instance']['default_value'], $form, $form_state);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Handle the default value.
    $default_value = array();
    if (isset($form['instance']['default_value'])) {
      $items = $form['#entity']->get($this->instance->getName());
      $default_value = $items->defaultValuesFormSubmit($form['instance']['default_value'], $form, $form_state);
    }
    $this->instance->default_value = $default_value;

    // Merge incoming values into the instance.
    foreach ($form_state['values']['instance'] as $key => $value) {
      $this->instance->$key = $value;
    }
    $this->instance->save();

    drupal_set_message($this->t('Saved %label configuration.', array('%label' => $this->instance->getLabel())));

    $request = $this->getRequest();
    if (($destinations = $request->query->get('destinations')) && $next_destination = FieldUI::getNextDestination($destinations)) {
      $request->query->remove('destinations');
      if (isset($next_destination['route_name'])) {
        $form_state->setRedirect($next_destination['route_name'], $next_destination['route_parameters'], $next_destination['options']);
      }
      else {
        $form_state['redirect'] = $next_destination;
      }
    }
    else {
      $form_state->setRedirectUrl(FieldUI::getOverviewRouteInfo($this->instance->entity_type, $this->instance->bundle));
    }
  }

  /**
   * Redirects to the field instance deletion form.
   */
  public function delete(array &$form, FormStateInterface $form_state) {
    $destination = array();
    $request = $this->getRequest();
    if ($request->query->has('destination')) {
      $destination = drupal_get_destination();
      $request->query->remove('destination');
    }
    $entity_type = $this->entityManager->getDefinition($this->instance->entity_type);
    $form_state->setRedirect(
      'field_ui.delete_' . $this->instance->entity_type,
      array(
        $entity_type->getBundleEntityType() => $this->instance->bundle,
        'field_instance_config' => $this->instance->id(),
      ),
      array('query' => $destination)
    );
  }

  /**
   * The _title_callback for the field instance settings form.
   *
   * @param \Drupal\field\FieldInstanceConfigInterface $field_instance_config
   *   The field instance.
   *
   * @return string
   *   The label of the field instance.
   */
  public function getTitle(FieldInstanceConfigInterface $field_instance_config) {
    return String::checkPlain($field_instance_config->label());
  }

}
