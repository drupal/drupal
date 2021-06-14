<?php

namespace Drupal\book\Plugin\views\argument_default;

use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\node\NodeStorageInterface;
use Drupal\node\Plugin\views\argument_default\Node;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Default argument plugin to get the current node's top level book.
 *
 * @ViewsArgumentDefault(
 *   id = "top_level_book",
 *   title = @Translation("Top Level Book from current node")
 * )
 */
class TopLevelBook extends Node {

  /**
   * The node storage controller.
   *
   * @var \Drupal\node\NodeStorageInterface
   */
  protected $nodeStorage;

  /**
   * Constructs a Drupal\book\Plugin\views\argument_default\TopLevelBook object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param array $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The route match.
   * @param \Drupal\node\NodeStorageInterface $node_storage
   *   The node storage controller.
   */
  public function __construct(array $configuration, $plugin_id, array $plugin_definition, RouteMatchInterface $route_match, NodeStorageInterface $node_storage) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $route_match);
    $this->nodeStorage = $node_storage;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('current_route_match'),
      $container->get('entity_type.manager')->getStorage('node')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getArgument() {
    // Use the argument_default_node plugin to get the nid argument.
    $nid = parent::getArgument();
    if (!empty($nid)) {
      $node = $this->nodeStorage->load($nid);
      if (isset($node->book['bid'])) {
        return $node->book['bid'];
      }
    }
  }

}
