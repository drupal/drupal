<?php

/**
 * @file
 * Contains \Drupal\aggregator\Plugin\Derivative\AggregatorCategoryBlock.
 */

namespace Drupal\aggregator\Plugin\Derivative;

use Drupal\Core\Plugin\Discovery\ContainerDerivativeInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\StringTranslation\Translator\TranslatorInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides block plugin definitions for aggregator categories.
 *
 * @see \Drupal\aggregator\Plugin\block\block\AggregatorCategoryBlock
 */
class AggregatorCategoryBlock implements ContainerDerivativeInterface {

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
   * The translation manager.
   *
   * @var \Drupal\Core\StringTranslation\Translator\TranslatorInterface
   */
  protected $translationManager;

  /**
   * Constructs a AggregatorCategoryBlock object.
   *
   * @param string $base_plugin_id
   *   The base plugin ID.
   * @param \Drupal\Core\Database\Connection $connection
   *   The database connection.
   * @param \Drupal\Core\StringTranslation\Translator\TranslatorInterface $translation_manager
   *   The translation manager.
   */
  public function __construct($base_plugin_id, Connection $connection, TranslatorInterface $translation_manager) {
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
   * Implements \Drupal\Component\Plugin\Derivative\DerivativeInterface::getDerivativeDefinition().
   */
  public function getDerivativeDefinition($derivative_id, array $base_plugin_definition) {
    if (!empty($this->derivatives) && !empty($this->derivatives[$derivative_id])) {
      return $this->derivatives[$derivative_id];
    }
    $result = $this->connection->query('SELECT cid, title FROM {aggregator_category} ORDER BY title WHERE cid = :cid', array(':cid' => $derivative_id))->fetchObject();
    $this->derivatives[$derivative_id] = $base_plugin_definition;
    $this->derivatives[$derivative_id]['admin_label'] = $this->translationManager->translate('@title category latest items', array('@title' => $result->title));
    return $this->derivatives[$derivative_id];
  }

  /**
   * Implements \Drupal\Component\Plugin\Derivative\DerivativeInterface::getDerivativeDefinitions().
   */
  public function getDerivativeDefinitions(array $base_plugin_definition) {
    // Provide a block plugin definition for each aggregator category.
    $result = $this->connection->query('SELECT cid, title FROM {aggregator_category} ORDER BY title');
    foreach ($result as $category) {
      $this->derivatives[$category->cid] = $base_plugin_definition;
      $this->derivatives[$category->cid]['admin_label'] = $this->translationManager->translate('@title category latest items', array('@title' => $category->title));
    }
    return $this->derivatives;
  }

}
