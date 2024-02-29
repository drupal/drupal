<?php

namespace Drupal\field_ui\Form;

use Drupal\Core\Entity\EntityDisplayRepositoryInterface;
use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityStorageException;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\Core\Entity\Plugin\DataType\EntityAdapter;
use Drupal\Core\Field\FieldFilteredMarkup;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\SubformState;
use Drupal\Core\Render\Element;
use Drupal\Core\Render\ElementInfoManagerInterface;
use Drupal\Core\TempStore\PrivateTempStore;
use Drupal\Core\TypedData\TypedDataInterface;
use Drupal\Core\TypedData\TypedDataManagerInterface;
use Drupal\Core\Url;
use Drupal\field\FieldConfigInterface;
use Drupal\field_ui\FieldUI;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a form for the field settings form.
 *
 * @internal
 */
class FieldConfigEditForm extends EntityForm {

  use FieldStorageCreationTrait;

  /**
   * The entity being used by this form.
   *
   * @var \Drupal\field\FieldConfigInterface
   */
  protected $entity;

  /**
   * The entity type bundle info service.
   *
   * @var \Drupal\Core\Entity\EntityTypeBundleInfoInterface
   */
  protected $entityTypeBundleInfo;

  /**
   * The name of the entity type.
   *
   * @var string
   */
  protected string $entityTypeId;

  /**
   * The entity bundle.
   *
   * @var string
   */
  protected string $bundle;

