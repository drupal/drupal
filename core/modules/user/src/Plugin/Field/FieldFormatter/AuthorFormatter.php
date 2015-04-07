<?php

/**
 * @file
 * Contains \Drupal\user\Plugin\Field\FieldFormatter\AuthorFormatter.
 */

namespace Drupal\user\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\Plugin\Field\FieldFormatter\EntityReferenceFormatterBase;

/**
 * Plugin implementation of the 'author' formatter.
 *
 * @FieldFormatter(
 *   id = "author",
 *   label = @Translation("Author"),
 *   description = @Translation("Display the referenced author user entity."),
 *   field_types = {
 *     "entity_reference"
 *   }
 * )
 */
class AuthorFormatter extends EntityReferenceFormatterBase {

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items) {
    $elements = array();

    foreach ($items as $delta => $item) {
      /** @var $referenced_user \Drupal\user\UserInterface */
      if ($referenced_user = $item->entity) {
        $elements[$delta] = array(
          '#theme' => 'username',
          '#account' => $referenced_user,
          '#link_options' => array('attributes' => array('rel' => 'author')),
          '#cache' => array(
            'tags' => $referenced_user->getCacheTags(),
          ),
        );
      }
    }

    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public static function isApplicable(FieldDefinitionInterface $field_definition) {
    return $field_definition->getFieldStorageDefinition()->getSetting('target_type') == 'user';
  }

}
