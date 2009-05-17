<?php

/**
 * @file
 * Provides the interface and base class for Views Wizard plugins.
 */

/**
 * Defines a common interface for Views Wizard plugins.
 */
interface ViewsWizardInterface {
  function __construct($plugin);

  /**
   * For AJAX callbacks to build other elements in the "show" form.
   */
  function build_form($form, &$form_state);

  /**
   * Validate form and values.
   *
   * @return an array of form errors.
   */
  function validate($form, &$form_state);

  /**
   * Create a new View from form values.
   *
   * @return a view object.
   *
   * @throws ViewsWizardException in the event of a problem.
   */
  function create_view($form, &$form_state);
}

/**
 * A custom exception class for our errors.
 */
class ViewsWizardException extends Exception {
}

/**
 * A very generic Views Wizard class - can be constructed for any base table.
 */
class ViewsUiBaseViewsWizard implements ViewsWizardInterface {
  protected $base_table;
  protected $entity_type;
  protected $entity_info = array();
  protected $validated_views = array();
  protected $plugin = array();
  protected $filter_defaults = array(
    'id' => NULL,
    'expose' => array('operator' => FALSE),
    'group' => 1,
  );

  function __construct($plugin) {
    $this->base_table = $plugin['base_table'];
    $default = $this->filter_defaults;

    if (isset($plugin['filters'])) {
      foreach ($plugin['filters'] as $name => $info) {
        $default['id'] = $name;
        $plugin['filters'][$name] = $info + $default;
      }
    }

    $this->plugin = $plugin;

    $entities = entity_get_info();
    foreach ($entities as $entity_type => $entity_info) {
      if (isset($entity_info['base table']) && $this->base_table == $entity_info['base table']) {
        $this->entity_info = $entity_info;
        $this->entity_type = $entity_type;
      }
    }
  }

