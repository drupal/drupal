<?php

namespace Drupal\Core\Entity\Query;

use Drupal\Core\Database\Query\PagerSelectExtender;
use Drupal\Core\Entity\EntityTypeInterface;

/**
 * The base entity query class.
 */
abstract class QueryBase implements QueryInterface {

  /**
   * The entity type this query runs against.
   *
   * @var string
   */
  protected $entityTypeId;

  /**
   * Information about the entity type.
   *
   * @var \Drupal\Core\Entity\EntityTypeInterface
   */
  protected $entityType;

  /**
   * The list of sorts.
   *
   * @var array
   */
  protected $sort = [];

  /**
   * TRUE if this is a count query, FALSE if it isn't.
   *
   * @var bool
   */
  protected $count = FALSE;

  /**
   * Conditions.
   *
   * @var \Drupal\Core\Entity\Query\ConditionInterface
   */
  protected $condition;

  /**
   * The list of aggregate expressions.
   *
   * @var array
   */
  protected $aggregate = [];

  /**
   * The list of columns to group on.
   *
   * @var array
   */
  protected $groupBy = [];

  /**
   * Aggregate Conditions
   *
   * @var \Drupal\Core\Entity\Query\ConditionAggregateInterface
   */
  protected $conditionAggregate;

  /**
   * The list of sorts over the aggregate results.
   *
   * @var array
   */
  protected $sortAggregate = [];

  /**
   * The query range.
   *
   * @var array
   */
  protected $range = [];

  /**
   * The query metadata for alter purposes.
   *
   * @var array
   */
  protected $alterMetaData;

  /**
   * The query tags.
   *
   * @var array
   */
  protected $alterTags;

  /**
   * Whether access check is requested or not. Defaults to TRUE.
   *
   * @var bool
   */
  protected $accessCheck = TRUE;

  /**
   * Flag indicating whether to query the current revision or all revisions.
   *
   * @var bool
   */
  protected $allRevisions = FALSE;

  /**
   * Flag indicating whether to query the latest revision.
   *
   * @var bool
   */
  protected $latestRevision = FALSE;

  /**
   * The query pager data.
   *
   * @var array
   *
   * @see Query::pager()
   */
  protected $pager = [];

  /**
   * List of potential namespaces of the classes belonging to this query.
   *
   * @var array
   */
  protected $namespaces = [];

