<?php

/**
 * @file
 * Contains \Drupal\views\Plugin\views\join\JoinPluginBase.
 */

namespace Drupal\views\Plugin\views\join;

use Drupal\Core\Plugin\PluginBase;

/**
 * @defgroup views_join_handlers Views join handler plugins
 * @{
 * Handler plugins for Views table joins.
 *
 * Handler plugins help build the view query object. Join handler plugins
 * handle table joins.
 *
 * Views join handlers extend \Drupal\views\Plugin\views\join\JoinPluginBase.
 * They must be annotated with \Drupal\views\Annotation\ViewsJoin annotation,
 * and they must be in namespace directory Plugin\views\join.
 *
 * Here is an example of how to join from table one to table two so it produces
 * the following SQL:
 * @code
 * INNER JOIN {two} ON one.field_a = two.field_b
 * @endcode
 * The required php code for this kind of functionality is the following:
 * @code
 * $configuration = array(
 *   'table' => 'two',
 *   'field' => 'field_b',
 *   'left_table' => 'one',
 *   'left_field' => 'field_a',
 *   'operator' => '='
 * );
 * $join = Views::pluginManager('join')->createInstance('standard', $configuration);
 * @endcode
 *
 * Here is an example of a more complex join:
 * @code
 * class JoinComplex extends JoinPluginBase {
 *   public function buildJoin($select_query, $table, $view_query) {
 *     // Add an additional hardcoded condition to the query.
 *     $this->extra = 'foo.bar = baz.boing';
 *     parent::buildJoin($select_query, $table, $view_query);
 *   }
 * }
 * @endcode
 *
 * @ingroup views_plugins
 * @see plugin_api
 */

/**
 * Represents a join and creates the SQL necessary to implement the join.
 *
 * Extensions of this class can be used to create more interesting joins.
 */
class JoinPluginBase extends PluginBase implements JoinPluginInterface {

  /**
   * The table to join (right table).
   *
   * @var string
   */
  public $table;

  /**
   * The field to join on (right field).
   *
   * @var string
   */
  public $field;

  /**
   * The table we join to.
   *
   * @var string
   */
  public $leftTable;

  /**
   * The field we join to.
   *
   * @var string
   */
  public $leftField;

  /**
   * An array of extra conditions on the join.
   *
   * Each condition is either a string that's directly added, or an array of
   * items:
   *   - table(optional): If not set, current table; if NULL, no table. If you
   *     specify a table in cached configuration, Views will try to load from an
   *     existing alias. If you use realtime joins, it works better.
   *   - field(optional): Field or formula. In formulas we can reference the
   *     right table by using %alias.
   *   - operator(optional): The operator used, Defaults to "=".
   *   - value: Must be set. If an array, operator will be defaulted to IN.
   *   - numeric: If true, the value will not be surrounded in quotes.
   *
   * @see SelectQueryInterface::addJoin()
   *
   * @var array
   */
  public $extra;

  /**
   * The join type, so for example LEFT (default) or INNER.
   *
   * @var string
   */
  public $type;

  /**
   * The configuration array passed by initJoin.
   *
   * @var array
   *
   * @see \Drupal\views\Plugin\views\join\JoinPluginBase::initJoin()
   */
  public $configuration = array();

  /**
   * How all the extras will be combined. Either AND or OR.
   *
   * @var string
   */
  public $extraOperator;

  /**
   * Defines whether a join has been adjusted.
   *
   * Views updates the join object to set the table alias instead of the table
   * name. Once views has changed the alias it sets the adjusted value so it
   * does not have to be updated anymore. If you create your own join object
   * you should set the adjusted in the definition array to TRUE if you already
   * know the table alias.
   *
   * @var bool
   *
   * @see \Drupal\views\Plugin\HandlerBase::getTableJoin()
   * @see \Drupal\views\Plugin\views\query\Sql::adjustJoin()
   * @see \Drupal\views\Plugin\views\relationship\RelationshipPluginBase::query()
   */
  public $adjusted;

