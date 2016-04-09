<?php

namespace Drupal\field_ui\Form;

use Drupal\Component\Plugin\Factory\DefaultFactory;
use Drupal\Component\Plugin\PluginManagerBase;
use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityWithPluginCollectionInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldTypePluginManagerInterface;
use Drupal\Core\Field\PluginSettingsInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\field_ui\Element\FieldUiTable;
use Drupal\field_ui\FieldUI;

/**
 * Base class for EntityDisplay edit forms.
 */
abstract class EntityDisplayFormBase extends EntityForm {

  /**
   * The display context. Either 'view' or 'form'.
   *
   * @var string
   */
  protected $displayContext;

  /**
   * The widget or formatter plugin manager.
   *
   * @var \Drupal\Component\Plugin\PluginManagerBase
   */
  protected $pluginManager;

  /**
   * A list of field types.
   *
   * @var array
   */
  protected $fieldTypes;

  /**
   * The entity being used by this form.
   *
   * @var \Drupal\Core\Entity\Display\EntityDisplayInterface
   */
  protected $entity;

  /**
   * Constructs a new EntityDisplayFormBase.
   *
   * @param \Drupal\Core\Field\FieldTypePluginManagerInterface $field_type_manager
   *   The field type manager.
   * @param \Drupal\Component\Plugin\PluginManagerBase $plugin_manager
   *   The widget or formatter plugin manager.
   */
  public function __construct(FieldTypePluginManagerInterface $field_type_manager, PluginManagerBase $plugin_manager) {
    $this->fieldTypes = $field_type_manager->getDefinitions();
    $this->pluginManager = $plugin_manager;
  }

  /**
   * {@inheritdoc}
   */
  public function getEntityFromRouteMatch(RouteMatchInterface $route_match, $entity_type_id) {
    $route_parameters = $route_match->getParameters()->all();

    return $this->getEntityDisplay($route_parameters['entity_type_id'], $route_parameters['bundle'], $route_parameters[$this->displayContext . '_mode_name']);
  }

  /**
   * Get the regions needed to create the overview form.
   *
   * @return array
   *   Example usage:
   *   @code
   *     return array(
   *       'content' => array(
   *         // label for the region.
   *         'title' => $this->t('Content'),
   *         // Indicates if the region is visible in the UI.
   *         'invisible' => TRUE,
   *         // A message to indicate that there is nothing to be displayed in
   *         // the region.
   *         'message' => $this->t('No field is displayed.'),
   *       ),
   *     );
   *   @endcode
   */
  public function getRegions() {
    return array(
      'content' => array(
        'title' => $this->t('Content'),
        'invisible' => TRUE,
        'message' => $this->t('No field is displayed.')
      ),
      'hidden' => array(
        'title' => $this->t('Disabled', array(), array('context' => 'Plural')),
        'message' => $this->t('No field is hidden.')
      ),
    );
  }

  /**
   * Returns an associative array of all regions.
   *
   * @return array
   *   An array containing the region options.
   */
  public function getRegionOptions() {
    $options = array();
    foreach ($this->getRegions() as $region => $data) {
      $options[$region] = $data['title'];
    }
    return $options;
  }

