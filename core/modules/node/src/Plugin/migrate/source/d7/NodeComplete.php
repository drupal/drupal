<?php

namespace Drupal\node\Plugin\migrate\source\d7;

use Drupal\Core\Database\Query\SelectInterface;
use Drupal\migrate\Row;

/**
 * Drupal 7 all node revisions source, including translation revisions.
 *
 * For available configuration keys, refer to the parent classes.
 *
 * @see \Drupal\migrate\Plugin\migrate\source\SqlBase
 * @see \Drupal\migrate\Plugin\migrate\source\SourcePluginBase
 *
 * @MigrateSource(
 *   id = "d7_node_complete",
 *   source_module = "node"
 * )
 */
class NodeComplete extends NodeRevision {

  /**
   * The join options between the node and the node_revisions_table.
   */
  const JOIN = 'n.nid = nr.nid';

  /**
   * {@inheritdoc}
   */
  public function query() {
    $query = parent::query();
    $query->orderBy('nr.vid');

    // Get any entity translation revision data.
    if ($this->getDatabase()->schema()
      ->tableExists('entity_translation_revision')) {
      $query->leftJoin('entity_translation_revision', 'etr', '[nr].[nid] = [etr].[entity_id] AND [nr].[vid] = [etr].[revision_id]');
      $query->fields('etr', [
        'entity_type',
        'entity_id',
        'revision_id',
        'source',
        'translate',
      ]);
      $conditions = $query->orConditionGroup();
      $conditions->condition('etr.entity_type', 'node');
      $conditions->isNull('etr.entity_type');
      $query->condition($conditions);
      $query->addExpression("COALESCE([etr].[language], [n].[language])", 'language');
      $query->addField('etr', 'uid', 'etr_uid');
      $query->addField('etr', 'status', 'etr_status');
      $query->addField('etr', 'created', 'etr_created');
      $query->addField('etr', 'changed', 'etr_changed');

      $query->orderBy('etr.revision_id');
      $query->orderBy('etr.language');
    }
    return $query;
  }

  /**
   * {@inheritdoc}
   */
  public function prepareRow(Row $row) {
    // Override properties when this is an entity translation revision. The tnid
    // will be set in d7_node source plugin to the value of 'nid'.
    if ($row->getSourceProperty('etr_created')) {
      $row->setSourceProperty('vid', $row->getSourceProperty('revision_id'));
      $row->setSourceProperty('created', $row->getSourceProperty('etr_created'));
      $row->setSourceProperty('timestamp', $row->getSourceProperty('etr_changed'));
      $row->setSourceProperty('revision_uid', $row->getSourceProperty('etr_uid'));
      $row->setSourceProperty('source_langcode', $row->getSourceProperty('source'));
    }
    return parent::prepareRow($row);
  }

  /**
   * {@inheritdoc}
   */
  public function getIds() {
    return [
      'nid' => [
        'type' => 'integer',
        'alias' => 'n',
      ],
      'vid' => [
        'type' => 'integer',
        'alias' => 'nr',
      ],
      'language' => [
        'type' => 'string',
        'alias' => 'n',
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function handleTranslations(SelectInterface $query) {}

}
