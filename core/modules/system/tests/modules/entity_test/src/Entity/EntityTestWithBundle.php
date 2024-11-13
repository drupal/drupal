<?php

declare(strict_types=1);

namespace Drupal\entity_test\Entity;

use Drupal\Core\Entity\Attribute\ContentEntityType;
use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Entity\EntityDeleteForm;
use Drupal\Core\Entity\Routing\DefaultHtmlRouteProvider;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\entity_test\EntityTestAccessControlHandler;
use Drupal\entity_test\EntityTestListBuilder;
use Drupal\entity_test\EntityTestViewBuilder as TestViewBuilder;

/**
 * Defines the Test entity with bundle entity class.
 */
#[ContentEntityType(
  id: 'entity_test_with_bundle',
  label: new TranslatableMarkup('Test entity with bundle'),
  persistent_cache: FALSE,
  entity_keys: [
    'id' => 'id',
    'uuid' => 'uuid',
    'bundle' => 'type',
    'label' => 'name',
    'langcode' => 'langcode',
  ],
  handlers: [
    'list_builder' => EntityTestListBuilder::class,
    'view_builder' => TestViewBuilder::class,
    'access' => EntityTestAccessControlHandler::class,
    'form' => [
      'default' => ContentEntityForm::class,
      'delete' => EntityDeleteForm::class,
    ],
    'route_provider' => [
      'html' => DefaultHtmlRouteProvider::class,
    ],
  ],
  links: [
    'canonical' => '/entity_test_with_bundle/{entity_test_with_bundle}',
    'add-page' => '/entity_test_with_bundle/add',
    'add-form' => '/entity_test_with_bundle/add/{entity_test_bundle}',
    'edit-form' => '/entity_test_with_bundle/{entity_test_with_bundle}/edit',
    'delete-form' => '/entity_test_with_bundle/{entity_test_with_bundle}/delete',
    'create' => '/entity_test_with_bundle',
  ],
  admin_permission: 'administer entity_test_with_bundle content',
  bundle_entity_type: 'entity_test_bundle',
  base_table: 'entity_test_with_bundle',
  data_table: 'entity_test_with_bundle_field_data',
  translatable: TRUE,
)]
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
      ->setDisplayConfigurable('view', TRUE)
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
