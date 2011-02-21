<?php
// $Id: views_plugin_row_node_rss.inc,v 1.7.4.7 2010/11/11 19:41:11 dereine Exp $
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

    return $options;
  }

  function options_form(&$form, &$form_state) {
    parent::options_form($form, $form_state);

    $form['item_length'] = array(
      '#type' => 'select',
      '#title' => t('Display type'),
      '#options' => array(
        'fulltext' => t('Full text'),
        'teaser' => t('Title plus teaser'),
        'title' => t('Title only'),
        'default' => t('Use default RSS settings'),
      ),
      '#default_value' => $this->options['item_length'],
    );
  }

  function pre_render($values) {
    foreach ($values as $row) {
      $nids[] = $row->{$this->field_alias};
    }
    $this->nodes = node_load_multiple($nids);
  }

  function render($row) {
    // For the most part, this code is taken from node_feed() in node.module
    global $base_url;

    $nid = $row->{$this->field_alias};
    if (!is_numeric($nid)) {
      return;
    }

    $item_length = $this->options['item_length'];
    if ($item_length == 'default') {
      $item_length = variable_get('feed_item_length', 'teaser');
    }

    // Load the specified node:
    $node = $this->nodes[$nid];
    if (empty($node)) {
      return;
    }

    $item_text = '';

    $node->rss_namespaces = array();
    $node->rss_elements = array(
      array('key' => 'pubDate', 'value' => gmdate('r', $node->created)),
      array('key' => 'dc:creator', 'value' => $node->name, 'namespace' => array('xmlns:dc' => 'http://purl.org/dc/elements/1.1/')),
      array('key' => 'guid', 'value' => $node->nid . ' at ' . $base_url, 'attributes' => array('isPermaLink' => 'false'))
    );

    // The node gets built and modules add to or modify $node->rss_elements
    // and $node->rss_namespaces.
    node_build_content($node, 'rss');

    foreach ($node->rss_elements as $element) {
      if (isset($element['namespace'])) {
        $this->view->style_plugin->namespaces = array_merge($this->view->style_plugin->namespaces, $element['namespace']);
      }
    }

    if ($item_length != 'title' && !empty($node->content)) {
      // We render node contents and force links to be last.
      $links = drupal_render($node->content['links']);
      $item_text .= drupal_render($node->content) . $links;
    }

    $item->description = $item_text;
    $item->title = $node->title;
    $item->link = url("node/$node->nid", array('absolute' => TRUE));
    $item->elements = $node->rss_elements;
    $item->nid = $node->nid;
    if (isset($node->readmore)) {
      $item->readmore = $node->readmore;
    }

    return theme($this->theme_functions(),
      array(
        'view' => $this->view,
        'options' => $this->options,
        'row' => $item
      ));
  }
}

