<?php

namespace Drupal\comment\Plugin\migrate\source\d7;

@trigger_error('CommentType is deprecated in Drupal 8.4.x and will be removed before Drupal 9.0.x. Use \Drupal\node\Plugin\migrate\source\d7\NodeType instead.', E_USER_DEPRECATED);

use Drupal\comment\Plugin\Field\FieldType\CommentItemInterface;
use Drupal\migrate\Row;
use Drupal\migrate_drupal\Plugin\migrate\source\DrupalSqlBase;

/**
 * Drupal 7 comment type source from database.
 *
 * @MigrateSource(
 *   id = "d7_comment_type",
 *   source_module = "comment"
 * )
 *
 * @deprecated in drupal:8.4.0 and is removed from drupal:9.0.0. Use
 * \Drupal\node\Plugin\migrate\source\d7\NodeType instead.
 */
class CommentType extends DrupalSqlBase {

  /**
   * A map of D7 node types to their labels.
   *
   * @var string[]
   */
  protected $nodeTypes = [];

  /**
   * {@inheritdoc}
   */
  public function query() {
    return $this->select('field_config_instance', 'fci')
      ->distinct()
      ->fields('fci', ['bundle'])
      ->condition('fci.entity_type', 'comment');
  }

  /**
   * {@inheritdoc}
   */
  protected function initializeIterator() {
    $this->nodeTypes = $this->select('node_type', 'nt')
      ->fields('nt', ['type', 'name'])
      ->execute()
      ->fetchAllKeyed();

    return parent::initializeIterator();
  }

  /**
   * {@inheritdoc}
   */
  public function prepareRow(Row $row) {
    $node_type = substr($row->getSourceProperty('bundle'), 13);
    $row->setSourceProperty('node_type', $node_type);

    $row->setSourceProperty('default_mode', $this->variableGet("comment_default_mode_$node_type", 1));
    $row->setSourceProperty('per_page', $this->variableGet("comment_default_per_page_$node_type", 50));
    $row->setSourceProperty('anonymous', $this->variableGet("comment_anonymous_$node_type", FALSE));
    $row->setSourceProperty('form_location', $this->variableGet("comment_form_location_$node_type", CommentItemInterface::FORM_BELOW));
    $row->setSourceProperty('preview', $this->variableGet("comment_preview_$node_type", TRUE));
    $row->setSourceProperty('subject', $this->variableGet("comment_subject_field_$node_type", TRUE));

    $label = $this->t('@node_type comment', [
      '@node_type' => $this->nodeTypes[$node_type],
    ]);
    $row->setSourceProperty('label', $label);

    return parent::prepareRow($row);
  }

  /**
   * {@inheritdoc}
   */
  public function fields() {
    return [
      'label' => $this->t('The label of the comment type.'),
      'bundle' => $this->t('Bundle ID of the comment type.'),
      'node_type' => $this->t('The node type to which this comment type is attached.'),
      'default_mode' => $this->t('Default comment mode.'),
      'per_page' => $this->t('Number of comments visible per page.'),
      'anonymous' => $this->t('Whether anonymous comments are allowed.'),
      'form_location' => $this->t('Location of the comment form.'),
      'preview' => $this->t('Whether previews are enabled for the comment type.'),
      'subject' => $this->t('Whether a subject field is enabled for the comment type.'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getIds() {
    return [
      'bundle' => [
        'type' => 'string',
      ],
    ];
  }

}
