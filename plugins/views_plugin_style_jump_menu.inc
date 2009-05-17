<?php

/**
 * @file
 * Contains the table style plugin.
 */

/**
 * Style plugin to render each item as a row in a table.
 *
 * @ingroup views_style_plugins
 */
class views_plugin_style_jump_menu extends views_plugin_style {
  function option_definition() {
    $options = parent::option_definition();

    $options['hide'] = array('default' => FALSE, 'bool' => TRUE);
    $options['path'] = array('default' => '');
    $options['text'] = array('default' => 'Go', 'translatable' => TRUE);
    $options['choose'] = array('default' => '- Choose -', 'translatable' => TRUE);
    $options['default_value'] = array('default' => FALSE, 'bool' => TRUE);

    return $options;
  }

  /**
   * Render the given style.
   */
  function options_form(&$form, &$form_state) {
    parent::options_form($form, $form_state);
    $handlers = $this->display->handler->get_handlers('field');
    if (empty($handlers)) {
      $form['error_markup'] = array(
        '#markup' => t('You need at least one field before you can configure your jump menu settings'),
        '#prefix' => '<div class="error messages">',
        '#suffix' => '</div>',
      );
      return;
    }

    $form['markup'] = array(
      '#markup' => t('To properly configure a jump menu, you must select one field that will represent the path to utilize. You should then set that field to exclude. All other displayed fields will be part of the menu. Please note that all HTML will be stripped from this output as select boxes cannot show HTML.'),
      '#prefix' => '<div class="form-item description">',
      '#suffix' => '</div>',
    );

    foreach ($handlers as $id => $handler) {
      $options[$id] = $handler->ui_name();
    }

    $form['path'] = array(
      '#type' => 'select',
      '#title' => t('Path field'),
      '#options' => $options,
      '#default_value' => $this->options['path'],
    );

    $form['hide'] = array(
      '#type' => 'checkbox',
      '#title' => t('Hide the "Go" button'),
      '#default_value' => !empty($this->options['hide']),
      '#description' => t('If hidden, this button will only be hidden for users with javascript and the page will automatically jump when the select is changed.'),
    );

    $form['text'] = array(
      '#type' => 'textfield',
      '#title' => t('Button text'),
      '#default_value' => $this->options['text'],
    );

    $form['choose'] = array(
      '#type' => 'textfield',
      '#title' => t('Choose text'),
      '#default_value' => $this->options['choose'],
      '#description' => t('The text that will appear as the selected option in the jump menu.'),
    );

    $form['default_value'] = array(
      '#type' => 'checkbox',
      '#title' => t('Select the current contextual filter value'),
      '#default_value' => !empty($this->options['default_value']),
      '#description' => t('If checked, the current path will be displayed as the default option in the jump menu, if applicable.'),
    );
  }

  /**
   * Render the display in this style.
   *
   * This is overridden so that we can render our grouping specially.
   */
  function render() {
    $sets = $this->render_grouping($this->view->result, $this->options['grouping']);

    // Turn this all into an $options array for the jump menu.
    $this->view->row_index = 0;
    $options = array();
    $paths = array();

    foreach ($sets as $title => $records) {
      foreach ($records as $row_index => $row) {
        $this->view->row_index = $row_index;
        $path = strip_tags(decode_entities($this->get_field($this->view->row_index, $this->options['path'])));
        // Putting a '/' in front messes up url() so let's take that out
        // so users don't shoot themselves in the foot.
        $base_path = base_path();
        if (strpos($path, $base_path) === 0) {
          $path = drupal_substr($path, drupal_strlen($base_path));
        }

        // use drupal_parse_url() to preserve query and fragment in case the user
        // wants to do fun tricks.
        $url_options = drupal_parse_url($path);

        $path = url($url_options['path'], $url_options);
        $field = strip_tags(decode_entities($this->row_plugin->render($row)));
        $key = md5($path . $field) . "::" . $path;
        if ($title) {
          $options[$title][$key] = $field;
        }
        else {
          $options[$key] = $field;
        }
        $paths[$path] = $key;
        $this->view->row_index++;
      }
    }
    unset($this->view->row_index);

    $default_value = '';
    if ($this->options['default_value'] && !empty($paths[url($_GET['q'])])) {
      $default_value = $paths[url($_GET['q'])];
    }

    ctools_include('jump-menu');
    $settings = array(
      'hide' => $this->options['hide'],
      'button' => $this->options['text'],
      'choose' => $this->options['choose'],
      'default_value' => $default_value,
    );

    $form = drupal_get_form('ctools_jump_menu', $options, $settings);
    return $form;
  }

  function render_set($title, $records) {
    $options = array();
    $fields = $this->rendered_fields;
  }
}
