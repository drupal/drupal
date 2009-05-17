<?php

/**
 * @file
 * Contains the node RSS row style plugin.
 */

/**
 * Plugin which performs a node_view on the resulting object
 * and formats it as an RSS item.
 */
class views_plugin_row_node_rss extends views_plugin_row {
  // Basic properties that let the row style follow relationships.
  var $base_table = 'node';
  var $base_field = 'nid';

  // Stores the nodes loaded with pre_render.
  var $nodes = array();

  function option_definition() {
    $options = parent::option_definition();

    $options['item_length'] = array('default' => 'default');
    $options['links'] = array('default' => FALSE, 'bool' => TRUE);

    return $options;
  }

  /**
   * Override init function to convert fulltext view-mode to full.
   */
  function init(&$view, &$display, $options = NULL) {
    parent::init($view, $display, $options);

    if ($this->options['item_length'] == 'fulltext') {
      $this->options['item_length'] = 'full';
    }
  }

  function options_form(&$form, &$form_state) {
    parent::options_form($form, $form_state);

    $form['item_length'] = array(
      '#type' => 'select',
      '#title' => t('Display type'),
      '#options' => $this->options_form_summary_options(),
      '#default_value' => $this->options['item_length'],
    );
    $form['links'] = array(
      '#type' => 'checkbox',
      '#title' => t('Display links'),
      '#default_value' => $this->options['links'],
    );
  }

  /**
   * Return the main options, which are shown in the summary title.
   */
  function options_form_summary_options() {
    $entity_info = entity_get_info('node');
    $options = array();
    if (!empty($entity_info['view modes'])) {
      foreach ($entity_info['view modes'] as $mode => $settings) {
        $options[$mode] = $settings['label'];
      }
    }
    $options['title'] = t('Title only');
    $options['default'] = t('Use site default RSS settings');
    return $options;
  }

  function summary_title() {
    $options = $this->options_form_summary_options();
    return check_plain($options[$this->options['item_length']]);
  }


  function pre_render($values) {
    $nids = array();
    foreach ($values as $row) {
      $nids[] = $row->{$this->field_alias};
    }
    if (!empty($nids)) {
      $this->nodes = node_load_multiple($nids);
    }
  }

  function render($row) {
    // For the most part, this code is taken from node_feed() in node.module
    global $base_url;

    $nid = $row->{$this->field_alias};
    if (!is_numeric($nid)) {
      return;
    }

    $display_mode = $this->options['item_length'];
    if ($display_mode == 'default') {
      $display_mode = variable_get('feed_item_length', 'teaser');
    }

    // Load the specified node:
    $node = $this->nodes[$nid];
    if (empty($node)) {
      return;
    }

    $item_text = '';

    $uri = entity_uri('node', $node);
    $node->link = url($uri['path'], $uri['options'] + array('absolute' => TRUE));
    $node->rss_namespaces = array();
    $node->rss_elements = array(
      array(
        'key' => 'pubDate',
        'value' => gmdate('r', $node->created),
      ),
      array(
        'key' => 'dc:creator',
        'value' => $node->name,
      ),
      array(
        'key' => 'guid',
        'value' => $node->nid . ' at ' . $base_url,
        'attributes' => array('isPermaLink' => 'false'),
      ),
    );

    // The node gets built and modules add to or modify $node->rss_elements
    // and $node->rss_namespaces.

    $build_mode = $display_mode;

    $build = node_view($node, $build_mode);
    unset($build['#theme']);

    if (!empty($node->rss_namespaces)) {
      $this->view->style_plugin->namespaces = array_merge($this->view->style_plugin->namespaces, $node->rss_namespaces);
    }
    elseif (function_exists('rdf_get_namespaces')) {
      // Merge RDF namespaces in the XML namespaces in case they are used
      // further in the RSS content.
      $xml_rdf_namespaces = array();
      foreach (rdf_get_namespaces() as $prefix => $uri) {
        $xml_rdf_namespaces['xmlns:' . $prefix] = $uri;
      }
      $this->view->style_plugin->namespaces += $xml_rdf_namespaces;
    }

    // Hide the links if desired.
    if (!$this->options['links']) {
      hide($build['links']);
    }

    if ($display_mode != 'title') {
      // We render node contents and force links to be last.
      $build['links']['#weight'] = 1000;
      $item_text .= drupal_render($build);
    }

    $item = new stdClass();
    $item->description = $item_text;
    $item->title = $node->title;
    $item->link = $node->link;
    $item->elements = $node->rss_elements;
    $item->nid = $node->nid;

    return theme($this->theme_functions(), array(
      'view' => $this->view,
      'options' => $this->options,
      'row' => $item
    ));
  }
}
