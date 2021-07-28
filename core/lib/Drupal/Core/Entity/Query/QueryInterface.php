<?php

namespace Drupal\Core\Entity\Query;

use Drupal\Core\Database\Query\AlterableInterface;

/**
 * Interface for entity queries.
 *
 * Never instantiate classes implementing this interface directly. Always use
 * the QueryFactory class.
 *
 * @ingroup database
 */
interface QueryInterface extends AlterableInterface {

  /**
   * Gets the ID of the entity type for this query.
   *
   * @return string
   *   The entity type ID.
   */
  public function getEntityTypeId();

  /**
   * Add a condition to the query or a condition group.
   *
   * For example, to find all entities containing both the Turkish 'merhaba'
   * and the Polish 'siema' within a 'greetings' text field:
   * @code
   *   $entity_ids = \Drupal::entityQuery($entity_type)
   *     ->accessCheck(FALSE)
   *     ->condition('greetings', 'merhaba', '=', 'tr')
   *     ->condition('greetings.value', 'siema', '=', 'pl')
   *     ->execute();
   * @endcode
   *
   * @param string|\Drupal\Core\Entity\Query\ConditionInterface $field
   *   Name of the field being queried or an instance of ConditionInterface.
   *   In the case of the name, it must contain a field name, optionally
   *   followed by a column name. The column can be the reference property,
   *   usually "entity", for reference fields and that can be followed
   *   similarly by a field name and so on. Additionally, the target entity type
   *   can be specified by appending the ":target_entity_type_id" to "entity".
   *   Some examples:
   *   - nid
   *   - tags.value
   *   - tags
   *   - tags.entity.name
   *   - tags.entity:taxonomy_term.name
   *   - uid.entity.name
   *   - uid.entity:user.name
   *   "tags" "is the same as "tags.value" as value is the default column.
   *   If two or more conditions have the same field names they apply to the
   *   same delta within that field. In order to limit the condition to a
   *   specific item a numeric delta should be added between the field name and
   *   the column name.
   *   @code
   *   ->condition('tags.5.value', 'news')
   *   @endcode
   *   This will require condition to be satisfied on a specific delta of the
   *   field. The condition above will require the 6th value of the field to
   *   match the provided value. Further, it's possible to create a condition on
   *   the delta itself by using '%delta'. For example,
   *   @code
   *   ->condition('tags.%delta', 5)
   *   @endcode
   *   will find only entities which have at least six tags. Finally, the
   *   condition on the delta itself accompanied with a condition on the value
   *   will require the value to appear in the specific delta range. For
   *   example,
   *   @code
   *   ->condition('tags.%delta', 0, '>'))
   *   ->condition('tags.%delta.value', 'news'))
   *   @endcode
   *   will only find the "news" tag if it is not the first value. It should be
   *   noted that conditions on specific deltas and delta ranges are only
   *   supported when querying content entities.
   * @param string|int|bool|array|null $value
   *   (optional) The value for $field. In most cases, this is a scalar and it's
   *   treated as case-insensitive. For more complex operators, it is an array.
   *   The meaning of each element in the array is dependent on $operator.
   *   Defaults to NULL, for most operators (except: 'IS NULL', 'IS NOT NULL')
   *   it always makes the condition false.
   * @param string|null $operator
   *   (optional) The comparison operator. Possible values:
   *   - '=', '<>', '>', '>=', '<', '<=', 'STARTS_WITH', 'CONTAINS',
   *     'ENDS_WITH': These operators expect $value to be a literal of the
   *     same type as the column.
   *   - 'IN', 'NOT IN': These operators expect $value to be an array of
   *     literals of the same type as the column.
   *   - 'IS NULL', 'IS NOT NULL': These operators ignore $value, for that
   *     reason it is recommended to use a $value of NULL for clarity.
   *   - 'BETWEEN', 'NOT BETWEEN': These operators expect $value to be an array
   *     of two literals of the same type as the column.
   *   If NULL, defaults to the '=' operator.
   * @param string|null $langcode
   *   (optional) The language code allows filtering results by specific
   *   language. If two or more conditions omit the langcode within
   *   one condition group then they are presumed to apply to the same
   *   translation. If within one condition group one condition has a langcode
   *   and another does not they are not presumed to apply to the same
   *   translation. If omitted (NULL), any translation satisfies the condition.
   *
   * @return $this
   *
   * @see \Drupal\Core\Entity\Query\QueryInterface::andConditionGroup()
   * @see \Drupal\Core\Entity\Query\QueryInterface::orConditionGroup()
   * @see \Drupal\Core\Entity\Query\ConditionInterface
   * @see \Drupal\Core\Entity\Query\QueryInterface::exists()
   * @see \Drupal\Core\Entity\Query\QueryInterface::notExists()
   */
  public function condition($field, $value = NULL, $operator = NULL, $langcode = NULL);

  /**
   * Queries for a non-empty value on a field.
   *
   * @param string $field
   *   Name of a field.
   * @param string|null $langcode
   *   (optional) The language code allows filtering results by specific
   *   language. If omitted (NULL), any translation satisfies the condition.
   *
   * @return $this
   */
  public function exists($field, $langcode = NULL);

