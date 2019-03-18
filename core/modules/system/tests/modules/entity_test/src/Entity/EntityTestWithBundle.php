<?php

namespace Drupal\entity_test\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;

/**
 * Defines the Test entity with bundle entity class.
 *
 * @ContentEntityType(
 *   id = "entity_test_with_bundle",
 *   label = @Translation("Test entity with bundle"),
 *   handlers = {
 *     "list_builder" = "Drupal\entity_test\EntityTestListBuilder",
 *     "view_builder" = "Drupal\entity_test\EntityTestViewBuilder",
 *     "access" = "Drupal\entity_test\EntityTestAccessControlHandler",
 *     "form" = {
 *       "default" = "\Drupal\Core\Entity\ContentEntityForm",
 *       "delete" = "\Drupal\Core\Entity\EntityDeleteForm"
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\DefaultHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "entity_test_with_bundle",
 *   data_table = "entity_test_with_bundle_field_data",
 *   admin_permission = "administer entity_test_with_bundle content",
 *   persistent_cache = FALSE,
 *   translatable = TRUE,
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *     "bundle" = "type",
 *     "label" = "name",
 *     "langcode" = "langcode",
 *   },
 *   bundle_entity_type = "entity_test_bundle",
 *   links = {
 *     "canonical" = "/entity_test_with_bundle/{entity_test_with_bundle}",
 *     "add-page" = "/entity_test_with_bundle/add",
 *     "add-form" = "/entity_test_with_bundle/add/{entity_test_bundle}",
 *     "edit-form" = "/entity_test_with_bundle/{entity_test_with_bundle}/edit",
 *     "delete-form" = "/entity_test_with_bundle/{entity_test_with_bundle}/delete",
 *     "create" = "/entity_test_with_bundle",
 *   },
 * )
 */
class EntityTestWithBundle extends ContentEntityBase {

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['name'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Name'))
      ->setDescription(t('The name of the test entity.'))
      ->setTranslatable(TRUE)
      ->setSetting('max_length', 32)
      ->setDisplayOptions('view', [
        'label' => 'hidden',
        'type' => 'string',
        'weight' => -5,
      ])
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => -5,
      ]);
    return $fields;
  }

}
