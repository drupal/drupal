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
   */
  public function getEntityTypeId();

  /**
   * Add a condition to the query or a condition group.
   *
   * For example, to find all entities containing both the Turkish 'merhaba'
   * and the Polish 'siema' within a 'greetings' text field:
   * @code
   *   $entity_ids = \Drupal::entityQuery($entity_type)
   *     ->condition('greetings', 'merhaba', '=', 'tr')
   *     ->condition('greetings.value', 'siema', '=', 'pl')
   *     ->execute();
   * @endcode
   *
   * @param $field
   *   Name of the field being queried. It must contain a field name, optionally
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
   * @param $value
   *   The value for $field. In most cases, this is a scalar and it's treated as
   *   case-insensitive. For more complex operators, it is an array. The meaning
   *   of each element in the array is dependent on $operator.
   * @param $operator
   *   Possible values:
   *   - '=', '<>', '>', '>=', '<', '<=', 'STARTS_WITH', 'CONTAINS',
   *     'ENDS_WITH': These operators expect $value to be a literal of the
   *     same type as the column.
   *   - 'IN', 'NOT IN': These operators expect $value to be an array of
   *     literals of the same type as the column.
   *   - 'BETWEEN': This operator expects $value to be an array of two literals
   *     of the same type as the column.
   * @param $langcode
   *   Language code (optional). If omitted, any translation satisfies the
   *   condition. However, if two or more conditions omit the langcode within
   *   one condition group then they are presumed to apply to the same
   *   translation. If within one condition group one condition has a langcode
   *   and another does not they are not presumed to apply to the same
   *   translation.
   *
   * @return \Drupal\Core\Entity\Query\QueryInterface
   * @see \Drupal\Core\Entity\Query\andConditionGroup
   * @see \Drupal\Core\Entity\Query\orConditionGroup
   */
  public function condition($field, $value = NULL, $operator = NULL, $langcode = NULL);

  /**
   * Queries for a non-empty value on a field.
   *
   * @param $field
   *   Name of a field.
   * @param $langcode
   *   Language code (optional).
   * @return \Drupal\Core\Entity\Query\QueryInterface
   */
  public function exists($field, $langcode = NULL);

  /**
   * Queries for an empty field.
   *
   * @param $field
   *   Name of a field.
   * @param $langcode
   *   Language code (optional).
   * @return \Drupal\Core\Entity\Query\QueryInterface
   */
  public function notExists($field, $langcode = NULL);

  /**
   * Enables a pager for the query.
   *
   * @param $limit
   *   An integer specifying the number of elements per page.  If passed a false
   *   value (FALSE, 0, NULL), the pager is disabled.
   * @param $element
   *   An optional integer to distinguish between multiple pagers on one page.
   *   If not provided, one is automatically calculated.
   *
   * @return \Drupal\Core\Entity\Query\QueryInterface
   *   The called object.
   */
  public function pager($limit = 10, $element = NULL);

  /**
   * @param null $start
   * @param null $length
   * @return \Drupal\Core\Entity\Query\QueryInterface
   *   The called object.
   */
  public function range($start = NULL, $length = NULL);

  /**
   * @param $field
   *   Name of a field.
   * @param string $direction
   * @param $langcode
   *   Language code (optional).
   * @return \Drupal\Core\Entity\Query\QueryInterface
   *   The called object.
   */
  public function sort($field, $direction = 'ASC', $langcode = NULL);

  /**
   * Makes this a count query.
   *
   * For count queries, execute() returns the number entities found.
   *
   * @return \Drupal\Core\Entity\Query\QueryInterface
   *   The called object.
   */
  public function count();

  /**
   * Enables sortable tables for this query.
   *
   * @param $headers
   *   An array of headers of the same structure as described in
   *   template_preprocess_table(). Use a 'specifier' in place of a 'field' to
   *   specify what to sort on. This can be an entity or a field as described
   *   in condition().
   *
   * @return \Drupal\Core\Entity\Query\QueryInterface
   *   The called object.
   */
  public function tableSort(&$headers);

  /**
   * @return \Drupal\Core\Entity\Query\QueryInterface
   *   The called object.
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
   *   $query = \Drupal::entityQuery('drawing');
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
   */
  public function andConditionGroup();

  /**
   * Creates a new group of conditions ORed together.
   *
   * For example, consider a map entity with an 'attributes' field
   * containing 'building_type' and 'color' columns.  To find all green and
   * red bikesheds:
   * @code
   *   $query = \Drupal::entityQuery('map');
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
   *     ->condition('attributes.color', array('red', 'green'))
   *     ->condition('attributes.building_type', 'bikeshed')
   *     ->execute();
   * @endcode
   *
   * @return \Drupal\Core\Entity\Query\ConditionInterface
   */
  public function orConditionGroup();

  /**
   * Queries the current revision.
   *
   * @return $this
   */
  public function currentRevision();

  /**
   * Queries all the revisions.
   *
   * @return $this
   */
  public function allRevisions();

}
