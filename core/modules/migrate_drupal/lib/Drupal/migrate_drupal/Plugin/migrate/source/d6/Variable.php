<?php

/**
 * @file
 * Contains \Drupal\migrate\Plugin\migrate\source\d6\Variable.
 */

namespace Drupal\migrate_drupal\Plugin\migrate\source\d6;

use Drupal\migrate\Entity\MigrationInterface;

/**
 * Drupal 6 variable source from database.
 *
 * This source class always returns a single row and as such is not a good
 * example for any normal source class returning multiple rows.
 *
 * @PluginID("drupal6_variable")
 */
class Variable extends Drupal6SqlBase {

  /**
   * The variable names to fetch.
   *
   * @var array
   */
  protected $variables;

  /**
   * {@inheritdoc}
   */
  function __construct(array $configuration, $plugin_id, array $plugin_definition, MigrationInterface $migration) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $migration);
    $this->variables = $this->configuration['variables'];
  }

  protected function runQuery() {
    return new \ArrayIterator(array(array_map('unserialize', $this->query()->execute()->fetchAllKeyed())));
  }

  public function count() {
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
  function query() {
    return $this->getDatabase()
      ->select('variable', 'v')
      ->fields('v', array('name', 'value'))
      ->condition('name', $this->variables, 'IN');
  }
}
