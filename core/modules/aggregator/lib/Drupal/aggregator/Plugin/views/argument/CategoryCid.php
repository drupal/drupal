<?php

/**
 * @file
 * Contains \Drupal\aggregator\Plugin\views\argument\CategoryCid.
 */

namespace Drupal\aggregator\Plugin\views\argument;

use Drupal\views\Plugin\views\argument\Numeric;
use Drupal\Component\Annotation\PluginID;
use Drupal\Component\Utility\String;
use Drupal\Core\Database\Connection;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Argument handler to accept an aggregator category id.
 *
 * @ingroup views_argument_handlers
 *
 * @PluginID("aggregator_category_cid")
 */
class CategoryCid extends Numeric {

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
  function titleQuery() {
    $titles = $this->database->query("SELECT title FROM {aggregator_category} where cid IN (:cid)", array(':cid' => $this->value))->fetchCol();

    return array_map(function ($title) {
      return String::checkPlain($title);
    }, $titles);
  }

}