  function build_form($form, &$form_state) {
    $style_options = views_fetch_plugin_names('style', 'normal', array($this->base_table));
    $feed_row_options = views_fetch_plugin_names('row', 'feed', array($this->base_table));
    $path_prefix = url(NULL, array('absolute' => TRUE)) . (variable_get('clean_url', 0) ? '' : '?q=');

    // Add filters and sorts which apply to the view as a whole.
    $this->build_filters($form, $form_state);
    $this->build_sorts($form, $form_state);

    $form['displays']['page'] = array(
      '#type' => 'fieldset',
      '#attributes' => array('class' => array('views-attachment', 'fieldset-no-legend'),),
      '#tree' => TRUE,
    );
    $form['displays']['page']['create'] = array(
      '#title' => t('Create a page'),
      '#type' => 'checkbox',
      '#attributes' => array('class' => array('strong')),
      '#default_value' => TRUE,
      '#id' => 'edit-page-create',
    );

    // All options for the page display are included in this container so they
    // can be hidden en masse when the "Create a page" checkbox is unchecked.
    $form['displays']['page']['options'] = array(
      '#type' => 'container',
      '#attributes' => array('class' => array('options-set'),),
      '#dependency' => array(
        'edit-page-create' => array(1),
      ),
      '#pre_render' => array('ctools_dependent_pre_render'),
      '#prefix' => '<div><div id="edit-page-wrapper">',
      '#suffix' => '</div></div>',
      '#parents' => array('page'),
    );

    $form['displays']['page']['options']['title'] = array(
      '#title' => t('Page title'),
      '#type' => 'textfield',
    );
    $form['displays']['page']['options']['path'] = array(
      '#title' => t('Path'),
      '#type' => 'textfield',
      '#field_prefix' => $path_prefix,
    );
    $form['displays']['page']['options']['style'] = array(
      '#type' => 'fieldset',
      '#attributes' => array('class' => array('container-inline', 'fieldset-no-legend')),
    );

    // Create the dropdown for choosing the display format.
    $form['displays']['page']['options']['style']['style_plugin'] = array(
      '#title' => t('Display format'),
      '#help_topic' => 'style',
      '#type' => 'select',
      '#options' => $style_options,
    );
    $style_form = &$form['displays']['page']['options']['style'];
    $style_form['style_plugin']['#default_value'] = views_ui_get_selected($form_state, array('page', 'style', 'style_plugin'), 'default', $style_form['style_plugin']);
    // Changing this dropdown updates $form['displays']['page']['options'] via
    // AJAX.
    views_ui_add_ajax_trigger($style_form, 'style_plugin', array('displays', 'page', 'options'));

    $this->build_form_style($form, $form_state, 'page');
    $form['displays']['page']['options']['items_per_page'] = array(
      '#title' => t('Items to display'),
      '#type' => 'textfield',
      '#default_value' => '10',
      '#size' => 5,
      '#element_validate' => array('views_element_validate_integer'),
    );
    $form['displays']['page']['options']['pager'] = array(
      '#title' => t('Use a pager'),
      '#type' => 'checkbox',
      '#default_value' => TRUE,
    );
    $form['displays']['page']['options']['link'] = array(
      '#title' => t('Create a menu link'),
      '#type' => 'checkbox',
      '#id' => 'edit-page-link',
    );
    $form['displays']['page']['options']['link_properties'] = array(
      '#type' => 'container',
      '#dependency' => array(
        'edit-page-link' => array(1),
      ),
      '#pre_render' => array('ctools_dependent_pre_render'),
      '#prefix' => '<div id="edit-page-link-properties-wrapper">',
      '#suffix' => '</div>',
    );
    if (module_exists('menu')) {
      $menu_options = menu_get_menus();
    }
    else {
      // These are not yet translated.
      $menu_options = menu_list_system_menus();
      foreach ($menu_options as $name => $title) {
        $menu_options[$name] = t($title);
      }
    }
    $form['displays']['page']['options']['link_properties']['menu_name'] = array(
      '#title' => t('Menu'),
      '#type' => 'select',
      '#options' => $menu_options,
    );
    $form['displays']['page']['options']['link_properties']['title'] = array(
      '#title' => t('Link text'),
      '#type' => 'textfield',
    );
    // Only offer a feed if we have at least one available feed row style.
    if ($feed_row_options) {
      $form['displays']['page']['options']['feed'] = array(
        '#title' => t('Include an RSS feed'),
        '#type' => 'checkbox',
        '#id' => 'edit-page-feed',
      );
      $form['displays']['page']['options']['feed_properties'] = array(
        '#type' => 'container',
        '#dependency' => array(
          'edit-page-feed' => array(1),
        ),
        '#pre_render' => array('ctools_dependent_pre_render'),
        '#prefix' => '<div id="edit-page-feed-properties-wrapper">',
        '#suffix' => '</div>',
      );
      $form['displays']['page']['options']['feed_properties']['path'] = array(
        '#title' => t('Feed path'),
        '#type' => 'textfield',
        '#field_prefix' => $path_prefix,
      );
      // This will almost never be visible.
      $form['displays']['page']['options']['feed_properties']['row_plugin'] = array(
        '#title' => t('Feed row style'),
        '#type' => 'select',
        '#options' => $feed_row_options,
        '#default_value' => key($feed_row_options),
        '#access' => (count($feed_row_options) > 1),
        '#dependency' => array(
          'edit-page-feed' => array(1),
        ),
        '#pre_render' => array('ctools_dependent_pre_render'),
        '#prefix' => '<div id="edit-page-feed-properties-row-plugin-wrapper">',
        '#suffix' => '</div>',
      );
    }

    $form['displays']['block'] = array(
      '#type' => 'fieldset',
      '#attributes' => array('class' => array('views-attachment', 'fieldset-no-legend'),),
      '#tree' => TRUE,
    );
    $form['displays']['block']['create'] = array(
      '#title' => t('Create a block'),
      '#type' => 'checkbox',
      '#attributes' => array('class' => array('strong')),
      '#id' => 'edit-block-create',
    );

    // All options for the block display are included in this container so they
    // can be hidden en masse when the "Create a block" checkbox is unchecked.
    $form['displays']['block']['options'] = array(
      '#type' => 'container',
      '#attributes' => array('class' => array('options-set'),),
      '#dependency' => array(
        'edit-block-create' => array(1),
      ),
      '#pre_render' => array('ctools_dependent_pre_render'),
      '#prefix' => '<div id="edit-block-wrapper">',
      '#suffix' => '</div>',
      '#parents' => array('block'),
    );

    $form['displays']['block']['options']['title'] = array(
      '#title' => t('Block title'),
      '#type' => 'textfield',
    );
    $form['displays']['block']['options']['style'] = array(
      '#type' => 'fieldset',
      '#attributes' => array('class' => array('container-inline', 'fieldset-no-legend')),
    );

    // Create the dropdown for choosing the display format.
    $form['displays']['block']['options']['style']['style_plugin'] = array(
      '#title' => t('Display format'),
      '#help_topic' => 'style',
      '#type' => 'select',
      '#options' => $style_options,
    );
    $style_form = &$form['displays']['block']['options']['style'];
    $style_form['style_plugin']['#default_value'] = views_ui_get_selected($form_state, array('block', 'style', 'style_plugin'), 'default', $style_form['style_plugin']);
    // Changing this dropdown updates $form['displays']['block']['options'] via
    // AJAX.
    views_ui_add_ajax_trigger($style_form, 'style_plugin', array('displays', 'block', 'options'));

    $this->build_form_style($form, $form_state, 'block');
    $form['displays']['block']['options']['items_per_page'] = array(
      '#title' => t('Items per page'),
      '#type' => 'textfield',
      '#default_value' => '5',
      '#size' => 5,
      '#element_validate' => array('views_element_validate_integer'),
    );
    $form['displays']['block']['options']['pager'] = array(
      '#title' => t('Use a pager'),
      '#type' => 'checkbox',
      '#default_value' => FALSE,
    );

    return $form;
  }

