<?php

/**
 * @file
 * Contains \Drupal\edit\Form\EditFieldForm.
 */

namespace Drupal\edit\Form;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityStorageControllerInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Form\FormBase;
use Drupal\entity\Entity\EntityFormDisplay;
use Drupal\user\TempStoreFactory;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Builds and process a form for editing a single entity field.
 */
class EditFieldForm extends FormBase {

  /**
   * Stores the tempstore factory.
   *
   * @var \Drupal\user\TempStoreFactory
   */
  protected $tempStoreFactory;

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * The node type storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageControllerInterface
   */
  protected $nodeTypeStorage;

  /**
   * Constructs a new EditFieldForm.
   *
   * @param \Drupal\user\TempStoreFactory $temp_store_factory
   *   The tempstore factory.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   * @param \Drupal\Core\Entity\EntityStorageControllerInterface $node_type_storage
   *   The node type storage.
   */
  public function __construct(TempStoreFactory $temp_store_factory, ModuleHandlerInterface $module_handler, EntityStorageControllerInterface $node_type_storage) {
    $this->moduleHandler = $module_handler;
    $this->nodeTypeStorage = $node_type_storage;
    $this->tempStoreFactory = $temp_store_factory;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('user.tempstore'),
      $container->get('module_handler'),
      $container->get('entity.manager')->getStorageController('node_type')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'edit_field_form';
  }

  /**
   * {@inheritdoc}
   *
   * Builds a form for a single entity field.
   */
  public function buildForm(array $form, array &$form_state, EntityInterface $entity = NULL, $field_name = NULL) {
    if (!isset($form_state['entity'])) {
      $this->init($form_state, $entity, $field_name);
    }

    // Add the field form.
    field_attach_form($form_state['entity'], $form, $form_state, $form_state['langcode'], array('field_name' =>  $form_state['field_name']));

    // Add a dummy changed timestamp field to attach form errors to.
    if ($entity instanceof EntityChangedInterface) {
      $form['changed_field'] = array(
        '#type' => 'hidden',
        '#value' => $entity->getChangedTime(),
      );
    }

    // Add a submit button. Give it a class for easy JavaScript targeting.
    $form['actions'] = array('#type' => 'actions');
    $form['actions']['submit'] = array(
      '#type' => 'submit',
      '#value' => t('Save'),
      '#attributes' => array('class' => array('edit-form-submit')),
    );

    // Simplify it for optimal in-place use.
    $this->simplify($form, $form_state);

    return $form;
  }

  /**
   * Initialize the form state and the entity before the first form build.
   */
  protected function init(array &$form_state, EntityInterface $entity, $field_name) {
    // @todo Rather than special-casing $node->revision, invoke prepareEdit()
    //   once http://drupal.org/node/1863258 lands.
    if ($entity->getEntityTypeId() == 'node') {
      $node_type_settings = $this->nodeTypeStorage->load($entity->bundle())->getModuleSettings('node');
      $options = (isset($node_type_settings['options'])) ? $node_type_settings['options'] : array();
      $entity->setNewRevision(!empty($options['revision']));
      $entity->log = NULL;
    }

    $form_state['entity'] = $entity;
    $form_state['field_name'] = $field_name;

    // @todo Allow the usage of different form modes by exposing a hook and the
    //   UI for them.
    $form_state['form_display'] = EntityFormDisplay::collectRenderDisplay($entity, 'default');
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, array &$form_state) {
    $entity = $this->buildEntity($form, $form_state);
    field_attach_form_validate($entity, $form, $form_state, array('field_name' =>  $form_state['field_name']));

    // Do validation on the changed field as well and assign the error to the
    // dummy form element we added for this. We don't know the name of this
    // field on the entity, so we need to find it and validate it ourselves.
    if ($changed_field_name = $this->getChangedFieldName($entity)) {
      $changed_field_errors = $entity->$changed_field_name->validate();
      if (count($changed_field_errors)) {
        $this->setFormError('changed_field', $form_state, $changed_field_errors[0]->getMessage());
      }
    }
  }

  /**
   * {@inheritdoc}
   *
   * Saves the entity with updated values for the edited field.
   */
  public function submitForm(array &$form, array &$form_state) {
    $form_state['entity'] = $this->buildEntity($form, $form_state);

    // Store entity in tempstore with its UUID as tempstore key.
    $this->tempStoreFactory->get('edit')->set($form_state['entity']->uuid(), $form_state['entity']);
  }

  /**
   * Returns a cloned entity containing updated field values.
   *
   * Calling code may then validate the returned entity, and if valid, transfer
   * it back to the form state and save it.
   */
  protected function buildEntity(array $form, array &$form_state) {
    /** @var $entity \Drupal\Core\Entity\EntityInterface */
    $entity = clone $form_state['entity'];
    $field_name = $form_state['field_name'];

    field_attach_extract_form_values($entity, $form, $form_state, array('field_name' => $field_name));

    // @todo Refine automated log messages and abstract them to all entity
    //   types: http://drupal.org/node/1678002.
    if ($entity->getEntityTypeId() == 'node' && $entity->isNewRevision() && !isset($entity->log)) {
      $entity->log = t('Updated the %field-name field through in-place editing.', array('%field-name' => $entity->get($field_name)->getFieldDefinition()->getLabel()));
    }

    return $entity;
  }

  /**
   * Simplifies the field edit form for in-place editing.
   *
   * This function:
   * - Hides the field label inside the form, because JavaScript displays it
   *   outside the form.
   * - Adjusts textarea elements to fit their content.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   */
  protected function simplify(array &$form, array &$form_state) {
    $field_name = $form_state['field_name'];
    $widget_element =& $form[$field_name]['widget'];

    // Hide the field label from displaying within the form, because JavaScript
    // displays the equivalent label that was provided within an HTML data
    // attribute of the field's display element outside of the form. Do this for
    // widgets without child elements (like Option widgets) as well as for ones
    // with per-delta elements. Skip single checkboxes, because their title is
    // key to their UI. Also skip widgets with multiple subelements, because in
    // that case, per-element labeling is informative.
    $num_children = count(element_children($widget_element));
    if ($num_children == 0 && $widget_element['#type'] != 'checkbox') {
      $widget_element['#title_display'] = 'invisible';
    }
    if ($num_children == 1 && isset($widget_element[0]['value'])) {
      // @todo While most widgets name their primary element 'value', not all
      //   do, so generalize this.
      $widget_element[0]['value']['#title_display'] = 'invisible';
    }

    // Adjust textarea elements to fit their content.
    if (isset($widget_element[0]['value']['#type']) && $widget_element[0]['value']['#type'] == 'textarea') {
      $lines = count(explode("\n", $widget_element[0]['value']['#default_value']));
      $widget_element[0]['value']['#rows'] = $lines + 1;
    }
  }

  /**
   * Finds the field name for the field carrying the changed timestamp, if any.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The entity.
   *
   * @return string|null
   *   The name of the field found or NULL if not found.
   */
  protected function getChangedFieldName(ContentEntityInterface $entity) {
    foreach ($entity->getFieldDefinitions() as $field) {
      if ($field->getType() == 'changed') {
        return $field->getName();
      }
    }
  }

}
