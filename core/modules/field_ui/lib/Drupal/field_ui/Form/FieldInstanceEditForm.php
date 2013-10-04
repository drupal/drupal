<?php

/**
 * @file
 * Contains \Drupal\field_ui\Form\FieldInstanceEditForm.
 */

namespace Drupal\field_ui\Form;

use Drupal\Core\Entity\EntityManager;
use Drupal\Core\Form\FormBase;
use Drupal\field\FieldInstanceInterface;
use Drupal\field_ui\FieldUI;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a form for the field instance settings form.
 */
class FieldInstanceEditForm extends FormBase {

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
   * Constructs a new field instance form.
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
      $container->get('entity.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormID() {
    return 'field_ui_field_instance_edit_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, array &$form_state, FieldInstanceInterface $field_instance = NULL) {
    $this->instance = $form_state['instance'] = $field_instance;

    $bundle = $this->instance->bundle;
    $entity_type = $this->instance->entity_type;
    $field = $this->instance->getField();
    $bundles = entity_get_bundles();

    drupal_set_title($this->t('%instance settings for %bundle', array(
      '%instance' => $this->instance->getFieldLabel(),
      '%bundle' => $bundles[$entity_type][$bundle]['label'],
    )), PASS_THROUGH);

    $form['#field'] = $field;
    // Create an arbitrary entity object (used by the 'default value' widget).
    $ids = (object) array('entity_type' => $this->instance->entity_type, 'bundle' => $this->instance->bundle, 'entity_id' => NULL);
    $form['#entity'] = _field_create_entity_from_ids($ids);
    $items = $form['#entity']->get($this->instance->getFieldName());

    if (!empty($field->locked)) {
      $form['locked'] = array(
        '#markup' => $this->t('The field %field is locked and cannot be edited.', array('%field' => $this->instance->getFieldLabel())),
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
      '#value' => $this->instance->getFieldName(),
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
      '#default_value' => $this->instance->getFieldLabel() ?: $field->getFieldName(),
      '#required' => TRUE,
      '#weight' => -20,
    );

    $form['instance']['description'] = array(
      '#type' => 'textarea',
      '#title' => $this->t('Help text'),
      '#default_value' => $this->instance->getFieldDescription(),
      '#rows' => 5,
      '#description' => $this->t('Instructions to present to the user below this field on the editing form.<br />Allowed HTML tags: @tags', array('@tags' => _field_filter_xss_display_allowed_tags())) . '<br />' . $this->t('This field supports tokens.'),
      '#weight' => -10,
    );

    $form['instance']['required'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Required field'),
      '#default_value' => $this->instance->isFieldRequired(),
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
  public function validateForm(array &$form, array &$form_state) {
    if (isset($form['instance']['default_value'])) {
      $items = $form['#entity']->get($this->instance->getFieldName());
      $items->defaultValuesFormValidate($form['instance']['default_value'], $form, $form_state);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, array &$form_state) {
    // Handle the default value.
    $default_value = array();
    if (isset($form['instance']['default_value'])) {
      $items = $form['#entity']->get($this->instance->getFieldName());
      $default_value = $items->defaultValuesFormSubmit($form['instance']['default_value'], $form, $form_state);
    }
    $this->instance->default_value = $default_value;

    // Merge incoming values into the instance.
    foreach ($form_state['values']['instance'] as $key => $value) {
      $this->instance->$key = $value;
    }
    $this->instance->save();

    drupal_set_message($this->t('Saved %label configuration.', array('%label' => $this->instance->getFieldLabel())));

    $form_state['redirect'] = $this->getNextDestination();
  }

  /**
   * Redirects to the field instance deletion form.
   */
  public function delete(array &$form, array &$form_state) {
    $destination = array();
    $request = $this->getRequest();
    if ($request->query->has('destination')) {
      $destination = drupal_get_destination();
      $request->query->remove('destination');
    }
    $form_state['redirect'] = array('admin/structure/types/manage/' . $this->instance['bundle'] . '/fields/' . $this->instance->id() . '/delete', array('query' => $destination));
  }

  /**
   * Returns the next redirect path in a multipage sequence.
   *
   * @return string|array
   *   Either the next path, or an array of redirect paths.
   */
  protected function getNextDestination() {
    $next_destination = FieldUI::getNextDestination($this->getRequest());
    if (empty($next_destination)) {
      $next_destination = $this->entityManager->getAdminPath($this->instance->entity_type, $this->instance->bundle) . '/fields';
    }
    return $next_destination;
  }

}