  /**
   * Build the part of the form that builds the display format options.
   */
  protected function build_form_style(&$form, &$form_state, $type) {
    $style_form =& $form['displays'][$type]['options']['style'];
    $style = $style_form['style_plugin']['#default_value'];
    $style_plugin = views_get_plugin('style', $style);
    if (isset($style_plugin) && $style_plugin->uses_row_plugin()) {
      $options = $this->row_style_options($type);
      $style_form['row_plugin'] = array(
        '#type' => 'select',
        '#title' => t('of'),
        '#options' => $options,
        '#access' => count($options) > 1,
      );
      // For the block display, the default value should be "titles (linked)",
      // if it's available (since that's the most common use case).
      $block_with_linked_titles_available = ($type == 'block' && isset($options['titles_linked']));
      $default_value = $block_with_linked_titles_available ? 'titles_linked' : key($options);
      $style_form['row_plugin']['#default_value'] = views_ui_get_selected($form_state, array($type, 'style', 'row_plugin'), $default_value, $style_form['row_plugin']);
      // Changing this dropdown updates the individual row options via AJAX.
      views_ui_add_ajax_trigger($style_form, 'row_plugin', array('displays', $type, 'options', 'style', 'row_options'));

      // This is the region that can be updated by AJAX. The base class doesn't
      // add anything here, but child classes can.
      $style_form['row_options'] = array(
        '#theme_wrappers' => array('container'),
      );
    }
    elseif ($style_plugin->uses_fields()) {
      $style_form['row_plugin'] = array('#markup' => '<span>' . t('of fields') . '</span>');
    }
  }

  /**
   * Add possible row style options.
   *
   * Per default use fields with base field.
   */
  protected function row_style_options($type) {
    $data = views_fetch_data($this->base_table);
    // Get all available row plugins by default.
    $options = views_fetch_plugin_names('row', 'normal', array($this->base_table));
    return $options;
  }

