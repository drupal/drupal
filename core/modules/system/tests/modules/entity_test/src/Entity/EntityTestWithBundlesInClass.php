<?php

namespace Drupal\entity_test\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;

/**
 * Defines the Test entity with bundles defined by the class.
 *
 * @ContentEntityType(
 *   id = "entity_test_class_bundles",
 *   label = @Translation("Test entity with bundles in class"),
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
 *   base_table = "entity_test_class_bundles",
 *   data_table = "entity_test_class_bundles_field_data",
 *   admin_permission = "administer entity_test_class_bundles content",
 *   persistent_cache = FALSE,
 *   translatable = TRUE,
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *     "bundle" = "type",
 *     "label" = "name",
 *     "langcode" = "langcode",
 *   },
 *   links = {
 *     "canonical" = "/entity_test_class_bundles/{entity_test_class_bundles}",
 *     "add-page" = "/entity_test_class_bundles/add",
 *     "add-form" = "/entity_test_class_bundles/add/{entity_test_bundle}",
 *     "edit-form" = "/entity_test_class_bundles/{entity_test_class_bundles}/edit",
 *     "delete-form" = "/entity_test_class_bundles/{entity_test_class_bundles}/delete",
 *     "create" = "/entity_test_class_bundles",
 *   },
 * )
 */
class EntityTestWithBundlesInClass extends ContentEntityBase {

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

  /**
   * {@inheritdoc}
   */
  public static function bundleDefinitions(EntityTypeInterface $entity_type) {
    return [
      'alpha' => [
        'label' => t('Alpha'),
      ],
    ];
  }

}
