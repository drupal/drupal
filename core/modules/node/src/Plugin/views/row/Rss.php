<?php

/**
 * @file
 * Contains \Drupal\node\Plugin\views\row\Rss.
 */

namespace Drupal\node\Plugin\views\row;

use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\views\Plugin\views\row\RssPluginBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\node\NodeStorageInterface;

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
 *   base = {"node_field_data"},
 *   display_types = {"feed"}
 * )
 */
class Rss extends RssPluginBase {

  // Basic properties that let the row style follow relationships.
  var $base_table = 'node_field_data';

  var $base_field = 'nid';

  // Stores the nodes loaded with preRender.
  var $nodes = array();

  /**
   * {@inheritdoc}
   */
  protected $entityTypeId = 'node';

  /**
   * The node storage
   *
   * @var \Drupal\node\NodeStorageInterface
   */
  protected $nodeStorage;

  /**
   * Constructs the Rss object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Entity\EntityManagerInterface $entity_manager
   *   The entity manager.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityManagerInterface $entity_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $entity_manager);
    $this->nodeStorage = $entity_manager->getStorage('node');
  }

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
    $nids = array();
    foreach ($values as $row) {
      $nids[] = $row->{$this->field_alias};
    }
    if (!empty($nids)) {
      $this->nodes = $this->nodeStorage->loadMultiple($nids);
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

    $description_build = [];

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
      $description_build = $build;
    }

    $item = new \stdClass();
    $item->description = $description_build;
    $item->title = $node->label();
    $item->link = $node->link;
    // Provide a reference so that the render call in
    // template_preprocess_views_view_row_rss() can still access it.
    $item->elements = &$node->rss_elements;
    $item->nid = $node->id();
    $build = array(
      '#theme' => $this->themeFunctions(),
      '#view' => $this->view,
      '#options' => $this->options,
      '#row' => $item,
    );

    return $build;
  }

}
