<?php

namespace Drupal\views\Plugin\views\join;

use Drupal\Core\Database\Query\SelectInterface;
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
 * Here are some examples of configuration for the join plugins.
 *
 * For this SQL:
 * @code
 * LEFT JOIN {two} ON one.field_a = two.field_b
 * @endcode
 * Use this configuration:
 * @code
 * $configuration = array(
 *   'table' => 'two',
 *   'field' => 'field_b',
 *   'left_table' => 'one',
 *   'left_field' => 'field_a',
 *   'operator' => '=',
 * );
 * $join = Views::pluginManager('join')->createInstance('standard', $configuration);
 * @endcode
 * Note that the default join type is a LEFT join when 'type' is not supplied in
 * the join plugin configuration.
 *
 * If an SQL expression is needed for the first part of the left table join
 * condition, 'left_formula' can be used instead of 'left_field'.
 * For this SQL:
 * @code
 * LEFT JOIN {two} ON MAX(one.field_a) = two.field_b AND one.field_c = 'some_val'
 * @endcode
 * Use this configuration:
 * @code
 * $configuration = array(
 *   'table' => 'two',
 *   'field' => 'field_b',
 *   'left_table' => 'one',
 *   'left_formula' => 'MAX(one.field_a)',
 *   'operator' => '=',
 *   'extra' => array(
 *     0 => array(
 *       'left_field' => 'field_c',
 *       'value' => 'some_val',
 *     ),
 *   ),
 * );
 * $join = Views::pluginManager('join')->createInstance('standard', $configuration);
 * @endcode
 *
 * For this SQL:
 * @code
 * INNER JOIN {two} ON one.field_a = two.field_b AND one.field_c = 'some_val'
 * @endcode
 * Use this configuration:
 * @code
 * $configuration = array(
 *   'type' => 'INNER',
 *   'table' => 'two',
 *   'field' => 'field_b',
 *   'left_table' => 'one',
 *   'left_field' => 'field_a',
 *   'operator' => '=',
 *   'extra' => array(
 *     0 => array(
 *       'left_field' => 'field_c',
 *       'value' => 'some_val',
 *     ),
 *   ),
 * );
 * $join = Views::pluginManager('join')->createInstance('standard', $configuration);
 * @endcode
 *
 * For this SQL:
 * @code
 * INNER JOIN {two} ON one.field_a = two.field_b AND two.field_d = 'other_val'
 * @endcode
 * Use this configuration:
 * @code
 * $configuration = array(
 *   'type' => 'INNER',
 *   'table' => 'two',
 *   'field' => 'field_b',
 *   'left_table' => 'one',
 *   'left_field' => 'field_a',
 *   'operator' => '=',
 *   'extra' => array(
 *     0 => array(
 *       'field' => 'field_d',
 *       'value' => 'other_val',
 *     ),
 *   ),
 * );
 * $join = Views::pluginManager('join')->createInstance('standard', $configuration);
 * @endcode
 *
 * For this SQL:
 * @code
 * INNER JOIN {two} ON one.field_a = two.field_b AND one.field_c = two.field_d
 * @endcode
 * Use this configuration:
 * @code
 * $configuration = array(
 *   'type' => 'INNER',
 *   'table' => 'two',
 *   'field' => 'field_b',
 *   'left_table' => 'one',
 *   'left_field' => 'field_a',
 *   'operator' => '=',
 *   'extra' => array(
 *     0 => array(
 *       'left_field' => 'field_c',
 *       'field' => 'field_d',
 *     ),
 *   ),
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
#[\AllowDynamicProperties]
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
   * A formula to be used instead of the left field.
   *
   * @var string
   */
  public $leftFormula;

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
   *   - left_field(optional): Field or formula. In formulas we can reference
   *     the left table by using %alias.
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
  public $configuration = [];

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
    $configuration += [
      'type' => 'LEFT',
      'extra_operator' => 'AND',
      'operator' => '=',
    ];
    $this->configuration = $configuration;

    if (!empty($configuration['table'])) {
      $this->table = $configuration['table'];
    }

    $this->leftTable = $configuration['left_table'];

    if (!empty($configuration['left_field'])) {
      $this->leftField = $configuration['left_field'];
    }

    $this->field = $configuration['field'];

    if (!empty($configuration['left_formula'])) {
      $this->leftFormula = $configuration['left_formula'];
    }

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
      $left_table = $view_query->getTableInfo($this->leftTable);
      $left_field = $this->leftFormula ?: "$left_table[alias].$this->leftField";
    }
    else {
      // This can be used if left_field is a formula or something. It should be used only *very* rarely.
      $left_field = $this->leftField;
      $left_table = NULL;
    }

    $condition = "$left_field " . $this->configuration['operator'] . " $table[alias].$this->field";
    $arguments = [];

    // Tack on the extra.
    if (isset($this->extra)) {
      $this->joinAddExtra($arguments, $condition, $table, $select_query, $left_table);
    }

    $select_query->addJoin($this->type, $right_table, $table['alias'], $condition, $arguments);
  }

  /**
   * Adds the extras to the join condition.
   *
   * @param array $arguments
   *   Array of query arguments.
   * @param string $condition
   *   The condition to be built.
   * @param array $table
   *   The right table.
   * @param \Drupal\Core\Database\Query\SelectInterface $select_query
   *   The current select query being built.
   * @param array $left_table
   *   The left table.
   */
  protected function joinAddExtra(&$arguments, &$condition, $table, SelectInterface $select_query, $left_table = NULL) {
    if (is_array($this->extra)) {
      $extras = [];
      foreach ($this->extra as $info) {
        $extras[] = $this->buildExtra($info, $arguments, $table, $select_query, $left_table);
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

  /**
   * Builds a single extra condition.
   *
   * @param array $info
   *   The extra information. See JoinPluginBase::$extra for details.
   * @param array $arguments
   *   Array of query arguments.
   * @param array $table
   *   The right table.
   * @param \Drupal\Core\Database\Query\SelectInterface $select_query
   *   The current select query being built.
   * @param array $left
   *   The left table.
   *
   * @return string
   *   The extra condition
   */
  protected function buildExtra($info, &$arguments, $table, SelectInterface $select_query, $left) {
    // Do not require 'value' to be set; allow for field syntax instead.
    $info += [
      'value' => NULL,
    ];
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
      $info['value'] = array_shift($info['value']);
    }
    if (is_array($info['value'])) {
      // We use an SA-CORE-2014-005 conformant placeholder for our array
      // of values. Also, note that the 'IN' operator is implicit.
      // @see https://www.drupal.org/node/2401615.
      $operator = !empty($info['operator']) ? $info['operator'] : 'IN';
      $placeholder = ':views_join_condition_' . $select_query->nextPlaceholder() . '[]';
      $placeholder_sql = "( $placeholder )";
    }
    else {
      // With a single value, the '=' operator is implicit.
      $operator = !empty($info['operator']) ? $info['operator'] : '=';
      $placeholder = $placeholder_sql = ':views_join_condition_' . $select_query->nextPlaceholder();
    }
    // Set 'field' as join table field if available or set 'left field' as
    // join table field is not set.
    if (isset($info['field'])) {
      $join_table_field = "$join_table$info[field]";
      // Allow the value to be set either with the 'value' element or
      // with 'left_field'.
      if (isset($info['left_field'])) {
        $placeholder_sql = "$left[alias].$info[left_field]";
      }
      else {
        $arguments[$placeholder] = $info['value'];
      }
    }
    // Set 'left field' as join table field is not set.
    else {
      $join_table_field = "$left[alias].$info[left_field]";
      $arguments[$placeholder] = $info['value'];
    }
    // Render out the SQL fragment with parameters.
    return "$join_table_field $operator $placeholder_sql";
  }

}
