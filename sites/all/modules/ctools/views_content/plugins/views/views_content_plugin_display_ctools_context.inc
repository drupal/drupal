<?php
// $Id: views_content_plugin_display_ctools_context.inc,v 1.2 2010/10/11 22:18:23 sdboyer Exp $
/**
 * @file
 * Contains the block display plugin.
 */

/**
 * The plugin that handles a block.
 *
 * @ingroup views_display_plugins
 */
class views_content_plugin_display_ctools_context extends views_plugin_display {
  function get_style_type() { return 'context'; }

  function defaultable_sections($section = NULL) {
    if (in_array($section, array('style_options', 'style_plugin', 'row_options', 'row_plugin',))) {
      return FALSE;
    }

    return parent::defaultable_sections($section);
  }

  function option_definition() {
    $options = parent::option_definition();

    $options['admin_title'] = array('default' => '', 'translatable' => TRUE);

    // Overrides for standard stuff:
    $options['style_plugin']['default'] = 'ctools_context';
    $options['row_plugin']['default'] = 'fields';
    $options['defaults']['default']['style_plugin'] = FALSE;
    $options['defaults']['default']['style_options'] = FALSE;
    $options['defaults']['default']['row_plugin'] = FALSE;
    $options['defaults']['default']['row_options'] = FALSE;

    return $options;
  }

  /**
   * The display block handler returns the structure necessary for a block.
   */
  function execute() {
    $this->executing = TRUE;
    return $this->view->render();
  }

  function preview() {
    $this->previewing = TRUE;
    return $this->view->render();
  }

  /**
   * Render this display.
   */
  function render() {
    if (!empty($this->previewing)) {
      return theme($this->theme_functions(), $this->view);
    }
    else {
      // We want to process the view like we're theming it, but not actually
      // use the template part. Therefore we run through all the preprocess
      // functions which will populate the variables array.
      $hooks = theme_get_registry();
      $info = $hooks[$this->definition['theme']];
      if (!empty($info['file'])) {
        @include_once('./' . $info['path'] . '/' . $info['file']);
      }
      $this->variables = array('view' => &$this->view);

      if (isset($info['preprocess functions']) && is_array($info['preprocess functions'])) {
        foreach ($info['preprocess functions'] as $preprocess_function) {
          if (function_exists($preprocess_function)) {
            $preprocess_function($this->variables, $this->definition['theme']);
          }
        }
      }
    }

    return $this->variables;
  }

  /**
   * Provide the summary for page options in the views UI.
   *
   * This output is returned as an array.
   */
  function options_summary(&$categories, &$options) {
    // It is very important to call the parent function here:
    parent::options_summary($categories, $options);

    $categories['context'] = array(
      'title' => t('Context settings'),
    );

    $admin_title = $this->get_option('admin_title');
    if (empty($admin_title)) {
      $admin_title = t('Use view name');
    }

    if (strlen($admin_title) > 16) {
      $admin_title = substr($admin_title, 0, 16) . '...';
    }

    $options['admin_title'] = array(
      'category' => 'context',
      'title' => t('Admin title'),
      'value' => $admin_title,
    );

  }

  /**
   * Provide the default form for setting options.
   */
  function options_form(&$form, &$form_state) {
    // It is very important to call the parent function here:
    parent::options_form($form, $form_state);
    switch ($form_state['section']) {
      case 'row_plugin':
        // This just overwrites the existing row_plugin which is using the wrong options.
        $form['row_plugin']['#options'] = views_fetch_plugin_names('row', 'normal', array($this->view->base_table));
        break;
      case 'admin_title':
        $form['#title'] .= t('Administrative title');

        $form['admin_title'] = array(
          '#type' => 'textfield',
          '#default_value' => $this->get_option('admin_title'),
          '#description' => t('This is the title that will appear for this view context in the configure context dialog. If left blank, the view name will be used.'),
        );
        break;
    }
  }

  /**
   * Perform any necessary changes to the form values prior to storage.
   * There is no need for this function to actually store the data.
   */
  function options_submit(&$form, &$form_state) {
    // It is very important to call the parent function here:
    parent::options_submit($form, $form_state);
    switch ($form_state['section']) {
      case 'admin_title':
        $this->set_option($form_state['section'], $form_state['values'][$form_state['section']]);
        break;
    }
  }

  /**
   * Block views use exposed widgets only if AJAX is set.
   */
  function uses_exposed() {
    return FALSE;
  }
}
