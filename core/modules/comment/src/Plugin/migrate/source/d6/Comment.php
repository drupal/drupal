<?php

namespace Drupal\comment\Plugin\migrate\source\d6;

use Drupal\migrate\Row;
use Drupal\migrate_drupal\Plugin\migrate\source\DrupalSqlBase;

/**
 * Drupal 6 comment source from database.
 *
 * @MigrateSource(
 *   id = "d6_comment",
 *   source_module = "comment"
 * )
 */
class Comment extends DrupalSqlBase {

  /**
   * {@inheritdoc}
   */
  public function query() {
    $query = $this->select('comments', 'c')
      ->fields('c', ['cid', 'pid', 'nid', 'uid', 'subject',
      'comment', 'hostname', 'timestamp', 'status', 'thread', 'name',
      'mail', 'homepage', 'format',
    ]);
    $query->innerJoin('node', 'n', 'c.nid = n.nid');
    $query->fields('n', ['type', 'language']);
    $query->orderBy('c.timestamp');
    return $query;
  }

  /**
   * {@inheritdoc}
   */
  public function prepareRow(Row $row) {
    // @todo Remove the call to ->prepareComment() in
    // https://www.drupal.org/project/drupal/issues/3069260 when the Drupal 9
    // branch opens.
    return parent::prepareRow($this->prepareComment($row));
  }

  /**
   * Provides a BC layer for deprecated sources.
   *
   * @param \Drupal\migrate\Row $row
   *   The row from the source to process.
   *
   * @return \Drupal\migrate\Row
   *   The row object.
   *
   * @throws \Exception
   *   Passing a Row with a frozen source to this method will trigger an
   *   \Exception when attempting to set the source properties.
   *
   * @todo Remove usages of this method and deprecate for removal in
   *   https://www.drupal.org/project/drupal/issues/3069260 when the Drupal 9
   *   branch opens.
   */
  protected function prepareComment(Row $row) {
    if ($this->variableGet('comment_subject_field_' . $row->getSourceProperty('type'), 1)) {
      // Comment subject visible.
      $row->setSourceProperty('field_name', 'comment');
      $row->setSourceProperty('comment_type', 'comment');
    }
    else {
      $row->setSourceProperty('field_name', 'comment_no_subject');
      $row->setSourceProperty('comment_type', 'comment_no_subject');
    }

    // In D6, status=0 means published, while in D8 means the opposite.
    // See https://www.drupal.org/node/237636.
    $row->setSourceProperty('status', !$row->getSourceProperty('status'));

    // If node did not have a language, use site default language as a fallback.
    if (!$row->getSourceProperty('language')) {
      $language_default = $this->variableGet('language_default', NULL);
      $language = $language_default ? $language_default->language : 'en';
      $row->setSourceProperty('language', $language);
    }
    return $row;
  }

  /**
   * {@inheritdoc}
   */
  public function fields() {
    return [
      'cid' => $this->t('Comment ID.'),
      'pid' => $this->t('Parent comment ID. If set to 0, this comment is not a reply to an existing comment.'),
      'nid' => $this->t('The {node}.nid to which this comment is a reply.'),
      'uid' => $this->t('The {users}.uid who authored the comment. If set to 0, this comment was created by an anonymous user.'),
      'subject' => $this->t('The comment title.'),
      'comment' => $this->t('The comment body.'),
      'hostname' => $this->t("The author's host name."),
      'timestamp' => $this->t('The time that the comment was created, or last edited by its author, as a Unix timestamp.'),
      'status' => $this->t('The published status of a comment. (0 = Published, 1 = Not Published)'),
      'format' => $this->t('The {filter_formats}.format of the comment body.'),
      'thread' => $this->t("The vancode representation of the comment's place in a thread."),
      'name' => $this->t("The comment author's name. Uses {users}.name if the user is logged in, otherwise uses the value typed into the comment form."),
      'mail' => $this->t("The comment author's email address from the comment form, if user is anonymous, and the 'Anonymous users may/must leave their contact information' setting is turned on."),
      'homepage' => $this->t("The comment author's home page address from the comment form, if user is anonymous, and the 'Anonymous users may/must leave their contact information' setting is turned on."),
      'type' => $this->t("The {node}.type to which this comment is a reply."),
      'language' => $this->t("The {node}.language to which this comment is a reply. Site default language is used as a fallback if node does not have a language."),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getIds() {
    $ids['cid']['type'] = 'integer';
    return $ids;
  }

}
