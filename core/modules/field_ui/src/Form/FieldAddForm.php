<?php

namespace Drupal\field_ui\Form;

use Drupal\Core\Ajax\AjaxFormHelperTrait;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\OpenModalDialogCommand;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityDisplayRepositoryInterface;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Field\FieldTypePluginManagerInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\field_ui\FieldUI;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a form for the "field storage" add page.
 *
 * @internal
 */
class FieldAddForm extends FormBase {

  /**
   * The name of the entity type.
   *
   * @var string
   */
  protected $entityTypeId;

  /**
   * The entity bundle.
   *
   * @var string
   */
  protected $bundle;

  /**
   * The type of field to add.
   *
   * @var string
   */
  protected $fieldType;

  /**
   * The type of field to add.
   *
   * @var string
   */
  protected $category;

  /**
   * @var \Drupal\field\FieldConfigInterface
   */
  protected $field;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The entity field manager.
   *
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  protected $entityFieldManager;

  /**
   * The entity display repository.
   *
   * @var \Drupal\Core\Entity\EntityDisplayRepositoryInterface
   */
  protected $entityDisplayRepository;

  /**
   * The field type plugin manager.
   *
   * @var \Drupal\Core\Field\FieldTypePluginManagerInterface
   */
  protected $fieldTypePluginManager;

