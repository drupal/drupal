<?php

namespace Drupal\migrate_drupal\Plugin\migrate\source;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\State\StateInterface;
use Drupal\migrate\Plugin\MigrationInterface;

/**
 * Drupal 6/7 variable source from database.
 *
 * This source class fetches variables from the source Drupal database.
 * Depending on the configuration, this returns zero or a single row and as such
 * is not a good example for any normal source class returning multiple rows.
 *
 * Available configuration keys (one of which must be defined):
 * - variables: (optional) The list of variables to retrieve from the source
 *   database. Specified variables are retrieved in a single row.
 * - variables_no_row_if_missing: (optional) The list of variables to retrieve
 *   from the source database. If any of the variables listed here are missing
 *   in the source, then the source will return zero rows.
 *
 * Examples:
 *
 * With this configuration, the source will return one row even when the
 * "filter_fallback_format" variable isn't available:
 * @code
 * source:
 *   plugin: variable
 *   variables:
 *     - filter_fallback_format
 * @endcode
 *
 * With this configuration, the source will return one row if the variable is
 * available, and zero if it isn't:
 * @code
 * source:
 *   plugin: variable
 *   variables_no_row_if_missing:
 *     - filter_fallback_format
 * @endcode
 *
 * The variables and the variables_no_row_if_missing lists are always merged
 * together. All of the following configurations are valid:
 * @code
 * source:
 *   plugin: variable
 *   variables:
 *     - book_child_type
 *     - book_block_mode
 *     - book_allowed_types
 *   variables_no_row_if_missing:
 *     - book_child_type
 *     - book_block_mode
 *     - book_allowed_types
 *
 * source:
 *   plugin: variable
 *   variables:
 *     - book_child_type
 *     - book_block_mode
 *   variables_no_row_if_missing:
 *     - book_allowed_types
 *
 * source:
 *   plugin: variable
 *   variables_no_row_if_missing:
 *     - book_child_type
 *     - book_block_mode
 *     - book_allowed_types
 * @endcode
 *
 * For additional configuration keys, refer to the parent classes.
 *
 * @see \Drupal\migrate\Plugin\migrate\source\SqlBase
 * @see \Drupal\migrate\Plugin\migrate\source\SourcePluginBase
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
   * The variables that result in no row if any are missing from the source.
   *
   * @var array
   */
  protected $variablesNoRowIfMissing;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, MigrationInterface $migration, StateInterface $state, EntityTypeManagerInterface $entity_type_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $migration, $state, $entity_type_manager);
    $this->variablesNoRowIfMissing = $this->configuration['variables_no_row_if_missing'] ?? [];
    $variables = $this->configuration['variables'] ?? [];
    $this->variables = array_unique(array_merge(array_values($variables), array_values($this->variablesNoRowIfMissing)));
  }

  /**
   * {@inheritdoc}
   */
  protected function initializeIterator() {
    if ($this->count()) {
      return new \ArrayIterator([$this->values()]);
    }

    return new \ArrayIterator();
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
  protected function doCount() {
    if (empty($this->variablesNoRowIfMissing)) {
      return 1;
    }
    $variable_names = array_keys($this->query()->execute()->fetchAllAssoc('name'));

    if (!empty(array_diff($this->variablesNoRowIfMissing, $variable_names))) {
      return 0;
    }
    return 1;
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