  /**
   * Constructs this object.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type definition.
   * @param string $conjunction
   *   - AND: all of the conditions on the query need to match.
   *   - OR: at least one of the conditions on the query need to match.
   * @param array $namespaces
   *   List of potential namespaces of the classes belonging to this query.
   */
  public function __construct(EntityTypeInterface $entity_type, $conjunction, array $namespaces) {
    $this->entityTypeId = $entity_type->id();
    $this->entityType = $entity_type;
    $this->conjunction = $conjunction;
    $this->namespaces = $namespaces;
    $this->condition = $this->conditionGroupFactory($conjunction);
    if ($this instanceof QueryAggregateInterface) {
      $this->conditionAggregate = $this->conditionAggregateGroupFactory($conjunction);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getEntityTypeId() {
    return $this->entityTypeId;
  }

  /**
   * {@inheritdoc}
   */
  public function condition($property, $value = NULL, $operator = NULL, $langcode = NULL) {
    $this->condition->condition($property, $value, $operator, $langcode);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function exists($property, $langcode = NULL) {
    $this->condition->exists($property, $langcode);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function notExists($property, $langcode = NULL) {
    $this->condition->notExists($property, $langcode);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function range($start = NULL, $length = NULL) {
    $this->range = [
      'start' => $start,
      'length' => $length,
    ];
    return $this;
  }

  /**
   * Creates an object holding a group of conditions.
   *
   * See andConditionGroup() and orConditionGroup() for more.
   *
   * @param string $conjunction
   *   - AND (default): this is the equivalent of andConditionGroup().
   *   - OR: this is the equivalent of orConditionGroup().
   *
   * @return \Drupal\Core\Entity\Query\ConditionInterface
   *   An object holding a group of conditions.
   */
  protected function conditionGroupFactory($conjunction = 'AND') {
    $class = static::getClass($this->namespaces, 'Condition');
    return new $class($conjunction, $this, $this->namespaces);
  }

  /**
   * {@inheritdoc}
   */
  public function andConditionGroup() {
    return $this->conditionGroupFactory('and');
  }

  /**
   * {@inheritdoc}
   */
  public function orConditionGroup() {
    return $this->conditionGroupFactory('or');
  }

  /**
   * {@inheritdoc}
   */
  public function sort($field, $direction = 'ASC', $langcode = NULL) {
    $this->sort[] = [
      'field' => $field,
      'direction' => strtoupper($direction),
      'langcode' => $langcode,
    ];
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function count() {
    $this->count = TRUE;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function accessCheck($access_check = TRUE) {
    $this->accessCheck = $access_check;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function currentRevision() {
    $this->allRevisions = FALSE;
    $this->latestRevision = FALSE;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function latestRevision() {
    $this->allRevisions = TRUE;
    $this->latestRevision = TRUE;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function allRevisions() {
    $this->allRevisions = TRUE;
    $this->latestRevision = FALSE;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function pager($limit = 10, $element = NULL) {
    // Even when not using SQL, storing the element PagerSelectExtender is as
    // good as anywhere else.
    if (!isset($element)) {
      $element = PagerSelectExtender::$maxElement++;
    }
    elseif ($element >= PagerSelectExtender::$maxElement) {
      PagerSelectExtender::$maxElement = $element + 1;
    }

    $this->pager = [
      'limit' => $limit,
      'element' => $element,
    ];
    return $this;
  }

  /**
   * Gets the total number of results and initialize a pager for the query.
   *
   * The pager can be disabled by either setting the pager limit to 0, or by
   * setting this query to be a count query.
   */
  protected function initializePager() {
    if ($this->pager && !empty($this->pager['limit']) && !$this->count) {
      $page = pager_find_page($this->pager['element']);
      $count_query = clone $this;
      $this->pager['total'] = $count_query->count()->execute();
      $this->pager['start'] = $page * $this->pager['limit'];
      pager_default_initialize($this->pager['total'], $this->pager['limit'], $this->pager['element']);
      $this->range($this->pager['start'], $this->pager['limit']);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function tableSort(&$headers) {
    // If 'field' is not initialized, the header columns aren't clickable.
    foreach ($headers as $key => $header) {
      if (is_array($header) && isset($header['specifier'])) {
        $headers[$key]['field'] = '';
      }
    }

    $order = tablesort_get_order($headers);
    $direction = tablesort_get_sort($headers);
    foreach ($headers as $header) {
      if (is_array($header) && ($header['data'] == $order['name'])) {
        $this->sort($header['specifier'], $direction, isset($header['langcode']) ? $header['langcode'] : NULL);
      }
    }

    return $this;
  }

  /**
   * Makes sure that the Condition object is cloned as well.
   */
  public function __clone() {
    $this->condition = clone $this->condition;
  }

  /**
   * {@inheritdoc}
   */
  public function addTag($tag) {
    $this->alterTags[$tag] = 1;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function hasTag($tag) {
    return isset($this->alterTags[$tag]);
  }

  /**
   * {@inheritdoc}
   */
  public function hasAllTags() {
    return !(boolean) array_diff(func_get_args(), array_keys($this->alterTags));
  }

  /**
   * {@inheritdoc}
   */
  public function hasAnyTag() {
    return (boolean) array_intersect(func_get_args(), array_keys($this->alterTags));
  }

  /**
   * {@inheritdoc}
   */
  public function addMetaData($key, $object) {
    $this->alterMetaData[$key] = $object;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getMetaData($key) {
    return isset($this->alterMetaData[$key]) ? $this->alterMetaData[$key] : NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function aggregate($field, $function, $langcode = NULL, &$alias = NULL) {
    if (!isset($alias)) {
      $alias = $this->getAggregationAlias($field, $function);
    }

    $this->aggregate[$alias] = [
      'field' => $field,
      'function' => $function,
      'alias' => $alias,
      'langcode' => $langcode,
    ];

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function conditionAggregate($field, $function = NULL, $value = NULL, $operator = '=', $langcode = NULL) {
    $this->aggregate($field, $function, $langcode);
    $this->conditionAggregate->condition($field, $function, $value, $operator, $langcode);

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function sortAggregate($field, $function, $direction = 'ASC', $langcode = NULL) {
    $alias = $this->getAggregationAlias($field, $function);

    $this->sortAggregate[$alias] = [
      'field' => $field,
      'function' => $function,
      'direction' => $direction,
      'langcode' => $langcode,
    ];
    $this->aggregate($field, $function, $langcode, $alias);

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function groupBy($field, $langcode = NULL) {
    $this->groupBy[] = [
      'field' => $field,
      'langcode' => $langcode,
    ];

    return $this;
  }

  /**
   * Generates an alias for a field and it's aggregated function.
   *
   * @param string $field
   *   The field name used in the alias.
   * @param string $function
   *   The aggregation function used in the alias.
   *
   * @return string
   *   The alias for the field.
   */
  protected function getAggregationAlias($field, $function) {
    return strtolower($field . '_' . $function);
  }

  /**
   * Gets a list of namespaces of the ancestors of a class.
   *
   * @param $object
   *   An object within a namespace.
   *
   * @return array
   *   A list containing the namespace of the class, the namespace of the
   *   parent of the class and so on and so on.
   */
  public static function getNamespaces($object) {
    $namespaces = [];
    for ($class = get_class($object); $class; $class = get_parent_class($class)) {
      $namespaces[] = substr($class, 0, strrpos($class, '\\'));
    }
    return $namespaces;
  }

  /**
   * Finds a class in a list of namespaces.
   *
   * @param array $namespaces
   *   A list of namespaces.
   * @param string $short_class_name
   *   A class name without namespace.
   *
   * @return string
   *   The fully qualified name of the class.
   */
  public static function getClass(array $namespaces, $short_class_name) {
    foreach ($namespaces as $namespace) {
      $class = $namespace . '\\' . $short_class_name;
      if (class_exists($class)) {
        return $class;
      }
    }
  }

}
