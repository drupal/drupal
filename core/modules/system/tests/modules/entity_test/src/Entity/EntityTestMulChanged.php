<?php

namespace Drupal\entity_test\Entity;

use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;

/**
 * Defines the test entity class.
 *
 * @ContentEntityType(
 *   id = "entity_test_mul_changed",
 *   label = @Translation("Test entity - data table"),
 *   handlers = {
 *     "view_builder" = "Drupal\entity_test\EntityTestViewBuilder",
 *     "access" = "Drupal\entity_test\EntityTestAccessControlHandler",
 *     "form" = {
 *       "default" = "Drupal\entity_test\EntityTestForm",
 *       "delete" = "Drupal\entity_test\EntityTestDeleteForm"
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\DefaultHtmlRouteProvider",
 *     },
 *     "views_data" = "Drupal\views\EntityViewsData"
 *   },
 *   base_table = "entity_test_mul_changed",
 *   data_table = "entity_test_mul_changed_property",
 *   translatable = TRUE,
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *     "bundle" = "type",
 *     "label" = "name",
 *     "langcode" = "langcode"
 *   },
 *   links = {
 *     "add-form" = "/entity_test_mul_changed/add",
 *     "canonical" = "/entity_test_mul_changed/manage/{entity_test_mul_changed}",
 *     "edit-form" = "/entity_test_mul_changed/manage/{entity_test_mul_changed}/edit",
 *     "delete-form" = "/entity_test/delete/entity_test_mul_changed/{entity_test_mul_changed}",
 *   },
 *   field_ui_base_route = "entity.entity_test_mul_changed.admin_form",
 * )
 */
class EntityTestMulChanged extends EntityTestMul implements EntityChangedInterface {

  use EntityChangedTrait;

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['changed'] = BaseFieldDefinition::create('changed_test')
      ->setLabel(t('Changed'))
      ->setDescription(t('The time that the entity was last edited.'))
      ->setTranslatable(TRUE);

    $fields['not_translatable'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Non translatable'))
      ->setDescription(t('A non-translatable string field'));

    return $fields;
  }

  /**
   * {@inheritdoc}
   */
  public function save() {
    // Ensure a new timestamp.
    sleep(1);
    return parent::save();
  }

}