  /**
   * Build the part of the form that allows the user to select the view's filters.
   *
   * By default, this adds "of type" and "tagged with" filters (when they are
   * available).
   */
  protected function build_filters(&$form, &$form_state) {
    // Find all the fields we are allowed to filter by.
    $fields = views_fetch_fields($this->base_table, 'filter');

    $entity_info = $this->entity_info;
    // If the current base table support bundles and has more than one (like user).
    if (isset($entity_info['bundle keys']) && isset($entity_info['bundles'])) {
      // Get all bundles and their human readable names.
      $options = array('all' => t('All'));
      foreach ($entity_info['bundles'] as $type => $bundle) {
        $options[$type] = $bundle['label'];
      }
      $form['displays']['show']['type'] = array(
        '#type' => 'select',
        '#title' => t('of type'),
        '#options' => $options,
      );
      $selected_bundle = views_ui_get_selected($form_state, array('show', 'type'), 'all', $form['displays']['show']['type']);
      $form['displays']['show']['type']['#default_value'] = $selected_bundle;
      // Changing this dropdown updates the entire content of $form['displays']
      // via AJAX, since each bundle might have entirely different fields
      // attached to it, etc.
      views_ui_add_ajax_trigger($form['displays']['show'], 'type', array('displays'));
    }

    // Check if we are allowed to filter by taxonomy, and if so, add the
    // "tagged with" filter to the view.
    //
    // We construct this filter using taxonomy_index.tid (which limits the
    // filtering to a specific vocabulary) rather than taxonomy_term_data.name
    // (which matches terms in any vocabulary). This is because it is a more
    // commonly-used filter that works better with the autocomplete UI, and
    // also to avoid confusion with other vocabularies on the site that may
    // have terms with the same name but are not used for free tagging.
    //
    // The downside is that if there *is* more than one vocabulary on the site
    // that is used for free tagging, the wizard will only be able to make the
    // "tagged with" filter apply to one of them (see below for the method it
    // uses to choose).
    if (isset($fields['taxonomy_index.tid'])) {
      // Check if this view will be displaying fieldable entities.
      if (!empty($entity_info['fieldable'])) {
        // Find all "tag-like" taxonomy fields associated with the view's
        // entities. If a particular entity type (i.e., bundle) has been
        // selected above, then we only search for taxonomy fields associated
        // with that bundle. Otherwise, we use all bundles.
        $bundles = array_keys($entity_info['bundles']);
        // Double check that this is a real bundle before using it (since above
        // we added a dummy option 'all' to the bundle list on the form).
        if (isset($selected_bundle) && in_array($selected_bundle, $bundles)) {
          $bundles = array($selected_bundle);
        }
        $tag_fields = array();
        foreach ($bundles as $bundle) {
          foreach (field_info_instances($this->entity_type, $bundle) as $instance) {
            // We define "tag-like" taxonomy fields as ones that use the
            // "Autocomplete term widget (tagging)" widget.
            if ($instance['widget']['type'] == 'taxonomy_autocomplete') {
              $tag_fields[] = $instance['field_name'];
            }
          }
        }
        $tag_fields = array_unique($tag_fields);
        if (!empty($tag_fields)) {
          // If there is more than one "tag-like" taxonomy field available to
          // the view, we can only make our filter apply to one of them (as
          // described above). We choose 'field_tags' if it is available, since
          // that is created by the Standard install profile in core and also
          // commonly used by contrib modules; thus, it is most likely to be
          // associated with the "main" free-tagging vocabulary on the site.
          if (in_array('field_tags', $tag_fields)) {
            $tag_field_name = 'field_tags';
          }
          else {
            $tag_field_name = reset($tag_fields);
          }
          // Add the autocomplete textfield to the wizard.
          $form['displays']['show']['tagged_with'] = array(
            '#type' => 'textfield',
            '#title' => t('tagged with'),
            '#autocomplete_path' => 'taxonomy/autocomplete/' . $tag_field_name,
            '#size' => 30,
            '#maxlength' => 1024,
            '#field_name' => $tag_field_name,
            '#element_validate' => array('views_ui_taxonomy_autocomplete_validate'),
          );
        }
      }
    }
  }

