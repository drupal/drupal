<?php
// $Id: stylizer_ui.class.php,v 1.3 2011/01/05 22:35:46 merlinofchaos Exp $

/**
 * UI class for Stylizer.
 */
class stylizer_ui extends ctools_export_ui {

  function access($op, $item) {
    $access = parent::access($op, $item);
    if ($op == 'add' && $access && empty($this->base_types)) {
     // Make sure there are base styles defined.
     $access = FALSE;
    }
    return $access;
  }

  function list_form(&$form, &$form_state) {
    ctools_include('stylizer');
    parent::list_form($form, $form_state);

    $all = array('all' => t('- All -'));

    if (empty($this->base_types)) {
      // Give a warning about the missing base styles.
      drupal_set_message($this->plugin['strings']['message']['missing base type'], 'warning');
    }

    $types = $all;
    foreach ($this->base_types as $module => $info) {
      foreach ($info as $key => $base_type) {
        $types[$module . '-' . $key] = $base_type['title'];
      }
    }

    $form['top row']['type'] = array(
      '#type' => 'select',
      '#title' => t('Type'),
      '#options' => $types,
      '#default_value' => 'all',
      '#weight' => -10,
      '#attributes' => array('class' => array('ctools-auto-submit')),
    );

    $plugins = ctools_get_style_bases();
    $form_state['style_plugins'] = $plugins;

    $options = $all;
    // @todo base should use $module . '-' . $name
    foreach ($plugins as $name => $plugin) {
      $options[$name] = $plugin['title'];
    }

    $form['top row']['base'] = array(
      '#type' => 'select',
      '#title' => t('Base'),
      '#options' => $all + $options,
      '#default_value' => 'all',
      '#weight' => -9,
      '#attributes' => array('class' => array('ctools-auto-submit')),
    );
  }

  function list_sort_options() {
    return array(
      'disabled' => t('Enabled, title'),
      'title' => t('Title'),
      'name' => t('Name'),
      'base' => t('Base'),
      'type' => t('Type'),
      'storage' => t('Storage'),
    );
  }

  function list_filter($form_state, $item) {
    if (empty($form_state['style_plugins'][$item->settings['style_base']])) {
      $this->style_plugin = array(
        'name' => 'broken',
        'title' => t('Missing plugin'),
        'type' => t('Unknown'),
        'module' => '',
      );
    }
    else {
      $this->style_plugin = $form_state['style_plugins'][$item->settings['style_base']];
    }

    // This isn't really a field, but by setting this we can list it in the
    // filter fields and have the search box pick it up.
    $item->plugin_title = $this->style_plugin['title'];

    if ($form_state['values']['type'] != 'all') {
      list($module, $type) = explode('-', $form_state['values']['type']);
      if ($module != $this->style_plugin['module'] || $type != $this->style_plugin['type']) {
        return TRUE;
      }
    }

    if ($form_state['values']['base'] != 'all' && $form_state['values']['base'] != $this->style_plugin['name']) {
      return TRUE;
    }

    return parent::list_filter($form_state, $item);
  }

  function list_search_fields() {
    $fields = parent::list_search_fields();
    $fields[] = 'plugin_title';
    return $fields;
  }

  function list_build_row($item, &$form_state, $operations) {
    // Set up sorting
    switch ($form_state['values']['order']) {
      case 'disabled':
        $this->sorts[$item->name] = empty($item->disabled) . $item->admin_title;
        break;
      case 'title':
        $this->sorts[$item->name] = $item->admin_title;
        break;
      case 'name':
        $this->sorts[$item->name] = $item->name;
        break;
      case 'type':
        $this->sorts[$item->name] = $this->style_plugin['type'] . $item->admin_title;
        break;
      case 'base':
        $this->sorts[$item->name] = $this->style_plugin['title'] . $item->admin_title;
        break;
      case 'storage':
        $this->sorts[$item->name] = $item->type . $item->admin_title;
        break;
    }

    if (!empty($this->base_types[$this->style_plugin['module']][$this->style_plugin['type']])) {
      $type = $this->base_types[$this->style_plugin['module']][$this->style_plugin['type']]['title'];
    }
    else {
      $type = t('Unknown');
    }

    $this->rows[$item->name] = array(
      'data' => array(
        array('data' => $type, 'class' => array('ctools-export-ui-type')),
        array('data' => check_plain($item->name), 'class' => array('ctools-export-ui-name')),
        array('data' => check_plain($item->admin_title), 'class' => array('ctools-export-ui-title')),
        array('data' => check_plain($this->style_plugin['title']), 'class' => array('ctools-export-ui-base')),
        array('data' => check_plain($item->type), 'class' => array('ctools-export-ui-storage')),
        array('data' => theme('links', array('links' => $operations)), 'class' => array('ctools-export-ui-operations')),
      ),
      'title' => check_plain($item->admin_description),
      'class' => array(!empty($item->disabled) ? 'ctools-export-ui-disabled' : 'ctools-export-ui-enabled'),
    );
  }

