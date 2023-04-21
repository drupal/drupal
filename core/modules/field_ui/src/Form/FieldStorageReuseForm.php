<?php

namespace Drupal\field_ui\Form;

use Drupal\Core\Entity\EntityDisplayRepositoryInterface;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Field\FieldTypePluginManagerInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\field\FieldStorageConfigInterface;
use Drupal\field_ui\FieldUI;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\StringTranslation\PluralTranslatableMarkup;

/**
 * Provides a form for the "field storage" add page.
 *
 * @internal
 */
class FieldStorageReuseForm extends FormBase {

  use FieldStorageCreationTrait;

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
   * Constructs a new FieldStorageReuseForm object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager.
   * @param \Drupal\Core\Field\FieldTypePluginManagerInterface $fieldTypePluginManager
   *   The field type plugin manager.
   * @param \Drupal\Core\Entity\EntityFieldManagerInterface $entityFieldManager
   *   The entity field manager.
   * @param \Drupal\Core\Entity\EntityDisplayRepositoryInterface $entityDisplayRepository
   *   The entity display repository.
   * @param \Drupal\Core\Entity\EntityTypeBundleInfoInterface $bundleInfoService
   *   The bundle info service.
   */
  public function __construct(
    protected EntityTypeManagerInterface $entityTypeManager,
    protected FieldTypePluginManagerInterface $fieldTypePluginManager,
    protected EntityFieldManagerInterface $entityFieldManager,
    protected EntityDisplayRepositoryInterface $entityDisplayRepository,
    protected EntityTypeBundleInfoInterface $bundleInfoService
  ) {}

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'field_ui_field_storage_reuse_form';
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('plugin.manager.field.field_type'),
      $container->get('entity_field.manager'),
      $container->get('entity_display.repository'),
      $container->get('entity_type.bundle.info')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $entity_type_id = NULL, $bundle = NULL) {
    if (!$form_state->get('entity_type_id')) {
      $form_state->set('entity_type_id', $entity_type_id);
    }
    if (!$form_state->get('bundle')) {
      $form_state->set('bundle', $bundle);
    }

    $this->entityTypeId = $form_state->get('entity_type_id');
    $this->bundle = $form_state->get('bundle');

    $form['text'] = [
      '#plain_text' => $this->t("You can re-use a field from other sub-types of the same entity type. Re-using a field creates another usage of the same field storage."),
    ];

    $form['search'] = [
      '#type' => 'search',
      '#title' => $this->t('Filter by field or field type'),
      '#attributes' => [
        'class' => ['js-table-filter-text'],
        'data-table' => '.js-reuse-table',
        'autocomplete' => 'off',
      ],
    ];
    $form['add'] = [
      '#type' => 'container',
      '#attributes' => ['class' => ['form--inline', 'clearfix']],
    ];

    $bundles = $this->bundleInfoService->getAllBundleInfo();
    $existing_field_storage_options = $this->getExistingFieldStorageOptions();

    $rows = [];
    foreach ($existing_field_storage_options as $field) {
      $field_bundles = $field['field_storage']->getBundles();
      $summary = $this->fieldTypePluginManager->getStorageSettingsSummary($field['field_storage']);
      $cardinality = $field['field_storage']->getCardinality();
      $readable_cardinality = $cardinality === -1 ? $this->t('Unlimited') : new PluralTranslatableMarkup(1, 'Single value', 'Multiple values: @cardinality', ['@cardinality' => $cardinality]);

      // Remove empty values.
      $list = array_filter([...$summary, $readable_cardinality]);
      $settings_summary = [
        '#theme' => 'item_list',
        '#items' => $list,
        '#attributes' => [
          'class' => ['field-settings-summary-cell'],
        ],
      ];
      $bundle_label_arr = [];
      foreach ($field_bundles as $bundle) {
        $bundle_label_arr[] = $bundles[$this->entityTypeId][$bundle]['label'];
      }
      sort($bundle_label_arr);

      // Combine bundles to be a single string separated by a comma.
      $settings_summary['#items'][] = $this->t('Used in: @list', ['@list' => implode(", ", $bundle_label_arr)]);
      $row = [
        '#attributes' => [
          'data-field-id' => $field["field_name"],
        ],
        'field' => [
          '#plain_text' => $field['field_name'],
          '#type' => 'item',
        ],
        'field_type' => [
          '#plain_text' => $field['field_type'],
          '#type' => 'item',
        ],
        'summary' => $settings_summary,
        'operations' => [
          '#type' => 'submit',
          '#name' => $field['field_name'],
          '#value' => $this->t('Re-use'),
          '#wrapper_attributes' => [
            'colspan' => 5,
          ],
          '#attributes' => [
            'class' => [
              'button',
              'button--small',
              'use-ajax',
            ],
            'aria-label' => $this->t('Reuse @field_name', ['@field_name' => $field['field_name']]),
            'data-dialog-type' => 'modal',
          ],
          '#submit' => [
            'callback' => [$this, 'reuseCallback'],
          ],
        ],
      ];
      $rows[] = $row;
    }

    // Sort rows by field name.
    ksort($rows);
    $form['add']['table'] = [
      '#type' => 'table',
      '#header' => [
        $this->t('Field'),
        $this->t('Field Type'),
        $this->t('Summary'),
        $this->t('Operations'),
      ],
      '#attributes' => [
        'class' => ['js-reuse-table'],
      ],
    ];
    $form['add']['table'] += $rows;
    $form['#attached']['library'][] = 'field_ui/drupal.field_ui';

    return $form;
  }

  /**
   * Returns an array of existing field storages that can be added to a bundle.
   *
   * @return array
   *   An array of existing field storages keyed by name.
   */
  protected function getExistingFieldStorageOptions(): array {
    $options = [];
    // Load the field_storages and build the list of options.
    $field_types = $this->fieldTypePluginManager->getDefinitions();
    foreach ($this->entityFieldManager->getFieldStorageDefinitions($this->entityTypeId) as $field_name => $field_storage) {
      // Do not show:
      // - non-configurable field storages,
      // - locked field storages,
      // - field storages that should not be added via user interface,
      // - field storages that already have a field in the bundle.
      $field_type = $field_storage->getType();
      if ($field_storage instanceof FieldStorageConfigInterface
        && !$field_storage->isLocked()
        && empty($field_types[$field_type]['no_ui'])
        && !in_array($this->bundle, $field_storage->getBundles(), TRUE)) {

        $options[$field_name] = [
          'field_type' => $field_types[$field_type]['label'],
          'field_name' => $field_name,
          'field_storage' => $field_storage,
        ];
      }
    }

    asort($options);

    return $options;
  }

  /**
   * Callback function to handle re-using an existing field.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @throws \Exception
   *   Thrown when there is an error re-using the field.
   */
  public function reuseCallback(array $form, FormStateInterface $form_state) {
    $entity_type = $this->entityTypeManager->getDefinition($this->entityTypeId);
    $field_name = $form_state->getTriggeringElement()['#name'];
    // Get settings from existing configuration.
    $default_options = $this->getExistingFieldDefaults($field_name);
    $fields = $this->entityTypeManager->getStorage('field_config')->getQuery()
      ->accessCheck()
      ->condition('entity_type', $this->entityTypeId)
      ->condition('field_name', $field_name)
      ->execute();
    $field = $fields ? $this->entityTypeManager->getStorage('field_config')->load(reset($fields)) : NULL;
    // Have a default label in case a field storage doesn't have any fields.
    $existing_storage_label = $field ? $field->label() : $field_name;
    try {
      $field = $this->entityTypeManager->getStorage('field_config')->create([
        ...$default_options['field_config'] ?? [],
        'field_name' => $field_name,
        'entity_type' => $this->entityTypeId,
        'bundle' => $this->bundle,
        'label' => $existing_storage_label,
        // Field translatability should be explicitly enabled by the users.
        'translatable' => FALSE,
      ]);
      $field->save();

      // Configure the display modes.
      $this->configureEntityFormDisplay($field_name, $default_options['entity_form_display'] ?? []);
      $this->configureEntityViewDisplay($field_name, $default_options['entity_view_display'] ?? []);

      // Store new field information for any additional submit handlers.
      $form_state->set(['fields_added', '_add_existing_field'], $field_name);
      $form_state->setRedirect("entity.field_config.{$this->entityTypeId}_field_edit_form", array_merge(FieldUI::getRouteBundleParameter($entity_type, $this->bundle), ['field_config' => "$this->entityTypeId.$this->bundle.$field_name"]));
    }
    catch (\Exception $e) {
      $this->messenger()->addError($this->t('There was a problem reusing field %label: @message', [
        '%label' => $existing_storage_label,
        '@message' => $e->getMessage(),
      ]));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // This is no-op because there is no single submit action on the form. All
    // the actions are handled by a callback attached to individual buttons.
    // @see \Drupal\field_ui\Form\FieldStorageReuseForm::reuseCallback.
  }

  /**
   * Get default options from an existing field and bundle.
   *
   * @param string $field_name
   *   The machine name of the field.
   *
   * @return array
   *   An array of settings with keys 'field_config', 'entity_form_display', and
   *   'entity_view_display' if these are defined for an existing field
   *   instance. If the field is not defined for the specified bundle (or for
   *   any bundle if $existing_bundle is omitted) then return an empty array.
   */
  protected function getExistingFieldDefaults(string $field_name): array {
    $default_options = [];
    $field_map = $this->entityFieldManager->getFieldMap();

    if (empty($field_map[$this->entityTypeId][$field_name]['bundles'])) {
      return [];
    }
    $bundles = $field_map[$this->entityTypeId][$field_name]['bundles'];

    // Sort bundles to ensure deterministic behavior.
    sort($bundles);
    $existing_bundle = reset($bundles);

    // Copy field configuration.
    $existing_field = $this->entityFieldManager->getFieldDefinitions($this->entityTypeId, $existing_bundle)[$field_name];
    $default_options['field_config'] = [
      'description' => $existing_field->getDescription(),
      'settings' => $existing_field->getSettings(),
      'required' => $existing_field->isRequired(),
      'default_value' => $existing_field->getDefaultValueLiteral(),
      'default_value_callback' => $existing_field->getDefaultValueCallback(),
    ];

    // Copy form and view mode configuration.
    $properties = [
      'targetEntityType' => $this->entityTypeId,
      'bundle' => $existing_bundle,
    ];
    /** @var \Drupal\Core\Entity\Display\EntityFormDisplayInterface $existing_forms */
    $existing_forms = $this->entityTypeManager->getStorage('entity_form_display')->loadByProperties($properties);
    foreach ($existing_forms as $form) {
      if ($settings = $form->getComponent($field_name)) {
        $default_options['entity_form_display'][$form->getMode()] = $settings;
      }
    }
    /** @var \Drupal\Core\Entity\Display\EntityViewDisplayInterface $existing_views */
    $existing_views = $this->entityTypeManager->getStorage('entity_view_display')->loadByProperties($properties);
    foreach ($existing_views as $view) {
      if ($settings = $view->getComponent($field_name)) {
        $default_options['entity_view_display'][$view->getMode()] = $settings;
      }
    }

    return $default_options;
  }

}