  /**
   * Build the part of the form that allows the user to select the view's sort order.
   *
   * By default, this adds a "sorted by [date]" filter (when it is available).
   */
  protected function build_sorts(&$form, &$form_state) {
    $sorts = array(
      'none' => t('Unsorted'),
    );
    // Check if we are allowed to sort by creation date.
    if (!empty($this->plugin['created_column'])) {
      $sorts += array(
        $this->plugin['created_column'] . ':DESC' => t('Newest first'),
        $this->plugin['created_column'] . ':ASC' => t('Oldest first'),
      );
    }
    if (isset($this->plugin['available_sorts'])) {
      $sorts += $this->plugin['available_sorts'];
    }

    // If there is no sorts option available continue.
    if (!empty($sorts)) {
      $form['displays']['show']['sort'] = array(
        '#type' => 'select',
        '#title' => t('sorted by'),
        '#options' => $sorts,
        '#default_value' => isset($this->plugin['created_column']) ? $this->plugin['created_column'] . ':DESC' : 'none',
      );
    }
  }

  protected function instantiate_view($form, &$form_state) {
    // Build the basic view properties.
    $view = views_new_view();
    $view->name = $form_state['values']['name'];
    $view->human_name = $form_state['values']['human_name'];
    $view->description = $form_state['values']['description'];
    $view->tag = 'default';
    $view->core = VERSION;
    $view->base_table = $this->base_table;

    // Build all display options for this view.
    $display_options = $this->build_display_options($form, $form_state);

    // Allow the fully built options to be altered. This happens before adding
    // the options to the view, so that once they are eventually added we will
    // be able to get all the overrides correct.
    $this->alter_display_options($display_options, $form, $form_state);

    $this->add_displays($view, $display_options, $form, $form_state);

    return $view;
  }

  /**
   * Build an array of display options for the view.
   *
   * @return
   *   An array whose keys are the names of each display and whose values are
   *   arrays of options for that display.
   */
  protected function build_display_options($form, $form_state) {
    // Display: Master
    $display_options['default'] = $this->default_display_options($form, $form_state);
    $display_options['default'] += array(
      'filters' => array(),
      'sorts' => array(),
    );
    $display_options['default']['filters'] += $this->default_display_filters($form, $form_state);
    $display_options['default']['sorts'] += $this->default_display_sorts($form, $form_state);

    // Display: Page
    if (!empty($form_state['values']['page']['create'])) {
      $display_options['page'] = $this->page_display_options($form, $form_state);

      // Display: Feed (attached to the page)
      if (!empty($form_state['values']['page']['feed'])) {
        $display_options['feed'] = $this->page_feed_display_options($form, $form_state);
      }
    }

    // Display: Block
    if (!empty($form_state['values']['block']['create'])) {
      $display_options['block'] = $this->block_display_options($form, $form_state);
    }

    return $display_options;
  }

  /**
   * Alter the full array of display options before they are added to the view.
   */
  protected function alter_display_options(&$display_options, $form, $form_state) {
    // If any of the displays use jump menus, we want to add fields to the view
    // that store the path that will be used in the jump menu. The fields to
    // use for this are defined by the plugin.
    if (isset($this->plugin['path_field'])) {
      $path_field = $this->plugin['path_field'];
      $path_fields_added = FALSE;
      foreach ($display_options as $display_type => $options) {
        if (!empty($options['style_plugin']) && $options['style_plugin'] == 'jump_menu') {
          // Regardless of how many displays have jump menus, we only need to
          // add a single set of path fields to the view.
          if (!$path_fields_added) {
            // The plugin might provide supplemental fields that it needs to
            // generate the path (for example, node revisions need the node ID
            // as well as the revision ID). We need to add these first so they
            // are available as replacement patterns in the main path field.
            $path_fields = !empty($this->plugin['path_fields_supplemental']) ? $this->plugin['path_fields_supplemental'] : array();
            $path_fields[] = &$path_field;

            // Generate a unique ID for each field so we don't overwrite
            // existing ones.
            foreach ($path_fields as &$field) {
              $field['id'] = view::generate_item_id($field['id'], $display_options['default']['fields']);
              $display_options['default']['fields'][$field['id']] = $field;
            }

            $path_fields_added = TRUE;
          }

          // Configure the style plugin to use the path field to generate the
          // jump menu path.
          $display_options[$display_type]['style_options']['path'] = $path_field['id'];
        }
      }
    }

    // If any of the displays use the table style, take sure that the fields
    // always have a labels by unsetting the override.
    foreach ($display_options as &$options) {
      if ($options['style_plugin'] == 'table') {
        foreach ($display_options['default']['fields'] as &$field) {
          unset($field['label']);
        }
      }
    }
  }