  /**
   * Collects the definitions of fields whose display is configurable.
   *
   * @return \Drupal\Core\Field\FieldDefinitionInterface[]
   *   The array of field definitions
   */
  protected function getFieldDefinitions() {
    $context = $this->displayContext;
    return array_filter($this->entityManager->getFieldDefinitions($this->entity->getTargetEntityTypeId(), $this->entity->getTargetBundle()), function(FieldDefinitionInterface $field_definition) use ($context) {
      return $field_definition->isDisplayConfigurable($context);
    });
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    $field_definitions = $this->getFieldDefinitions();
    $extra_fields = $this->getExtraFields();

    $form += array(
      '#entity_type' => $this->entity->getTargetEntityTypeId(),
      '#bundle' => $this->entity->getTargetBundle(),
      '#fields' => array_keys($field_definitions),
      '#extra' => array_keys($extra_fields),
    );

    if (empty($field_definitions) && empty($extra_fields) && $route_info = FieldUI::getOverviewRouteInfo($this->entity->getTargetEntityTypeId(), $this->entity->getTargetBundle())) {
      drupal_set_message($this->t('There are no fields yet added. You can add new fields on the <a href=":link">Manage fields</a> page.', array(':link' => $route_info->toString())), 'warning');
      return $form;
    }

    $table = array(
      '#type' => 'field_ui_table',
      '#header' => $this->getTableHeader(),
      '#regions' => $this->getRegions(),
      '#attributes' => array(
        'class' => array('field-ui-overview'),
        'id' => 'field-display-overview',
      ),
      '#tabledrag' => array(
        array(
          'action' => 'order',
          'relationship' => 'sibling',
          'group' => 'field-weight',
        ),
        array(
          'action' => 'match',
          'relationship' => 'parent',
          'group' => 'field-parent',
          'subgroup' => 'field-parent',
          'source' => 'field-name',
        ),
      ),
    );

    // Field rows.
    foreach ($field_definitions as $field_name => $field_definition) {
      $table[$field_name] = $this->buildFieldRow($field_definition, $form, $form_state);
    }

    // Non-field elements.
    foreach ($extra_fields as $field_id => $extra_field) {
      $table[$field_id] = $this->buildExtraFieldRow($field_id, $extra_field);
    }

    $form['fields'] = $table;

    // Custom display settings.
    if ($this->entity->getMode() == 'default') {
      // Only show the settings if there is at least one custom display mode.
      $display_mode_options = $this->getDisplayModeOptions();
      // Unset default option.
      unset($display_mode_options['default']);
      if ($display_mode_options) {
        $form['modes'] = array(
          '#type' => 'details',
          '#title' => $this->t('Custom display settings'),
        );
        // Prepare default values for the 'Custom display settings' checkboxes.
        $default = array();
        if ($enabled_displays = array_filter($this->getDisplayStatuses())) {
          $default = array_keys(array_intersect_key($display_mode_options, $enabled_displays));
        }
        $form['modes']['display_modes_custom'] = array(
          '#type' => 'checkboxes',
          '#title' => $this->t('Use custom display settings for the following modes'),
          '#options' => $display_mode_options,
          '#default_value' => $default,
        );
      }
    }

    // In overviews involving nested rows from contributed modules (i.e
    // field_group), the 'plugin type' selects can trigger a series of changes
    // in child rows. The #ajax behavior is therefore not attached directly to
    // the selects, but triggered by the client-side script through a hidden
    // #ajax 'Refresh' button. A hidden 'refresh_rows' input tracks the name of
    // affected rows.
    $form['refresh_rows'] = array('#type' => 'hidden');
    $form['refresh'] = array(
      '#type' => 'submit',
      '#value' => $this->t('Refresh'),
      '#op' => 'refresh_table',
      '#submit' => array('::multistepSubmit'),
      '#ajax' => array(
        'callback' => '::multistepAjax',
        'wrapper' => 'field-display-overview-wrapper',
        'effect' => 'fade',
        // The button stays hidden, so we hide the Ajax spinner too. Ad-hoc
        // spinners will be added manually by the client-side script.
        'progress' => 'none',
      ),
      '#attributes' => array('class' => array('visually-hidden'))
    );

    $form['actions'] = array('#type' => 'actions');
    $form['actions']['submit'] = array(
      '#type' => 'submit',
      '#button_type' => 'primary',
      '#value' => $this->t('Save'),
    );

    $form['#attached']['library'][] = 'field_ui/drupal.field_ui';

    return $form;
  }

