<?php

namespace Drupal\node\Plugin\views\argument;

use Drupal\Core\DependencyInjection\DeprecatedServicePropertyTrait;
use Drupal\views\Plugin\views\argument\NumericArgument;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\node\NodeStorageInterface;

/**
 * Argument handler to accept a node revision id.
 *
 * @ViewsArgument("node_vid")
 */
class Vid extends NumericArgument {

  use DeprecatedServicePropertyTrait;

  /**
   * {@inheritdoc}
   */
  protected $deprecatedProperties = ['database' => 'database'];

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
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, $node_storage) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    if (!$node_storage instanceof NodeStorageInterface) {
      @trigger_error('Passing the database service to ' . __METHOD__ . '() is deprecated in drupal:9.2.0 and will be removed before drupal:10.0.0. See https://www.drupal.org/node/3178412', E_USER_DEPRECATED);
      $node_storage = func_get_arg(4);
    }
    if (!$node_storage instanceof NodeStorageInterface) {
      throw new \InvalidArgumentException('The fourth argument must implement \Drupal\node\NodeStorageInterface.');
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
      ->accessCheck(FALSE)
      ->allRevisions()
      ->groupBy('title')
      ->execute();

    foreach ($results as $result) {
      $titles[] = $result['title'];
    }

    return $titles;
  }

}
