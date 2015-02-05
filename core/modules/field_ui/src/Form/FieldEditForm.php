<?php

/**
 * @file
 * Contains \Drupal\field_ui\Form\FieldEditForm.
 */

namespace Drupal\field_ui\Form;

use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Field\AllowedTagsXssTrait;
use Drupal\Core\Form\FormBase;
use Drupal\Component\Utility\String;
use Drupal\Core\Form\FormStateInterface;
use Drupal\field\FieldConfigInterface;
use Drupal\field_ui\FieldUI;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a form for the field settings form.
 */
class FieldEditForm extends FormBase {

  use AllowedTagsXssTrait;

  /**
   * The field being edited.
   *
   * @var \Drupal\field\FieldConfigInterface
   */
  protected $field;

  /**
   * The entity manager.
   *
   * @var \Drupal\Core\Entity\EntityManagerInterface
   */
  protected $entityManager;

  /**
   * Constructs a new field form.
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
    return 'field_ui_field_edit_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, FieldConfigInterface $field_config = NULL) {
    $this->field = $field_config;
    $form_state->set('field', $field_config);

    $bundle = $this->field->bundle;
    $entity_type = $this->field->entity_type;
    $field_storage = $this->field->getFieldStorageDefinition();
    $bundles = entity_get_bundles();

    $form_title = $this->t('%field settings for %bundle', array(
      '%field' => $this->field->getLabel(),
      '%bundle' => $bundles[$entity_type][$bundle]['label'],
    ));
    $form['#title'] = $form_title;

    $form['#field'] = $field_storage;
    // Create an arbitrary entity object (used by the 'default value' widget).
    $ids = (object) array('entity_type' => $this->field->entity_type, 'bundle' => $this->field->bundle, 'entity_id' => NULL);
    $form['#entity'] = _field_create_entity_from_ids($ids);
    $items = $form['#entity']->get($this->field->getName());
    $item = $items->first() ?: $items->appendItem();

    if (!empty($field_storage->locked)) {
      $form['locked'] = array(
        '#markup' => $this->t('The field %field is locked and cannot be edited.', array('%field' => $this->field->getLabel())),
      );
      return $form;
    }

    // Create a form structure for the field values.
    $form['field'] = array(
      '#tree' => TRUE,
    );

    // Build the non-configurable field values.
    $form['field']['field_name'] = array(
      '#type' => 'value',
      '#value' => $this->field->getName(),
    );
    $form['field']['entity_type'] = array(
      '#type' => 'value',
      '#value' => $entity_type,
    );
    $form['field']['bundle'] = array(
      '#type' => 'value',
      '#value' => $bundle,
    );

    // Build the configurable field values.
    $form['field']['label'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#default_value' => $this->field->getLabel() ?: $field_storage->getName(),
      '#required' => TRUE,
      '#weight' => -20,
    );

    $form['field']['description'] = array(
      '#type' => 'textarea',
      '#title' => $this->t('Help text'),
      '#default_value' => $this->field->getDescription(),
      '#rows' => 5,
      '#description' => $this->t('Instructions to present to the user below this field on the editing form.<br />Allowed HTML tags: @tags', array('@tags' => $this->displayAllowedTags())) . '<br />' . $this->t('This field supports tokens.'),
      '#weight' => -10,
    );

    $form['field']['required'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Required field'),
      '#default_value' => $this->field->isRequired(),
      '#weight' => -5,
    );

    // Add field settings for the field type and a container for third party
    // settings that modules can add to via hook_form_FORM_ID_alter().
    $form['field']['settings'] = $item->fieldSettingsForm($form, $form_state);
    $form['field']['settings']['#weight'] = 10;
    $form['field']['third_party_settings'] = array();
    $form['field']['third_party_settings']['#weight'] = 11;

    // Add handling for default value.
    if ($element = $items->defaultValuesForm($form, $form_state)) {
      $element = array_merge($element , array(
        '#type' => 'details',
        '#title' => $this->t('Default value'),
        '#open' => TRUE,
        '#description' => $this->t('The default value for this field, used when creating new content.'),
      ));

      $form['field']['default_value'] = $element;
    }

    $form['actions'] = array('#type' => 'actions');
    $form['actions']['submit'] = array(
      '#type' => 'submit',
      '#value' => $this->t('Save settings'),
      '#button_type' => 'primary',
    );
    $form['actions']['delete'] = array(
      '#type' => 'submit',
      '#value' => $this->t('Delete field'),
      '#submit' => array('::delete'),
    );
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    if (isset($form['field']['default_value'])) {
      $items = $form['#entity']->get($this->field->getName());
      $items->defaultValuesFormValidate($form['field']['default_value'], $form, $form_state);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Handle the default value.
    $default_value = array();
    if (isset($form['field']['default_value'])) {
      $items = $form['#entity']->get($this->field->getName());
      $default_value = $items->defaultValuesFormSubmit($form['field']['default_value'], $form, $form_state);
    }
    $this->field->default_value = $default_value;

    // Merge incoming values into the field.
    foreach ($form_state->getValue('field') as $key => $value) {
      $this->field->set($key, $value);
    }
    $this->field->save();

    drupal_set_message($this->t('Saved %label configuration.', array('%label' => $this->field->getLabel())));

    $request = $this->getRequest();
    if (($destinations = $request->query->get('destinations')) && $next_destination = FieldUI::getNextDestination($destinations)) {
      $request->query->remove('destinations');
      $form_state->setRedirectUrl($next_destination);
    }
    else {
      $form_state->setRedirectUrl(FieldUI::getOverviewRouteInfo($this->field->entity_type, $this->field->bundle));
    }
  }

  /**
   * Redirects to the field deletion form.
   */
  public function delete(array &$form, FormStateInterface $form_state) {
    $destination = array();
    $request = $this->getRequest();
    if ($request->query->has('destination')) {
      $destination = drupal_get_destination();
      $request->query->remove('destination');
    }
    $entity_type = $this->entityManager->getDefinition($this->field->entity_type);
    $form_state->setRedirect(
      'entity.field_config.' . $this->field->entity_type . '_field_delete_form',
      array(
        $entity_type->getBundleEntityType() => $this->field->bundle,
        'field_config' => $this->field->id(),
      ),
      array('query' => $destination)
    );
  }

  /**
   * The _title_callback for the field settings form.
   *
   * @param \Drupal\field\FieldConfigInterface $field_config
   *   The field.
   *
   * @return string
   *   The label of the field.
   */
  public function getTitle(FieldConfigInterface $field_config) {
    return String::checkPlain($field_config->label());
  }

}
