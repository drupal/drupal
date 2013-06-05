<?php

/**
 * @file
 * Definition of Drupal\field\Plugin\views\field\Field.
 */

namespace Drupal\field\Plugin\views\field;

use Drupal\Core\Language\Language;
use Drupal\Core\Entity\EntityInterface;
use Drupal\views\ViewExecutable;
use Drupal\views\Plugin\views\display\DisplayPluginBase;
use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\Component\Annotation\PluginID;
use Drupal\views\Views;

/**
 * A field that displays fieldapi fields.
 *
 * @ingroup views_field_handlers
 *
 * @PluginID("field")
 */
class Field extends FieldPluginBase {

  /**
   * An array to store field renderable arrays for use by render_items.
   *
   * @var array
   */
  public $items = array();

  /**
   * Store the field information.
   *
   * @var array
   */
  public $field_info = array();


  /**
   * Does the field supports multiple field values.
   *
   * @var bool
   */
  public $multiple;

  /**
   * Does the rendered fields get limited.
   *
   * @var bool
   */
  public $limit_values;

  /**
   * A shortcut for $view->base_table.
   *
   * @var string
   */
  public $base_table;

  /**
   * Store the field instance.
   *
   * @var array
   */
  public $instance;

  /**
   * An array of formatter options.
   *
   * @var array
   */
  protected $formatterOptions;

  /**
   * Overrides \Drupal\views\Plugin\views\field\FieldPluginBase::init().
   */
  public function init(ViewExecutable $view, DisplayPluginBase $display, array &$options = NULL) {
    parent::init($view, $display, $options);

    $this->field_info = $field = field_info_field($this->definition['field_name']);
    $this->multiple = FALSE;
    $this->limit_values = FALSE;

    if ($field['cardinality'] > 1 || $field['cardinality'] == FIELD_CARDINALITY_UNLIMITED) {
      $this->multiple = TRUE;

      // If "Display all values in the same row" is FALSE, then we always limit
      // in order to show a single unique value per row.
      if (!$this->options['group_rows']) {
        $this->limit_values = TRUE;
      }

      // If "First and last only" is chosen, limit the values
      if (!empty($this->options['delta_first_last'])) {
        $this->limit_values = TRUE;
      }

      // Otherwise, we only limit values if the user hasn't selected "all", 0, or
      // the value matching field cardinality.
      if ((intval($this->options['delta_limit']) && ($this->options['delta_limit'] != $field['cardinality'])) || intval($this->options['delta_offset'])) {
        $this->limit_values = TRUE;
      }
    }
  }

  /**
   * Check whether current user has access to this handler.
   *
   * @return bool
   *   Return TRUE if the user has access to view this field.
   */
  public function access() {
    $base_table = $this->get_base_table();
    return field_access('view', $this->field_info, $this->definition['entity_tables'][$base_table]);
  }

  /**
   * Set the base_table and base_table_alias.
   *
   * @return string
   *   The base table which is used in the current view "context".
   */
  function get_base_table() {
    if (!isset($this->base_table)) {
      // This base_table is coming from the entity not the field.
      $this->base_table = $this->view->storage->get('base_table');

      // If the current field is under a relationship you can't be sure that the
      // base table of the view is the base table of the current field.
      // For example a field from a node author on a node view does have users as base table.
      if (!empty($this->options['relationship']) && $this->options['relationship'] != 'none') {
        $relationships = $this->view->display_handler->getOption('relationships');
        if (!empty($relationships[$this->options['relationship']])) {
          $options = $relationships[$this->options['relationship']];
          $data = Views::viewsData()->get($options['table']);
          $this->base_table = $data[$options['field']]['relationship']['base'];
        }
      }
    }

    return $this->base_table;
  }

