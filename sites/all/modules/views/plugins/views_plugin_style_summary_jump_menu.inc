<?php
// $Id: views_plugin_style_summary_jump_menu.inc,v 1.1.6.6 2010/12/14 00:47:26 merlinofchaos Exp $
/**
 * @file
 * Contains the default summary style plugin, which displays items in an HTML list.
 */

/**
 * The default style plugin for summaries.
 *
 * @ingroup views_style_plugins
 */
class views_plugin_style_summary_jump_menu extends views_plugin_style {
  function option_definition() {
    $options = parent::option_definition();

    $options['base_path'] = array('default' => '');
    $options['count'] = array('default' => TRUE);
    $options['hide'] = array('default' => FALSE);
    $options['text'] = array('default' => 'Go', 'translatable' => TRUE);
    $options['choose'] = array('default' => '- Choose -', 'translatable' => TRUE);
    $options['default_value'] = array('default' => FALSE);

    return $options;
  }

  function query() {
    $pager = array(
      'type' => 'none',
      'options' => array(),
    );
    $this->display->handler->set_option('pager', $pager);
  }

  function options_form(&$form, &$form_state) {
    parent::options_form($form, $form_state);
    $form['base_path'] = array(
      '#type' => 'textfield',
      '#title' => t('Base path'),
      '#default_value' => $this->options['base_path'],
      '#description' => t('Define the base path for links in this summary
        view, i.e. http://example.com/<strong>your_view_path/archive</strong>.
        Do not include beginning and ending forward slash. If this value
        is empty, views will use the first path found as the base path,
        in page displays, or / if no path could be found.'),
    );
    $form['count'] = array(
      '#type' => 'checkbox',
      '#default_value' => !empty($this->options['count']),
      '#title' => t('Display record count with link'),
    );

    $form['hide'] = array(
      '#type' => 'checkbox',
      '#title' => t('Hide the "Go" button.'),
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
      '#title' => t('Select the current argument.'),
      '#default_value' => !empty($this->options['default_value']),
      '#description' => t('If checked, the current argument setting will be displayed as the default option in the jump menu, if applicable.'),
    );
  }

  function render() {
    $argument = $this->view->argument[$this->view->build_info['summary_level']];

    $url_options = array();

    if (!empty($this->view->exposed_raw_input)) {
      $url_options['query'] = $this->view->exposed_raw_input;
    }

    $options = array();
    $default_value = '';
    foreach ($this->view->result as $id => $row) {
      $args = $this->view->args;
      $args[$argument->position] = $argument->summary_argument($row);
      $base_path = NULL;
      if (!empty($argument->options['style_options']['base_path'])) {
        $base_path = $argument->options['style_options']['base_path'];
      }
      $path = url($this->view->get_url($args, $base_path), $url_options);

      $options[$path] = strip_tags($argument->summary_name($row));
      if (!empty($this->options['count'])) {
        $options[$path] .= ' (' . intval($row->{$argument->count_alias}) . ')';
      }
      if ($this->options['default_value'] && $_GET['q'] == $this->view->get_url($args)) {
        $default_value = $path;
      }
    }

    ctools_include('jump-menu');
    $settings = array(
      'hide' => $this->options['hide'],
      'button' => $this->options['text'],
      'choose' => $this->options['choose'],
      'default_value' => $default_value,
    );

    return drupal_get_form('ctools_jump_menu', $options, $settings);
  }
}

