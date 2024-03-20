<?php

namespace Drupal\views\Plugin\views\join;

use Drupal\Core\Database\Query\SelectInterface;
use Drupal\views\Attribute\ViewsJoin;

/**
 * Implementation for the "field OR language" join.
 *
 * If the extra conditions contain either ".langcode" or ".bundle", they will be
 * grouped and joined with OR instead of AND. The entire group will then be
 * joined to the other conditions with AND.
 *
 * This is needed for configurable fields that are translatable on some bundles
 * and untranslatable on others. The correct field values to fetch in this case
 * have a langcode that matches the entity record *or* have a bundle on which
 * the field is untranslatable. Thus, the entity base table (or data table, or
 * revision data table, respectively) must join the field data table (or field
 * revision table) on a matching langcode *or* a bundle where the field is
 * untranslatable. The following example views data achieves this for a node
 * field named 'field_tags' which is translatable on an 'article' node type, but
 * not on the 'news' and 'page' node types:
 *
 * @code
 *   $data['node__field_tags']['table']['join']['node_field_data'] = [
 *     'join_id' => 'field_or_language_join',
 *     'table' => 'node__field_tags',
 *     'left_field' => 'nid',
 *     'field' => 'entity_id',
 *     'extra' => [
 *       [
 *         'field' => 'deleted',
 *         'value' => 0,
 *         'numeric' => TRUE,
 *       ],
 *       [
 *         'left_field' => 'langcode',
 *         'field' => 'langcode',
 *       ],
 *       [
 *         'field' => 'bundle',
 *         'value' => ['news', 'page'],
 *       ],
 *     ],
 *   ];
 * @endcode
 *
 * The resulting join condition for this example would be the following:
 *
 * @code
 *   ON node__field_tags.deleted = 0
 *     AND (
 *       node_field_data.langcode = node__field_tags.langcode
 *       OR node__field.tags.bundle IN ['news', 'page']
 *     )
 * @endcode
 *
 * @see views_field_default_views_data()
 *
 * @ingroup views_join_handlers
 */
#[ViewsJoin("field_or_language_join")]
class FieldOrLanguageJoin extends JoinPluginBase {

  /**
   * {@inheritdoc}
   */
  protected function joinAddExtra(&$arguments, &$condition, $table, SelectInterface $select_query, $left_table = NULL) {
    if (empty($this->extra)) {
      return;
    }

    if (is_array($this->extra)) {
      $extras = [];
      foreach ($this->extra as $extra) {
        $extras[] = $this->buildExtra($extra, $arguments, $table, $select_query, $left_table);
      }

      // Remove and store the langcode OR bundle join condition extra.
      $language_bundle_conditions = [];
      foreach ($extras as $key => $extra) {
        if (str_contains($extra, '.langcode') || str_contains($extra, '.bundle')) {
          $language_bundle_conditions[] = $extra;
          unset($extras[$key]);
        }
      }

      if (count($extras) > 1) {
        $condition .= ' AND (' . implode(' ' . $this->extraOperator . ' ', $extras) . ')';
      }
      elseif ($extras) {
        $condition .= ' AND ' . array_shift($extras);
      }

      // Tack on the langcode OR bundle join condition extra.
      if (!empty($language_bundle_conditions)) {
        $condition .= ' AND (' . implode(' OR ', $language_bundle_conditions) . ')';
      }
    }
    elseif (is_string($this->extra)) {
      $condition .= " AND ($this->extra)";
    }
  }

}