  /**
   * Add the array of display options to the view, with appropriate overrides.
   */
  protected function add_displays($view, $display_options, $form, $form_state) {
    // Display: Master
    $default_display = $view->new_display('default', 'Master', 'default');
    foreach ($display_options['default'] as $option => $value) {
      $default_display->set_option($option, $value);
    }

    // Display: Page
    if (isset($display_options['page'])) {
      $display = $view->new_display('page', 'Page', 'page');
      // The page display is usually the main one (from the user's point of
      // view). Its options should therefore become the overall view defaults,
      // so that new displays which are added later automatically inherit them.
      $this->set_default_options($display_options['page'], $display, $default_display);

      // Display: Feed (attached to the page)
      if (isset($display_options['feed'])) {
        $display = $view->new_display('feed', 'Feed', 'feed');
        $this->set_override_options($display_options['feed'], $display, $default_display);
      }
    }

    // Display: Block
    if (isset($display_options['block'])) {
      $display = $view->new_display('block', 'Block', 'block');
      // When there is no page, the block display options should become the
      // overall view defaults.
      if (!isset($display_options['page'])) {
        $this->set_default_options($display_options['block'], $display, $default_display);
      }
      else {
        $this->set_override_options($display_options['block'], $display, $default_display);
      }
    }
  }

  /**
   * Most subclasses will need to override this method to provide some fields
   * or a different row plugin.
   */
  protected function default_display_options($form, $form_state) {
    $display_options = array();
    $display_options['access']['type'] = 'none';
    $display_options['cache']['type'] = 'none';
    $display_options['query']['type'] = 'views_query';
    $display_options['exposed_form']['type'] = 'basic';
    $display_options['pager']['type'] = 'full';
    $display_options['style_plugin'] = 'default';
    $display_options['row_plugin'] = 'fields';

    // Add a least one field so the view validates and the user has already a preview.
    // Therefore the basefield could provide 'defaults][field]' in it's base settings.
    // If there is nothing like this choose the first field with a field handler.
    $data = views_fetch_data($this->base_table);
    if (isset($data['table']['base']['defaults']['field'])) {
      $field = $data['table']['base']['defaults']['field'];
    }
    else {
      foreach ($data as $field => $field_data) {
        if (isset($field_data['field']['handler'])) {
          break;
        }
      }
    }
    $display_options['fields'][$field] = array(
      'table' => $this->base_table,
      'field' => $field,
      'id' => $field,
    );

    return $display_options;
  }

  protected function default_display_filters($form, $form_state) {
    $filters = array();

    // Add any filters provided by the plugin.
    if (isset($this->plugin['filters'])) {
      foreach ($this->plugin['filters'] as $name => $info) {
        $filters[$name] = $info;
      }
    }

    // Add any filters specified by the user when filling out the wizard.
    $filters = array_merge($filters, $this->default_display_filters_user($form, $form_state));

    return $filters;
  }

