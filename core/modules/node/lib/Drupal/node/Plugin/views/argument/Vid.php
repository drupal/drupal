<?php

/**
 * @file
 * Definition of Drupal\node\Plugin\views\argument\Vid.
 */

namespace Drupal\node\Plugin\views\argument;

use Drupal\Core\Database\Connection;
use Drupal\views\Plugin\views\argument\Numeric;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Argument handler to accept a node revision id.
 *
 * @PluginID("node_vid")
 */
class Vid extends Numeric {

  /**
   * Database Service Object.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * Constructs a Drupal\Component\Plugin\PluginBase object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param array $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Database\Connection $database
   *   Database Service Object.
   */
  public function __construct(array $configuration, $plugin_id, array $plugin_definition, Connection $database) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->database = $database;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, array $plugin_definition) {
    return new static($configuration, $plugin_id, $plugin_definition, $container->get('database'));
  }

  /**
   * Override the behavior of title(). Get the title of the revision.
   */
  public function titleQuery() {
    $titles = array();

    $results = $this->database->query('SELECT nr.vid, nr.nid, npr.title FROM {node_revision} nr WHERE nr.vid IN (:vids)', array(':vids' => $this->value))->fetchAllAssoc('vid', PDO::FETCH_ASSOC);
    $nids = array();
    foreach ($results as $result) {
      $nids[] = $result['nid'];
    }

    $nodes = node_load_multiple(array_unique($nids));

    foreach ($results as $result) {
      $nodes[$result['nid']]->set('title', $result['title']);
      $titles[] = check_plain($nodes[$result['nid']]->label());
    }

    return $titles;
  }

}