  /**
   * Called to add the field to a query.
   *
   * By default, all needed data is taken from entities loaded by the query
   * plugin. Columns are added only if they are used in groupings.
   */
  public function query($use_groupby = FALSE) {
    $this->get_base_table();

    $entity_type = $this->definition['entity_tables'][$this->base_table];
    $fields = $this->additional_fields;
    // No need to add the entity type.
    $entity_type_key = array_search('entity_type', $fields);
    if ($entity_type_key !== FALSE) {
      unset($fields[$entity_type_key]);
    }

    if ($use_groupby) {
      // Add the fields that we're actually grouping on.
      $options = array();
      if ($this->options['group_column'] != 'entity_id') {
        $options = array($this->options['group_column'] => $this->options['group_column']);
      }
      $options += is_array($this->options['group_columns']) ? $this->options['group_columns'] : array();

      $fields = array();
      $rkey = $this->definition['is revision'] ? 'FIELD_LOAD_REVISION' : 'FIELD_LOAD_CURRENT';
      // Go through the list and determine the actual column name from field api.
      foreach ($options as $column) {
        $name = $column;
        if (isset($this->field_info['storage_details']['sql'][$rkey][$this->table][$column])) {
          $name = $this->field_info['storage_details']['sql'][$rkey][$this->table][$column];
        }

        $fields[$column] = $name;
      }

      $this->group_fields = $fields;
    }

    // Add additional fields (and the table join itself) if needed.
    if ($this->add_field_table($use_groupby)) {
      $this->ensureMyTable();
      $this->addAdditionalFields($fields);

      // Filter by langcode, if field translation is enabled.
      $field = $this->field_info;
      if (field_is_translatable($entity_type, $field) && !empty($this->view->display_handler->options['field_langcode_add_to_query'])) {
        $column = $this->tableAlias . '.langcode';
        // By the same reason as field_language the field might be Language::LANGCODE_NOT_SPECIFIED in reality so allow it as well.
        // @see this::field_langcode()
        $default_langcode = language_default()->langcode;
        $langcode = str_replace(array('***CURRENT_LANGUAGE***', '***DEFAULT_LANGUAGE***'),
                                array(drupal_container()->get(Language::TYPE_CONTENT)->langcode, $default_langcode),
                                $this->view->display_handler->options['field_langcode']);
        $placeholder = $this->placeholder();
        $langcode_fallback_candidates = array($langcode);
        if (field_language_fallback_enabled()) {
          require_once DRUPAL_ROOT . '/includes/language.inc';
          $langcode_fallback_candidates = array_merge($langcode_fallback_candidates, language_fallback_get_candidates());
        }
        else {
          $langcode_fallback_candidates[] = Language::LANGCODE_NOT_SPECIFIED;
        }
        $this->query->add_where_expression(0, "$column IN($placeholder) OR $column IS NULL", array($placeholder => $langcode_fallback_candidates));
      }
    }
  }

  /**
   * Determine if the field table should be added to the query.
   */
  function add_field_table($use_groupby) {
    // Grouping is enabled.
    if ($use_groupby) {
      return TRUE;
    }
    // This a multiple value field, but "group multiple values" is not checked.
    if ($this->multiple && !$this->options['group_rows']) {
      return TRUE;
    }
    return FALSE;
  }

  /**
   * Determine if this field is click sortable.
   */
  function click_sortable() {
    // Not click sortable in any case.
    if (empty($this->definition['click sortable'])) {
      return FALSE;
    }
    // A field is not click sortable if it's a multiple field with
    // "group multiple values" checked, since a click sort in that case would
    // add a join to the field table, which would produce unwanted duplicates.
    if ($this->multiple && $this->options['group_rows']) {
      return FALSE;
    }
    return TRUE;
  }

  /**
   * Called to determine what to tell the clicksorter.
   */
  public function clickSort($order) {
    // No column selected, can't continue.
    if (empty($this->options['click_sort_column'])) {
      return;
    }

    $this->ensureMyTable();
    $column = _field_sql_storage_columnname($this->definition['field_name'], $this->options['click_sort_column']);
    if (!isset($this->aliases[$column])) {
      // Column is not in query; add a sort on it (without adding the column).
      $this->aliases[$column] = $this->tableAlias . '.' . $column;
    }
    $this->query->add_orderby(NULL, NULL, $order, $this->aliases[$column]);
  }

