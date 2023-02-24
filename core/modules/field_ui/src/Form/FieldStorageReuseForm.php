<?php


namespace Drupal\field_ui\Form;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityDisplayRepositoryInterface;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Field\FieldTypePluginManagerInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\field\FieldStorageConfigInterface;
use Drupal\field_ui\FieldUI;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Url;
use Drupal\Component\Serialization\Json;

/**
 * Provides a form for the "field storage" add page.
 *
 * @internal
 */
class FieldStorageReuseForm extends FormBase {

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
  public function __construct(EntityTypeManagerInterface $entity_type_manager, FieldTypePluginManagerInterface $field_type_plugin_manager, ConfigFactoryInterface $config_factory, EntityFieldManagerInterface $entity_field_manager = NULL, EntityDisplayRepositoryInterface $entity_display_repository = NULL)
  {
    $this->entityTypeManager = $entity_type_manager;
    $this->fieldTypePluginManager = $field_type_plugin_manager;
    $this->configFactory = $config_factory;
    $this->entityFieldManager = $entity_field_manager;
    $this->entityDisplayRepository = $entity_display_repository;
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId()
  {
    return 'field_ui_field_storage_reuse_form';
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container)
  {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('plugin.manager.field.field_type'),
      $container->get('config.factory'),
      $container->get('entity_field.manager'),
      $container->get('entity_display.repository'),

    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $entity_type_id = NULL, $bundle = NULL)
  {
    if (!$form_state->get('entity_type_id')) {
      $form_state->set('entity_type_id', $entity_type_id);
    }
    if (!$form_state->get('bundle')) {
      $form_state->set('bundle', $bundle);
    }

    $this->entityTypeId = $form_state->get('entity_type_id');
    $this->bundle = $form_state->get('bundle');

    // Gather valid field types.
    $field_type_options = [];
    foreach ($this->fieldTypePluginManager->getGroupedDefinitions($this->fieldTypePluginManager->getUiDefinitions()) as $category => $field_types) {
      foreach ($field_types as $name => $field_type) {
        $field_type_options[$category][$name] = $field_type['label'];
      }
    }

    $form['text'] = [
      '#type' => 'inline_template',
      '#template' => "You can re-use a field from other sub-types of the same entity type. Re-using a field creates another usage of the same field storage.",
    ];

    $form['search'] = [
      '#type' => 'search',
      '#placeholder' => $this->t('Search'),
    ];

    $form['add'] = [
      '#type' => 'container',
      '#attributes' => ['class' => ['form--inline', 'clearfix']],
    ];
    $bundle_info_service = \Drupal::service('entity_type.bundle.info');
    $bundles = $bundle_info_service->getAllBundleInfo();
    $existing_field_storage_options = $this->getExistingFieldStorageOptions();

    $rows = [];
    foreach ($existing_field_storage_options as $field) {
      $field_bundles = $field['field_storage']->getBundles();
      $summary = \Drupal::service('plugin.manager.field.field_type')->getStorageSettingsSummary($field['field_storage']);
      $cardinality =  $field['field_storage']->getCardinality();
      $readable_cardinality = $cardinality === -1 ? 'Unlimited' : ($cardinality === 1 ? '' : "Multiple values: $cardinality");
      $max_length = is_integer($field['field_storage']->getSetting('max_length')) ? "Max length: {$field['field_storage']->getSetting('max_length')}" : '';

      // Remove empty values.
      $list = array_filter([...$summary, $readable_cardinality, $max_length]);
      $settings_summary = empty($list) ? [] : [
          '#theme' => 'item_list',
          '#items' => $list,
        '#attributes' => [
          'class' => ['field-settings-summary-cell'],
        ],
      ];
      foreach ($field_bundles as $field_bundle) {
        $bundle_label = $bundles[$this->entityTypeId][$field_bundle]['label'];
        $row = [
          'field' => ['#markup' => $field['field_name']],
          'field_type' => ['#markup' => $field['field_type']],
          'settings' => $settings_summary,
          'content_type' => ['#markup' => $bundle_label],
          'operations' => [
              '#type' => 'button',
              '#name' => $field['field_name'],
              '#existing_storage_label' => $field['field_type'],
              '#button_type' => 'small',
              '#value' => $this->t('Re-use'),
              '#url' => Url::fromRoute('entity.node.canonical', ['node' => 1]),
//             TODO: Fix the parameters on this route.
//              '#url' => Url::fromRoute("entity.field_config.{$entity_type_id}_field_edit_form", [
//                'node_config' => 'node',
//                'field_type' => 'body',
//                FieldUI::getRouteBundleParameter($entity_type, $this->bundle)
//              ]),
              '#wrapper_attributes' => [
                'colspan' => 5,
              ],
            '#attributes' => [
                'class' => ['button', 'button--action', 'button-primary', 'use-ajax'],
                'data-dialog-type' => 'modal',
                'data-dialog-options' => Json::encode([
                  'width' => '85vw'
                ]),
              ],
              '#ajax' => [
                'callback' => [$this, 'reuseField'],
              ],
              '#submit' => [],
          ],
        ];
        $rows[] = $row;
      }
    }

    // Sort rows by field name.
    ksort($rows);
    $form['add']['table'] = [
      '#type' => 'table',
      '#header' => array($this->t('Field'), $this->t('Field Type'), $this->t('Settings'), $this->t('Content Type'), $this->t('Operations')),
    ];
    $form['add']['table'] += $rows;

    // Place the 'translatable' property as an explicit value so that contrib
    // modules can form_alter() the value for newly created fields. By default
    // we create field storage as translatable so it will be possible to enable
    // translation at field level.
    $form['translatable'] = [
      '#type' => 'value',
      '#value' => TRUE,
    ];

    $form['#attached']['library'][] = 'field_ui/drupal.field_ui';

    return $form;
  }

  /**
   * Returns an array of existing field storages that can be added to a bundle.
   *
   * @return array
   *   An array of existing field storages keyed by name.
   */
  protected function getExistingFieldStorageOptions()
  {
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

  public function reuseField(array &$form, FormStateInterface $form_state): AjaxResponse
  {
    $entity_type = $this->entityTypeManager->getDefinition($this->entityTypeId);
    $reuse_button =  $form_state->getTriggeringElement();
    $field_name = $reuse_button['#name'];
    $existing_storage_label = $reuse_button['#existing_storage_label'];
    try {
      $field = $this->entityTypeManager->getStorage('field_config')->create([
        'field_name' => $field_name,
        'entity_type' => $this->entityTypeId,
        'bundle' => $this->bundle,
        'label' => $existing_storage_label,
      ]);
      $field->save();

      $this->configureEntityFormDisplay($field_name);
      $this->configureEntityViewDisplay($field_name);

      $route_parameters = [
          'field_config' => $field->id(),
        ] + FieldUI::getRouteBundleParameter($entity_type, $this->bundle);
      $destinations[] = ['route_name' => "entity.field_config.{$this->entityTypeId}_field_edit_form", 'route_parameters' => $route_parameters];
      $destinations[] = ['route_name' => "entity.{$this->entityTypeId}.field_ui_fields", 'route_parameters' => $route_parameters];

      // Store new field information for any additional submit handlers.
      $form_state->set(['fields_added', '_add_existing_field'], $field_name);
    }
    catch (\Exception $e) {
      $error = TRUE;
      $this->messenger()->addError($this->t('There was a problem reusing field %label: @message', ['%label' => $existing_storage_label, '@message' => $e->getMessage()]));
    }
    $response = new AjaxResponse();
    return $response;
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

  public function submitForm(array &$form, FormStateInterface $form_state)
  {
    // TODO: Implement submitForm() method.
  }
}