  function list_table_header() {
    return array(
      array('data' => t('Type'), 'class' => array('ctools-export-ui-type')),
      array('data' => t('Name'), 'class' => array('ctools-export-ui-name')),
      array('data' => t('Title'), 'class' => array('ctools-export-ui-title')),
      array('data' => t('Base'), 'class' => array('ctools-export-ui-base')),
      array('data' => t('Storage'), 'class' => array('ctools-export-ui-storage')),
      array('data' => t('Operations'), 'class' => array('ctools-export-ui-operations')),
    );
  }

  function init($plugin) {
    ctools_include('stylizer');
    $this->base_types = ctools_get_style_base_types();

    parent::init($plugin);
  }

  function get_wizard_info(&$form_state) {
    $form_info = parent::get_wizard_info($form_state);
    ctools_include('stylizer');

    // For add forms, we have temporarily set the 'form type' to include
    // the style type so the default wizard_info can find the path. If
    // we did that, we have to put it back.
    if (!empty($form_state['type'])) {
      $form_state['form type'] = 'add';
      $form_info['show back'] = TRUE;
    }

    // Ensure these do not get out of sync.
    $form_state['item']->settings['name'] = $form_state['item']->name;
    $form_state['settings'] = $form_state['item']->settings;

    // Figure out the base style plugin in use and make sure that is available.
    $plugin = NULL;
    if (!empty($form_state['item']->settings['style_base'])) {
      $plugin = ctools_get_style_base($form_state['item']->settings['style_base']);
      ctools_stylizer_add_plugin_forms($form_info, $plugin, $form_state['op']);
    }
    else {
      // This is here so the 'finish' button does not show up, and because
      // we don't have the selected style we don't know what the next form(s)
      // will be.
      $form_info['order']['next'] = t('Configure style');

    }

    // If available, make sure these are available for the 'choose' form.
    if (!empty($form_state['item']->style_module)) {
      $form_state['module'] = $form_state['item']->style_module;
      $form_state['type'] = $form_state['item']->style_type;
    }

    $form_state['base_style_plugin'] = $plugin;
    $form_state['settings'] = $form_state['item']->settings;
    return $form_info;
  }

  /**
   * Store the stylizer info in our settings.
   *
   * The stylizer wizard stores its stuff in slightly different places, so
   * we have to find it and move it to the right place.
   */
  function store_stylizer_info(&$form_state) {
    /*
    foreach (array('name', 'admin_title', 'admin_description') as $key) {
      if (!empty($form_state['values'][$key])) {
        $form_state['item']->{$key} = $form_state['values'][$key];
      }
    }
    */

    if ($form_state['step'] != 'import') {
      $form_state['item']->settings = $form_state['settings'];
    }
    // Do not let the 'name' accidentally get out of sync under any circumstances.
    $form_state['item']->settings['name'] = $form_state['item']->name;
  }

  function edit_wizard_next(&$form_state) {
    $this->store_stylizer_info($form_state);
    parent::edit_wizard_next($form_state);
  }

  function edit_wizard_finish(&$form_state) {
    // These might be stored by the stylizer wizard, so we should clear them.
    if (isset($form_state['settings']['old_settings'])) {
      unset($form_state['settings']['old_settings']);
    }
    $this->store_stylizer_info($form_state);
    parent::edit_wizard_finish($form_state);
  }

  function edit_form_type(&$form, &$form_state) {
    foreach ($this->base_types as $module => $info) {
      foreach ($info as $key => $base_type) {
        $types[$module . '-' . $key] = $base_type['title'];
      }
    }

    $form['type'] = array(
      '#type' => 'select',
      '#title' => t('Type'),
      '#options' => $types,
      '#default_value' => 'all',
      '#weight' => -10,
      '#attributes' => array('class' => array('ctools-auto-submit')),
    );
  }

  function edit_form_type_submit(&$form, &$form_state) {
    list($form_state['item']->style_module, $form_state['item']->style_type) = explode('-', $form_state['values']['type']);
  }
}