  protected function defineOptions() {
    $options = parent::defineOptions();

    // defineOptions runs before init/construct, so no $this->field_info
    $field = field_info_field($this->definition['field_name']);
    $field_type = field_info_field_types($field['type']);
    $column_names = array_keys($field['columns']);
    $default_column = '';
    // Try to determine a sensible default.
    if (count($column_names) == 1) {
      $default_column = $column_names[0];
    }
    elseif (in_array('value', $column_names)) {
      $default_column = 'value';
    }

    // If the field has a "value" column, we probably need that one.
    $options['click_sort_column'] = array(
      'default' => $default_column,
    );
    $options['type'] = array(
      'default' => $field_type['default_formatter'],
    );
    $options['settings'] = array(
      'default' => array(),
    );
    $options['group_column'] = array(
      'default' => $default_column,
    );
    $options['group_columns'] = array(
      'default' => array(),
    );

    // Options used for multiple value fields.
    $options['group_rows'] = array(
      'default' => TRUE,
      'bool' => TRUE,
    );
    // If we know the exact number of allowed values, then that can be
    // the default. Otherwise, default to 'all'.
    $options['delta_limit'] = array(
      'default' => ($field['cardinality'] > 1) ? $field['cardinality'] : 'all',
    );
    $options['delta_offset'] = array(
      'default' => 0,
    );
    $options['delta_reversed'] = array(
      'default' => FALSE,
      'bool' => TRUE,
    );
    $options['delta_first_last'] = array(
      'default' => FALSE,
      'bool' => TRUE,
    );

    $options['multi_type'] = array(
      'default' => 'separator'
    );
    $options['separator'] = array(
      'default' => ', '
    );

    $options['field_api_classes'] = array(
      'default' => FALSE,
      'bool' => TRUE,
    );

    return $options;
  }

  public function buildOptionsForm(&$form, &$form_state) {
    parent::buildOptionsForm($form, $form_state);

    $field = $this->field_info;
    $formatters = $this->formatterOptions($field['type']);
    $column_names = array_keys($field['columns']);

    // If this is a multiple value field, add its options.
    if ($this->multiple) {
      $this->multiple_options_form($form, $form_state);
    }

    // No need to ask the user anything if the field has only one column.
    if (count($field['columns']) == 1) {
      $form['click_sort_column'] = array(
        '#type' => 'value',
        '#value' => isset($column_names[0]) ? $column_names[0] : '',
      );
    }
    else {
      $form['click_sort_column'] = array(
        '#type' => 'select',
        '#title' => t('Column used for click sorting'),
        '#options' => drupal_map_assoc($column_names),
        '#default_value' => $this->options['click_sort_column'],
        '#description' => t('Used by Style: Table to determine the actual column to click sort the field on. The default is usually fine.'),
        '#fieldset' => 'more',
      );
    }

    $form['type'] = array(
      '#type' => 'select',
      '#title' => t('Formatter'),
      '#options' => $formatters,
      '#default_value' => $this->options['type'],
      '#ajax' => array(
        'path' => views_ui_build_form_url($form_state),
      ),
      '#submit' => array(array($this, 'submitTemporaryForm')),
      '#executes_submit_callback' => TRUE,
    );

    $form['field_api_classes'] = array(
      '#title' => t('Use field template'),
      '#type' => 'checkbox',
      '#default_value' => $this->options['field_api_classes'],
      '#description' => t('If checked, field api classes will be added by field templates. This is not recommended unless your CSS depends upon these classes. If not checked, template will not be used.'),
      '#fieldset' => 'style_settings',
      '#weight' => 20,
    );

    if ($this->multiple) {
      $form['field_api_classes']['#description'] .= ' ' . t('Checking this option will cause the group Display Type and Separator values to be ignored.');
    }

    // Get the currently selected formatter.
    $format = $this->options['type'];

    $settings = $this->options['settings'] + field_info_formatter_settings($format);

    // Provide an instance array for hook_field_formatter_settings_form().
    $this->instance = $this->fakeFieldInstance($format, $settings);

    $options = array(
      'instance' => $this->instance,
      'configuration' => array(
        'type' => $format,
        'settings' => $settings,
        'label' => '',
        'weight' => 0,
      ),
      'view_mode' => '_custom',
    );

    // Get the settings form.
    $settings_form = array('#value' => array());
    if ($formatter = drupal_container()->get('plugin.manager.field.formatter')->getInstance($options)) {
      $settings_form = $formatter->settingsForm($form, $form_state);
    }
    $form['settings'] = $settings_form;
  }

