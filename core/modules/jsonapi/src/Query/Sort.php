<?php

namespace Drupal\jsonapi\Query;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Http\Exception\CacheableBadRequestHttpException;

/**
 * Gathers information about the sort parameter.
 *
 * @internal JSON:API maintains no PHP API since its API is the HTTP API. This
 *   class may change at any time and this will break any dependencies on it.
 *
 * @see https://www.drupal.org/project/drupal/issues/3032787
 * @see jsonapi.api.php
 */
class Sort {

  /**
   * The JSON:API sort key name.
   *
   * @var string
   */
  const KEY_NAME = 'sort';

  /**
   * The field key in the sort parameter: sort[lorem][<field>].
   *
   * @var string
   */
  const PATH_KEY = 'path';

  /**
   * The direction key in the sort parameter: sort[lorem][<direction>].
   *
   * @var string
   */
  const DIRECTION_KEY = 'direction';

  /**
   * The langcode key in the sort parameter: sort[lorem][<langcode>].
   *
   * @var string
   */
  const LANGUAGE_KEY = 'langcode';

  /**
   * The fields on which to sort.
   *
   * @var string
   */
  protected $fields;

  /**
   * Constructs a new Sort object.
   *
   * Takes an array of sort fields. Example:
   *   [
   *     [
   *       'path' => 'changed',
   *       'direction' => 'DESC',
   *     ],
   *     [
   *       'path' => 'title',
   *       'direction' => 'ASC',
   *       'langcode' => 'en-US',
   *     ],
   *   ]
   *
   * @param array $fields
   *   The entity query sort fields.
   */
  public function __construct(array $fields) {
    $this->fields = $fields;
  }

  /**
   * Gets the root condition group.
   */
  public function fields() {
    return $this->fields;
  }

  /**
   * Creates a Sort object from a query parameter.
   *
   * @param mixed $parameter
   *   The `sort` query parameter from the Symfony request object.
   *
   * @return self
   *   A Sort object with defaults.
   */
  public static function createFromQueryParameter($parameter) {
    if (empty($parameter)) {
      $cacheability = (new CacheableMetadata())->addCacheContexts(['url.query_args:sort']);
      throw new CacheableBadRequestHttpException($cacheability, 'You need to provide a value for the sort parameter.');
    }

    // Expand a JSON:API compliant sort into a more expressive sort parameter.
    if (is_string($parameter)) {
      $parameter = static::expandFieldString($parameter);
    }

    // Expand any defaults into the sort array.
    $expanded = [];
    foreach ($parameter as $sort_index => $sort_item) {
      $expanded[$sort_index] = static::expandItem($sort_item);
    }

    return new static($expanded);
  }

  /**
   * Expands a simple string sort into a more expressive sort that we can use.
   *
   * @param string $fields
   *   The comma separated list of fields to expand into an array.
   *
   * @return array
   *   The expanded sort.
   */
  protected static function expandFieldString($fields) {
    return array_map(function ($field) {
      $sort = [];

      if ($field[0] == '-') {
        $sort[static::DIRECTION_KEY] = 'DESC';
        $sort[static::PATH_KEY] = substr($field, 1);
      }
      else {
        $sort[static::DIRECTION_KEY] = 'ASC';
        $sort[static::PATH_KEY] = $field;
      }

      return $sort;
    }, explode(',', $fields));
  }

  /**
   * Expands a sort item in case a shortcut was used.
   *
   * @param array $sort_item
   *   The raw sort item.
   *
   * @return array
   *   The expanded sort item.
   */
  protected static function expandItem(array $sort_item) {
    $cacheability = (new CacheableMetadata())->addCacheContexts(['url.query_args:sort']);
    $defaults = [
      static::DIRECTION_KEY => 'ASC',
      static::LANGUAGE_KEY => NULL,
    ];

    if (!isset($sort_item[static::PATH_KEY])) {
      throw new CacheableBadRequestHttpException($cacheability, 'You need to provide a field name for the sort parameter.');
    }

    $expected_keys = [
      static::PATH_KEY,
      static::DIRECTION_KEY,
      static::LANGUAGE_KEY,
    ];

    $expanded = array_merge($defaults, $sort_item);

    // Verify correct sort keys.
    if (count(array_diff($expected_keys, array_keys($expanded))) > 0) {
      throw new CacheableBadRequestHttpException($cacheability, 'You have provided an invalid set of sort keys.');
    }

    return $expanded;
  }

}
