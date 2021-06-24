<?php

namespace Drupal\node\Plugin\views\row;

use Drupal\views\Plugin\views\row\RssPluginBase;

/**
 * Performs a node_view on the resulting object and formats it as an RSS item.
 *
 * @ViewsRow(
 *   id = "node_rss",
 *   title = @Translation("Content"),
 *   help = @Translation("Display the content with standard node view."),
 *   theme = "views_view_row_rss",
 *   register_theme = FALSE,
 *   base = {"node_field_data"},
 *   display_types = {"feed"}
 * )
 */
class Rss extends RssPluginBase {

  /**
   * The base table for this row plugin.
   *
   * @var string
   */
  public $base_table = 'node_field_data';

  /**
   * The base field for this row plugin.
   *
   * @var string
   */
  public $base_field = 'nid';

  /**
   * Stores the nodes loaded with preRender.
   *
   * @var array
   */
  public $nodes = [];

  /**
   * {@inheritdoc}
   */
  protected $entityTypeId = 'node';

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm_summary_options() {
    $options = parent::buildOptionsForm_summary_options();
    $options['title'] = $this->t('Title only');
    $options['default'] = $this->t('Use site default RSS settings');
    return $options;
  }

  public function summaryTitle() {
    $options = $this->buildOptionsForm_summary_options();
    return $options[$this->options['view_mode']];
  }

  public function preRender($values) {
    $nids = [];
    foreach ($values as $row) {
      $nids[] = $row->{$this->field_alias};
    }
    if (!empty($nids)) {
      $this->nodes = $this->entityTypeManager->getStorage('node')->loadMultiple($nids);
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

    $node->rss_namespaces = [];
    $node->rss_elements = [
      [
        'key' => 'pubDate',
        'value' => gmdate('r', $node->getCreatedTime()),
      ],
      [
        'key' => 'dc:creator',
        'value' => $node->getOwner()->getDisplayName(),
      ],
      [
        'key' => 'guid',
        'value' => $node->id() . ' at ' . $base_url,
        'attributes' => ['isPermaLink' => 'false'],
      ],
    ];

    // The node gets built and modules add to or modify $node->rss_elements
    // and $node->rss_namespaces.

    $build_mode = $display_mode;

    $build = \Drupal::entityTypeManager()
      ->getViewBuilder('node')
      ->view($node, $build_mode);
    unset($build['#theme']);

    if (!empty($node->rss_namespaces)) {
      $this->view->style_plugin->namespaces = array_merge($this->view->style_plugin->namespaces, $node->rss_namespaces);
    }
    elseif (function_exists('rdf_get_namespaces')) {
      // Merge RDF namespaces in the XML namespaces in case they are used
      // further in the RSS content.
      $xml_rdf_namespaces = [];
      foreach (rdf_get_namespaces() as $prefix => $uri) {
        $xml_rdf_namespaces['xmlns:' . $prefix] = $uri;
      }
      $this->view->style_plugin->namespaces += $xml_rdf_namespaces;
    }

    $item = new \stdClass();
    if ($display_mode != 'title') {
      // We render node contents.
      $item->description = $build;
    }
    $item->title = $node->label();
    $item->link = $node->toUrl('canonical', ['absolute' => TRUE])->toString();
    // Provide a reference so that the render call in
    // template_preprocess_views_view_row_rss() can still access it.
    $item->elements = &$node->rss_elements;
    $item->nid = $node->id();
    $build = [
      '#theme' => $this->themeFunctions(),
      '#view' => $this->view,
      '#options' => $this->options,
      '#row' => $item,
    ];

    return $build;
  }

}