  protected function default_display_filters_user($form, $form_state) {
    $filters = array();

    if (!empty($form_state['values']['show']['type']) && $form_state['values']['show']['type'] != 'all') {
      $bundle_key = $this->entity_info['bundle keys']['bundle'];
      // Figure out the table where $bundle_key lives. It may not be the same as
      // the base table for the view; the taxonomy vocabulary machine_name, for
      // example, is stored in taxonomy_vocabulary, not taxonomy_term_data.
      $fields = views_fetch_fields($this->base_table, 'filter');
      if (isset($fields[$this->base_table . '.' . $bundle_key])) {
        $table = $this->base_table;
      }
      else {
        foreach ($fields as $field_name => $value) {
          if ($pos = strpos($field_name, '.' . $bundle_key)) {
            $table = substr($field_name, 0, $pos);
            break;
          }
        }
      }
      $table_data = views_fetch_data($table);
      // Check whether the bundle key filter handler is or an child of it views_handler_filter_in_operator
      // If it's not just use a single value instead of an array.
      $handler = $table_data[$bundle_key]['filter']['handler'];
      if ($handler == 'views_handler_filter_in_operator' || is_subclass_of($handler, 'views_handler_filter_in_operator')) {
        $value = drupal_map_assoc(array($form_state['values']['show']['type']));
      }
      else {
        $value = $form_state['values']['show']['type'];
      }

      $filters[$bundle_key] = array(
        'id' => $bundle_key,
        'table' => $table,
        'field' => $bundle_key,
        'value' => $value,
      );
    }

    // @todo: Figure out why this isn't part of node_views_wizard.
    if (!empty($form_state['values']['show']['tagged_with']['tids'])) {
      $filters['tid'] = array(
        'id' => 'tid',
        'table' => 'taxonomy_index',
        'field' => 'tid',
        'value' => $form_state['values']['show']['tagged_with']['tids'],
        'vocabulary' => $form_state['values']['show']['tagged_with']['vocabulary'],
      );
      // If the user entered more than one valid term in the autocomplete
      // field, they probably intended both of them to be applied.
      if (count($form_state['values']['show']['tagged_with']['tids']) > 1) {
        $filters['tid']['operator'] = 'and';
        // Sort the terms so the filter will be displayed as it normally would
        // on the edit screen.
        sort($filters['tid']['value']);
      }
    }

    return $filters;
  }

  protected function default_display_sorts($form, $form_state) {
    $sorts = array();

    // Add any sorts provided by the plugin.
    if (isset($this->plugin['sorts'])) {
      foreach ($this->plugin['sorts'] as $name => $info) {
        $sorts[$name] = $info;
      }
    }

    // Add any sorts specified by the user when filling out the wizard.
    $sorts = array_merge($sorts, $this->default_display_sorts_user($form, $form_state));

    return $sorts;
  }

  protected function default_display_sorts_user($form, $form_state) {
    $sorts = array();

    // Don't add a sort if there is no form value or the user selected none as sort.
    if (!empty($form_state['values']['show']['sort']) && $form_state['values']['show']['sort'] != 'none') {
      list($column, $sort) = explode(':', $form_state['values']['show']['sort']);
      // Column either be a column-name or the table-columnn-ame.
      $column = explode('-', $column);
      if (count($column) > 1) {
        $table = $column[0];
        $column = $column[1];
      }
      else {
        $table = $this->base_table;
        $column = $column[0];
      }

      $sorts[$column] = array(
        'id' => $column,
        'table' => $table,
        'field' => $column,
        'order' => $sort,
      );
    }

    return $sorts;
  }

  protected function page_display_options($form, $form_state) {
    $display_options = array();
    $page = $form_state['values']['page'];
    $display_options['title'] = $page['title'];
    $display_options['path'] = $page['path'];
    $display_options['style_plugin'] = $page['style']['style_plugin'];
    // Not every style plugin supports row style plugins.
    $display_options['row_plugin'] = isset($page['style']['row_plugin']) ? $page['style']['row_plugin'] : 'fields';
    if (empty($page['items_per_page'])) {
      $display_options['pager']['type'] = 'none';
    }
    elseif ($page['pager']) {
      $display_options['pager']['type'] = 'full';
    }
    else {
      $display_options['pager']['type'] = 'some';
    }
    $display_options['pager']['options']['items_per_page'] = $page['items_per_page'];
    if (!empty($page['link'])) {
      $display_options['menu']['type'] = 'normal';
      $display_options['menu']['title'] = $page['link_properties']['title'];
      $display_options['menu']['name'] = $page['link_properties']['menu_name'];
    }
    return $display_options;
  }

  protected function block_display_options($form, $form_state) {
    $display_options = array();
    $block = $form_state['values']['block'];
    $display_options['title'] = $block['title'];
    $display_options['style_plugin'] = $block['style']['style_plugin'];
    $display_options['row_plugin'] = isset($block['style']['row_plugin']) ? $block['style']['row_plugin'] : 'fields';
    $display_options['pager']['type'] = $block['pager'] ? 'full' : (empty($block['items_per_page']) ? 'none' : 'some');
    $display_options['pager']['options']['items_per_page'] = $block['items_per_page'];
    return $display_options;
  }

