<?php

namespace Drupal\comment\Plugin\migrate\source\d6;

/**
 * @MigrateSource(
 *   id = "d6_comment_variable_per_comment_type"
 * )
 */
class CommentVariablePerCommentType extends CommentVariable {

  /**
   * Retrieves the values of the comment variables grouped by comment type.
   *
   * @return array
   */
  protected function getCommentVariables() {
    $node_types = parent::getCommentVariables();
    // The return key used to separate comment types with hidden subject field.
    $return = [];
    foreach ($node_types as $node_type => $data) {
      // Only 2 comment types depending on subject field visibility.
      if (!empty($data['comment_subject_field'])) {
        // Default label and description should be set in migration.
        $return['comment'] = [
          'comment_type' => 'comment',
          'label' => $this->t('Default comments'),
          'description' => $this->t('Allows commenting on content')
        ];
      }
      else {
        // Provide a special comment type with hidden subject field.
        $return['comment_no_subject'] = [
          'comment_type' => 'comment_no_subject',
          'label' => $this->t('Comments without subject field'),
          'description' => $this->t('Allows commenting on content, comments without subject field')
        ];
      }
    }
    return $return;
  }

  /**
   * {@inheritdoc}
   */
  public function fields() {
    return [
      'comment_type' => $this->t('The comment type'),
      'label' => $this->t('The comment type label'),
      'description' => $this->t('The comment type description'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getIds() {
    $ids['comment_type']['type'] = 'string';
    return $ids;
  }

}
