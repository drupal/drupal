<?php

/**
 * @file
 * Contains the comment RSS row style plugin.
 */

/**
 * Plugin which formats the comments as RSS items.
 */
class views_plugin_row_comment_rss extends views_plugin_row {
   var $base_table = 'comment';
   var $base_field = 'cid';

  function option_definition() {
    $options = parent::option_definition();

    $options['item_length'] = array('default' => 'default');
    $options['links'] = array('default' => FALSE, 'bool' => TRUE);

    return $options;
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


  function pre_render($result) {
    $cids = array();
    $nids = array();

    foreach ($result as $row) {
      $cids[] = $row->cid;
    }

    $this->comments = comment_load_multiple($cids);
    foreach ($this->comments as &$comment) {
      $comment->depth = count(explode('.', $comment->thread)) - 1;
      $nids[] = $comment->nid;
    }

    $this->nodes = node_load_multiple($nids);
  }

  /**
   * Return the main options, which are shown in the summary title
   *
   * @see views_plugin_row_node_rss::options_form_summary_options()
   * @todo: Maybe provide a views_plugin_row_rss_entity and reuse this method
   * in views_plugin_row_comment|node_rss.inc
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


  function render($row) {
    global $base_url;

    $cid = $row->{$this->field_alias};
    if (!is_numeric($cid)) {
      return;
    }

    $item_length = $this->options['item_length'];
    if ($item_length == 'default') {
      $item_length = variable_get('feed_item_length', 'teaser');
    }

    // Load the specified comment and its associated node:
    $comment = $this->comments[$cid];
    if (empty($comment) || empty($this->nodes[$comment->nid])) {
      return;
    }

    $item_text = '';

    $uri = entity_uri('comment', $comment);
    $comment->link = url($uri['path'], $uri['options'] + array('absolute' => TRUE));
    $comment->rss_namespaces = array();
    $comment->rss_elements = array(
      array(
        'key' => 'pubDate',
        'value' => gmdate('r', $comment->created),
      ),
      array(
        'key' => 'dc:creator',
        'value' => $comment->name,
      ),
      array(
        'key' => 'guid',
        'value' => 'comment ' . $comment->cid . ' at ' . $base_url,
        'attributes' => array('isPermaLink' => 'false'),
      ),
    );

    // The comment gets built and modules add to or modify
    // $comment->rss_elements and $comment->rss_namespaces.
    $build = comment_view($comment, $this->nodes[$comment->nid], 'rss');
    unset($build['#theme']);

    if (!empty($comment->rss_namespaces)) {
      $this->view->style_plugin->namespaces = array_merge($this->view->style_plugin->namespaces, $comment->rss_namespaces);
    }

    // Hide the links if desired.
    if (!$this->options['links']) {
      hide($build['links']);
    }

    if ($item_length != 'title') {
      // We render comment contents and force links to be last.
      $build['links']['#weight'] = 1000;
      $item_text .= drupal_render($build);
    }

    $item = new stdClass();
    $item->description = $item_text;
    $item->title = $comment->subject;
    $item->link = $comment->link;
    $item->elements = $comment->rss_elements;
    $item->cid = $comment->cid;

    return theme($this->theme_functions(), array(
      'view' => $this->view,
      'options' => $this->options,
      'row' => $item
    ));
  }
}
