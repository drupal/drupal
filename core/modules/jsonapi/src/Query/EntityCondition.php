<?php

namespace Drupal\jsonapi\Query;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Http\Exception\CacheableBadRequestHttpException;

/**
 * A condition object for the EntityQuery.
 *
 * @internal JSON:API maintains no PHP API since its API is the HTTP API. This
 *   class may change at any time and this will break any dependencies on it.
 *
 * @see https://www.drupal.org/project/jsonapi/issues/3032787
 * @see jsonapi.api.php
 */
class EntityCondition {

  /**
   * The field key in the filter condition: filter[lorem][condition][<field>].
   *
   * @var string
   */
  const PATH_KEY = 'path';

  /**
   * The value key in the filter condition: filter[lorem][condition][<value>].
   *
   * @var string
   */
  const VALUE_KEY = 'value';

  /**
   * The operator key in the condition: filter[lorem][condition][<operator>].
   *
   * @var string
   */
  const OPERATOR_KEY = 'operator';

  /**
   * The allowed condition operators.
   *
   * @var string[]
   */
  public static $allowedOperators = [
    '=', '<>',
    '>', '>=', '<', '<=',
    'STARTS_WITH', 'CONTAINS', 'ENDS_WITH',
    'IN', 'NOT IN',
    'BETWEEN', 'NOT BETWEEN',
    'IS NULL', 'IS NOT NULL',
  ];

  /**
   * The field to be evaluated.
   *
   * @var string
   */
  protected $field;

  /**
   * The condition operator.
   *
   * @var string
   */
  protected $operator;

  /**
   * The value against which the field should be evaluated.
   *
   * @var mixed
   */
  protected $value;

  /**
   * Constructs a new EntityCondition object.
   */
  public function __construct($field, $value, $operator = NULL) {
    $this->field = $field;
    $this->value = $value;
    $this->operator = ($operator) ? $operator : '=';
  }

  /**
   * The field to be evaluated.
   *
   * @return string
   *   The field upon which to evaluate the condition.
   */
  public function field() {
    return $this->field;
  }

  /**
   * The comparison operator to use for the evaluation.
   *
   * For a list of allowed operators:
   *
   * @see \Drupal\jsonapi\Query\EntityCondition::allowedOperators
   *
   * @return string
   *   The condition operator.
   */
  public function operator() {
    return $this->operator;
  }

  /**
   * The value against which the condition should be evaluated.
   *
   * @return mixed
   *   The condition comparison value.
   */
  public function value() {
    return $this->value;
  }

  /**
   * Creates an EntityCondition object from a query parameter.
   *
   * @param mixed $parameter
   *   The `filter[condition]` query parameter from the request.
   *
   * @return self
   *   An EntityCondition object with defaults.
   */
  public static function createFromQueryParameter($parameter) {
    static::validate($parameter);
    $field = $parameter[static::PATH_KEY];
    $value = (isset($parameter[static::VALUE_KEY])) ? $parameter[static::VALUE_KEY] : NULL;
    $operator = (isset($parameter[static::OPERATOR_KEY])) ? $parameter[static::OPERATOR_KEY] : NULL;
    return new static($field, $value, $operator);
  }

  /**
   * Validates the filter has the required fields.
   */
  protected static function validate($parameter) {
    $valid_key_combinations = [
      [static::PATH_KEY, static::VALUE_KEY],
      [static::PATH_KEY, static::OPERATOR_KEY],
      [static::PATH_KEY, static::VALUE_KEY, static::OPERATOR_KEY],
    ];

    $given_keys = array_keys($parameter);
    $valid_key_set = array_reduce($valid_key_combinations, function ($valid, $set) use ($given_keys) {
      return ($valid) ? $valid : count(array_diff($set, $given_keys)) === 0;
    }, FALSE);

    $has_operator_key = isset($parameter[static::OPERATOR_KEY]);
    $has_path_key = isset($parameter[static::PATH_KEY]);
    $has_value_key = isset($parameter[static::VALUE_KEY]);

    $cacheability = (new CacheableMetadata())->addCacheContexts(['url.query_args:filter']);
    if (!$valid_key_set) {
      // Try to provide a more specific exception is a key is missing.
      if (!$has_operator_key) {
        if (!$has_path_key) {
          throw new CacheableBadRequestHttpException($cacheability, "Filter parameter is missing a '" . static::PATH_KEY . "' key.");
        }
        if (!$has_value_key) {
          throw new CacheableBadRequestHttpException($cacheability, "Filter parameter is missing a '" . static::VALUE_KEY . "' key.");
        }
      }

      // Catchall exception.
      $reason = "You must provide a valid filter condition. Check that you have set the required keys for your filter.";
      throw new CacheableBadRequestHttpException($cacheability, $reason);
    }

    if ($has_operator_key) {
      $operator = $parameter[static::OPERATOR_KEY];
      if (!in_array($operator, static::$allowedOperators)) {
        $reason = "The '" . $operator . "' operator is not allowed in a filter parameter.";
        throw new CacheableBadRequestHttpException($cacheability, $reason);
      }

      if (in_array($operator, ['IS NULL', 'IS NOT NULL']) && $has_value_key) {
        $reason = "Filters using the '" . $operator . "' operator should not provide a value.";
        throw new CacheableBadRequestHttpException($cacheability, $reason);
      }
    }
  }

}
