<?php

/**
 * @file
 * Contains the RSS style plugin.
 */

/**
 * Default style plugin to render an RSS feed.
 *
 * @ingroup views_style_plugins
 */
class views_plugin_style_rss extends views_plugin_style {
  function attach_to($display_id, $path, $title) {
    $display = $this->view->display[$display_id]->handler;
    $url_options = array();
    $input = $this->view->get_exposed_input();
    if ($input) {
      $url_options['query'] = $input;
    }
    $url_options['absolute'] = TRUE;

    $url = url($this->view->get_url(NULL, $path), $url_options);
    if ($display->has_path()) {
      if (empty($this->preview)) {
        drupal_add_feed($url, $title);
      }
    }
    else {
      if (empty($this->view->feed_icon)) {
        $this->view->feed_icon = '';
      }

      $this->view->feed_icon .= theme('feed_icon', array('url' => $url, 'title' => $title));
      drupal_add_html_head_link(array(
        'rel' => 'alternate',
        'type' => 'application/rss+xml',
        'title' => $title,
        'href' => $url
      ));
    }
  }

  function option_definition() {
    $options = parent::option_definition();

    $options['description'] = array('default' => '', 'translatable' => TRUE);

    return $options;
  }

  function options_form(&$form, &$form_state) {
    parent::options_form($form, $form_state);

    $form['description'] = array(
      '#type' => 'textfield',
      '#title' => t('RSS description'),
      '#default_value' => $this->options['description'],
      '#description' => t('This will appear in the RSS feed itself.'),
      '#maxlength' => 1024,
    );
  }

  /**
   * Return an array of additional XHTML elements to add to the channel.
   *
   * @return
   *   An array that can be passed to format_xml_elements().
   */
  function get_channel_elements() {
    return array();
  }

  /**
   * Get RSS feed description.
   *
   * @return string
   *   The string containing the description with the tokens replaced.
   */
  function get_description() {
    $description = $this->options['description'];

    // Allow substitutions from the first row.
    $description = $this->tokenize_value($description, 0);

    return $description;
  }

  function render() {
    if (empty($this->row_plugin)) {
      vpr('views_plugin_style_default: Missing row plugin');
      return;
    }
    $rows = '';

    // This will be filled in by the row plugin and is used later on in the
    // theming output.
    $this->namespaces = array('xmlns:dc' => 'http://purl.org/dc/elements/1.1/');

    // Fetch any additional elements for the channel and merge in their
    // namespaces.
    $this->channel_elements = $this->get_channel_elements();
    foreach ($this->channel_elements as $element) {
      if (isset($element['namespace'])) {
        $this->namespaces = array_merge($this->namespaces, $element['namespace']);
      }
    }

    foreach ($this->view->result as $row_index => $row) {
      $this->view->row_index = $row_index;
      $rows .= $this->row_plugin->render($row);
    }

    $output = theme($this->theme_functions(),
      array(
        'view' => $this->view,
        'options' => $this->options,
        'rows' => $rows
      ));
    unset($this->view->row_index);
    return $output;
  }
}
