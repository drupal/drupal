<?php

namespace Drupal\field_ui\Form;

use Drupal\Component\Serialization\Json;
use Drupal\Component\Utility\Html;
use Drupal\Component\Utility\SortArray;
use Drupal\Core\Ajax\AjaxHelperTrait;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\OpenModalDialogWithUrl;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Field\FieldTypeCategoryManagerInterface;
use Drupal\Core\Field\FieldTypePluginManagerInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\TempStore\PrivateTempStore;
use Drupal\Core\Url;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\field_ui\FieldUI;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a form for the "field storage" add subform.
 *
 * @internal
 */
class FieldStorageAddForm extends FormBase {

  use AjaxHelperTrait;

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

  public function __construct(protected EntityTypeManagerInterface $entityTypeManager, protected FieldTypePluginManagerInterface $fieldTypePluginManager, ConfigFactoryInterface $configFactory, protected EntityFieldManagerInterface $entityFieldManager, protected PrivateTempStore $tempStore, protected FieldTypeCategoryManagerInterface $fieldTypeCategoryManager) {
    $this->setConfigFactory($configFactory);
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
      $container->get('tempstore.private')->get('field_ui'),
      $container->get('plugin.manager.field.field_type_category'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'field_ui_field_storage_add_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $entity_type_id = NULL, $bundle = NULL, $selected_field_type = NULL, $display_as_group = 'false') {
    $display_as_group = str_contains($display_as_group, 'true');
    if (!$form_state->get('entity_type_id')) {
      $form_state->set('entity_type_id', $entity_type_id);
    }
    if (!$form_state->get('bundle')) {
      $form_state->set('bundle', $bundle);
    }
    if (!$form_state->get('field_type')) {
      $form_state->set('field_type', $selected_field_type);
    }
    if (!$form_state->get('display_as_group')) {
      $form_state->set('display_as_group', $display_as_group);
    }
    $this->entityTypeId = $form_state->get('entity_type_id');
    $this->bundle = $form_state->get('bundle');

    $unique_definitions = [];
    $grouped_definitions = $this->fieldTypePluginManager
      ->getGroupedDefinitions($this->fieldTypePluginManager->getEntityTypeUiDefinitions($this->entityTypeId), 'label', 'id');
    if (array_key_exists($selected_field_type, $grouped_definitions)) {
      $field_types = $grouped_definitions[$selected_field_type];
      foreach ($field_types as $name => $field_type) {
        $unique_definitions[$selected_field_type][$name] = ['unique_identifier' => $name] + $field_type;
      }
    }
    $entity_type = $this->entityTypeManager->getDefinition($this->entityTypeId);
    $route_parameters_back = [] + FieldUI::getRouteBundleParameter($entity_type, $this->bundle);

    $form['actions'] = ['#type' => 'actions'];

    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#size' => 30,
      '#required' => TRUE,
      '#maxlength' => 255,
      '#weight' => -20,
    ];

    $field_prefix = $this->configFactory->get('field_ui.settings')->get('field_prefix');
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
      '#required' => TRUE,
    ];

    $form['field_options_wrapper'] = [
      '#prefix' => '<div class="field-options-wrapper">',
      '#suffix' => '</div>',
    ];

    // Set the selected field to the form state by checking
    // the checked attribute.
    if (isset($selected_field_type)) {
      if ($display_as_group) {
        $form['field_options_wrapper']['label'] = [
          '#type' => 'label',
          '#title' => $this->t('Choose a field type'),
          '#required' => TRUE,
        ];
        $form['field_options_wrapper']['fields'] = [
          '#type' => 'container',
          '#attributes' => [
            'class' => ['group-field-options'],
          ],
        ];
        foreach ($unique_definitions[$selected_field_type] as $option_key => $option) {
          $description = !is_array($option['description']) ? $option['description'] : [
            '#theme' => 'item_list',
            '#items' => $option['description'],
          ];
          $radio_element = [
            '#type' => 'radio',
            '#theme_wrappers' => ['form_element__new_storage_type'],
            '#title' => $option['label'],
            '#description' => $description,
            '#id' => Html::getClass($option['unique_identifier']),
            '#weight' => $option['weight'],
            '#parents' => ['field_options_wrapper'],
            '#attributes' => [
              'class' => ['field-option-radio'],
              'data-once' => 'field-click-to-select',
              'checked' => $this->getRequest()->request->get('field_options_wrapper') !== NULL && $this->getRequest()->request->get('field_options_wrapper') == $option_key,
            ],
            '#wrapper_attributes' => [
              'class' => ['js-click-to-select', 'subfield-option'],
            ],
            '#variant' => 'field-suboption',
          ];
          $radio_element['#return_value'] = $option['unique_identifier'];
          if ((string) $option['unique_identifier'] === 'entity_reference') {
            $radio_element['#title'] = 'Other';
            $radio_element['#weight'] = 10;
          }
          $group_field_options[$option['unique_identifier']] = $radio_element;
        }
        uasort($group_field_options, [SortArray::class, 'sortByWeightProperty']);
        $form['field_options_wrapper']['fields'] += $group_field_options;
      }

      $form['actions']['previous'] = [
        '#type' => 'link',
        '#title' => $this->t('Change field type'),
        '#url' => Url::fromRoute("field_ui.field_storage_config_add_$entity_type_id", $route_parameters_back),
        '#attributes' => [
          'class' => ['button', 'use-ajax'],
          'data-dialog-type' => 'modal',
          'data-dialog-options' => Json::encode([
            'width' => '1100',
          ]),
        ],
      ];

      $form['actions']['submit'] = [
        '#type' => 'submit',
        '#value' => $this->t('Continue'),
        '#submit' => ['::submitForm'],
        '#attributes' => [
          'class' => ['button', 'button--primary'],
          'data-dialog-type' => 'modal',
          'data-dialog-options' => Json::encode([
            'width' => '1100',
          ]),
        ],
      ];
      if ($this->isAjax()) {
        $form['actions']['submit']['#ajax']['callback'] = '::ajaxSubmit';
      }
    }

