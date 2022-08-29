<?php

namespace Drupal\comment\Plugin\Field\FieldFormatter;

use Drupal\Component\Utility\Unicode;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\Plugin\Field\FieldFormatter\StringFormatter;

/**
 * Plugin implementation of the 'comment_permalink' formatter.
 *
 * All the other entities use 'canonical' or 'revision' links to link the entity
 * to itself but comments use permalink URL.
 *
 * @FieldFormatter(
 *   id = "comment_permalink",
 *   label = @Translation("Comment Permalink"),
 *   field_types = {
 *     "string",
 *     "uri",
 *   }
 * )
 */
class CommentPermalinkFormatter extends StringFormatter {

  /**
   * {@inheritdoc}
   */
  protected function getEntityUrl(EntityInterface $comment) {
    /** @var \Drupal\comment\CommentInterface $comment */
    $comment_permalink = $comment->permalink();
    if ($comment->hasField('comment_body') && ($body = $comment->get('comment_body')->value)) {
      $attributes = $comment_permalink->getOption('attributes') ?: [];
      $attributes += ['title' => Unicode::truncate($body, 128)];
      $comment_permalink->setOption('attributes', $attributes);
    }
    return $comment_permalink;
  }

  /**
   * {@inheritdoc}
   */
  public static function isApplicable(FieldDefinitionInterface $field_definition) {
    return parent::isApplicable($field_definition) && $field_definition->getTargetEntityTypeId() === 'comment' && $field_definition->getName() === 'subject';
  }

}