  /**
   * Builds the table row structure for a single field.
   *
   * @param \Drupal\Core\Field\FieldDefinitionInterface $field_definition
   *   The field definition.
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return array
   *   A table row array.
   */
  protected function buildFieldRow(FieldDefinitionInterface $field_definition, array $form, FormStateInterface $form_state) {
    $field_name = $field_definition->getName();
    $display_options = $this->entity->getComponent($field_name);
    $label = $field_definition->getLabel();

    // Disable fields without any applicable plugins.
    if (empty($this->getApplicablePluginOptions($field_definition))) {
      $this->entity->removeComponent($field_name)->save();
      $display_options = $this->entity->getComponent($field_name);
    }

    $regions = array_keys($this->getRegions());
    $field_row = array(
      '#attributes' => array('class' => array('draggable', 'tabledrag-leaf')),
      '#row_type' => 'field',
      '#region_callback' => array($this, 'getRowRegion'),
      '#js_settings' => array(
        'rowHandler' => 'field',
        'defaultPlugin' => $this->getDefaultPlugin($field_definition->getType()),
      ),
      'human_name' => array(
        '#plain_text' => $label,
      ),
      'weight' => array(
        '#type' => 'textfield',
        '#title' => $this->t('Weight for @title', array('@title' => $label)),
        '#title_display' => 'invisible',
        '#default_value' => $display_options ? $display_options['weight'] : '0',
        '#size' => 3,
        '#attributes' => array('class' => array('field-weight')),
      ),
      'parent_wrapper' => array(
        'parent' => array(
          '#type' => 'select',
          '#title' => $this->t('Label display for @title', array('@title' => $label)),
          '#title_display' => 'invisible',
          '#options' => array_combine($regions, $regions),
          '#empty_value' => '',
          '#attributes' => array('class' => array('js-field-parent', 'field-parent')),
          '#parents' => array('fields', $field_name, 'parent'),
        ),
        'hidden_name' => array(
          '#type' => 'hidden',
          '#default_value' => $field_name,
          '#attributes' => array('class' => array('field-name')),
        ),
      ),
    );

    $field_row['plugin'] = array(
      'type' => array(
        '#type' => 'select',
        '#title' => $this->t('Plugin for @title', array('@title' => $label)),
        '#title_display' => 'invisible',
        '#options' => $this->getPluginOptions($field_definition),
        '#default_value' => $display_options ? $display_options['type'] : 'hidden',
        '#parents' => array('fields', $field_name, 'type'),
        '#attributes' => array('class' => array('field-plugin-type')),
      ),
      'settings_edit_form' => array(),
    );

    // Get the corresponding plugin object.
    $plugin = $this->entity->getRenderer($field_name);

    // Base button element for the various plugin settings actions.
    $base_button = array(
      '#submit' => array('::multistepSubmit'),
      '#ajax' => array(
        'callback' => '::multistepAjax',
        'wrapper' => 'field-display-overview-wrapper',
        'effect' => 'fade',
      ),
      '#field_name' => $field_name,
    );

    if ($form_state->get('plugin_settings_edit') == $field_name) {
      // We are currently editing this field's plugin settings. Display the
      // settings form and submit buttons.
      $field_row['plugin']['settings_edit_form'] = array();

      if ($plugin) {
        // Generate the settings form and allow other modules to alter it.
        $settings_form = $plugin->settingsForm($form, $form_state);
        $third_party_settings_form = $this->thirdPartySettingsForm($plugin, $field_definition, $form, $form_state);

        if ($settings_form || $third_party_settings_form) {
          $field_row['plugin']['#cell_attributes'] = array('colspan' => 3);
          $field_row['plugin']['settings_edit_form'] = array(
            '#type' => 'container',
            '#attributes' => array('class' => array('field-plugin-settings-edit-form')),
            '#parents' => array('fields', $field_name, 'settings_edit_form'),
            'label' => array(
              '#markup' => $this->t('Plugin settings'),
            ),
            'settings' => $settings_form,
            'third_party_settings' => $third_party_settings_form,
            'actions' => array(
              '#type' => 'actions',
              'save_settings' => $base_button + array(
                '#type' => 'submit',
                '#button_type' => 'primary',
                '#name' => $field_name . '_plugin_settings_update',
                '#value' => $this->t('Update'),
                '#op' => 'update',
              ),
              'cancel_settings' => $base_button + array(
                '#type' => 'submit',
                '#name' => $field_name . '_plugin_settings_cancel',
                '#value' => $this->t('Cancel'),
                '#op' => 'cancel',
                // Do not check errors for the 'Cancel' button, but make sure we
                // get the value of the 'plugin type' select.
                '#limit_validation_errors' => array(array('fields', $field_name, 'type')),
              ),
            ),
          );
          $field_row['#attributes']['class'][] = 'field-plugin-settings-editing';
        }
      }
    }
    else {
      $field_row['settings_summary'] = array();
      $field_row['settings_edit'] = array();

      if ($plugin) {
        // Display a summary of the current plugin settings, and (if the
        // summary is not empty) a button to edit them.
        $summary = $plugin->settingsSummary();

        // Allow other modules to alter the summary.
        $this->alterSettingsSummary($summary, $plugin, $field_definition);

        if (!empty($summary)) {
          $field_row['settings_summary'] = array(
            '#type' => 'inline_template',
            '#template' => '<div class="field-plugin-summary">{{ summary|safe_join("<br />") }}</div>',
            '#context' => array('summary' => $summary),
            '#cell_attributes' => array('class' => array('field-plugin-summary-cell')),
          );
        }

        // Check selected plugin settings to display edit link or not.
        $settings_form = $plugin->settingsForm($form, $form_state);
        $third_party_settings_form = $this->thirdPartySettingsForm($plugin, $field_definition, $form, $form_state);
        if (!empty($settings_form) || !empty($third_party_settings_form)) {
          $field_row['settings_edit'] = $base_button + array(
            '#type' => 'image_button',
            '#name' => $field_name . '_settings_edit',
            '#src' => 'core/misc/icons/787878/cog.svg',
            '#attributes' => array('class' => array('field-plugin-settings-edit'), 'alt' => $this->t('Edit')),
            '#op' => 'edit',
            // Do not check errors for the 'Edit' button, but make sure we get
            // the value of the 'plugin type' select.
            '#limit_validation_errors' => array(array('fields', $field_name, 'type')),
            '#prefix' => '<div class="field-plugin-settings-edit-wrapper">',
            '#suffix' => '</div>',
          );
        }
      }
    }

    return $field_row;
  }

