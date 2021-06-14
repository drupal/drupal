<?php

namespace Drupal\comment\Plugin\migrate\source;

use Drupal\migrate\Exception\RequirementsException;
use Drupal\migrate\Row;
use Drupal\migrate_drupal\Plugin\migrate\source\DrupalSqlBase;

/**
 * Migration source for Drupal 6 and Drupal 7 comment types.
 *
 * @MigrateSource(
 *   id = "comment_type",
 *   source_module = "comment"
 * )
 */
class CommentType extends DrupalSqlBase {

  /**
   * {@inheritdoc}
   */
  public function query() {
    return $this->select('node_type', 't')
      ->fields('t', ['type', 'name']);
  }

  /**
   * {@inheritdoc}
   */
  public function prepareRow(Row $row) {
    $node_type = $row->getSourceProperty('type');
    foreach (array_keys($this->getCommentFields()) as $field) {
      $row->setSourceProperty($field, $this->variableGet($field . '_' . $node_type, NULL));
    }

    return parent::prepareRow($row);
  }

  /**
   * {@inheritdoc}
   */
  public function fields() {
    return [
      'name' => $this->t('Human name of the parent node type.'),
      'type' => $this->t('Machine name of the parent node type.'),
    ] + $this->getCommentFields();
  }

  /**
   * Returns the fields containing comment settings for each node type.
   *
   * @return string[]
   *   An associative array of field descriptions, keyed by field.
   */
  protected function getCommentFields() {
    return [
      'comment' => $this->t('Default comment setting'),
      'comment_default_mode' => $this->t('Default display mode'),
      'comment_default_per_page' => $this->t('Default comments per page'),
      'comment_anonymous' => $this->t('Anonymous commenting'),
      'comment_subject_field' => $this->t('Comment subject field'),
      'comment_preview' => $this->t('Preview comment'),
      'comment_form_location' => $this->t('Location of comment submission form'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getIds() {
    $ids['type']['type'] = 'string';
    return $ids;
  }

  /**
   * {@inheritdoc}
   */
  public function checkRequirements() {
    parent::checkRequirements();
    if (!$this->moduleExists('node')) {
      // Drupal 6 and Drupal 7 comment configuration migrations migrate comment
      // types and comment fields for node comments only.
      throw new RequirementsException('The node module is not enabled in the source site.', [
        'source_module_additional' => 'node',
      ]);
    }
  }

}
