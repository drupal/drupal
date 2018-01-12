<?php

namespace Drupal\migrate_drupal\Plugin\migrate\source;

use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\State\StateInterface;
use Drupal\migrate\Plugin\MigrationInterface;

/**
 * Drupal variable source from database.
 *
 * This source class always returns a single row and as such is not a good
 * example for any normal source class returning multiple rows.
 *
 * @MigrateSource(
 *   id = "variable",
 *   source_module = "system",
 * )
 */
class Variable extends DrupalSqlBase {

  /**
   * The variable names to fetch.
   *
   * @var array
   */
  protected $variables;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, MigrationInterface $migration, StateInterface $state, EntityManagerInterface $entity_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $migration, $state, $entity_manager);
    $this->variables = $this->configuration['variables'];
  }

  /**
   * {@inheritdoc}
   */
  protected function initializeIterator() {
    return new \ArrayIterator([$this->values()]);
  }

  /**
   * Return the values of the variables specified in the plugin configuration.
   *
   * @return array
   *   An associative array where the keys are the variables specified in the
   *   plugin configuration and the values are the values found in the source.
   *   Only those values are returned that are actually in the database.
   */
  protected function values() {
    // Create an ID field so we can record migration in the map table.
    // Arbitrarily, use the first variable name.
    $values['id'] = reset($this->variables);
    return $values + array_map('unserialize', $this->prepareQuery()->execute()->fetchAllKeyed());
  }

  /**
   * {@inheritdoc}
   */
  public function count($refresh = FALSE) {
    return intval($this->query()->countQuery()->execute()->fetchField() > 0);
  }

  /**
   * {@inheritdoc}
   */
  public function fields() {
    return array_combine($this->variables, $this->variables);
  }

  /**
   * {@inheritdoc}
   */
  public function query() {
    return $this->getDatabase()
      ->select('variable', 'v')
      ->fields('v', ['name', 'value'])
      ->condition('name', $this->variables, 'IN');
  }

  /**
   * {@inheritdoc}
   */
  public function getIds() {
    $ids['id']['type'] = 'string';
    return $ids;
  }

}