  /**
   * Builds the table row structure for a single extra field.
   *
   * @param string $field_id
   *   The field ID.
   * @param array $extra_field
   *   The pseudo-field element.
   *
   * @return array
   *   A table row array.
   */
  protected function buildExtraFieldRow($field_id, $extra_field) {
    $display_options = $this->entity->getComponent($field_id);

    $regions = array_keys($this->getRegions());
    $extra_field_row = array(
      '#attributes' => array('class' => array('draggable', 'tabledrag-leaf')),
      '#row_type' => 'extra_field',
      '#region_callback' => array($this, 'getRowRegion'),
      '#js_settings' => array('rowHandler' => 'field'),
      'human_name' => array(
        '#markup' => $extra_field['label'],
      ),
      'weight' => array(
        '#type' => 'textfield',
        '#title' => $this->t('Weight for @title', array('@title' => $extra_field['label'])),
        '#title_display' => 'invisible',
        '#default_value' => $display_options ? $display_options['weight'] : 0,
        '#size' => 3,
        '#attributes' => array('class' => array('field-weight')),
      ),
      'parent_wrapper' => array(
        'parent' => array(
          '#type' => 'select',
          '#title' => $this->t('Parents for @title', array('@title' => $extra_field['label'])),
          '#title_display' => 'invisible',
          '#options' => array_combine($regions, $regions),
          '#empty_value' => '',
          '#attributes' => array('class' => array('js-field-parent', 'field-parent')),
          '#parents' => array('fields', $field_id, 'parent'),
        ),
        'hidden_name' => array(
          '#type' => 'hidden',
          '#default_value' => $field_id,
          '#attributes' => array('class' => array('field-name')),
        ),
      ),
      'plugin' => array(
        'type' => array(
          '#type' => 'select',
          '#title' => $this->t('Visibility for @title', array('@title' => $extra_field['label'])),
          '#title_display' => 'invisible',
          '#options' => $this->getExtraFieldVisibilityOptions(),
          '#default_value' => $display_options ? 'visible' : 'hidden',
          '#parents' => array('fields', $field_id, 'type'),
          '#attributes' => array('class' => array('field-plugin-type')),
        ),
      ),
      'settings_summary' => array(),
      'settings_edit' => array(),
    );

    return $extra_field_row;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // If the main "Save" button was submitted while a field settings subform
    // was being edited, update the new incoming settings when rebuilding the
    // entity, just as if the subform's "Update" button had been submitted.
    if ($edit_field = $form_state->get('plugin_settings_edit')) {
      $form_state->set('plugin_settings_update', $edit_field);
    }

    parent::submitForm($form, $form_state);
    $form_values = $form_state->getValues();

    // Handle the 'display modes' checkboxes if present.
    if ($this->entity->getMode() == 'default' && !empty($form_values['display_modes_custom'])) {
      $display_modes = $this->getDisplayModes();
      $current_statuses = $this->getDisplayStatuses();

      $statuses = array();
      foreach ($form_values['display_modes_custom'] as $mode => $value) {
        if (!empty($value) && empty($current_statuses[$mode])) {
          // If no display exists for the newly enabled view mode, initialize
          // it with those from the 'default' view mode, which were used so
          // far.
          if (!$this->entityManager->getStorage($this->entity->getEntityTypeId())->load($this->entity->getTargetEntityTypeId() . '.' . $this->entity->getTargetBundle() . '.' . $mode)) {
            $display = $this->getEntityDisplay($this->entity->getTargetEntityTypeId(), $this->entity->getTargetBundle(), 'default')->createCopy($mode);
            $display->save();
          }

          $display_mode_label = $display_modes[$mode]['label'];
          $url = $this->getOverviewUrl($mode);
          drupal_set_message($this->t('The %display_mode mode now uses custom display settings. You might want to <a href=":url">configure them</a>.', ['%display_mode' => $display_mode_label, ':url' => $url->toString()]));
        }
        $statuses[$mode] = !empty($value);
      }

      $this->saveDisplayStatuses($statuses);
    }

    drupal_set_message($this->t('Your settings have been saved.'));
  }