  /**
   * The configuration factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Constructs a new FieldStorageAddForm object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Field\FieldTypePluginManagerInterface $field_type_plugin_manager
   *   The field type plugin manager.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The configuration factory.
   * @param \Drupal\Core\Entity\EntityFieldManagerInterface|null $entity_field_manager
   *   (optional) The entity field manager.
   * @param \Drupal\Core\Entity\EntityDisplayRepositoryInterface $entity_display_repository
   *   (optional) The entity display repository.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, FieldTypePluginManagerInterface $field_type_plugin_manager, ConfigFactoryInterface $config_factory, EntityFieldManagerInterface $entity_field_manager = NULL, EntityDisplayRepositoryInterface $entity_display_repository = NULL) {
    $this->entityTypeManager = $entity_type_manager;
    $this->fieldTypePluginManager = $field_type_plugin_manager;
    $this->configFactory = $config_factory;
    $this->entityFieldManager = $entity_field_manager;
    $this->entityDisplayRepository = $entity_display_repository;
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'field_ui_field_add_form';
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('plugin.manager.field.field_type'),
      $container->get('config.factory'),
      $container->get('entity_field.manager'),
      $container->get('entity_display.repository')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $entity_type_id = NULL, $bundle = NULL, $field_type = '') {
    $field_type_plugin_manager = \Drupal::service('plugin.manager.field.field_type');
    $options = [];
    foreach ($field_type_plugin_manager->getGroupedDefinitions($field_type_plugin_manager->getUiDefinitions())[$field_type] as $option) {
      $options[$option['id']] = $option['label'];
    }
    if (!$form_state->get('entity_type_id')) {
      $form_state->set('entity_type_id', $entity_type_id);
    }
    if (!$form_state->get('bundle')) {
      $form_state->set('bundle', $bundle);
    }

    $form['#prefix'] = '<div id="field-ui-add-form">';
    $form['#suffix'] = '</div>';

    $form['status_messages'] = [
      '#type' => 'status_messages',
      '#weight' => -100,
    ];

    $this->entityTypeId = $form_state->get('entity_type_id');
    $this->bundle = $form_state->get('bundle');
    $this->fieldType = $field_type;

    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#size' => 25,
    ];
    $form['options'] = [
      '#type' => 'radios',
      '#title' => $this->t('Select type'),
      '#size' => 25,
      '#options' => $options,
    ];
    $field_prefix = $this->config('field_ui.settings')->get('field_prefix');
    $form['field_name'] = [
      '#type' => 'machine_name',
      '#field_prefix' => $field_prefix,
      '#size' => 15,
      '#description' => $this->t('A unique machine-readable name containing letters, numbers, and underscores.'),
      // Calculate characters depending on the length of the field prefix
      // setting. Maximum length is 32.
      '#maxlength' => FieldStorageConfig::NAME_MAX_LENGTH - strlen($field_prefix),
      '#machine_name' => [
        'source' => ['label'],
        'exists' => [$this, 'fieldNameExists'],
      ],
      '#required' => FALSE,
    ];

//    // Gather valid field types.
//    $field_type_options = [];
//    foreach ($this->fieldTypePluginManager->getGroupedDefinitions($this->fieldTypePluginManager->getUiDefinitions()) as $category => $field_types) {
//      foreach ($field_types as $name => $field_type) {
//        $field_type_options[$category][$name] = $field_type['label'];
//      }
//    }

//
//    $form['tabs'] = [
//      '#theme' => 'field_ui_tabs',
//      '#items' => [
//        [
//          'value' => [
//            '#type' => 'link',
//            '#title' => $this->t('Basic settings'),
//            '#url' =>  Url::fromUri('internal://<none>#basic'),
//            '#attributes' => [
//              'class' => [
//                'tabs__link',
//                'js-tabs-link',
//                'is-active',
//              ],
//            ],
//          ],
//        ],
//        [
//          'value' => [
//            '#type' => 'link',
//            '#title' => $this->t('Advanced settings'),
//            '#url' =>  Url::fromUri('internal://<none>#advanced'),
//            '#attributes' => [
//              'class' => [
//                'tabs__link',
//                'js-tabs-link',
//              ],
//            ],
//          ],
//        ],
//      ],
//    ];
//    $form['basic'] = [
//      '#type' => 'container',
//      '#attributes' => [
//        'id' => ['basic']
//      ],
//      '#title' => $this->t('Basic settings'),
//    ];
//    $form['advanced'] = [
//      '#type' => 'container',
//      '#attributes' => [
//        'id' => ['advanced']
//      ],
//      '#title' => $this->t('Advanced settings'),
//    ];
//
//    $form['basic']['label'] = [
//      '#type' => 'textfield',
//      '#title' => $this->t('Label'),
//      '#size' => 25,
//    ];
//    $field_prefix = $this->config('field_ui.settings')->get('field_prefix');
//    $form['basic']['field_name'] = [
//      '#type' => 'machine_name',
//      '#field_prefix' => $field_prefix,
//      '#size' => 15,
//      '#description' => $this->t('A unique machine-readable name containing letters, numbers, and underscores.'),
//      // Calculate characters depending on the length of the field prefix
//      // setting. Maximum length is 32.
//      '#maxlength' => FieldStorageConfig::NAME_MAX_LENGTH - strlen($field_prefix),
//      '#machine_name' => [
//        'source' => ['basic', 'label'],
//        'exists' => [$this, 'fieldNameExists'],
//      ],
//      '#required' => FALSE,
//    ];
//
//    if (str_contains($this->fieldType, 'field_ui:')) {
//      [, $field_type, $option_key] = explode(':', $this->fieldType, 3);
//      // @todo Add support for pre-configured options.
//    }
//
//    $form['basic']['cardinality'] = [
//      '#type' => 'checkbox',
//      '#title' => $this->t('Allow multiple values'),
//    ];
//    $form['basic']['cardinality_unlimited'] = [
//      '#type' => 'checkbox',
//      '#title' => $this->t('Allow unlimited values'),
//      '#states' => [
//        'visible' => [
//          ':input[name="cardinality"]' => ['checked' => TRUE],
//        ],
//      ],
//    ];
//    $form['basic']['cardinality_number'] = [
//      '#type' => 'number',
//      '#min' => 2,
//      '#title' => $this->t('Limit'),
//      '#default_value' => '2',
//      '#size' => 2,
//      '#states' => [
//        'visible' => [
//          ':input[name="cardinality"]' => ['checked' => TRUE],
//        ],
//        'disabled' => [
//          ':input[name="cardinality_unlimited"]' => ['checked' => TRUE],
//        ]
//      ],
//    ];
//
//    // Create a run-time plugin instance for the field item to render the
//    // storage settings form.
//    $data_definition = FieldItemDataDefinition::createFromDataType('field_item:' . $field_type);
//    $ids = (object) [
//      'entity_type' => $this->entityTypeId,
//      'bundle' => $this->bundle,
//      'entity_id' => NULL,
//    ];
//    $entity_adapter = EntityAdapter::createFromEntity(_field_create_entity_from_ids($ids));
//    $plugin_class = $this->fieldTypePluginManager->getPluginClass($field_type);
//    /** @var \Drupal\Core\Field\FieldItemInterface $plugin_instance */
//    $plugin_instance = new $plugin_class($data_definition);
//    $plugin_instance->setContext(NULL, $entity_adapter);
//
//    $form['basic']['field_storage_settings'] = [
//      '#tree' => TRUE,
//    ];
//    $form['basic']['field_storage_settings'] += $plugin_instance->storageSettingsForm($form, $form_state, FALSE);
//    foreach (Element::children($form['basic']['field_storage_settings']) as $child) {
//      if (isset($form['basic']['field_storage_settings'][$child]['#group'])) {
//        $form['basic']['field_storage_settings'][$child]['#parents'] = ['field_storage_settings', $child];
//        $form['advanced'][$child] = $form['basic']['field_storage_settings'][$child];
//        unset($form['basic']['field_storage_settings'][$child]);
//      }
//    }
//
//    $form['basic']['field_settings'] = [
//      '#tree' => TRUE,
//    ];
//    $form['basic']['field_settings'] += $plugin_instance->fieldSettingsForm($form, $form_state);
//    foreach (Element::children($form['basic']['field_settings']) as $child) {
//      if (isset($form['basic']['field_settings'][$child]['#group'])) {
//        $form['basic']['field_settings'][$child]['#parents'] = ['field_settings', $child];
//        $form['advanced'][$child] = $form['basic']['field_settings'][$child];
//        unset($form['basic']['field_settings'][$child]);
//      }
//    }
//
//
//    $form['basic']['required'] = [
//      '#type' => 'checkbox',
//      '#title' => $this->t('Required field'),
//    ];
//
    $form['translatable'] = [
      '#type' => 'value',
      '#value' => TRUE,
    ];

//    $form['advanced']['description'] = [
//      '#type' => 'textarea',
//      '#title' => $this->t('Help text'),
//      '#rows' => 3,
//      '#attributes' => [
//        'style' => ['max-width: 50%']
//      ],
//      '#description' => $this->t('Instructions to present to the user below this field on the editing form.<br />Allowed HTML tags: @tags', ['@tags' => FieldFilteredMarkup::displayAllowedTags()]) . '<br />' . $this->t('This field supports tokens.'),
//    ];

