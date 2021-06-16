<?php

namespace Drupal\path_alias\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityPublishedTrait;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\path_alias\PathAliasInterface;

/**
 * Defines the path_alias entity class.
 *
 * @ContentEntityType(
 *   id = "path_alias",
 *   label = @Translation("URL alias"),
 *   label_collection = @Translation("URL aliases"),
 *   label_singular = @Translation("URL alias"),
 *   label_plural = @Translation("URL aliases"),
 *   label_count = @PluralTranslation(
 *     singular = "@count URL alias",
 *     plural = "@count URL aliases"
 *   ),
 *   handlers = {
 *     "storage" = "Drupal\path_alias\PathAliasStorage",
 *     "storage_schema" = "Drupal\path_alias\PathAliasStorageSchema",
 *   },
 *   base_table = "path_alias",
 *   revision_table = "path_alias_revision",
 *   entity_keys = {
 *     "id" = "id",
 *     "revision" = "revision_id",
 *     "langcode" = "langcode",
 *     "uuid" = "uuid",
 *     "published" = "status",
 *   },
 *   admin_permission = "administer url aliases",
 *   list_cache_tags = { "route_match" },
 *   constraints = {
 *     "UniquePathAlias" = {}
 *   }
 * )
 */
class PathAlias extends ContentEntityBase implements PathAliasInterface {

  use EntityPublishedTrait;

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['path'] = BaseFieldDefinition::create('string')
      ->setLabel(new TranslatableMarkup('System path'))
      ->setDescription(new TranslatableMarkup('The path that this alias belongs to.'))
      ->setRequired(TRUE)
      ->setRevisionable(TRUE)
      ->addPropertyConstraints('value', [
        'Regex' => [
          'pattern' => '/^\//i',
          'message' => new TranslatableMarkup('The source path has to start with a slash.'),
        ],
      ])
      ->addPropertyConstraints('value', ['ValidPath' => []]);

    $fields['alias'] = BaseFieldDefinition::create('string')
      ->setLabel(new TranslatableMarkup('URL alias'))
      ->setDescription(new TranslatableMarkup('An alias used with this path.'))
      ->setRequired(TRUE)
      ->setRevisionable(TRUE)
      ->addPropertyConstraints('value', [
        'Regex' => [
          'pattern' => '/^\//i',
          'message' => new TranslatableMarkup('The alias path has to start with a slash.'),
        ],
      ]);

    $fields['langcode']->setDefaultValue(LanguageInterface::LANGCODE_NOT_SPECIFIED);

    // Add the published field.
    $fields += static::publishedBaseFieldDefinitions($entity_type);
    $fields['status']->setTranslatable(FALSE);

    return $fields;
  }

  /**
   * {@inheritdoc}
   */
  public function preSave(EntityStorageInterface $storage) {
    parent::preSave($storage);

    // Trim the alias value of whitespace and slashes. Ensure to not trim the
    // slash on the left side.
    $alias = rtrim(trim($this->getAlias()), "\\/");
    $this->setAlias($alias);
  }

  /**
   * {@inheritdoc}
   */
  public function postSave(EntityStorageInterface $storage, $update = TRUE) {
    parent::postSave($storage, $update);

    $alias_manager = \Drupal::service('path_alias.manager');
    $alias_manager->cacheClear($this->getPath());
    if ($update) {
      $alias_manager->cacheClear($this->original->getPath());
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function postDelete(EntityStorageInterface $storage, array $entities) {
    parent::postDelete($storage, $entities);

    $alias_manager = \Drupal::service('path_alias.manager');
    foreach ($entities as $entity) {
      $alias_manager->cacheClear($entity->getPath());
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getPath() {
    return $this->get('path')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setPath($path) {
    $this->set('path', $path);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getAlias() {
    return $this->get('alias')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setAlias($alias) {
    $this->set('alias', $alias);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function label() {
    return $this->getAlias();
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheTagsToInvalidate() {
    return ['route_match'];
  }

}
