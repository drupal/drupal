<?php

/**
 * @file
 * Contains \Drupal\aggregator\Plugin\views\filter\CategoryCid.
 */

namespace Drupal\aggregator\Plugin\views\filter;

use Drupal\views\Plugin\views\filter\InOperator;
use Drupal\Component\Annotation\PluginID;

/**
 * Defines a filter handler that filters by aggregator category cid.
 *
 * @ingroup views_filter_handlers
 *
 * @PluginID("aggregator_category_cid")
 */
class CategoryCid extends InOperator {

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
   * {@inheritdoc}
   */
  function getValueOptions() {
    if (isset($this->value_options)) {
      return;
    }

    $this->value_options = array();
    $this->value_options = $this->database->query('SELECT cid, title FROM {aggregator_category} ORDER BY title')->fetchAllKeyed();
  }

}