    // @todo configuring the default value depends on the field having
    // initialized.
//    $item_list_class = $this->fieldTypePluginManager->getDefinition($field_type)['list_class'];
//    $item_list_instance = new $item_list_class($plugin_instance->getFieldDefinition());
//    $item_list_instance->setContext(NULL, $entity_adapter);
//    if ($element = $item_list_instance->defaultValuesForm($form, $form_state)) {
//      $element = array_merge($element, [
//        '#type' => 'details',
//        '#title' => $this->t('Default value'),
//        '#open' => TRUE,
//        '#tree' => TRUE,
//        '#description' => $this->t('The default value for this field, used when creating new content.'),
//        '#weight' => 12,
//      ]);
//
//      $form['default_value'] = $element;
//    }


    $form['actions'] = ['#type' => 'actions'];
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Save and configure'),
      '#button_type' => 'primary',
      '#ajax' => [
        'callback' => [$this, 'ajaxSubmitForm'],
      ]
    ];

    $form['#attached']['library'][] = 'field_ui/drupal.field_ui';

    return $form;
  }

  public function ajaxSubmitForm(array &$form, FormStateInterface $form_state) {
    $response = new AjaxResponse();
    if ($form_state::hasAnyErrors() || !$this->field) {
      $response->addCommand(new ReplaceCommand('#field-ui-add-form', $form));
      return $response;
    }

    /** @var \Drupal\Core\Entity\EntityFormBuilderInterface $entity_form_builder */
    $entity_form_builder = \Drupal::service('entity.form_builder');
    $next_form = $entity_form_builder->getForm($this->field, 'edit');

    $dialog_options = [
      'width' => '85vw',
    ];
    $response->addCommand(new OpenModalDialogCommand($this->t('Configure field'), $next_form, $dialog_options));

    return $response;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    // Missing label.
    if (!$form_state->getValue('label')) {
      $form_state->setErrorByName('label', $this->t('Add new field: you need to provide a label.'));
    }

    // Missing field name.
    if (!$form_state->getValue('field_name')) {
      $form_state->setErrorByName('field_name', $this->t('Add new field: you need to provide a machine name for the field.'));
    }
    // Field name validation.
    else {
      $field_name = $form_state->getValue('field_name');

      // Add the field prefix.
      $field_name = $this->configFactory->get('field_ui.settings')->get('field_prefix') . $field_name;
      $form_state->setValueForElement($form['field_name'], $field_name);
    }
  }



  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $error = FALSE;
    $values = $form_state->getValues();
    $destinations = [];
    $entity_type = $this->entityTypeManager->getDefinition($this->entityTypeId);

    // Create new field.
    $field_storage_values = [
      'field_name' => $values['field_name'],
      'entity_type' => $this->entityTypeId,
//      'type' => $this->fieldType,
      'type' => $values['options'],
      'translatable' => $values['translatable'],
//      'settings' => $values['field_storage_settings'],
//      'cardinality' => !$values['cardinality'] ? 1 : ($values['cardinality_unlimited'] ? FieldStorageConfigInterface::CARDINALITY_UNLIMITED : $values['cardinality_number']),
    ];
    $field_values = [
      'field_name' => $values['field_name'],
      'entity_type' => $this->entityTypeId,
      'bundle' => $this->bundle,
//      'required' => $values['required'],
      'label' => $values['label'],
      // Field translatability should be explicitly enabled by the users.
      'translatable' => FALSE,
//      'settings' => $values['field_settings']
    ];
    $widget_id = $formatter_id = NULL;
    $widget_settings = $formatter_settings = [];

