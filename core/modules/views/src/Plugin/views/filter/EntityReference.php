<?php

namespace Drupal\views\Plugin\views\filter;

use Drupal\Component\Plugin\DependentPluginInterface;
use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Entity\Element\EntityAutocomplete;
use Drupal\Core\Entity\EntityReferenceSelection\SelectionInterface;
use Drupal\Core\Entity\EntityReferenceSelection\SelectionPluginManagerInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\SubformState;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Render\Element;
use Drupal\views\Attribute\ViewsFilter;
use Drupal\views\FieldAPIHandlerTrait;
use Drupal\views\Plugin\EntityReferenceSelection\ViewsSelection;
use Drupal\views\Plugin\views\display\DisplayPluginBase;
use Drupal\views\ViewExecutable;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Filters a view by entity references.
 *
 * @ingroup views_filter_handlers
 */
#[ViewsFilter("entity_reference")]
class EntityReference extends ManyToOne {

  use FieldAPIHandlerTrait;

  /**
   * Type for the autocomplete filter format.
   */
  const WIDGET_AUTOCOMPLETE = 'autocomplete';

  /**
   * Type for the select list filter format.
   */
  const WIDGET_SELECT = 'select';

  /**
   * Max number of entities in the select widget.
   */
  const WIDGET_SELECT_LIMIT = 100;

  /**
   * The subform prefix.
   */
  const SUBFORM_PREFIX = 'reference_';

  /**
   * The all value.
   */
  const ALL_VALUE = 'All';

  /**
   * The selection handlers available for the target entity ID of the filter.
   *
   * @var array|null
   */
  protected ?array $handlerOptions = NULL;

  /**
   * Validated exposed input that will be set as the input value.
   *
   * If the select list widget is chosen.
   *
   * @var array
   */
  protected array $validatedExposedInput;

  /**
   * {@inheritdoc}
   */
  public function init(ViewExecutable $view, DisplayPluginBase $display, ?array &$options = NULL): void {
    parent::init($view, $display, $options);
    if (empty($this->definition['field_name'])) {
      $this->definition['field_name'] = $options['field'];
    }

    $this->definition['options callback'] = [$this, 'getValueOptionsCallback'];
    $this->definition['options arguments'] = [$this->getSelectionHandler($this->options['sub_handler'])];
  }

  /**
   * Constructs an EntityReference object.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    protected SelectionPluginManagerInterface $selectionPluginManager,
    protected EntityTypeManagerInterface $entityTypeManager,
    MessengerInterface $messenger,
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->setMessenger($messenger);

    // @todo Unify 'entity field'/'field_name' instead of converting back and
    // forth. https://www.drupal.org/node/2410779
    if (isset($this->definition['entity field'])) {
      $this->definition['field_name'] = $this->definition['entity field'];
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): EntityReference {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('plugin.manager.entity_reference_selection'),
      $container->get('entity_type.manager'),
      $container->get('messenger'),
    );
  }

  /**
   * Gets the entity reference selection handler.
   *
   * @param string|null $sub_handler
   *   The sub handler to get an instance of or NULL for the current selection.
   *
   * @return \Drupal\Core\Entity\EntityReferenceSelection\SelectionInterface
   *   The selection handler plugin instance.
   */
  protected function getSelectionHandler(?string $sub_handler = NULL): SelectionInterface {
    // Default values for the handler.
    $handler_settings = $this->options['sub_handler_settings'] ?? [];
    $handler_settings['handler'] = $sub_handler;
    $handler_settings['target_type'] = $this->getReferencedEntityType()->id();
    /** @var \Drupal\Core\Entity\EntityReferenceSelection\SelectionInterface */
    return $this->selectionPluginManager->getInstance($handler_settings);
  }

