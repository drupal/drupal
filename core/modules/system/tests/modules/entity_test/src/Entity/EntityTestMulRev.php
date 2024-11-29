<?php

declare(strict_types=1);

namespace Drupal\entity_test\Entity;

use Drupal\Core\Entity\Attribute\ContentEntityType;
use Drupal\Core\Entity\Routing\DefaultHtmlRouteProvider;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\entity_test\EntityTestAccessControlHandler;
use Drupal\entity_test\EntityTestDeleteForm;
use Drupal\entity_test\EntityTestForm;
use Drupal\entity_test\EntityTestViewBuilder as TestViewBuilder;
use Drupal\views\EntityViewsData;

/**
 * Defines the test entity class.
 */
#[ContentEntityType(
  id: 'entity_test_mulrev',
  label: new TranslatableMarkup('Test entity - mul revisions and data table'),
  entity_keys: [
    'id' => 'id',
    'uuid' => 'uuid',
    'bundle' => 'type',
    'revision' => 'revision_id',
    'label' => 'name',
    'langcode' => 'langcode',
  ],
  handlers: [
    'view_builder' => TestViewBuilder::class,
    'access' => EntityTestAccessControlHandler::class,
    'form' => [
      'default' => EntityTestForm::class,
      'delete' => EntityTestDeleteForm::class,
    ],
    'views_data' => EntityViewsData::class,
    'route_provider' => ['html' => DefaultHtmlRouteProvider::class],
  ],
  links: [
    'add-form' => '/entity_test_mulrev/add/{type}',
    'add-page' => '/entity_test_mulrev/add',
    'canonical' => '/entity_test_mulrev/manage/{entity_test_mulrev}',
    'delete-form' => '/entity_test/delete/entity_test_mulrev/{entity_test_mulrev}',
    'edit-form' => '/entity_test_mulrev/manage/{entity_test_mulrev}/edit',
    'revision' => '/entity_test_mulrev/{entity_test_mulrev}/revision/{entity_test_mulrev_revision}/view',
  ],
  admin_permission: 'administer entity_test content',
  base_table: 'entity_test_mulrev',
  data_table: 'entity_test_mulrev_property_data',
  revision_table: 'entity_test_mulrev_revision',
  revision_data_table: 'entity_test_mulrev_property_revision',
  translatable: TRUE,
  show_revision_ui: TRUE,
)]
class EntityTestMulRev extends EntityTestRev {

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['non_mul_field'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Non translatable'))
      ->setDescription(t('A non-translatable string field'))
      ->setRevisionable(TRUE);

    return $fields;
  }

}