  /**
   * {@inheritdoc}
   */
  protected function copyFormValuesToEntity(EntityInterface $entity, array $form, FormStateInterface $form_state) {
    $form_values = $form_state->getValues();

    if ($this->entity instanceof EntityWithPluginCollectionInterface) {
      // Do not manually update values represented by plugin collections.
      $form_values = array_diff_key($form_values, $this->entity->getPluginCollections());
    }

    // Collect data for 'regular' fields.
    foreach ($form['#fields'] as $field_name) {
      $values = $form_values['fields'][$field_name];

      if ($values['type'] == 'hidden') {
        $entity->removeComponent($field_name);
      }
      else {
        $options = $entity->getComponent($field_name);

        // Update field settings only if the submit handler told us to.
        if ($form_state->get('plugin_settings_update') === $field_name) {
          // Only store settings actually used by the selected plugin.
          $default_settings = $this->pluginManager->getDefaultSettings($options['type']);
          $options['settings'] = isset($values['settings_edit_form']['settings']) ? array_intersect_key($values['settings_edit_form']['settings'], $default_settings) : [];
          $options['third_party_settings'] = isset($values['settings_edit_form']['third_party_settings']) ? $values['settings_edit_form']['third_party_settings'] : [];
          $form_state->set('plugin_settings_update', NULL);
        }

        $options['type'] = $values['type'];
        $options['weight'] = $values['weight'];
        // Only formatters have configurable label visibility.
        if (isset($values['label'])) {
          $options['label'] = $values['label'];
        }
        $entity->setComponent($field_name, $options);
      }
    }

    // Collect data for 'extra' fields.
    foreach ($form['#extra'] as $name) {
      if ($form_values['fields'][$name]['type'] == 'hidden') {
        $entity->removeComponent($name);
      }
      else {
        $entity->setComponent($name, array(
          'weight' => $form_values['fields'][$name]['weight'],
        ));
      }
    }
  }

  /**
   * Form submission handler for multistep buttons.
   */
  public function multistepSubmit($form, FormStateInterface $form_state) {
    $trigger = $form_state->getTriggeringElement();
    $op = $trigger['#op'];

    switch ($op) {
      case 'edit':
        // Store the field whose settings are currently being edited.
        $field_name = $trigger['#field_name'];
        $form_state->set('plugin_settings_edit', $field_name);
        break;

      case 'update':
        // Set the field back to 'non edit' mode, and update $this->entity with
        // the new settings fro the next rebuild.
        $field_name = $trigger['#field_name'];
        $form_state->set('plugin_settings_edit', NULL);
        $form_state->set('plugin_settings_update', $field_name);
        $this->entity = $this->buildEntity($form, $form_state);
        break;

      case 'cancel':
        // Set the field back to 'non edit' mode.
        $form_state->set('plugin_settings_edit', NULL);
        break;

      case 'refresh_table':
        // If the currently edited field is one of the rows to be refreshed, set
        // it back to 'non edit' mode.
        $updated_rows = explode(' ', $form_state->getValue('refresh_rows'));
        $plugin_settings_edit = $form_state->get('plugin_settings_edit');
        if ($plugin_settings_edit && in_array($plugin_settings_edit, $updated_rows)) {
          $form_state->set('plugin_settings_edit', NULL);
        }
        break;
    }

    $form_state->setRebuild();
  }