//    // Check if we're dealing with a preconfigured field.
//    if (strpos($field_storage_values['type'], 'field_ui:') !== FALSE) {
//      [, $field_type, $option_key] = explode(':', $field_storage_values['type'], 3);
//      $field_storage_values['type'] = $field_type;
//
//      $field_definition = $this->fieldTypePluginManager->getDefinition($field_type);
//      $options = $this->fieldTypePluginManager->getPreconfiguredOptions($field_definition['id']);
//      $field_options = $options[$option_key];
//
//      // Merge in preconfigured field storage options.
//      if (isset($field_options['field_storage_config'])) {
//        foreach (['cardinality', 'settings'] as $key) {
//          if (isset($field_options['field_storage_config'][$key])) {
//            $field_storage_values[$key] = $field_options['field_storage_config'][$key];
//          }
//        }
//      }
//
//      // Merge in preconfigured field options.
//      if (isset($field_options['field_config'])) {
//        foreach (['required', 'settings'] as $key) {
//          if (isset($field_options['field_config'][$key])) {
//            $field_values[$key] = $field_options['field_config'][$key];
//          }
//        }
//      }
//
//      $widget_id = $field_options['entity_form_display']['type'] ?? NULL;
//      $widget_settings = $field_options['entity_form_display']['settings'] ?? [];
//      $formatter_id = $field_options['entity_view_display']['type'] ?? NULL;
//      $formatter_settings = $field_options['entity_view_display']['settings'] ?? [];
//    }

    // Create the field storage and field.
    try {
      $this->entityTypeManager->getStorage('field_storage_config')->create($field_storage_values)->save();
      $field = $this->entityTypeManager->getStorage('field_config')->create($field_values);
      $field->save();
      $this->field = $field;

      $this->configureEntityFormDisplay($values['field_name'], $widget_id, $widget_settings);
      $this->configureEntityViewDisplay($values['field_name'], $formatter_id, $formatter_settings);

      // Always show the field settings step, as the cardinality needs to be
      // configured for new fields.
      $route_parameters = [
        'field_config' => $field->id(),
      ] + FieldUI::getRouteBundleParameter($entity_type, $this->bundle);
      $destinations[] = ['route_name' => "entity.field_config.{$this->entityTypeId}_storage_edit_form", 'route_parameters' => $route_parameters];
      $destinations[] = ['route_name' => "entity.field_config.{$this->entityTypeId}_field_edit_form", 'route_parameters' => $route_parameters];
      $destinations[] = ['route_name' => "entity.{$this->entityTypeId}.field_ui_fields", 'route_parameters' => $route_parameters];

      // Store new field information for any additional submit handlers.
      $form_state->set(['fields_added', '_add_new_field'], $values['field_name']);
    }
    catch (\Exception $e) {
      $error = TRUE;
      $this->messenger()->addError($this->t('There was a problem creating field %label: @message', ['%label' => $values['label'], '@message' => $e->getMessage()]));
    }

    if ($destinations) {
      $destination = $this->getDestinationArray();
      $destinations[] = $destination['destination'];
      $form_state->setRedirectUrl(FieldUI::getNextDestination($destinations));
    }
    elseif (!$error) {
      $this->messenger()->addStatus($this->t('Your settings have been saved.'));
    }
  }

  /**
   * Configures the field for the default form mode.
   *
   * @param string $field_name
   *   The field name.
   * @param string|null $widget_id
   *   (optional) The plugin ID of the widget. Defaults to NULL.
   * @param array $widget_settings
   *   (optional) An array of widget settings. Defaults to an empty array.
   */
  protected function configureEntityFormDisplay($field_name, $widget_id = NULL, array $widget_settings = []) {
    $options = [];
    if ($widget_id) {
      $options['type'] = $widget_id;
      if (!empty($widget_settings)) {
        $options['settings'] = $widget_settings;
      }
    }
    // Make sure the field is displayed in the 'default' form mode (using
    // default widget and settings). It stays hidden for other form modes
    // until it is explicitly configured.
    $this->entityDisplayRepository->getFormDisplay($this->entityTypeId, $this->bundle, 'default')
      ->setComponent($field_name, $options)
      ->save();
  }

  /**
   * Configures the field for the default view mode.
   *
   * @param string $field_name
   *   The field name.
   * @param string|null $formatter_id
   *   (optional) The plugin ID of the formatter. Defaults to NULL.
   * @param array $formatter_settings
   *   (optional) An array of formatter settings. Defaults to an empty array.
   */
  protected function configureEntityViewDisplay($field_name, $formatter_id = NULL, array $formatter_settings = []) {
    $options = [];
    if ($formatter_id) {
      $options['type'] = $formatter_id;
      if (!empty($formatter_settings)) {
        $options['settings'] = $formatter_settings;
      }
    }
    // Make sure the field is displayed in the 'default' view mode (using
    // default formatter and settings). It stays hidden for other view
    // modes until it is explicitly configured.
    $this->entityDisplayRepository->getViewDisplay($this->entityTypeId, $this->bundle)
      ->setComponent($field_name, $options)
      ->save();
  }

  /**
   * Checks if a field machine name is taken.
   *
   * @param string $value
   *   The machine name, not prefixed.
   * @param array $element
   *   An array containing the structure of the 'field_name' element.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return bool
   *   Whether or not the field machine name is taken.
   */
  public function fieldNameExists($value, $element, FormStateInterface $form_state) {
    // Don't validate the case when an existing field has been selected.
    if ($form_state->getValue('existing_storage_name')) {
      return FALSE;
    }

    // Add the field prefix.
    $field_name = $this->configFactory->get('field_ui.settings')->get('field_prefix') . $value;

    $field_storage_definitions = $this->entityFieldManager->getFieldStorageDefinitions($this->entityTypeId);
    return isset($field_storage_definitions[$field_name]);
  }

}