  /**
   * Provides a fake field instance.
   *
   * @param string $formatter
   *   The machine name of the formatter to use.
   * @param array $formatter_settings
   *   An associative array of settings for the formatter.
   *
   * @return array
   *   An associative array of instance date for the fake field.
   *
   * @see field_info_instance()
   */
  function fakeFieldInstance($formatter, $formatter_settings) {
    $field_name = $this->definition['field_name'];
    $field = field_read_field($field_name);

    $field_type = field_info_field_types($field['type']);

    return array(
      // Build a fake entity type and bundle.
      'field_name' => $field_name,
      'entity_type' => 'views_fake',
      'bundle' => 'views_fake',

      // Use the default field settings.
      'settings' => field_info_instance_settings($field['type']),

      // Set the other fields to their default values.
      'required' => FALSE,
      'label' => $field_name,
      'description' => '',
      'deleted' => FALSE,
    );
  }

  /**
   * Provide options for multiple value fields.
   */
  function multiple_options_form(&$form, &$form_state) {
    $field = $this->field_info;

    $form['multiple_field_settings'] = array(
      '#type' => 'details',
      '#title' => t('Multiple field settings'),
      '#collapsed' => TRUE,
      '#weight' => 5,
    );

    $form['group_rows'] = array(
      '#title' => t('Display all values in the same row'),
      '#type' => 'checkbox',
      '#default_value' => $this->options['group_rows'],
      '#description' => t('If checked, multiple values for this field will be shown in the same row. If not checked, each value in this field will create a new row. If using group by, please make sure to group by "Entity ID" for this setting to have any effect.'),
      '#fieldset' => 'multiple_field_settings',
    );

    // Make the string translatable by keeping it as a whole rather than
    // translating prefix and suffix separately.
    list($prefix, $suffix) = explode('@count', t('Display @count value(s)'));

    if ($field['cardinality'] == FIELD_CARDINALITY_UNLIMITED) {
      $type = 'textfield';
      $options = NULL;
      $size = 5;
    }
    else {
      $type = 'select';
      $options = drupal_map_assoc(range(1, $field['cardinality']));
      $size = 1;
    }
    $form['multi_type'] = array(
      '#type' => 'radios',
      '#title' => t('Display type'),
      '#options' => array(
        'ul' => t('Unordered list'),
        'ol' => t('Ordered list'),
        'separator' => t('Simple separator'),
      ),
      '#states' => array(
        'visible' => array(
          ':input[name="options[group_rows]"]' => array('checked' => TRUE),
        ),
      ),
      '#default_value' => $this->options['multi_type'],
      '#fieldset' => 'multiple_field_settings',
    );

    $form['separator'] = array(
      '#type' => 'textfield',
      '#title' => t('Separator'),
      '#default_value' => $this->options['separator'],
      '#states' => array(
        'visible' => array(
          ':input[name="options[group_rows]"]' => array('checked' => TRUE),
          ':input[name="options[multi_type]"]' => array('value' => 'separator'),
        ),
      ),
      '#fieldset' => 'multiple_field_settings',
    );

    $form['delta_limit'] = array(
      '#type' => $type,
      '#size' => $size,
      '#field_prefix' => $prefix,
      '#field_suffix' => $suffix,
      '#options' => $options,
      '#default_value' => $this->options['delta_limit'],
      '#prefix' => '<div class="container-inline">',
      '#states' => array(
        'visible' => array(
          ':input[name="options[group_rows]"]' => array('checked' => TRUE),
        ),
      ),
      '#fieldset' => 'multiple_field_settings',
    );

    list($prefix, $suffix) = explode('@count', t('starting from @count'));
    $form['delta_offset'] = array(
      '#type' => 'textfield',
      '#size' => 5,
      '#field_prefix' => $prefix,
      '#field_suffix' => $suffix,
      '#default_value' => $this->options['delta_offset'],
      '#states' => array(
        'visible' => array(
          ':input[name="options[group_rows]"]' => array('checked' => TRUE),
        ),
      ),
      '#description' => t('(first item is 0)'),
      '#fieldset' => 'multiple_field_settings',
    );
    $form['delta_reversed'] = array(
      '#title' => t('Reversed'),
      '#type' => 'checkbox',
      '#default_value' => $this->options['delta_reversed'],
      '#suffix' => $suffix,
      '#states' => array(
        'visible' => array(
          ':input[name="options[group_rows]"]' => array('checked' => TRUE),
        ),
      ),
      '#description' => t('(start from last values)'),
      '#fieldset' => 'multiple_field_settings',
    );
    $form['delta_first_last'] = array(
      '#title' => t('First and last only'),
      '#type' => 'checkbox',
      '#default_value' => $this->options['delta_first_last'],
      '#suffix' => '</div>',
      '#states' => array(
        'visible' => array(
          ':input[name="options[group_rows]"]' => array('checked' => TRUE),
        ),
      ),
      '#fieldset' => 'multiple_field_settings',
    );
  }

