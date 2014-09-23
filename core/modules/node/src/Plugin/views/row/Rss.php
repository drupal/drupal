<?php

/**
 * @file
 * Definition of Drupal\node\Plugin\views\row\Rss.
 */

namespace Drupal\node\Plugin\views\row;

use Drupal\Component\Utility\SafeMarkup;
use Drupal\Component\Utility\String;
use Drupal\Core\Form\FormStateInterface;
use Drupal\views\Plugin\views\row\RowPluginBase;

/**
 * Plugin which performs a node_view on the resulting object
 * and formats it as an RSS item.
 *
 * @ViewsRow(
 *   id = "node_rss",
 *   title = @Translation("Content"),
 *   help = @Translation("Display the content with standard node view."),
 *   theme = "views_view_row_rss",
 *   register_theme = FALSE,
 *   base = {"node"},
 *   display_types = {"feed"}
 * )
 */
class Rss extends RowPluginBase {

  // Basic properties that let the row style follow relationships.
  var $base_table = 'node';

  var $base_field = 'nid';

  // Stores the nodes loaded with preRender.
  var $nodes = array();

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
      '#options' => $this->buildOptionsForm_summary_options(),
      '#default_value' => $this->options['view_mode'],
    );
  }

  /**
   * Return the main options, which are shown in the summary title.
   */
  public function buildOptionsForm_summary_options() {
    $view_modes = \Drupal::entityManager()->getViewModes('node');
    $options = array();
    foreach ($view_modes as $mode => $settings) {
      $options[$mode] = $settings['label'];
    }
    $options['title'] = $this->t('Title only');
    $options['default'] = $this->t('Use site default RSS settings');
    return $options;
  }

  public function summaryTitle() {
    $options = $this->buildOptionsForm_summary_options();
    return String::checkPlain($options[$this->options['view_mode']]);
  }

  public function preRender($values) {
    $nids = array();
    foreach ($values as $row) {
      $nids[] = $row->{$this->field_alias};
    }
    if (!empty($nids)) {
      $this->nodes = node_load_multiple($nids);
    }
  }

  public function render($row) {
    global $base_url;

    $nid = $row->{$this->field_alias};
    if (!is_numeric($nid)) {
      return;
    }

    $display_mode = $this->options['view_mode'];
    if ($display_mode == 'default') {
      $display_mode = \Drupal::config('system.rss')->get('items.view_mode');
    }

    // Load the specified node:
    /** @var \Drupal\node\NodeInterface $node */
    $node = $this->nodes[$nid];
    if (empty($node)) {
      return;
    }

    $item_text = '';

    $node->link = $node->url('canonical', array('absolute' => TRUE));
    $node->rss_namespaces = array();
    $node->rss_elements = array(
      array(
        'key' => 'pubDate',
        'value' => gmdate('r', $node->getCreatedTime()),
      ),
      array(
        'key' => 'dc:creator',
        'value' => $node->getOwner()->getUsername(),
      ),
      array(
        'key' => 'guid',
        'value' => $node->id() . ' at ' . $base_url,
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

    if ($display_mode != 'title') {
      // We render node contents.
      $item_text .= drupal_render($build);
    }

    $item = new \stdClass();
    $item->description = SafeMarkup::set($item_text);
    $item->title = $node->label();
    $item->link = $node->link;
    $item->elements = $node->rss_elements;
    $item->nid = $node->id();
    $theme_function = array(
      '#theme' => $this->themeFunctions(),
      '#view' => $this->view,
      '#options' => $this->options,
      '#row' => $item,
    );
    return drupal_render($theme_function);
  }

}