  /**
   * Queries for an empty field.
   *
   * @param string $field
   *   Name of a field.
   * @param string|null $langcode
   *   (optional) The language code allows filtering results by specific
   *   language. If omitted (NULL), any translation satisfies the condition.
   *
   * @return $this
   */
  public function notExists($field, $langcode = NULL);

  /**
   * Enables a pager for the query.
   *
   * @param int $limit
   *   (optional) An integer specifying the number of elements per page. If
   *   passed 0, the pager is disabled.
   * @param int|null $element
   *   (optional) An integer to distinguish between multiple pagers on one page.
   *   If not provided, one is automatically calculated by incrementing the
   *   next pager element value.
   *
   * @return $this
   */
  public function pager($limit = 10, $element = NULL);

  /**
   * Defines the range of the query.
   *
   * @param int|null $start
   *   (optional) The first record from the result set to return. If NULL,
   *   removes any range directives that are set.
   * @param int|null $length
   *   (optional) The maximum number of rows to return. If $start and $length
   *   are NULL, then a complete result set will be generated. If $start is
   *   not NULL and $length is NULL, then an empty result set will be
   *   generated.
   *
   * @return $this
   */
  public function range($start = NULL, $length = NULL);

  /**
   * Sorts the result set by a given field.
   *
   * @param string $field
   *   Name of a field.
   * @param string $direction
   *   (optional) The direction to sort. Allowed values are "ASC" and "DESC".
   *   Defaults to "ASC".
   * @param string|null $langcode
   *   (optional) The language code allows filtering results by specific
   *   language. If omitted (NULL), any translation satisfies the condition.
   *
   * @return $this
   *
   * @todo standardize $direction options in
   * https://www.drupal.org/project/drupal/issues/3079258
   */
  public function sort($field, $direction = 'ASC', $langcode = NULL);

  /**
   * Makes this a count query.
   *
   * For count queries, execute() returns the number entities found.
   *
   * @return $this
   */
  public function count();

  /**
   * Enables sortable tables for this query.
   *
   * @param array $headers
   *   An array of headers of the same structure as described in
   *   template_preprocess_table(). Use a 'specifier' in place of a 'field' to
   *   specify what to sort on. This can be an entity or a field as described
   *   in condition().
   *
   * @return $this
   */
  public function tableSort(&$headers);

  /**
   * Enables or disables access checking for this query.
   *
   * @param bool $access_check
   *   (optional) Whether access check is requested or not. Defaults to TRUE.
   *
   * @return $this
   */
  public function accessCheck($access_check = TRUE);

  /**
   * Execute the query.
   *
   * @return int|array
   *   Returns an integer for count queries or an array of ids. The values of
   *   the array are always entity ids. The keys will be revision ids if the
   *   entity supports revision and entity ids if not.
   */
  public function execute();

  /**
   * Creates a new group of conditions ANDed together.
   *
   * For example, consider a drawing entity type with a 'figures' multi-value
   * field containing 'shape' and 'color' columns. To find all drawings
   * containing both a red triangle and a blue circle:
   * @code
   *   $query = \Drupal::entityQuery('drawing')->accessCheck(FALSE);
   *   $group = $query->andConditionGroup()
   *     ->condition('figures.color', 'red')
   *     ->condition('figures.shape', 'triangle');
   *   $query->condition($group);
   *   $group = $query->andConditionGroup()
   *     ->condition('figures.color', 'blue')
   *     ->condition('figures.shape', 'circle');
   *   $query->condition($group);
   *   $entity_ids = $query->execute();
   * @endcode
   *
   * @return \Drupal\Core\Entity\Query\ConditionInterface
   *   A condition object whose conditions will be combined with AND.
   */
  public function andConditionGroup();

  /**
   * Creates a new group of conditions ORed together.
   *
   * For example, consider a map entity with an 'attributes' field
   * containing 'building_type' and 'color' columns. To find all green and
   * red bikesheds:
   * @code
   *   $query = \Drupal::entityQuery('map')->accessCheck(FALSE);
   *   $group = $query->orConditionGroup()
   *     ->condition('attributes.color', 'red')
   *     ->condition('attributes.color', 'green');
   *   $entity_ids = $query
   *     ->condition('attributes.building_type', 'bikeshed')
   *     ->condition($group)
   *     ->execute();
   * @endcode
   * Note that this particular example can be simplified:
   * @code
   *   $entity_ids = $query
   *     ->condition('attributes.color', ['red', 'green'])
   *     ->condition('attributes.building_type', 'bikeshed')
   *     ->execute();
   * @endcode
   *
   * @return \Drupal\Core\Entity\Query\ConditionInterface
   *   A condition object whose conditions will be combined with OR.
   */
  public function orConditionGroup();

  /**
   * Limits the query to only default revisions.
   *
   * See the @link entity_api Entity API topic @endlink for information about
   * the current revision.
   *
   * @return $this
   */
  public function currentRevision();

  /**
   * Queries the latest revision.
   *
   * The latest revision is the most recent revision of an entity. This will be
   * either the default revision, or a pending revision if one exists and it is
   * newer than the default.
   *
   * @return $this
   */
  public function latestRevision();

  /**
   * Queries all the revisions.
   *
   * @return $this
   */
  public function allRevisions();

}