  /**
   * Extend the groupby form with group columns.
   */
  public function buildGroupByForm(&$form, &$form_state) {
    parent::buildGroupByForm($form, $form_state);
    // With "field API" fields, the column target of the grouping function
    // and any additional grouping columns must be specified.
    $group_columns = array(
      'entity_id' => t('Entity ID'),
    ) + drupal_map_assoc(array_keys($this->field_info['columns']), 'ucfirst');

    $form['group_column'] = array(
      '#type' => 'select',
      '#title' => t('Group column'),
      '#default_value' => $this->options['group_column'],
      '#description' => t('Select the column of this field to apply the grouping function selected above.'),
      '#options' => $group_columns,
    );

    $options = drupal_map_assoc(array('bundle', 'language', 'entity_type'), 'ucfirst');

    // Add on defined fields, noting that they're prefixed with the field name.
    $form['group_columns'] = array(
      '#type' => 'checkboxes',
      '#title' => t('Group columns (additional)'),
      '#default_value' => $this->options['group_columns'],
      '#description' => t('Select any additional columns of this field to include in the query and to group on.'),
      '#options' => $options + $group_columns,
    );
  }

  public function submitGroupByForm(&$form, &$form_state) {
    parent::submitGroupByForm($form, $form_state);
    $item = &$form_state['handler']->options;

    // Add settings for "field API" fields.
    $item['group_column'] = $form_state['values']['options']['group_column'];
    $item['group_columns'] = array_filter($form_state['values']['options']['group_columns']);
  }

  /**
   * Render all items in this field together.
   *
   * When using advanced render, each possible item in the list is rendered
   * individually. Then the items are all pasted together.
   */
  function render_items($items) {
    if (!empty($items)) {
      if (!$this->options['group_rows']) {
        return implode('', $items);
      }

      if ($this->options['multi_type'] == 'separator') {
        return implode(filter_xss_admin($this->options['separator']), $items);
      }
      else {
        return theme('item_list',
          array(
            'items' => $items,
            'title' => NULL,
            'type' => $this->options['multi_type']
          ));
      }
    }
  }

