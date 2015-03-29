<?php

/**
 * @file
 * Definition of Drupal\node\Plugin\views\argument\Nid.
 */

namespace Drupal\node\Plugin\views\argument;

use Drupal\Component\Utility\SafeMarkup;
use Drupal\node\NodeStorageInterface;
use Drupal\views\Plugin\views\argument\NumericArgument;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Argument handler to accept a node id.
 *
 * @ViewsArgument("node_nid")
 */
class Nid extends NumericArgument {

  /**
   * The node storage.
   *
   * @var \Drupal\node\NodeStorageInterface
   */
  protected $nodeStorage;

  /**
   * Constructs the Nid object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param NodeStorageInterface $node_storage
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, NodeStorageInterface $node_storage) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
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
      $container->get('entity.manager')->getStorage('node')
    );
  }

  /**
   * Override the behavior of title(). Get the title of the node.
   */
  public function titleQuery() {
    $titles = array();

    $nodes = $this->nodeStorage->loadMultiple($this->value);
    foreach ($nodes as $node) {
      $titles[] = SafeMarkup::checkPlain($node->label());
    }
    return $titles;
  }

}
