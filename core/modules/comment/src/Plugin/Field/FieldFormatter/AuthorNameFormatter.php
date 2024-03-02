<?php

namespace Drupal\comment\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\Attribute\FieldFormatter;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Plugin implementation of the 'comment_username' formatter.
 */
#[FieldFormatter(
  id: 'comment_username',
  label: new TranslatableMarkup('Author name'),
  description: new TranslatableMarkup('Display the author name.'),
  field_types: [
    'string',
  ],
)]
class AuthorNameFormatter extends FormatterBase {

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];

    foreach ($items as $delta => $item) {
      /** @var \Drupal\comment\CommentInterface $comment */
      $comment = $item->getEntity();
      $account = $comment->getOwner();
      $elements[$delta] = [
        '#theme' => 'username',
        '#account' => $account,
        '#cache' => [
          'tags' => $account->getCacheTags() + $comment->getCacheTags(),
        ],
      ];
    }

    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public static function isApplicable(FieldDefinitionInterface $field_definition) {
    return $field_definition->getName() === 'name' && $field_definition->getTargetEntityTypeId() === 'comment';
  }

}