    // Place the 'translatable' property as an explicit value so that contrib
    // modules can form_alter() the value for newly created fields. By default,
    // we create field storage as translatable, so it will be possible to enable
    // translation at field level.
    $form['translatable'] = [
      '#type' => 'value',
      '#value' => TRUE,
    ];

    $form['#prefix'] = '<div id="field-storage-subfield">';
    $form['#suffix'] = '</div>';

    $form['#attached']['library'] = [
      'field_ui/drupal.field_ui',
      'field_ui/drupal.field_ui.manage_fields',
      'core/drupal.dialog.ajax',
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    // Missing subtype.
    if (!$form_state->getValue('field_options_wrapper') && isset($form['field_options_wrapper']['fields'])) {
      $form_state->setErrorByName('field_options_wrapper', $this->t('You need to select a field type.'));
    }
    // Additional validation to work when JS is disabled.
    if (!$form_state->getValue('label')) {
      $form_state->setErrorByName('label', $this->t('Label field is required.'));
    }
    if (!$form_state->getValue('field_name')) {
      $form_state->setErrorByName('label', $this->t('Machine-readable name field is required.'));
    }
    // Field name validation.
    else {
      $field_name = $form_state->getValue('field_name');

      // Add the field prefix.
      $field_name = $this->config('field_ui.settings')->get('field_prefix') . $field_name;
      $form_state->setValueForElement($form['field_name'], $field_name);
      // Set the temp store here, so we can actually see the error on the modal.
      $field_storage_type = $form_state->getValue('field_options_wrapper') ?? $form_state->get('field_type');
      $this->setTempStore($this->entityTypeId, $field_storage_type, $this->bundle, $form_state->getValue('label'), $form_state->getValue('field_name'), $form_state->getValue('translatable'));
      if (!empty($this->messenger()->messagesByType('error'))) {
        $form_state->setErrorByName('drupal-modal', $this->t('There was a problem creating field @label: @message', ['@label' => $form_state->getValue('label'), '@message' => explode(':', $this->messenger()->messagesByType('error')[0])[1]]));
        // We need to clear out the messenger so that we just see the message
        // on the modal and not on the page when it closes.
        $this->messenger()->deleteAll();
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $form_state->setRedirectUrl($this->getRedirectUrl($form_state->getValue('field_name')));
  }

  /**
   * Gets the redirect URL.
   *
   * @param string $field_name
   *   The field name.
   *
   * @return \Drupal\Core\Url
   *   The URL to redirect to.
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  private function getRedirectUrl(string $field_name): Url {
    $route_parameters = [
      'field_name' => $field_name,
      'entity_type' => $this->entityTypeId,
    ] + FieldUI::getRouteBundleParameter($this->entityTypeManager->getDefinition($this->entityTypeId), $this->bundle);
    return Url::fromRoute("field_ui.field_add_{$this->entityTypeId}", $route_parameters);
  }

  /**
   * Submit form #ajax callback.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   *   An AJAX response that display validation error messages or represents a
   *   successful submission.
   *
   * @see \Drupal\Core\Ajax\AjaxFormHelperTrait
   */
  public function ajaxSubmit(array &$form, FormStateInterface $form_state): AjaxResponse {
    if ($form_state->hasAnyErrors()) {
      $form['status_messages'] = [
        '#type' => 'status_messages',
        '#weight' => -1000,
      ];
      $form['#sorted'] = FALSE;
      $response = new AjaxResponse();
      $response->addCommand(new ReplaceCommand('#field-storage-subfield', $form));
    }
    else {
      $response = $this->successfulAjaxSubmit($form, $form_state);
    }
    return $response;
  }

  /**
   * Respond to a successful AJAX submission.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   *   An AJAX response.
   */
  protected function successfulAjaxSubmit(array $form, FormStateInterface $form_state): AjaxResponse {
    $response = new AjaxResponse();
    $response->addCommand(new OpenModalDialogWithUrl($this->getRedirectUrl($form_state->getValue('field_name'))->toString(), []));
    return $response;
  }

  /**
   * Get default options from preconfigured options for a new field.
   *
   * @param string $field_name
   *   The machine name of the field.
   * @param string $preset_key
   *   A key in the preconfigured options array for the field.
   *
   * @return array
   *   An array of settings with keys 'field_storage_config', 'field_config',
   *   'entity_form_display', and 'entity_view_display'.
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   *
   * @see \Drupal\Core\Field\PreconfiguredFieldUiOptionsInterface::getPreconfiguredOptions()
   */
  protected function getNewFieldDefaults(string $field_name, string $preset_key): array {
    $field_type_definition = $this->fieldTypePluginManager->getDefinition($field_name);
    $options = $this->fieldTypePluginManager->getPreconfiguredOptions($field_type_definition['id']);
    $field_options = $options[$preset_key] ?? [];

    $default_options = [];
    // Merge in preconfigured field storage options.
    if (isset($field_options['field_storage_config'])) {
      foreach (['cardinality', 'settings'] as $key) {
        if (isset($field_options['field_storage_config'][$key])) {
          $default_options['field_storage_config'][$key] = $field_options['field_storage_config'][$key];
        }
      }
    }

    // Merge in preconfigured field options.
    if (isset($field_options['field_config'])) {
      foreach (['required', 'settings'] as $key) {
        if (isset($field_options['field_config'][$key])) {
          $default_options['field_config'][$key] = $field_options['field_config'][$key];
        }
      }
    }

    // Preconfigured options only apply to the default display modes.
    foreach (['entity_form_display', 'entity_view_display'] as $key) {
      if (isset($field_options[$key])) {
        $default_options[$key] = [
          'default' => array_intersect_key($field_options[$key], ['type' => '', 'settings' => []]),
        ];
      }
      else {
        $default_options[$key] = ['default' => []];
      }
    }

    return $default_options;
  }

  /**
   * Store field information in temp store in order to build the edit form.
   *
   * @param string $entity_type
   *   The name of the entity type.
   * @param string $field_storage_type
   *   The machine name of the field storage.
   * @param string $bundle
   *   The entity bundle.
   * @param string $field_label
   *   The label of the field.
   * @param string $field_machine_name
   *   The machine name of the field.
   * @param bool $translatable
   *   TRUE if the field is translatable.
   */
  public function setTempStore(string $entity_type, string $field_storage_type, string $bundle, string $field_label, string $field_machine_name, bool $translatable): void {
    $field_values = [
      'entity_type' => $entity_type,
      'bundle' => $bundle,
    ];
    $default_options = [];
    // Check if we're dealing with a preconfigured field.
    if (strpos($field_storage_type, 'field_ui:') === 0) {
      [, $field_type, $preset_key] = explode(':', $field_storage_type, 3);
      $default_options = $this->getNewFieldDefaults($field_type, $preset_key);
    }
    else {
      $field_type = $field_storage_type;
    }
    $field_values += [
      ...$default_options['field_config'] ?? [],
      'field_name' => $field_machine_name,
      'label' => $field_label,
      // Field translatability should be explicitly enabled by the users.
      'translatable' => FALSE,
    ];

    $field_storage_values = [
      ...$default_options['field_storage_config'] ?? [],
      'field_name' => $field_machine_name,
      'type' => $field_type,
      'entity_type' => $entity_type,
      'translatable' => $translatable,
    ];
    try {
      $field_storage_entity = $this->entityTypeManager->getStorage('field_storage_config')->create($field_storage_values);
    }
    catch (\Exception $e) {
      $this->messenger()->addError($this->t('There was a problem creating field :@message', ['@message' => $e->getMessage()]));
      return;
    }

    // Save field and field storage values in tempstore.
    $this->tempStore->set($entity_type . ':' . $field_machine_name, [
      'field_storage' => $field_storage_entity,
      'field_config_values' => $field_values,
      'default_options' => $default_options,
    ]);
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
    // Add the field prefix.
    $field_name = $this->configFactory->get('field_ui.settings')->get('field_prefix') . $value;

    $field_storage_definitions = $this->entityFieldManager->getFieldStorageDefinitions($this->entityTypeId);
    return isset($field_storage_definitions[$field_name]);
  }

  /**
   * Submit handler for displaying fields after a group is selected.
   */
  public static function rebuildWithOptions($form, FormStateInterface &$form_state) {
    $form_state->setRebuild();
  }

  /**
   * Submit handler for resetting the form.
   */
  public static function startOver($form, FormStateInterface &$form_state) {
    $form_state->unsetValue('new_storage_type');
    $form_state->setRebuild();
  }

}
