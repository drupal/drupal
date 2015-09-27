<?php

/**
 * @file
 * Contains \Drupal\user\Plugin\Field\FieldFormatter\AuthorFormatter.
 */

namespace Drupal\user\Plugin\Field\FieldFormatter;

use Drupal\Core\Access\AccessResult;
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
    $elements = array();

    foreach ($this->getEntitiesToView($items, $langcode) as $delta => $entity) {
      /** @var $referenced_user \Drupal\user\UserInterface */
      $elements[$delta] = array(
        '#theme' => 'username',
        '#account' => $entity,
        '#link_options' => array('attributes' => array('rel' => 'author')),
        '#cache' => array(
          'tags' => $entity->getCacheTags(),
        ),
      );
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
    // Always allow an entity author's username to be read, even if the current
    // user does not have permission to view the entity author's profile.
    return AccessResult::allowed();
  }

}
