<?php

/**
 * @file
 * Contains \Drupal\aggregator\Plugin\Derivative\AggregatorFeedBlock.
 */

namespace Drupal\aggregator\Plugin\Derivative;

use Drupal\Core\Plugin\Discovery\ContainerDerivativeInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\StringTranslation\TranslationInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides block plugin definitions for aggregator feeds.
 *
 * @see \Drupal\aggregator\Plugin\block\block\AggregatorFeedBlock
 */
class AggregatorFeedBlock implements ContainerDerivativeInterface {

  /**
   * List of derivative definitions.
   *
   * @var array
   */
  protected $derivatives = array();

  /**
   * The base plugin ID this derivative is for.
   *
   * @var string
   */
  protected $basePluginId;

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $connection;

  /**
   * The translation manager
   *
   * @var \Drupal\Core\StringTranslation\TranslationInterface
   */
  protected $translationManager;

  /**
   * Constructs a AggregatorFeedBlock object.
   *
   * @param string $base_plugin_id
   *   The base plugin ID.
   * @param \Drupal\Core\Database\Connection $connection
   *   The database connection.
   * @param \Drupal\Core\StringTranslation\TranslationInterface $translation_manager
   *   The translation manager.
   */
  public function __construct($base_plugin_id, Connection $connection, TranslationInterface $translation_manager) {
    $this->basePluginId = $base_plugin_id;
    $this->connection = $connection;
    $this->translationManager = $translation_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, $base_plugin_id) {
    return new static(
      $base_plugin_id,
      $container->get('database'),
      $container->get('string_translation')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getDerivativeDefinition($derivative_id, array $base_plugin_definition) {
    if (!empty($this->derivatives) && !empty($this->derivatives[$derivative_id])) {
      return $this->derivatives[$derivative_id];
    }
    $result = $this->connection->query('SELECT fid, title, block FROM {aggregator_feed} WHERE block <> 0 AND fid = :fid', array(':fid' => $derivative_id))->fetchObject();
    $this->derivatives[$derivative_id] = $base_plugin_definition;
    $this->derivatives[$derivative_id]['delta'] = $result->fid;
    $this->derivatives[$derivative_id]['admin_label'] = $this->t('@title feed latest items', array('@title' => $result->title));
    return $this->derivatives[$derivative_id];
  }

  /**
   * {@inheritdoc}
   */
  public function getDerivativeDefinitions(array $base_plugin_definition) {
    // Add a block plugin definition for each feed.
    $result = $this->connection->query('SELECT fid, title FROM {aggregator_feed} WHERE block <> 0 ORDER BY fid');
    foreach ($result as $feed) {
      $this->derivatives[$feed->fid] = $base_plugin_definition;
      $this->derivatives[$feed->fid]['delta'] = $feed->fid;
      $this->derivatives[$feed->fid]['admin_label'] = $this->t('@title feed latest items', array('@title' => $feed->title));
    }
    return $this->derivatives;
  }

  /**
   * Translates a string to the current language or to a given language.
   *
   * See the t() documentation for details.
   */
  protected function t($string, array $args = array(), array $options = array()) {
    return $this->translationManager->translate($string, $args, $options);
  }

}
