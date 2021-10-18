<?php

namespace Drupal\migrate_drupal\Plugin\migrate\source\d6;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\State\StateInterface;
use Drupal\migrate\Exception\RequirementsException;
use Drupal\migrate\Plugin\MigrationInterface;
use Drupal\migrate_drupal\Plugin\migrate\source\DrupalSqlBase;

/**
 * Drupal 6 i18n_variable source from database.
 *
 * Available configuration keys:
 * - variables: (required) The list of variable translations to retrieve from
 *   the source database. All translations are retrieved in a single row.
 *
 * Examples:
 *
 * @code
 * plugin: d6_variable_translation
 * variables:
 *   - site_offline_message
 * @endcode
 * In this example the translations for site_offline_message variable are
 * retrieved from the source database.
 *
 * For additional configuration keys, refer to the parent classes.
 *
 * @see \Drupal\migrate\Plugin\migrate\source\SqlBase
 * @see \Drupal\migrate\Plugin\migrate\source\SourcePluginBase
 *
 * @MigrateSource(
 *   id = "d6_variable_translation",
 *   source_module = "i18n",
 * )
 */
class VariableTranslation extends DrupalSqlBase {

  /**
   * The variable names to fetch.
   *
   * @var array
   */
  protected $variables;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, MigrationInterface $migration, StateInterface $state, EntityTypeManagerInterface $entity_type_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $migration, $state, $entity_type_manager);
    $this->variables = $this->configuration['variables'];
  }

  /**
   * {@inheritdoc}
   */
  protected function initializeIterator() {
    return new \ArrayIterator($this->values());
  }

  /**
   * Return the values of the variables specified in the plugin configuration.
   *
   * @return array
   *   An associative array where the keys are the variables specified in the
   *   plugin configuration and the values are the values found in the source.
   *   A key/value pair is added for the language code. Only those values are
   *   returned that are actually in the database.
   */
  protected function values() {
    $values = [];
    $result = $this->prepareQuery()->execute()->FetchAllAssoc('language');
    foreach ($result as $i18n_variable) {
      $values[]['language'] = $i18n_variable->language;
    }
    $result = $this->prepareQuery()->execute()->FetchAll();
    foreach ($result as $i18n_variable) {
      foreach ($values as $key => $value) {
        if ($values[$key]['language'] === $i18n_variable->language) {
          $values[$key][$i18n_variable->name] = unserialize($i18n_variable->value);
          break;
        }
      }
    }
    return $values;
  }

  /**
   * {@inheritdoc}
   */
  public function count($refresh = FALSE) {
    return $this->initializeIterator()->count();
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
      ->select('i18n_variable', 'v')
      ->fields('v')
      ->condition('name', (array) $this->configuration['variables'], 'IN');
  }

  /**
   * {@inheritdoc}
   */
  public function getIds() {
    $ids['language']['type'] = 'string';
    return $ids;
  }

  /**
   * {@inheritdoc}
   */
  public function checkRequirements() {
    if (!$this->getDatabase()->schema()->tableExists('i18n_variable')) {
      throw new RequirementsException("Source database table 'i18n_variable' does not exist");
    }
    parent::checkRequirements();
  }

}
