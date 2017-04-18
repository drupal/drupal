<?php

namespace Drupal\node\Plugin\migrate\source\d7;

use Drupal\migrate\Row;
use Drupal\migrate_drupal\Plugin\migrate\source\DrupalSqlBase;

/**
 * Drupal 7 Node types source from database.
 *
 * @MigrateSource(
 *   id = "d7_node_type",
 *   source_provider = "node"
 * )
 */
class NodeType extends DrupalSqlBase {

  /**
   * The teaser length
   *
   * @var int
   */
  protected $teaserLength;

  /**
   * Node preview optional / required.
   *
   * @var int
   */
  protected $nodePreview;

  /**
   * {@inheritdoc}
   */
  public function query() {
    return $this->select('node_type', 't')->fields('t');
  }

  /**
   * {@inheritdoc}
   */
  public function fields() {
    return [
      'type' => $this->t('Machine name of the node type.'),
      'name' => $this->t('Human name of the node type.'),
      'description' => $this->t('Description of the node type.'),
      'help' => $this->t('Help text for the node type.'),
      'title_label' => $this->t('Title label.'),
      'disabled' => $this->t('Flag indicating the node type is enable'),
      'base' => $this->t('base node.'),
      'custom' => $this->t('Flag.'),
      'modified' => $this->t('Flag.'),
      'locked' => $this->t('Flag.'),
      'orig_type' => $this->t('The original type.'),
      'teaser_length' => $this->t('Teaser length'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function initializeIterator() {
    $this->teaserLength = $this->variableGet('teaser_length', 600);
    $this->nodePreview = $this->variableGet('node_preview', 0);
    return parent::initializeIterator();
  }

  /**
   * {@inheritdoc}
   */
  public function prepareRow(Row $row) {
    $row->setSourceProperty('teaser_length', $this->teaserLength);
    $row->setSourceProperty('node_preview', $this->nodePreview);

    $type = $row->getSourceProperty('type');
    $source_options = $this->variableGet('node_options_' . $type, ['promote', 'sticky']);
    $options = [];
    foreach (['promote', 'sticky', 'status', 'revision'] as $item) {
      $options[$item] = in_array($item, $source_options);
    }
    $row->setSourceProperty('options', $options);

    // Don't create a body field until we prove that this node type has one.
    $row->setSourceProperty('create_body', FALSE);

    if ($this->moduleExists('field')) {
      // Find body field for this node type.
      $body = $this->select('field_config_instance', 'fci')
        ->fields('fci', ['data'])
        ->condition('entity_type', 'node')
        ->condition('bundle', $row->getSourceProperty('type'))
        ->condition('field_name', 'body')
        ->execute()
        ->fetchAssoc();
      if ($body) {
        $row->setSourceProperty('create_body', TRUE);
        $body['data'] = unserialize($body['data']);
        $row->setSourceProperty('body_label', $body['data']['label']);
      }
    }

    $row->setSourceProperty('display_submitted', $this->variableGet('node_submitted_' . $type, TRUE));

    if ($menu_options = $this->variableGet('menu_options_' . $type, NULL)) {
      $row->setSourceProperty('available_menus', $menu_options);
    }
    if ($parent = $this->variableGet('menu_parent_' . $type, NULL)) {
      $row->setSourceProperty('parent', $parent . ':');
    }
    return parent::prepareRow($row);
  }

  /**
   * {@inheritdoc}
   */
  public function getIds() {
    $ids['type']['type'] = 'string';
    return $ids;
  }

}
