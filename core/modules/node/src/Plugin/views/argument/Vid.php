<?php

namespace Drupal\node\Plugin\views\argument;

use Drupal\Core\Database\Connection;
use Drupal\views\Plugin\views\argument\NumericArgument;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Argument handler to accept a node revision id.
 *
 * @ViewsArgument("node_vid")
 */
class Vid extends NumericArgument {

  /**
   * Database Service Object.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * The node storage.
   *
   * @var \Drupal\node\NodeStorageInterface
   */
  protected $nodeStorage;

  /**
   * Constructs a \Drupal\node\Plugin\views\argument\Vid object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\node\NodeStorageInterface $node_storage
   *   The node storage.
   * @param \Drupal\Core\Database\Connection|null $database
   *   Database Service Object.
   *
   * @todo Remove deprecation layer and add argument type to $node_storage.
   *    https://www.drupal.org/project/drupal/issues/3178818
   */
  // @codingStandardsIgnoreLine
  public function __construct(array $configuration, $plugin_id, $plugin_definition, $node_storage, $database = NULL) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    if ($node_storage instanceof Connection) {
      // Reorder the constructor parameters for BC.
      $node_storage = func_get_arg(4);
      $database = func_get_arg(3);
      @trigger_error('Calling ' . __METHOD__ . '() with the $database parameter is deprecated in drupal:9.2.0 and will be removed in drupal:10.0.0. See https://www.drupal.org/node/3178412', E_USER_DEPRECATED);
      if ($database) {
        $this->database = $database;
      }
    }
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
      $container->get('entity_type.manager')->getStorage('node')
    );
  }

  /**
   * Override the behavior of title(). Get the title of the revision.
   */
  public function titleQuery() {
    $titles = [];

    $results = $this->nodeStorage->getAggregateQuery()
      ->allRevisions()
      ->groupBy('title')
      ->execute();

    foreach ($results as $result) {
      $titles[] = $result['title'];
    }

    return $titles;
  }

}
