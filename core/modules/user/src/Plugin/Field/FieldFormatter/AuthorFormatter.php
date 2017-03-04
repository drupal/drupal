<?php

namespace Drupal\user\Plugin\Field\FieldFormatter;

use Drupal\Core\Entity\EntityInterface;
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
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];

    foreach ($this->getEntitiesToView($items, $langcode) as $delta => $entity) {
      /** @var $referenced_user \Drupal\user\UserInterface */
      $elements[$delta] = [
        '#theme' => 'username',
        '#account' => $entity,
        '#link_options' => ['attributes' => ['rel' => 'author']],
        '#cache' => [
          'tags' => $entity->getCacheTags(),
        ],
      ];
    }

    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public static function isApplicable(FieldDefinitionInterface $field_definition) {
    return $field_definition->getFieldStorageDefinition()->getSetting('target_type') == 'user';
  }

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity) {
    return $entity->access('view label', NULL, TRUE);
  }

}