  /**
   * Return an array of items for the field.
   */
  function get_items($values) {
    $original_entity = $this->get_entity($values);
    if (!$original_entity) {
      return array();
    }
    $entity = $this->process_entity($original_entity);
    if (!$entity) {
      return array();
    }

    $display = array(
      'type' => $this->options['type'],
      'settings' => $this->options['settings'],
      'label' => 'hidden',
      // Pass the View object in the display so that fields can act on it.
      'views_view' => $this->view,
      'views_field' => $this,
      'views_row_id' => $this->view->row_index,
    );

    $langcode = $this->field_langcode($entity);
    $render_array = field_view_field($entity, $this->definition['field_name'], $display, $langcode);

    $items = array();
    if ($this->options['field_api_classes']) {
      // Make a copy.
      $array = $render_array;
      return array(array('rendered' => drupal_render($render_array)));
    }

    foreach (element_children($render_array) as $count) {
      $items[$count]['rendered'] = $render_array[$count];
      // field_view_field() adds an #access property to the render array that
      // determines whether or not the current user is allowed to view the
      // field in the context of the current entity. We need to respect this
      // parameter when we pull out the children of the field array for
      // rendering.
      if (isset($render_array['#access'])) {
        $items[$count]['rendered']['#access'] = $render_array['#access'];
      }
      // Only add the raw field items (for use in tokens) if the current user
      // has access to view the field content.
      if ((!isset($items[$count]['rendered']['#access']) || $items[$count]['rendered']['#access']) && !empty($render_array['#items'][$count])) {
        $items[$count]['raw'] = $render_array['#items'][$count];
      }
    }
    return $items;
  }

  /**
   * Process an entity before using it for rendering.
   *
   * Replaces values with aggregated values if aggregation is enabled.
   * Takes delta settings into account (@todo remove in #1758616).
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity to be processed.
   *
   * @return
   *   TRUE if the processing completed successfully, otherwise FALSE.
   */
  function process_entity(EntityInterface $entity) {
    $processed_entity = clone $entity;
    $langcode = $this->field_langcode($processed_entity);
    // If we are grouping, copy our group fields into the cloned entity.
    // It's possible this will cause some weirdness, but there's only
    // so much we can hope to do.
    if (!empty($this->group_fields)) {
      // first, test to see if we have a base value.
      $base_value = array();
      // Note: We would copy original values here, but it can cause problems.
      // For example, text fields store cached filtered values as
      // 'safe_value' which doesn't appear anywhere in the field definition
      // so we can't affect it. Other side effects could happen similarly.
      $data = FALSE;
      foreach ($this->group_fields as $field_name => $column) {
        if (property_exists($values, $this->aliases[$column])) {
          $base_value[$field_name] = $values->{$this->aliases[$column]};
          if (isset($base_value[$field_name])) {
            $data = TRUE;
          }
        }
      }

      // If any of our aggregated fields have data, fake it:
      if ($data) {
        // Now, overwrite the original value with our aggregated value.
        // This overwrites it so there is always just one entry.
        $processed_entity->{$this->definition['field_name']}[$langcode] = array($base_value);
      }
      else {
        $processed_entity->{$this->definition['field_name']}[$langcode] = array();
      }
    }

    // The field we are trying to display doesn't exist on this entity.
    if (!isset($processed_entity->{$this->definition['field_name']})) {
      return FALSE;
    }

    // We are supposed to show only certain deltas.
    if ($this->limit_values && !empty($processed_entity->{$this->definition['field_name']})) {
      $all_values = !empty($processed_entity->{$this->definition['field_name']}[$langcode]) ? $processed_entity->{$this->definition['field_name']}[$langcode] : array();
      if ($this->options['delta_reversed']) {
        $all_values = array_reverse($all_values);
      }

      // Offset is calculated differently when row grouping for a field is
      // not enabled. Since there are multiple rows, the delta needs to be
      // taken into account, so that different values are shown per row.
      if (!$this->options['group_rows'] && isset($this->aliases['delta']) && isset($values->{$this->aliases['delta']})) {
        $delta_limit = 1;
        $offset = $values->{$this->aliases['delta']};
      }
      // Single fields don't have a delta available so choose 0.
      elseif (!$this->options['group_rows'] && !$this->multiple) {
        $delta_limit = 1;
        $offset = 0;
      }
      else {
        $delta_limit = $this->options['delta_limit'];
        $offset = intval($this->options['delta_offset']);

        // We should only get here in this case if there's an offset, and
        // in that case we're limiting to all values after the offset.
        if ($delta_limit == 'all') {
          $delta_limit = count($all_values) - $offset;
        }
      }

      // Determine if only the first and last values should be shown
      $delta_first_last = $this->options['delta_first_last'];

      $new_values = array();
      for ($i = 0; $i < $delta_limit; $i++) {
        $new_delta = $offset + $i;

        if (isset($all_values[$new_delta])) {
          // If first-last option was selected, only use the first and last values
          if (!$delta_first_last
            // Use the first value.
            || $new_delta == $offset
            // Use the last value.
            || $new_delta == ($delta_limit + $offset - 1)) {
            $new_values[] = $all_values[$new_delta];
          }
        }
      }
      $processed_entity->{$this->definition['field_name']}[$langcode] = $new_values;
    }

    return $processed_entity;
  }