  /**
   * Constructs a new FieldConfigDeleteForm object.
   *
   * @param \Drupal\Core\Entity\EntityTypeBundleInfoInterface $entity_type_bundle_info
   *   The entity type bundle info service.
   * @param \Drupal\Core\TypedData\TypedDataManagerInterface $typedDataManager
   *   The type data manger.
   * @param \Drupal\Core\Entity\EntityDisplayRepositoryInterface|null $entityDisplayRepository
   *   The entity display repository.
   * @param \Drupal\Core\TempStore\PrivateTempStore|null $tempStore
   *   The private tempstore.
   * @param \Drupal\Core\Render\ElementInfoManagerInterface|null $elementInfo
   *   The element info manager.
   */
  public function __construct(
    EntityTypeBundleInfoInterface $entity_type_bundle_info,
    protected TypedDataManagerInterface $typedDataManager,
    protected ?EntityDisplayRepositoryInterface $entityDisplayRepository = NULL,
    protected ?PrivateTempStore $tempStore = NULL,
    protected ?ElementInfoManagerInterface $elementInfo = NULL,
  ) {
    $this->entityTypeBundleInfo = $entity_type_bundle_info;
    if ($this->entityDisplayRepository === NULL) {
      @trigger_error('Calling FieldConfigEditForm::__construct() without the $entityDisplayRepository argument is deprecated in drupal:10.2.0 and will be required in drupal:11.0.0. See https://www.drupal.org/node/3383771', E_USER_DEPRECATED);
      $this->entityDisplayRepository = \Drupal::service('entity_display.repository');
    }
    if ($this->tempStore === NULL) {
      @trigger_error('Calling FieldConfigEditForm::__construct() without the $tempStore argument is deprecated in drupal:10.2.0 and will be required in drupal:11.0.0. See https://www.drupal.org/node/3383771', E_USER_DEPRECATED);
      $this->tempStore = \Drupal::service('tempstore.private')->get('field_ui');
    }
    if ($this->elementInfo === NULL) {
      @trigger_error('Calling FieldConfigEditForm::__construct() without the $elementInfo argument is deprecated in drupal:10.2.0 and will be required in drupal:11.0.0. See https://www.drupal.org/node/3383771', E_USER_DEPRECATED);
      $this->elementInfo = \Drupal::service('plugin.manager.element_info');
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.bundle.info'),
      $container->get('typed_data_manager'),
      $container->get('entity_display.repository'),
      $container->get('tempstore.private')->get('field_ui'),
      $container->get('plugin.manager.element_info'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    // Ensure that the form ID remains consistent between both 'default' and
    // 'edit' operations. This is needed because historically it was only
    // possible to edit the field configuration.
    return 'field_config_edit_form';
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);
    $form['#entity_builders'][] = 'field_form_field_config_edit_form_entity_builder';

    $field_storage = $this->entity->getFieldStorageDefinition();
    $bundles = $this->entityTypeBundleInfo->getBundleInfo($this->entity->getTargetEntityTypeId());

    $form_title = $this->t('%field settings for %bundle', [
      '%field' => $this->entity->getLabel(),
      '%bundle' => $bundles[$this->entity->getTargetBundle()]['label'],
    ]);
    $form['#title'] = $form_title;

    if ($field_storage->isLocked()) {
      $form['locked'] = [
        '#markup' => $this->t('The field %field is locked and cannot be edited.', ['%field' => $this->entity->getLabel()]),
      ];
      return $form;
    }

    // Build the configurable field values.
    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#default_value' => $this->entity->getLabel() ?: $field_storage->getName(),
      '#required' => TRUE,
      '#maxlength' => 255,
      '#weight' => -20,
    ];

    $form['description'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Help text'),
      '#default_value' => $this->entity->getDescription(),
      '#rows' => 5,
      '#description' => $this->t('Instructions to present to the user below this field on the editing form.<br />Allowed HTML tags: @tags', ['@tags' => FieldFilteredMarkup::displayAllowedTags()]) . '<br />' . $this->t('This field supports tokens.'),
      '#weight' => -10,
    ];

    $form['required'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Required field'),
      '#default_value' => $this->entity->isRequired(),
      '#weight' => -5,
    ];

    // Create an arbitrary entity object (used by the 'default value' widget).
    $ids = (object) [
      'entity_type' => $this->entity->getTargetEntityTypeId(),
      'bundle' => $this->entity->getTargetBundle(),
      'entity_id' => NULL,
    ];
    $form['field_storage'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Field Storage'),
      '#weight' => -15,
      '#tree' => TRUE,
    ];
    $form['field_storage']['subform'] = [
      '#parents' => ['field_storage', 'subform'],
    ];
    $form['field_storage']['subform']['field_storage_submit'] = [
      '#type' => 'submit',
      '#name' => 'field_storage_submit',
      '#attributes' => [
        'class' => ['js-hide'],
      ],
      '#value' => $this->t('Update settings'),
      '#process' => ['::processFieldStorageSubmit'],
      '#limit_validation_errors' => [$form['field_storage']['subform']['#parents']],
      '#submit' => ['::fieldStorageSubmit'],
    ];
    $field_storage_form = $this->entityTypeManager->getFormObject('field_storage_config', $this->operation);
    $field_storage_form->setEntity($field_storage);
    $subform_state = SubformState::createForSubform($form['field_storage']['subform'], $form, $form_state, $field_storage_form);
    $form['field_storage']['subform'] = $field_storage_form->buildForm($form['field_storage']['subform'], $subform_state, $this->entity);

    $form['#entity'] = _field_create_entity_from_ids($ids);
    $items = $this->getTypedData($this->entity, $form['#entity']);
    $item = $items->first() ?: $items->appendItem();

    $this->addAjaxCallbacks($form['field_storage']['subform']);

    if (isset($form['field_storage']['subform']['cardinality_container'])) {
      $form['field_storage']['subform']['cardinality_container']['#parents'] = [
        'field_storage',
        'subform',
      ];
    }
    // Add field settings for the field type and a container for third party
    // settings that modules can add to via hook_form_FORM_ID_alter().
    $form['settings'] = [
      '#tree' => TRUE,
      '#weight' => 10,
    ];
    $form['settings'] += $item->fieldSettingsForm($form, $form_state);
    $form['third_party_settings'] = [
      '#tree' => TRUE,
      '#weight' => 11,
    ];

    // Create a new instance of typed data for the field to ensure that default
    // value widget is always rendered from a clean state.
    $items = $this->getTypedData($this->entity, $form['#entity']);

    // Add handling for default value.
    if ($element = $items->defaultValuesForm($form, $form_state)) {
      $has_required = $this->hasAnyRequired($element);

      $element = array_merge($element, [
        '#type' => 'details',
        '#title' => $this->t('Default value'),
        '#open' => TRUE,
        '#tree' => TRUE,
        '#description' => $this->t('The default value for this field, used when creating new content.'),
        '#weight' => 12,
      ]);

      if (!$has_required) {
        $has_default_value = count($this->entity->getDefaultValue($form['#entity'])) > 0;
        $element['#states'] = [
          'invisible' => [
            ':input[name="set_default_value"]' => ['checked' => FALSE],
          ],
        ];
        $form['set_default_value'] = [
          '#type' => 'checkbox',
          '#title' => $this->t('Set default value'),
          '#default_value' => $has_default_value,
          '#description' => $this->t('Provide a pre-filled value for the editing form.'),
          '#weight' => $element['#weight'],
        ];
      }

      $form['default_value'] = $element;
    }
    $form['#prefix'] = '<div id="field-combined">';
    $form['#suffix'] = '</div>';
    $form['#attached']['library'][] = 'field_ui/drupal.field_ui';
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function afterBuild(array $element, FormStateInterface $form_state) {
    // Delegate ::afterBuild to the subform.
    // @todo remove after https://www.drupal.org/i/3385205 has been addressed.
    if (isset($element['field_storage_submit'])) {
      $field_storage_form = $this->entityTypeManager->getFormObject('field_storage_config', $this->operation);
      $field_storage_form->setEntity($this->entity->getFieldStorageDefinition());
      return $field_storage_form->afterBuild($element, SubformState::createForSubform($element, $form_state->getCompleteForm(), $form_state));
    }

    return parent::afterBuild($element, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  protected function copyFormValuesToEntity(EntityInterface $entity, array $form, FormStateInterface $form_state) {
    parent::copyFormValuesToEntity($entity, $form, $form_state);

    // Update the current field storage instance based on subform state.
    if (!empty($form['field_storage']['subform'])) {
      $subform_state = SubformState::createForSubform($form['field_storage']['subform'], $form, $form_state);
      $field_storage_form = $this->entityTypeManager->getFormObject('field_storage_config', $this->operation);
      $field_storage_form->setEntity($entity->getFieldStorageDefinition());

      $reflector = new \ReflectionObject($entity);

      // Update the field storage entity based on subform values.
      $property = $reflector->getProperty('fieldStorage');
      $property->setValue($entity, $field_storage_form->buildEntity($form['field_storage']['subform'], $subform_state));

      // Remove the item definition to make sure it's not storing stale data.
      $property = $reflector->getProperty('itemDefinition');
      $property->setValue($entity, NULL);
    }
  }

  /**
   * A function to check if element contains any required elements.
   *
   * @param array $element
   *   An element to check.
   *
   * @return bool
   */
  private function hasAnyRequired(array $element) {
    $has_required = FALSE;
    foreach (Element::children($element) as $child) {
      if (isset($element[$child]['#required']) && $element[$child]['#required']) {
        $has_required = TRUE;
        break;
      }
      if (Element::children($element[$child])) {
        return $this->hasAnyRequired($element[$child]);
      }
    }

    return $has_required;
  }

  /**
   * {@inheritdoc}
   */
  protected function actions(array $form, FormStateInterface $form_state) {
    $actions = parent::actions($form, $form_state);
    $actions['submit']['#value'] = $this->t('Save settings');

    if (!$this->entity->isNew()) {
      $target_entity_type = $this->entityTypeManager->getDefinition($this->entity->getTargetEntityTypeId());
      $route_parameters = [
        'field_config' => $this->entity->id(),
      ] + FieldUI::getRouteBundleParameter($target_entity_type, $this->entity->getTargetBundle());
      $url = new Url('entity.field_config.' . $target_entity_type->id() . '_field_delete_form', $route_parameters);

      if ($this->getRequest()->query->has('destination')) {
        $query = $url->getOption('query');
        $query['destination'] = $this->getRequest()->query->get('destination');
        $url->setOption('query', $query);
      }
      $actions['delete'] = [
        '#type' => 'link',
        '#title' => $this->t('Delete'),
        '#url' => $url,
        '#access' => $this->entity->access('delete'),
        '#attributes' => [
          'class' => ['button', 'button--danger'],
        ],
      ];
    }

    return $actions;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);

    $field_storage_form = $this->entityTypeManager->getFormObject('field_storage_config', $this->operation);
    $field_storage_form->setEntity($this->entity->getFieldStorageDefinition());
    $subform_state = SubformState::createForSubform($form['field_storage']['subform'], $form, $form_state, $field_storage_form);
    $field_storage_form->validateForm($form['field_storage']['subform'], $subform_state);

    // Make sure that the default value form is validated using the field
    // configuration that was just submitted.
    $field_config = $this->buildEntity($form, $form_state);
    if (isset($form['default_value']) && (!isset($form['set_default_value']) || $form_state->getValue('set_default_value'))) {
      $items = $this->getTypedData($field_config, $form['#entity']);
      $items->defaultValuesFormValidate($form['default_value'], $form, $form_state);
    }

    // The form is rendered based on the entity property, meaning that it must
    // be updated based on the latest form state even though it might be invalid
    // at this point.
    $this->entity = $this->buildEntity($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    $field_storage_form = $this->entityTypeManager->getFormObject('field_storage_config', $this->operation);
    $field_storage_form->setEntity($this->entity->getFieldStorageDefinition());
    $subform_state = SubformState::createForSubform($form['field_storage']['subform'], $form, $form_state, $field_storage_form);
    $field_storage_form->submitForm($form['field_storage']['subform'], $subform_state);
    try {
      $field_storage_form->save($form['field_storage']['subform'], $subform_state);
    }
    catch (EntityStorageException $exception) {
      $this->handleEntityStorageException($form_state, $exception);
      return;
    }

    // Handle the default value.
    $default_value = [];
    if (isset($form['default_value']) && (!isset($form['set_default_value']) || $form_state->getValue('set_default_value'))) {
      $items = $this->getTypedData($this->entity, $form['#entity']);
      $default_value = $items->defaultValuesFormSubmit($form['default_value'], $form, $form_state);
    }
    $this->entity->setDefaultValue($default_value);
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    // Save field config.
    try {
      try {
        $this->entity->save();
      }
      catch (EntityStorageException $exception) {
        $this->handleEntityStorageException($form_state, $exception);
        return;
      }

      if (isset($form_state->getStorage()['default_options'])) {
        $default_options = $form_state->getStorage()['default_options'];
        // Configure the default display modes.
        $this->entityTypeId = $this->entity->getTargetEntityTypeId();
        $this->bundle = $this->entity->getTargetBundle();
        $this->configureEntityFormDisplay($this->entity->getName(), $default_options['entity_form_display'] ?? []);
        $this->configureEntityViewDisplay($this->entity->getName(), $default_options['entity_view_display'] ?? []);
      }

      if ($this->entity->isNew()) {
        // Delete the temp store entry.
        $this->tempStore->delete($this->entity->getTargetEntityTypeId() . ':' . $this->entity->getName());
      }

      $this->messenger()
        ->addStatus($this->t('Saved %label configuration.', ['%label' => $this->entity->getLabel()]));

      $request = $this->getRequest();
      if (($destinations = $request->query->all('destinations')) && $next_destination = FieldUI::getNextDestination($destinations)) {
        $request->query->remove('destinations');
        $form_state->setRedirectUrl($next_destination);
      }
      else {
        $form_state->setRedirectUrl(FieldUI::getOverviewRouteInfo($this->entity->getTargetEntityTypeId(), $this->entity->getTargetBundle()));
      }
    }
    catch (\Exception $e) {
      $this->messenger()->addError(
        $this->t(
          'Attempt to update field %label failed: %message.',
          [
            '%label' => $this->entity->getLabel(),
            '%message' => $e->getMessage(),
          ]
        )
      );
    }
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
    return $field_config->label();
  }

  /**
   * Gets typed data object for the field.
   *
   * @param \Drupal\field\FieldConfigInterface $field_config
   *   The field configuration.
   * @param \Drupal\Core\Entity\FieldableEntityInterface $parent
   *   The parent entity that the field is attached to.
   *
   * @return \Drupal\Core\TypedData\TypedDataInterface
   */
  private function getTypedData(FieldConfigInterface $field_config, FieldableEntityInterface $parent): TypedDataInterface {
    // Make sure that typed data manager is re-generating the instance. This
    // important because we want the returned instance to match the current
    // state, which could be different from what has been stored in config.
    $this->typedDataManager->clearCachedDefinitions();

    $entity_adapter = EntityAdapter::createFromEntity($parent);
    return $this->typedDataManager->create($field_config, $field_config->getDefaultValue($parent), $field_config->getName(), $entity_adapter);
  }

  /**
   * Process handler for subform submit.
   */
  public static function processFieldStorageSubmit(array $element, FormStateInterface $form_state, &$complete_form) {
    // Limit validation errors to the field storage form while the field storage
    // form is being edited.
    $complete_form['#limit_validation_errors'] = [array_slice($element['#parents'], 0, -1)];
    return $element;
  }

  /**
   * Submit handler for subform submit.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public function fieldStorageSubmit(&$form, FormStateInterface $form_state) {
    // The default value widget needs to be regenerated.
    $form_storage = &$form_state->getStorage();
    unset($form_storage['default_value_widget']);
    $form_state->setRebuild();
  }

  /**
   * Add Ajax callback for all inputs.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   */
  private function addAjaxCallbacks(array &$form): void {
    if (isset($form['#type']) && !isset($form['#ajax'])) {
      if ($this->elementInfo->getInfoProperty($form['#type'], '#input') && !$this->elementInfo->getInfoProperty($form['#type'], '#is_button')) {
        $form['#ajax'] = [
          'trigger_as' => ['name' => 'field_storage_submit'],
          'wrapper' => 'field-combined',
          'event' => 'change',
        ];
      }
    }

    foreach (Element::children($form) as $child_key) {
      $this->addAjaxCallbacks($form[$child_key]);
    }
  }

  /**
   * Handles entity storage exceptions and redirects the form.
   *
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   * @param \Drupal\Core\Entity\EntityStorageException $exception
   *   The exception.
   */
  protected function handleEntityStorageException(FormStateInterface $form_state, EntityStorageException $exception): void {
    $this->tempStore->delete($this->entity->getTargetEntityTypeId() . ':' . $this->entity->getName());
    $form_state->setRedirectUrl(FieldUI::getOverviewRouteInfo($this->entity->getTargetEntityTypeId(),
      $this->entity->getTargetBundle()));
    $this->messenger()
      ->addError($this->t('An error occurred while saving the field: @error',
        ['@error' => $exception->getMessage()]));
  }

}
