<?php

/**
 * @file
 * Definition of Drupal\comment\Plugin\views\row\Rss.
 */

namespace Drupal\comment\Plugin\views\row;

use Drupal\Core\Form\FormStateInterface;
use Drupal\views\Plugin\views\row\RowPluginBase;

/**
 * Plugin which formats the comments as RSS items.
 *
 * @ViewsRow(
 *   id = "comment_rss",
 *   title = @Translation("Comment"),
 *   help = @Translation("Display the comment as RSS."),
 *   theme = "views_view_row_rss",
 *   register_theme = FALSE,
 *   base = {"comment"},
 *   display_types = {"feed"}
 * )
 */
class Rss extends RowPluginBase {

   var $base_table = 'comment';
   var $base_field = 'cid';

  protected function defineOptions() {
    $options = parent::defineOptions();

    $options['view_mode'] = array('default' => 'default');

    return $options;
  }

  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildOptionsForm($form, $form_state);

    $form['view_mode'] = array(
      '#type' => 'select',
      '#title' => $this->t('Display type'),
      '#options' => $this->options_form_summary_options(),
      '#default_value' => $this->options['view_mode'],
    );
  }

  public function preRender($result) {
    $cids = array();

    foreach ($result as $row) {
      $cids[] = $row->cid;
    }

    $this->comments = entity_load_multiple('comment', $cids);
    foreach ($this->comments as $comment) {
      $comment->depth = count(explode('.', $comment->getThread())) - 1;
    }

  }

  /**
   * Return the main options, which are shown in the summary title
   *
   * @see views_plugin_row_node_rss::options_form_summary_options()
   * @todo: Maybe provide a views_plugin_row_rss_entity and reuse this method
   * in views_plugin_row_comment|node_rss.inc
   */
  function options_form_summary_options() {
    $view_modes = \Drupal::entityManager()->getViewModes('node');
    $options = array();
    foreach ($view_modes as $mode => $settings) {
      $options[$mode] = $settings['label'];
    }
    $options['title'] = $this->t('Title only');
    $options['default'] = $this->t('Use site default RSS settings');
    return $options;
  }

  public function render($row) {
    global $base_url;

    $cid = $row->{$this->field_alias};
    if (!is_numeric($cid)) {
      return;
    }

    $view_mode = $this->options['view_mode'];
    if ($view_mode == 'default') {
      $view_mode = \Drupal::config('system.rss')->get('items.view_mode');
    }

    // Load the specified comment and its associated node:
    /** @var $comment \Drupal\comment\CommentInterface */
    $comment = $this->comments[$cid];
    if (empty($comment)) {
      return;
    }

    $item_text = '';

    $comment->link = $comment->url('canonical', array('absolute' => TRUE));
    $comment->rss_namespaces = array();
    $comment->rss_elements = array(
      array(
        'key' => 'pubDate',
        'value' => gmdate('r', $comment->getCreatedTime()),
      ),
      array(
        'key' => 'dc:creator',
        'value' => $comment->getAuthorName(),
      ),
      array(
        'key' => 'guid',
        'value' => 'comment ' . $comment->id() . ' at ' . $base_url,
        'attributes' => array('isPermaLink' => 'false'),
      ),
    );

    // The comment gets built and modules add to or modify
    // $comment->rss_elements and $comment->rss_namespaces.
    $build = comment_view($comment, 'rss');
    unset($build['#theme']);

    if (!empty($comment->rss_namespaces)) {
      $this->view->style_plugin->namespaces = array_merge($this->view->style_plugin->namespaces, $comment->rss_namespaces);
    }

    if ($view_mode != 'title') {
      // We render comment contents.
      $item_text .= drupal_render_root($build);
    }

    $item = new \stdClass();
    $item->description = $item_text;
    $item->title = $comment->label();
    $item->link = $comment->link;
    $item->elements = $comment->rss_elements;
    $item->cid = $comment->id();

    $build = array(
      '#theme' => $this->themeFunctions(),
      '#view' => $this->view,
      '#options' => $this->options,
      '#row' => $item,
    );
    return drupal_render_root($build);
  }

}