  /**
   * Constructs a Drupal\views\Plugin\views\join\JoinPluginBase object.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    // Merge in some default values.
    $configuration += array(
      'type' => 'LEFT',
      'extra_operator' => 'AND'
    );
    $this->configuration = $configuration;

    if (!empty($configuration['table'])) {
      $this->table = $configuration['table'];
    }

    $this->leftTable = $configuration['left_table'];
    $this->leftField = $configuration['left_field'];
    $this->field = $configuration['field'];

    if (!empty($configuration['extra'])) {
      $this->extra = $configuration['extra'];
    }

    if (isset($configuration['adjusted'])) {
      $this->adjusted = $configuration['adjusted'];
    }

    $this->extraOperator = strtoupper($configuration['extra_operator']);
    $this->type = $configuration['type'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildJoin($select_query, $table, $view_query) {
    if (empty($this->configuration['table formula'])) {
      $right_table = $this->table;
    }
    else {
      $right_table = $this->configuration['table formula'];
    }

    if ($this->leftTable) {
      $left = $view_query->getTableInfo($this->leftTable);
      $left_field = "$left[alias].$this->leftField";
    }
    else {
      // This can be used if left_field is a formula or something. It should be used only *very* rarely.
      $left_field = $this->leftField;
    }

    $condition = "$left_field = $table[alias].$this->field";
    $arguments = array();

    // Tack on the extra.
    if (isset($this->extra)) {
      if (is_array($this->extra)) {
        $extras = array();
        foreach ($this->extra as $info) {
          // Do not require 'value' to be set; allow for field syntax instead.
          $info += array(
            'value' => NULL,
          );
          // Figure out the table name. Remember, only use aliases provided
          // if at all possible.
          $join_table = '';
          if (!array_key_exists('table', $info)) {
            $join_table = $table['alias'] . '.';
          }
          elseif (isset($info['table'])) {
            // If we're aware of a table alias for this table, use the table
            // alias instead of the table name.
            if (isset($left) && $left['table'] == $info['table']) {
              $join_table = $left['alias'] . '.';
            }
            else {
              $join_table = $info['table'] . '.';
            }
          }

          // Convert a single-valued array of values to the single-value case,
          // and transform from IN() notation to = notation
          if (is_array($info['value']) && count($info['value']) == 1) {
            if (empty($info['operator'])) {
              $operator = '=';
            }
            else {
              $operator = $info['operator'] == 'NOT IN' ? '!=' : '=';
            }
            $info['value'] = array_shift($info['value']);
          }

          if (is_array($info['value'])) {
            // With an array of values, we need multiple placeholders and the
            // 'IN' operator is implicit.
            $local_arguments = array();
            foreach ($info['value'] as $value) {
              $placeholder_i = ':views_join_condition_' . $select_query->nextPlaceholder();
              $local_arguments[$placeholder_i] = $value;
            }

            $operator = !empty($info['operator']) ? $info['operator'] : 'IN';
            $placeholder = '( ' . implode(', ', array_keys($local_arguments)) . ' )';
            $arguments += $local_arguments;
          }
          else {
            // With a single value, the '=' operator is implicit.
            $operator = !empty($info['operator']) ? $info['operator'] : '=';
            // Allow the value to be set either with the 'value' element or
            // with 'left_field'.
            if (isset($info['left_field'])) {
              $placeholder = "$left[alias].$info[left_field]";
            }
            else {
              $placeholder = ':views_join_condition_' . $select_query->nextPlaceholder();
              $arguments[$placeholder] = $info['value'];
            }
          }

          $extras[] = "$join_table$info[field] $operator $placeholder";
        }

        if ($extras) {
          if (count($extras) == 1) {
            $condition .= ' AND ' . array_shift($extras);
          }
          else {
            $condition .= ' AND (' . implode(' ' . $this->extraOperator . ' ', $extras) . ')';
          }
        }
      }
      elseif ($this->extra && is_string($this->extra)) {
        $condition .= " AND ($this->extra)";
      }
    }

    $select_query->addJoin($this->type, $right_table, $table['alias'], $condition, $arguments);
  }

}

/**
 * @}
 */
