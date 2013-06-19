<?php

/**
 * @file
 * Contains \Drupal\field_ui\DisplayOverview.
 */

namespace Drupal\field_ui;

use Drupal\field_ui\OverviewBase;
use Drupal\Core\Entity\EntityManager;
use Drupal\field\Plugin\Type\Formatter\FormatterPluginManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Field UI display overview form.
 */
class DisplayOverview extends OverviewBase {

  /**
   * The formatter plugin manager.
   *
   * @var \Drupal\field\Plugin\Type\Formatter\FormatterPluginManager
   */
  protected $formatterManager;

  /**
   * Constructs a new DisplayOverview.
   *
   * @param \Drupal\Core\Entity\EntityManager $entity_manager
   *   The entity manager.
   * @param \Drupal\field\Plugin\Type\Formatter\FormatterPluginManager $formatter_manager
   *   The formatter plugin manager.
   */
  public function __construct(EntityManager $entity_manager, FormatterPluginManager $formatter_manager) {
    parent::__construct($entity_manager);

    $this->formatterManager = $formatter_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('plugin.manager.entity'),
      $container->get('plugin.manager.field.formatter')
    );
  }

  /**
   * Implements Drupal\field_ui\OverviewBase::getRegions().
   */
  public function getRegions() {
    return array(
      'content' => array(
        'title' => t('Content'),
        'invisible' => TRUE,
        'message' => t('No field is displayed.')
      ),
      'hidden' => array(
        'title' => t('Disabled'),
        'message' => t('No field is hidden.')
      ),
    );
  }

  /**
   * Implements \Drupal\Core\Form\FormInterface::getFormID().
   */
  public function getFormID() {
    return 'field_ui_display_overview_form';
  }

  /**
   * Implements \Drupal\Core\Form\FormInterface::buildForm().
   */
  public function buildForm(array $form, array &$form_state, $entity_type = NULL, $bundle = NULL, $view_mode = NULL) {
    parent::buildForm($form, $form_state, $entity_type, $bundle);

    $this->mode = (isset($view_mode) ? $view_mode : 'default');
    // Gather type information.
    $instances = field_info_instances($this->entity_type, $this->bundle);
    $field_types = field_info_field_types();
    $extra_fields = field_info_extra_fields($this->entity_type, $this->bundle, 'display');
    $entity_display = entity_get_display($this->entity_type, $this->bundle, $this->mode);

    $form_state += array(
      'formatter_settings_edit' => NULL,
    );

    $form += array(
      '#entity_type' => $this->entity_type,
      '#bundle' => $this->bundle,
      '#view_mode' => $this->mode,
      '#fields' => array_keys($instances),
      '#extra' => array_keys($extra_fields),
    );

    if (empty($instances) && empty($extra_fields)) {
      drupal_set_message(t('There are no fields yet added. You can add new fields on the <a href="@link">Manage fields</a> page.', array('@link' => url($this->adminPath . '/fields'))), 'warning');
      return $form;
    }

    $table = array(
      '#type' => 'field_ui_table',
      '#pre_render' => array(array($this, 'tablePreRender')),
      '#tree' => TRUE,
      '#header' => array(
        t('Field'),
        t('Weight'),
        t('Parent'),
        t('Label'),
        array('data' => t('Format'), 'colspan' => 3),
      ),
      '#regions' => $this->getRegions(),
      '#parent_options' => drupal_map_assoc(array_keys($this->getRegions())),
      '#attributes' => array(
        'class' => array('field-ui-overview'),
        'id' => 'field-display-overview',
      ),
      // Add Ajax wrapper.
      '#prefix' => '<div id="field-display-overview-wrapper">',
      '#suffix' => '</div>',
    );

    $field_label_options = array(
      'above' => t('Above'),
      'inline' => t('Inline'),
      'hidden' => '<' . t('Hidden') . '>',
    );
    $extra_visibility_options = array(
      'visible' => t('Visible'),
      'hidden' => t('Hidden'),
    );

    // Field rows.
    foreach ($instances as $name => $instance) {
      $field = field_info_field($name);
      $display_options = $entity_display->getComponent($name);

      $table[$name] = array(
        '#attributes' => array('class' => array('draggable', 'tabledrag-leaf')),
        '#row_type' => 'field',
        '#region_callback' => array($this, 'getRowRegion'),
        '#js_settings' => array(
          'rowHandler' => 'field',
          'defaultFormatter' => $field_types[$field['type']]['default_formatter'],
        ),
        'human_name' => array(
          '#markup' => check_plain($instance['label']),
        ),
        'weight' => array(
          '#type' => 'textfield',
          '#title' => t('Weight for @title', array('@title' => $instance['label'])),
          '#title_display' => 'invisible',
          '#default_value' => $display_options ? $display_options['weight'] : '0',
          '#size' => 3,
          '#attributes' => array('class' => array('field-weight')),
        ),
        'parent_wrapper' => array(
          'parent' => array(
            '#type' => 'select',
            '#title' => t('Label display for @title', array('@title' => $instance['label'])),
            '#title_display' => 'invisible',
            '#options' => $table['#parent_options'],
            '#empty_value' => '',
            '#attributes' => array('class' => array('field-parent')),
            '#parents' => array('fields', $name, 'parent'),
          ),
          'hidden_name' => array(
            '#type' => 'hidden',
            '#default_value' => $name,
            '#attributes' => array('class' => array('field-name')),
          ),
        ),
        'label' => array(
          '#type' => 'select',
          '#title' => t('Label display for @title', array('@title' => $instance['label'])),
          '#title_display' => 'invisible',
          '#options' => $field_label_options,
          '#default_value' => $display_options ? $display_options['label'] : 'above',
        ),
      );

      $formatter_options = $this->formatterManager->getOptions($field['type']);
      $formatter_options['hidden'] = '<' . t('Hidden') . '>';
      $table[$name]['format'] = array(
        'type' => array(
          '#type' => 'select',
          '#title' => t('Formatter for @title', array('@title' => $instance['label'])),
          '#title_display' => 'invisible',
          '#options' => $formatter_options,
          '#default_value' => $display_options ? $display_options['type'] : 'hidden',
          '#parents' => array('fields', $name, 'type'),
          '#attributes' => array('class' => array('field-formatter-type')),
        ),
        'settings_edit_form' => array(),
      );

      // Check the currently selected formatter, and merge persisted values for
      // formatter settings.
      if (isset($form_state['values']['fields'][$name]['type'])) {
        $display_options['type'] = $form_state['values']['fields'][$name]['type'];
      }
      if (isset($form_state['formatter_settings'][$name])) {
        $display_options['settings'] = $form_state['formatter_settings'][$name];
      }

      // Get the corresponding formatter object.
      if ($display_options && $display_options['type'] != 'hidden') {
        $formatter = $this->formatterManager->getInstance(array(
          'field_definition' => $instance,
          'view_mode' => $this->mode,
          'configuration' => $display_options
        ));
      }
      else {
        $formatter = NULL;
      }

      // Base button element for the various formatter settings actions.
      $base_button = array(
        '#submit' => array(array($this, 'multistepSubmit')),
        '#ajax' => array(
          'callback' => array($this, 'multistepAjax'),
          'wrapper' => 'field-display-overview-wrapper',
          'effect' => 'fade',
        ),
        '#field_name' => $name,
      );

      if ($form_state['formatter_settings_edit'] == $name) {
        // We are currently editing this field's formatter settings. Display the
        // settings form and submit buttons.
        $table[$name]['format']['settings_edit_form'] = array();

        if ($formatter) {
          $formatter_type_info = $formatter->getPluginDefinition();

          // Generate the settings form and allow other modules to alter it.
          $settings_form = $formatter->settingsForm($form, $form_state);
          $context = array(
            'formatter' => $formatter,
            'field' => $field,
            'instance' => $instance,
            'view_mode' => $this->mode,
            'form' => $form,
          );
          drupal_alter('field_formatter_settings_form', $settings_form, $form_state, $context);

          if ($settings_form) {
            $table[$name]['format']['#cell_attributes'] = array('colspan' => 3);
            $table[$name]['format']['settings_edit_form'] = array(
              '#type' => 'container',
              '#attributes' => array('class' => array('field-formatter-settings-edit-form')),
              '#parents' => array('fields', $name, 'settings_edit_form'),
              'label' => array(
                '#markup' => t('Format settings:') . ' <span class="formatter-name">' . $formatter_type_info['label'] . '</span>',
              ),
              'settings' => $settings_form,
              'actions' => array(
                '#type' => 'actions',
                'save_settings' => $base_button + array(
                  '#type' => 'submit',
                  '#name' => $name . '_formatter_settings_update',
                  '#value' => t('Update'),
                  '#op' => 'update',
                ),
                'cancel_settings' => $base_button + array(
                  '#type' => 'submit',
                  '#name' => $name . '_formatter_settings_cancel',
                  '#value' => t('Cancel'),
                  '#op' => 'cancel',
                  // Do not check errors for the 'Cancel' button, but make sure we
                  // get the value of the 'formatter type' select.
                  '#limit_validation_errors' => array(array('fields', $name, 'type')),
                ),
              ),
            );
            $table[$name]['#attributes']['class'][] = 'field-formatter-settings-editing';
          }
        }
      }
      else {
        $table[$name]['settings_summary'] = array();
        $table[$name]['settings_edit'] = array();

        if ($formatter) {
          // Display a summary of the current formatter settings, and (if the
          // summary is not empty) a button to edit them.
          $summary = $formatter->settingsSummary();

          // Allow other modules to alter the summary.
          $context = array(
            'formatter' => $formatter,
            'field' => $field,
            'instance' => $instance,
            'view_mode' => $this->mode,
          );
          drupal_alter('field_formatter_settings_summary', $summary, $context);

          if (!empty($summary)) {
            $table[$name]['settings_summary'] = array(
              '#markup' => '<div class="field-formatter-summary">' . implode('<br />', $summary) . '</div>',
              '#cell_attributes' => array('class' => array('field-formatter-summary-cell')),
            );
            $table[$name]['settings_edit'] = $base_button + array(
              '#type' => 'image_button',
              '#name' => $name . '_formatter_settings_edit',
              '#src' => 'core/misc/configure-dark.png',
              '#attributes' => array('class' => array('field-formatter-settings-edit'), 'alt' => t('Edit')),
              '#op' => 'edit',
              // Do not check errors for the 'Edit' button, but make sure we get
              // the value of the 'formatter type' select.
              '#limit_validation_errors' => array(array('fields', $name, 'type')),
              '#prefix' => '<div class="field-formatter-settings-edit-wrapper">',
              '#suffix' => '</div>',
            );
          }
        }
      }
    }

    // Non-field elements.
    foreach ($extra_fields as $name => $extra_field) {
      $display_options = $entity_display->getComponent($name);

      $table[$name] = array(
        '#attributes' => array('class' => array('draggable', 'tabledrag-leaf')),
        '#row_type' => 'extra_field',
        '#region_callback' => array($this, 'getRowRegion'),
        '#js_settings' => array('rowHandler' => 'field'),
        'human_name' => array(
          '#markup' => check_plain($extra_field['label']),
        ),
        'weight' => array(
          '#type' => 'textfield',
          '#title' => t('Weight for @title', array('@title' => $extra_field['label'])),
          '#title_display' => 'invisible',
          '#default_value' => $display_options ? $display_options['weight'] : 0,
          '#size' => 3,
          '#attributes' => array('class' => array('field-weight')),
        ),
        'parent_wrapper' => array(
          'parent' => array(
            '#type' => 'select',
            '#title' => t('Parents for @title', array('@title' => $extra_field['label'])),
            '#title_display' => 'invisible',
            '#options' => $table['#parent_options'],
            '#empty_value' => '',
            '#attributes' => array('class' => array('field-parent')),
            '#parents' => array('fields', $name, 'parent'),
          ),
          'hidden_name' => array(
            '#type' => 'hidden',
            '#default_value' => $name,
            '#attributes' => array('class' => array('field-name')),
          ),
        ),
        'empty_cell' => array(
          '#markup' => '&nbsp;',
        ),
        'format' => array(
          'type' => array(
            '#type' => 'select',
            '#title' => t('Visibility for @title', array('@title' => $extra_field['label'])),
            '#title_display' => 'invisible',
            '#options' => $extra_visibility_options,
            '#default_value' => $display_options ? 'visible' : 'hidden',
            '#parents' => array('fields', $name, 'type'),
            '#attributes' => array('class' => array('field-formatter-type')),
          ),
        ),
        'settings_summary' => array(),
        'settings_edit' => array(),
      );
    }

    $form['fields'] = $table;

    // Custom display settings.
    if ($this->mode == 'default') {
      $view_modes = entity_get_view_modes($this->entity_type);
      // Only show the settings if there is more than one view mode.
      if (count($view_modes) > 1) {
        $form['modes'] = array(
          '#type' => 'details',
          '#title' => t('Custom display settings'),
          '#collapsed' => TRUE,
        );
        // Collect options and default values for the 'Custom display settings'
        // checkboxes.
        $options = array();
        $default = array();
        $view_mode_settings = field_view_mode_settings($this->entity_type, $this->bundle);
        foreach ($view_modes as $view_mode_name => $view_mode_info) {
          $options[$view_mode_name] = $view_mode_info['label'];
          if (!empty($view_mode_settings[$view_mode_name]['status'])) {
            $default[] = $view_mode_name;
          }
        }
        $form['modes']['view_modes_custom'] = array(
          '#type' => 'checkboxes',
          '#title' => t('Use custom display settings for the following view modes'),
          '#options' => $options,
          '#default_value' => $default,
        );
      }
    }

    // In overviews involving nested rows from contributed modules (i.e
    // field_group), the 'format type' selects can trigger a series of changes
    // in child rows. The #ajax behavior is therefore not attached directly to
    // the selects, but triggered by the client-side script through a hidden
    // #ajax 'Refresh' button. A hidden 'refresh_rows' input tracks the name of
    // affected rows.
    $form['refresh_rows'] = array('#type' => 'hidden');
    $form['refresh'] = array(
      '#type' => 'submit',
      '#value' => t('Refresh'),
      '#op' => 'refresh_table',
      '#submit' => array(array($this, 'multistepSubmit')),
      '#ajax' => array(
        'callback' => array($this, 'multistepAjax'),
        'wrapper' => 'field-display-overview-wrapper',
        'effect' => 'fade',
        // The button stays hidden, so we hide the Ajax spinner too. Ad-hoc
        // spinners will be added manually by the client-side script.
        'progress' => 'none',
      ),
      '#attributes' => array('class' => array('visually-hidden'))
    );

    $form['actions'] = array('#type' => 'actions');
    $form['actions']['submit'] = array('#type' => 'submit', '#value' => t('Save'));

    $form['#attached']['library'][] = array('field_ui', 'drupal.field_ui');

    // Add tabledrag behavior.
    $form['#attached']['drupal_add_tabledrag'][] = array('field-display-overview', 'order', 'sibling', 'field-weight');
    $form['#attached']['drupal_add_tabledrag'][] = array('field-display-overview', 'match', 'parent', 'field-parent', 'field-parent', 'field-name');

    return $form;
  }

  /**
   * Overrides \Drupal\field_ui\OverviewBase::submitForm().
   */
  public function submitForm(array &$form, array &$form_state) {
    $form_values = $form_state['values'];
    $display = entity_get_display($this->entity_type, $this->bundle, $this->mode);

    // Collect data for 'regular' fields.
    foreach ($form['#fields'] as $field_name) {
      // Retrieve the stored instance settings to merge with the incoming
      // values.
      $values = $form_values['fields'][$field_name];

      if ($values['type'] == 'hidden') {
        $display->removeComponent($field_name);
      }
      else {
        // Get formatter settings. They lie either directly in submitted form
        // values (if the whole form was submitted while some formatter
        // settings were being edited), or have been persisted in $form_state.
        $settings = array();
        if (isset($values['settings_edit_form']['settings'])) {
          $settings = $values['settings_edit_form']['settings'];
        }
        elseif (isset($form_state['formatter_settings'][$field_name])) {
          $settings = $form_state['formatter_settings'][$field_name];
        }
        elseif ($current_options = $display->getComponent($field_name)) {
          $settings = $current_options['settings'];
        }

        // Only save settings actually used by the selected formatter.
        $default_settings = field_info_formatter_settings($values['type']);
        $settings = array_intersect_key($settings, $default_settings);

        $display->setComponent($field_name, array(
          'label' => $values['label'],
          'type' => $values['type'],
          'weight' => $values['weight'],
          'settings' => $settings,
        ));
      }
    }

    // Collect data for 'extra' fields.
    foreach ($form['#extra'] as $name) {
      if ($form_values['fields'][$name]['type'] == 'hidden') {
        $display->removeComponent($name);
      }
      else {
        $display->setComponent($name, array(
          'weight' => $form_values['fields'][$name]['weight'],
        ));
      }
    }

    // Save the display.
    $display->save();

    // Handle the 'view modes' checkboxes if present.
    if ($this->mode == 'default' && !empty($form_values['view_modes_custom'])) {
      $entity_info = entity_get_info($this->entity_type);
      $view_modes = entity_get_view_modes($this->entity_type);
      $bundle_settings = field_bundle_settings($this->entity_type, $this->bundle);
      $view_mode_settings = field_view_mode_settings($this->entity_type, $this->bundle);

      foreach ($form_values['view_modes_custom'] as $view_mode => $value) {
        if (!empty($value) && empty($view_mode_settings[$view_mode]['status'])) {
          // If no display exists for the newly enabled view mode, initialize
          // it with those from the 'default' view mode, which were used so
          // far.
          if (!entity_load('entity_display', $this->entity_type . '.' . $this->bundle . '.' . $view_mode)) {
            $display = entity_get_display($this->entity_type, $this->bundle, 'default')->createCopy($view_mode);
            $display->save();
          }

          $view_mode_label = $view_modes[$view_mode]['label'];
          $path = $this->entityManager->getAdminPath($this->entity_type, $this->bundle) . "/display/$view_mode";
          drupal_set_message(t('The %view_mode mode now uses custom display settings. You might want to <a href="@url">configure them</a>.', array('%view_mode' => $view_mode_label, '@url' => url($path))));
        }
        $bundle_settings['view_modes'][$view_mode]['status'] = !empty($value);
      }

      // Save updated bundle settings.
      field_bundle_settings($this->entity_type, $this->bundle, $bundle_settings);
    }

    drupal_set_message(t('Your settings have been saved.'));
  }

  /**
   * Form submission handler for multistep buttons.
   */
  public function multistepSubmit($form, &$form_state) {
    $trigger = $form_state['triggering_element'];
    $op = $trigger['#op'];

    switch ($op) {
      case 'edit':
        // Store the field whose settings are currently being edited.
        $field_name = $trigger['#field_name'];
        $form_state['formatter_settings_edit'] = $field_name;
        break;

      case 'update':
        // Store the saved settings, and set the field back to 'non edit' mode.
        $field_name = $trigger['#field_name'];
        $values = $form_state['values']['fields'][$field_name]['settings_edit_form']['settings'];
        $form_state['formatter_settings'][$field_name] = $values;
        unset($form_state['formatter_settings_edit']);
        break;

      case 'cancel':
        // Set the field back to 'non edit' mode.
        unset($form_state['formatter_settings_edit']);
        break;

      case 'refresh_table':
        // If the currently edited field is one of the rows to be refreshed, set
        // it back to 'non edit' mode.
        $updated_rows = explode(' ', $form_state['values']['refresh_rows']);
        if (isset($form_state['formatter_settings_edit']) && in_array($form_state['formatter_settings_edit'], $updated_rows)) {
          unset($form_state['formatter_settings_edit']);
        }
        break;
    }

    $form_state['rebuild'] = TRUE;
  }

  /**
   * Ajax handler for multistep buttons.
   */
  public function multistepAjax($form, &$form_state) {
    $trigger = $form_state['triggering_element'];
    $op = $trigger['#op'];

    // Pick the elements that need to receive the ajax-new-content effect.
    switch ($op) {
      case 'edit':
        $updated_rows = array($trigger['#field_name']);
        $updated_columns = array('format');
        break;

      case 'update':
      case 'cancel':
        $updated_rows = array($trigger['#field_name']);
        $updated_columns = array('format', 'settings_summary', 'settings_edit');
        break;

      case 'refresh_table':
        $updated_rows = array_values(explode(' ', $form_state['values']['refresh_rows']));
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
        return ($row['format']['type']['#value'] == 'hidden' ? 'hidden' : 'content');
    }
  }

}