  protected function page_feed_display_options($form, $form_state) {
    $display_options = array();
    $display_options['pager']['type'] = 'some';
    $display_options['style_plugin'] = 'rss';
    $display_options['row_plugin'] = $form_state['values']['page']['feed_properties']['row_plugin'];
    $display_options['path'] = $form_state['values']['page']['feed_properties']['path'];
    $display_options['title'] = $form_state['values']['page']['title'];
    $display_options['displays'] = array(
      'default' => 'default',
      'page' => 'page',
    );
    return $display_options;
  }

  /**
   * Sets options for a display and makes them the default options if possible.
   *
   * This function can be used to set options for a display when it is desired
   * that the options also become the defaults for the view whenever possible.
   * This should be done for the "primary" display created in the view wizard,
   * so that new displays which the user adds later will be similar to this
   * one.
   *
   * @param $options
   *   An array whose keys are the name of each option and whose values are the
   *   desired values to set.
   * @param $display
   *   The display which the options will be applied to. The default display
   *   will actually be assigned the options (and this display will inherit
   *   them) when possible.
   * @param $default_display
   *   The default display, which will store the options when possible.
   */
  protected function set_default_options($options, $display, $default_display) {
    foreach ($options as $option => $value) {
      // If the default display supports this option, set the value there.
      // Otherwise, set it on the provided display.
      $default_value = $default_display->get_option($option);
      if (isset($default_value)) {
        $default_display->set_option($option, $value);
      }
      else {
        $display->set_option($option, $value);
      }
    }
  }

  /**
   * Sets options for a display, inheriting from the defaults when possible.
   *
   * This function can be used to set options for a display when it is desired
   * that the options inherit from the default display whenever possible. This
   * avoids setting too many options as overrides, which will be harder for the
   * user to modify later. For example, if $this->set_default_options() was
   * previously called on a page display and then this function is called on a
   * block display, and if the user entered the same title for both displays in
   * the views wizard, then the view will wind up with the title stored as the
   * default (with the page and block both inheriting from it).
   *
   * @param $options
   *   An array whose keys are the name of each option and whose values are the
   *   desired values.
   * @param $display
   *   The display which the options will apply to. It will get the options by
   *   inheritance from the default display when possible.
   * @param $default_display
   *   The default display, from which the options will be inherited when
   *   possible.
   */
  protected function set_override_options($options, $display, $default_display) {
    foreach ($options as $option => $value) {
      // Only override the default value if it is different from the value that
      // was provided.
      $default_value = $default_display->get_option($option);
      if (!isset($default_value)) {
        $display->set_option($option, $value);
      }
      elseif ($default_value !== $value) {
        $display->override_option($option, $value);
      }
    }
  }

  protected function retrieve_validated_view($form, $form_state, $unset = TRUE) {
    $key = hash('sha256', serialize($form_state['values']));
    $view = (isset($this->validated_views[$key]) ? $this->validated_views[$key] : NULL);
    if ($unset) {
      unset($this->validated_views[$key]);
    }
    return $view;
  }

  protected function set_validated_view($form, $form_state, $view) {
    $key = hash('sha256', serialize($form_state['values']));
    $this->validated_views[$key] = $view;
  }

  /**
   * Instantiates a view and validates values.
   */
  function validate($form, &$form_state) {
    $view = $this->instantiate_view($form, $form_state);
    $errors = $view->validate();
    if (!is_array($errors) || empty($errors)) {
      $this->set_validated_view($form, $form_state, $view);
      return array();
    }
    return $errors;
  }

  /**
   * Create a View from values that have been already submitted to validate().
   *
   * @throws ViewsWizardException if the values have not been validated.
   */
 function create_view($form, &$form_state) {
   $view = $this->retrieve_validated_view($form, $form_state);
   if (empty($view)) {
     throw new ViewsWizardException(t('Attempted to create_view with values that have not been validated'));
   }
   return $view;
 }

}