  function render_item($count, $item) {
    return render($item['rendered']);
  }

  protected function documentSelfTokens(&$tokens) {
    $field = $this->field_info;
    foreach ($field['columns'] as $id => $column) {
      $tokens['[' . $this->options['id'] . '-' . $id . ']'] = t('Raw @column', array('@column' => $id));
    }
  }

  protected function addSelfTokens(&$tokens, $item) {
    $field = $this->field_info;
    foreach ($field['columns'] as $id => $column) {
      // Use filter_xss_admin because it's user data and we can't be sure it is safe.
      // We know nothing about the data, though, so we can't really do much else.

      if (isset($item['raw'])) {
        // If $item['raw'] is an array then we can use as is, if it's an object
        // we cast it to an array, if it's neither, we can't use it.
        $raw = is_array($item['raw']) ? $item['raw'] :
               (is_object($item['raw']) ? (array)$item['raw'] : NULL);
      }
      if (isset($raw) && isset($raw[$id]) && is_scalar($raw[$id])) {
        $tokens['[' . $this->options['id'] . '-' . $id . ']'] = filter_xss_admin($raw[$id]);
      }
      else {
        // Take sure that empty values are replaced as well.
        $tokens['[' . $this->options['id'] . '-' . $id . ']'] = '';
      }
    }
  }

  /**
   * Return the language code of the language the field should be displayed in,
   * according to the settings.
   */
  function field_langcode(EntityInterface $entity) {
    if (field_is_translatable($entity->entityType(), $this->field_info)) {
      $default_langcode = language_default()->langcode;
      $langcode = str_replace(array('***CURRENT_LANGUAGE***', '***DEFAULT_LANGUAGE***'),
                              array(drupal_container()->get(Language::TYPE_CONTENT)->langcode, $default_langcode),
                              $this->view->display_handler->options['field_language']);

      // Give the Field Language API a chance to fallback to a different language
      // (or Language::LANGCODE_NOT_SPECIFIED), in case the field has no data for the selected language.
      // field_view_field() does this as well, but since the returned language code
      // is used before calling it, the fallback needs to happen explicitly.
      $langcode = field_language($entity, $this->field_info['field_name'], $langcode);

      return $langcode;
    }
    else {
      return Language::LANGCODE_NOT_SPECIFIED;
    }
  }

  /**
   * Returns an array of formatter options for a field type.
   *
   * Borrowed from field_ui.
   *
   * @param string $field_type
   *   (optional) The field type to get options for.
   *
   * @return array
   *   An array of formatter options.
   *
   * @see field_ui_formatter_options().
   */
  protected function formatterOptions($field_type = NULL) {
    if (!isset($this->formatterOptions)) {
      $field_types = field_info_field_types();
      $this->formatterOptions = array();
      foreach (field_info_formatter_types() as $name => $formatter) {
        foreach ($formatter['field_types'] as $formatter_field_type) {
          // Check that the field type exists.
          if (isset($field_types[$formatter_field_type])) {
            $this->formatterOptions[$formatter_field_type][$name] = $formatter['label'];
          }
        }
      }
    }

    if ($field_type) {
      return !empty($this->formatterOptions[$field_type]) ? $this->formatterOptions[$field_type] : array();
    }
    return $options;
  }

}