  /**
   * {@inheritdoc}
   */
  protected function defineOptions(): array {
    $options = parent::defineOptions();
    $options['sub_handler'] = [
      'default' => 'default:' . $this->getReferencedEntityType()->id(),
    ];
    $options['sub_handler_settings'] = ['default' => []];
    $options['widget'] = ['default' => static::WIDGET_AUTOCOMPLETE];
    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function hasExtraOptions(): bool {
    return TRUE;
  }

  /**
   * Get all selection plugins for this entity type.
   *
   * @return string[]
   *   The selection handlers available for the target entity ID of the filter.
   */
  protected function getSubHandlerOptions(): array {
    if ($this->handlerOptions) {
      return $this->handlerOptions;
    }
    $entity_type = $this->getReferencedEntityType();
    $selection_plugins = $this->selectionPluginManager->getSelectionGroups($entity_type->id());
    $this->handlerOptions = [];
    foreach (array_keys($selection_plugins) as $selection_group_id) {
      // We only display base plugins (e.g. 'default', 'views', ...).
      if (array_key_exists($selection_group_id, $selection_plugins[$selection_group_id])) {
        $this->handlerOptions[$selection_group_id] = (string) $selection_plugins[$selection_group_id][$selection_group_id]['label'];
      }
      elseif (array_key_exists($selection_group_id . ':' . $entity_type->id(), $selection_plugins[$selection_group_id])) {
        $selection_group_plugin = $selection_group_id . ':' . $entity_type->id();
        $this->handlerOptions[$selection_group_plugin] = (string) $selection_plugins[$selection_group_id][$selection_group_plugin]['base_plugin_label'];
      }
    }
    return $this->handlerOptions;
  }

  /**
   * {@inheritdoc}
   */
  public function buildExtraOptionsForm(&$form, FormStateInterface $form_state): void {
    $form['sub_handler'] = [
      '#type' => 'select',
      '#title' => $this->t('Reference method'),
      '#options' => $this->getSubHandlerOptions(),
      '#default_value' => $this->options['sub_handler'],
      '#required' => TRUE,
    ];

    // We store the settings from any sub handler in sub_handler_settings, but
    // in this form, we have multiple sub handlers conditionally displayed.
    // Copy the active sub_handler_settings into the handler specific settings
    // to set the defaults to match the saved options on build.
    if (!empty($this->options['sub_handler']) && !empty($this->options['sub_handler_settings'])) {
      $this->options[static::SUBFORM_PREFIX . $this->options['sub_handler']] = $this->options['sub_handler_settings'];
    }

    foreach ($this->getSubHandlerOptions() as $sub_handler => $sub_handler_label) {
      $subform_key = static::SUBFORM_PREFIX . $sub_handler;
      $subform = [
        '#type' => 'fieldset',
        '#title' => $this->t('Reference type "@type"', [
          '@type' => $sub_handler_label,
        ]),
        '#tree' => TRUE,
        '#parents' => [
          'options',
          $subform_key,
        ],
        // Make the sub handler settings conditional on the selected selection
        // handler.
        '#states' => [
          'visible' => [
            'select[name="options[sub_handler]"]' => ['value' => $sub_handler],
          ],
        ],
      ];

      // Build the sub form and sub for state.
      $selection_handler = $this->getSelectionHandler($sub_handler);
      if (!empty($this->options[$subform_key])) {
        $selection_config = $selection_handler->getConfiguration();
        $selection_config = NestedArray::mergeDeepArray([
          $selection_config,
          $this->options[$subform_key],
        ], TRUE);
        $selection_handler->setConfiguration($selection_config);
      }
      $subform_state = SubformState::createForSubform($subform, $form, $form_state);
      $sub_handler_settings = $selection_handler->buildConfigurationForm($subform, $subform_state);

      if ($selection_handler instanceof ViewsSelection) {
        if (isset($sub_handler_settings['view']['no_view_help'])) {
          // If there are no views with entity reference displays,
          // ViewsSelection still validates the view.
          // This will prevent form config extra form submission,
          // so we remove it here.
          unset($sub_handler_settings['view']['#element_validate']);
        }
      }
      else {
        // Remove unnecessary and inappropriate handler settings from the
        // filter config form.
        $sub_handler_settings['target_bundles_update']['#access'] = FALSE;
        $sub_handler_settings['auto_create']['#access'] = FALSE;
        $sub_handler_settings['auto_create_bundle']['#access'] = FALSE;
      }

      $subform = NestedArray::mergeDeepArray([
        $subform,
        $sub_handler_settings,
      ], TRUE);

      $form[$subform_key] = $subform;
      $this->cleanUpSubformChildren($form[$subform_key]);
    }

    $form['widget'] = [
      '#type' => 'radios',
      '#title' => $this->t('Selection type'),
      '#default_value' => $this->options['widget'],
      '#options' => [
        static::WIDGET_SELECT => $this->t('Select list'),
        static::WIDGET_AUTOCOMPLETE => $this->t('Autocomplete'),
      ],
      '#description' => $this->t('For performance and UX reasons, the maximum count of selectable entities for the "Select list" selection type is limited to @count. If more is expected, select "Autocomplete" instead.', [
        '@count' => static::WIDGET_SELECT_LIMIT,
      ]),
    ];
  }

  /**
   * Clean up subform children for properties that could cause problems.
   *
   * Views modal forms do not work with required or ajax elements.
   *
   * @param array $element
   *   The form element.
   */
  protected function cleanUpSubformChildren(array &$element): void {
    // Remove the required property to prevent focus errors.
    if (isset($element['#required']) && $element['#required']) {
      $element['#required'] = FALSE;
      $element['#element_validate'][] = [static::class, 'validateRequired'];
    }

    // Remove the ajax property as it does not work.
    if (!empty($element['#ajax'])) {
      unset($element['#ajax']);
    }

    // Recursively apply to nested fields within the handler sub form.
    foreach (Element::children($element) as $delta) {
      $this->cleanUpSubformChildren($element[$delta]);
    }
  }

  /**
   * Validates that a required field for a sub handler has a value.
   *
   * @param array $element
   *   The cardinality form render array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   */
  public static function validateRequired(array &$element, FormStateInterface $form_state): void {
    if (!empty($element['value'])) {
      return;
    }

    // Config extra handler does not output validation messages and
    // closes the modal with no feedback to the user.
    // @todo https://www.drupal.org/project/drupal/issues/3163740.
  }

  /**
   * {@inheritdoc}
   */
  public function validateExtraOptionsForm($form, FormStateInterface $form_state): void {
    $options = $form_state->getValue('options');
    $sub_handler = $options['sub_handler'];
    $subform = $form[static::SUBFORM_PREFIX . $sub_handler];
    $subform_state = SubformState::createForSubform($subform, $form, $form_state);

    // Copy handler_settings from options to settings to be compatible with
    // selection plugins.
    $subform_options = $form_state->getValue([
      'options',
      static::SUBFORM_PREFIX . $sub_handler,
    ]);
    $subform_state->setValue([
      'settings',
    ], $subform_options);
    $this->getSelectionHandler($sub_handler)
      ->validateConfigurationForm($subform, $subform_state);

    // Store the sub handler options in sub_handler_settings.
    $form_state->setValue(['options', 'sub_handler_settings'], $subform_options);

    // Remove options that are not from the selected sub_handler.
    foreach (array_keys($this->getSubHandlerOptions()) as $sub_handler_option) {
      if (isset($options[static::SUBFORM_PREFIX . $sub_handler_option])) {
        $form_state->unsetValue(['options', static::SUBFORM_PREFIX . $sub_handler_option]);
      }
    }

    parent::validateExtraOptionsForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitExtraOptionsForm($form, FormStateInterface $form_state): void {
    $sub_handler = $form_state->getValue('options')['sub_handler'];

    // Ensure that only the select sub handler option is saved.
    foreach (array_keys($this->getSubHandlerOptions()) as $sub_handler_option) {
      if ($sub_handler_option == $sub_handler) {
        $this->options['sub_handler_settings'] = $this->options[static::SUBFORM_PREFIX . $sub_handler_option];
      }
      if (isset($this->options[static::SUBFORM_PREFIX . $sub_handler_option])) {
        unset($this->options[static::SUBFORM_PREFIX . $sub_handler_option]);
      }
    }
  }

  /**
   * Normalize values for widget switching.
   *
   * The saved values can differ in live preview if switching back and forth
   * between the select and autocomplete widgets. This normalizes the values to
   * avoid errors when making the switch.
   *
   * @param array $form
   *   Associative array containing the structure of the form, passed by
   *   reference.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  protected function alternateWidgetsDefaultNormalize(array &$form, FormStateInterface $form_state): void {
    $field_id = '_' . $this->getFieldDefinition()->getName() . '-widget';
    $form[$field_id] = [
      '#type' => 'hidden',
      '#value' => $this->options['widget'],
    ];

    $previous_widget = $form_state->getUserInput()[$field_id] ?? NULL;
    if ($previous_widget && $previous_widget !== $this->options['widget']) {
      $form['value']['#value_callback'] = function ($element) {
        return $element['#default_value'] ?? '';
      };
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function valueForm(&$form, FormStateInterface $form_state) {
    if (!isset($this->options['sub_handler'])) {
      return;
    }
    switch ($this->options['widget']) {
      case static::WIDGET_SELECT:
        $this->valueFormAddSelect($form, $form_state);
        break;

      case static::WIDGET_AUTOCOMPLETE:
        $this->valueFormAddAutocomplete($form, $form_state);
        break;
    }

    if (!empty($this->view->live_preview)) {
      $this->alternateWidgetsDefaultNormalize($form, $form_state);
    }

    // Show or hide the value field depending on the operator field.
    $is_exposed = $this->options['exposed'];

    $visible = [];
    if ($is_exposed) {
      $operator_field = ($this->options['expose']['use_operator'] && $this->options['expose']['operator_id']) ? $this->options['expose']['operator_id'] : NULL;
    }
    else {
      $operator_field = 'options[operator]';
      $visible[] = [
        ':input[name="options[expose_button][checkbox][checkbox]"]' => ['checked' => TRUE],
        ':input[name="options[expose][use_operator]"]' => ['checked' => TRUE],
        ':input[name="options[expose][operator_id]"]' => ['empty' => FALSE],
      ];
    }
    if ($operator_field) {
      foreach ($this->operatorValues(1) as $operator) {
        $visible[] = [
          ':input[name="' . $operator_field . '"]' => ['value' => $operator],
        ];
      }
      $form['value']['#states'] = ['visible' => $visible];
    }

    if (!$is_exposed) {
      // Retain the helper option.
      $this->helper->buildOptionsForm($form, $form_state);

      // Show help text if not exposed to end users.
      $form['value']['#description'] = $this->t('Leave blank for all. Otherwise, the first selected item will be the default instead of "Any".');
    }
  }

  /**
   * Adds an autocomplete element to the form.
   *
   * @param array $form
   *   Associative array containing the structure of the form, passed by
   *   reference.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  protected function valueFormAddAutocomplete(array &$form, FormStateInterface $form_state): void {
    $referenced_type = $this->getReferencedEntityType();
    $form['value'] = [
      '#title' => $this->t('Select %entity_types', ['%entity_types' => $referenced_type->getPluralLabel()]),
      '#type' => 'entity_autocomplete',
      '#default_value' => EntityAutocomplete::getEntityLabels($this->getDefaultSelectedEntities()),
      '#tags' => TRUE,
      '#process_default_value' => FALSE,
      '#target_type' => $referenced_type->id(),
      '#selection_handler' => $this->options['sub_handler'],
      '#selection_settings' => $this->options['sub_handler_settings'],
      // Validation is done by validateExposed().
      '#validate_reference' => FALSE,
    ];
  }

  /**
   * Adds a select element to the form.
   *
   * @param array $form
   *   Associative array containing the structure of the form, passed by
   *   reference.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  protected function valueFormAddSelect(array &$form, FormStateInterface $form_state): void {
    $is_exposed = $form_state->get('exposed');

    $options = $this->getValueOptions();
    $default_value = (array) $this->value;

    if ($is_exposed) {
      $identifier = $this->options['expose']['identifier'];

      if (!empty($this->options['expose']['reduce'])) {
        $options = $this->reduceValueOptions($options);

        if (!empty($this->options['expose']['multiple']) && empty($this->options['expose']['required'])) {
          $default_value = [];
        }
      }

      if (empty($this->options['expose']['multiple'])) {
        if (empty($this->options['expose']['required']) && (empty($default_value) || !empty($this->options['expose']['reduce']))) {
          $default_value = static::ALL_VALUE;
        }
        elseif (empty($default_value)) {
          $keys = array_keys($options);
          $default_value = array_shift($keys);
        }
        else {
          // Set the default value to be the first element of the array.
          $default_value = reset($default_value);
        }
      }
    }

    $referenced_type = $this->getReferencedEntityType();
    $form['value'] = [
      '#type' => 'select',
      '#title' => $this->t('Select @entity_types', ['@entity_types' => $referenced_type->getPluralLabel()]),
      '#multiple' => TRUE,
      '#options' => $options,
      // Set a minimum size to facilitate easier selection of entities.
      '#size' => min(8, count($options)),
      '#default_value' => $default_value,
    ];

    $user_input = $form_state->getUserInput();
    if ($is_exposed && isset($identifier) && !isset($user_input[$identifier])) {
      $user_input[$identifier] = $default_value;
      $form_state->setUserInput($user_input);
    }
  }

  /**
   * Gets all entities selected by default.
   *
   * @return \Drupal\Core\Entity\EntityInterface[]
   *   All entities selected by default, or an empty array, if none.
   */
  protected function getDefaultSelectedEntities(): array {
    $referenced_type_id = $this->getReferencedEntityType()->id();
    $entity_storage = $this->entityTypeManager->getStorage($referenced_type_id);

    return !empty($this->value) && !isset($this->value[static::ALL_VALUE]) ? $entity_storage->loadMultiple($this->value) : [];
  }

  /**
   * Returns the value options for a select widget.
   *
   * @param \Drupal\Core\Entity\EntityReferenceSelection\SelectionInterface $selection_handler
   *   The selection handler.
   *
   * @return string[]
   *   The options.
   *
   * @see \Drupal\views\Plugin\views\filter\InOperator::getValueOptions()
   */
  protected function getValueOptionsCallback(SelectionInterface $selection_handler): array {
    $entity_data = [];
    if ($this->options['widget'] === static::WIDGET_SELECT) {
      $entity_data = $selection_handler->getReferenceableEntities(NULL, 'CONTAINS', static::WIDGET_SELECT_LIMIT);
    }

    $options = [];
    foreach ($entity_data as $bundle) {
      foreach ($bundle as $id => $entity_label) {
        $options[$id] = $entity_label;
      }
    }

    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function validate(): array {
    // InOperator validation logic is not appropriate for entity reference
    // autocomplete or select, so prevent parent class validation from
    // occurring.
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function acceptExposedInput($input): bool {
    if (empty($this->options['exposed'])) {
      return TRUE;
    }

    // We need to know the operator, which is normally set in
    // \Drupal\views\Plugin\views\filter\FilterPluginBase::acceptExposedInput(),
    // before we actually call the parent version of ourselves.
    if (!empty($this->options['expose']['use_operator']) && !empty($this->options['expose']['operator_id']) && isset($input[$this->options['expose']['operator_id']])) {
      $this->operator = $input[$this->options['expose']['operator_id']];
    }

    // If view is an attachment and is inheriting exposed filters, then assume
    // exposed input has already been validated.
    if (!empty($this->view->is_attachment) && $this->view->display_handler->usesExposed()) {
      $this->validatedExposedInput = (array) $this->view->exposed_raw_input[$this->options['expose']['identifier']];
    }

    // If we're checking for EMPTY or NOT, we don't need any input, and we can
    // say that our input conditions are met by just having the right operator.
    if ($this->operator == 'empty' || $this->operator == 'not empty') {
      return TRUE;
    }

    // If it's non-required and there's no value don't bother filtering.
    if (!$this->options['expose']['required'] && empty($this->validatedExposedInput)) {
      return FALSE;
    }

    $accept_exposed_input = parent::acceptExposedInput($input);
    if ($accept_exposed_input) {
      // If we have previously validated input, override.
      if (isset($this->validatedExposedInput)) {
        $this->value = $this->validatedExposedInput;
      }
    }

    return $accept_exposed_input;
  }

  /**
   * {@inheritdoc}
   */
  public function validateExposed(&$form, FormStateInterface $form_state): void {
    if (empty($this->options['exposed'])) {
      return;
    }

    $identifier = $this->options['expose']['identifier'];

    // Set the validated exposed input from the select list when not the all
    // value option.
    if ($this->options['widget'] == static::WIDGET_SELECT) {
      if ($form_state->getValue($identifier) != static::ALL_VALUE) {
        $this->validatedExposedInput = (array) $form_state->getValue($identifier);
      }
      return;
    }

    if (empty($identifier)) {
      return;
    }

    $values = $form_state->getValue($identifier);
    if (!is_array($values)) {
      return;
    }

    foreach ($values as $value) {
      $this->validatedExposedInput[] = $value['target_id'];
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function valueSubmit($form, FormStateInterface $form_state): void {
    // Prevent the parent class InOperator from altering the array.
    // @see \Drupal\views\Plugin\views\filter\InOperator::valueSubmit().
  }

  /**
   * Gets the target entity type referenced by this field.
   *
   * @return \Drupal\Core\Entity\EntityTypeInterface
   *   The entity type definition.
   */
  protected function getReferencedEntityType(): EntityTypeInterface {
    $field_def = $this->getFieldDefinition();
    $entity_type_id = $field_def->getItemDefinition()
      ->getSetting('target_type');
    return $this->entityTypeManager->getDefinition($entity_type_id);
  }

  /**
   * {@inheritdoc}
   */
  public function calculateDependencies(): array {
    $dependencies = parent::calculateDependencies();

    $sub_handler = $this->options['sub_handler'];
    $selection_handler = $this->getSelectionHandler($sub_handler);
    if ($selection_handler instanceof DependentPluginInterface) {
      $dependencies += $selection_handler->calculateDependencies();
    }

    foreach ($this->getDefaultSelectedEntities() as $entity) {
      $dependencies[$entity->getConfigDependencyKey()][] = $entity->getConfigDependencyName();
    }

    return $dependencies;
  }

}