  /**
   * Ajax handler for multistep buttons.
   */
  public function multistepAjax($form, FormStateInterface $form_state) {
    $trigger = $form_state->getTriggeringElement();
    $op = $trigger['#op'];

    // Pick the elements that need to receive the ajax-new-content effect.
    switch ($op) {
      case 'edit':
        $updated_rows = array($trigger['#field_name']);
        $updated_columns = array('plugin');
        break;

      case 'update':
      case 'cancel':
        $updated_rows = array($trigger['#field_name']);
        $updated_columns = array('plugin', 'settings_summary', 'settings_edit');
        break;

      case 'refresh_table':
        $updated_rows = array_values(explode(' ', $form_state->getValue('refresh_rows')));
        $updated_columns = array('settings_summary', 'settings_edit');
        break;
    }

    foreach ($updated_rows as $name) {
      foreach ($updated_columns as $key) {
        $element = &$form['fields'][$name][$key];
        $element['#prefix'] = '<div class="ajax-new-content">' . (isset($element['#prefix']) ? $element['#prefix'] : '');
        $element['#suffix'] = (isset($element['#suffix']) ? $element['#suffix'] : '') . '</div>';
      }
    }

    // Return the whole table.
    return $form['fields'];
  }

  /**
   * Performs pre-render tasks on field_ui_table elements.
   *
   * @param array $elements
   *   A structured array containing two sub-levels of elements. Properties
   *   used:
   *   - #tabledrag: The value is a list of $options arrays that are passed to
   *     drupal_attach_tabledrag(). The HTML ID of the table is added to each
   *     $options array.
   *
   * @return array
   *
   * @see drupal_render()
   * @see \Drupal\Core\Render\Element\Table::preRenderTable()
   *
   * @deprecated in Drupal 8.0.0, will be removed before Drupal 9.0.0.
   */
  public function tablePreRender($elements) {
    return FieldUiTable::tablePreRender($elements);
  }

  /**
   * Determines the rendering order of an array representing a tree.
   *
   * Callback for array_reduce() within
   * \Drupal\field_ui\Form\EntityDisplayFormBase::tablePreRender().
   *
   * @deprecated in Drupal 8.0.0, will be removed before Drupal 9.0.0.
   */
  public function reduceOrder($array, $a) {
    return FieldUiTable::reduceOrder($array, $a);
  }

  /**
   * Returns the extra fields of the entity type and bundle used by this form.
   *
   * @return array
   *   An array of extra field info.
   *
   * @see \Drupal\Core\Entity\EntityManagerInterface::getExtraFields()
   */
  protected function getExtraFields() {
    $context = $this->displayContext == 'view' ? 'display' : $this->displayContext;
    $extra_fields = $this->entityManager->getExtraFields($this->entity->getTargetEntityTypeId(), $this->entity->getTargetBundle());
    return isset($extra_fields[$context]) ? $extra_fields[$context] : array();
  }

  /**
   * Returns an entity display object to be used by this form.
   *
   * @param string $entity_type_id
   *   The target entity type ID of the entity display.
   * @param string $bundle
   *   The target bundle of the entity display.
   * @param string $mode
   *   A view or form mode.
   *
   * @return \Drupal\Core\Entity\Display\EntityDisplayInterface
   *   An entity display.
   */
  abstract protected function getEntityDisplay($entity_type_id, $bundle, $mode);

  /**
   * Returns an array of applicable widget or formatter options for a field.
   *
   * @param \Drupal\Core\Field\FieldDefinitionInterface $field_definition
   *   The field definition.
   *
   * @return array
   *   An array of applicable widget or formatter options.
   */
  protected function getApplicablePluginOptions(FieldDefinitionInterface $field_definition) {
    $options = $this->pluginManager->getOptions($field_definition->getType());
    $applicable_options = array();
    foreach ($options as $option => $label) {
      $plugin_class = DefaultFactory::getPluginClass($option, $this->pluginManager->getDefinition($option));
      if ($plugin_class::isApplicable($field_definition)) {
        $applicable_options[$option] = $label;
      }
    }
    return $applicable_options;
  }

  /**
   * Returns an array of widget or formatter options for a field.
   *
   * @param \Drupal\Core\Field\FieldDefinitionInterface $field_definition
   *   The field definition.
   *
   * @return array
   *   An array of widget or formatter options.
   */
  protected function getPluginOptions(FieldDefinitionInterface $field_definition) {
    $applicable_options = $this->getApplicablePluginOptions($field_definition);
    return $applicable_options + array('hidden' => '- ' . $this->t('Hidden') . ' -');
  }

  /**
   * Returns the ID of the default widget or formatter plugin for a field type.
   *
   * @param string $field_type
   *   The field type.
   *
   * @return string
   *   The widget or formatter plugin ID.
   */
  abstract protected function getDefaultPlugin($field_type);

  /**
   * Returns the form or view modes used by this form.
   *
   * @return array
   *   An array of form or view mode info.
   */
  abstract protected function getDisplayModes();

  /**
   * Returns an array of form or view mode options.
   *
   * @return array
   *   An array of form or view mode options.
   */
  abstract protected function getDisplayModeOptions();

  /**
   * Returns the region to which a row in the display overview belongs.
   *
   * @param array $row
   *   The row element.
   *
   * @return string|null
   *   The region name this row belongs to.
   */
  public function getRowRegion($row) {
    switch ($row['#row_type']) {
      case 'field':
      case 'extra_field':
        return ($row['plugin']['type']['#value'] == 'hidden' ? 'hidden' : 'content');
    }
  }

  /**
   * Returns an array of visibility options for extra fields.
   *
   * @return array
   *   An array of visibility options.
   */
  protected function getExtraFieldVisibilityOptions() {
    return array(
      'visible' => $this->t('Visible'),
      'hidden' => '- ' . $this->t('Hidden') . ' -',
    );
  }

  /**
   * Returns entity (form) displays for the current entity display type.
   *
   * @return \Drupal\Core\Entity\Display\EntityDisplayInterface[]
   *   An array holding entity displays or entity form displays.
   */
  protected function getDisplays() {
    $load_ids = array();
    $display_entity_type = $this->entity->getEntityTypeId();
    $entity_type = $this->entityManager->getDefinition($display_entity_type);
    $config_prefix = $entity_type->getConfigPrefix();
    $ids = $this->configFactory()->listAll($config_prefix . '.' . $this->entity->getTargetEntityTypeId() . '.' . $this->entity->getTargetBundle() . '.');
    foreach ($ids as $id) {
      $config_id = str_replace($config_prefix . '.', '', $id);
      list(,, $display_mode) = explode('.', $config_id);
      if ($display_mode != 'default') {
        $load_ids[] = $config_id;
      }
    }
    return $this->entityManager->getStorage($display_entity_type)->loadMultiple($load_ids);
  }

  /**
   * Returns form or view modes statuses for the bundle used by this form.
   *
   * @return array
   *   An array of form or view mode statuses.
   */
  protected function getDisplayStatuses() {
    $display_statuses = array();
    $displays = $this->getDisplays();
    foreach ($displays as $display) {
      $display_statuses[$display->get('mode')] = $display->status();
    }
    return $display_statuses;
  }

  /**
   * Saves the updated display mode statuses.
   *
   * @param array $display_statuses
   *   An array holding updated form or view mode statuses.
   */
  protected function saveDisplayStatuses($display_statuses) {
    $displays = $this->getDisplays();
    foreach ($displays as $display) {
      $display->set('status', $display_statuses[$display->get('mode')]);
      $display->save();
    }
  }

  /**
   * Returns an array containing the table headers.
   *
   * @return array
   *   The table header.
   */
  abstract protected function getTableHeader();

  /**
   * Returns the Url object for a specific entity (form) display edit form.
   *
   * @param string $mode
   *   The form or view mode.
   *
   * @return \Drupal\Core\Url
   *   A Url object for the overview route.
   */
  abstract protected function getOverviewUrl($mode);

  /**
   * Adds the widget or formatter third party settings forms.
   *
   * @param \Drupal\Core\Field\PluginSettingsInterface $plugin
   *   The widget or formatter.
   * @param \Drupal\Core\Field\FieldDefinitionInterface $field_definition
   *   The field definition.
   * @param array $form
   *   The (entire) configuration form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   *
   * @return array
   *   The widget or formatter third party settings form.
   */
  abstract protected function thirdPartySettingsForm(PluginSettingsInterface $plugin, FieldDefinitionInterface $field_definition, array $form, FormStateInterface $form_state);

  /**
   * Alters the widget or formatter settings summary.
   *
   * @param array $summary
   *   The widget or formatter settings summary.
   * @param \Drupal\Core\Field\PluginSettingsInterface $plugin
   *   The widget or formatter.
   * @param \Drupal\Core\Field\FieldDefinitionInterface $field_definition
   *   The field definition.
   */
  abstract protected function alterSettingsSummary(array &$summary, PluginSettingsInterface $plugin, FieldDefinitionInterface $field_definition);

}
