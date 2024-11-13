<?php

declare(strict_types=1);

namespace Drupal\entity_test\Entity;

use Drupal\Core\Entity\Attribute\ContentEntityType;
use Drupal\Core\Entity\Form\DeleteMultipleForm;
use Drupal\Core\Entity\Routing\DefaultHtmlRouteProvider;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Entity\EntityPublishedInterface;
use Drupal\Core\Entity\EntityPublishedTrait;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\entity_test\EntityTestAccessControlHandler;
use Drupal\entity_test\EntityTestDeleteForm;
use Drupal\entity_test\EntityTestForm;
use Drupal\entity_test\EntityTestViewBuilder as TestViewBuilder;
use Drupal\views\EntityViewsData;

/**
 * Defines the test entity class.
 */
#[ContentEntityType(
  id: 'entity_test_mulrevpub',
  label: new TranslatableMarkup('Test entity - revisions, data table, and published interface'),
  entity_keys: [
    'id' => 'id',
    'uuid' => 'uuid',
    'bundle' => 'type',
    'revision' => 'revision_id',
    'label' => 'name',
    'langcode' => 'langcode',
    'published' => 'status',
  ],
  handlers: [
    'view_builder' => TestViewBuilder::class,
    'access' => EntityTestAccessControlHandler::class,
    'form' => [
      'default' => EntityTestForm::class,
      'delete' => EntityTestDeleteForm::class,
      'delete-multiple-confirm' => DeleteMultipleForm::class,
    ],
    'views_data' => EntityViewsData::class,
    'route_provider' => [
      'html' => DefaultHtmlRouteProvider::class,
    ],
  ],
  links: [
    'add-form' => '/entity_test_mulrevpub/add',
    'canonical' => '/entity_test_mulrevpub/manage/{entity_test_mulrevpub}',
    'delete-form' => '/entity_test/delete/entity_test_mulrevpub/{entity_test_mulrevpub}',
    'delete-multiple-form' => '/entity_test/delete',
    'edit-form' => '/entity_test_mulrevpub/manage/{entity_test_mulrevpub}/edit',
    'revision' => '/entity_test_mulrevpub/{entity_test_mulrevpub}/revision/{entity_test_mulrevpub_revision}/view',
  ],
  admin_permission: 'administer entity_test content',
  base_table: 'entity_test_mulrevpub',
  data_table: 'entity_test_mulrevpub_property_data',
  revision_table: 'entity_test_mulrevpub_revision',
  revision_data_table: 'entity_test_mulrevpub_property_revision',
  translatable: TRUE,
  show_revision_ui: TRUE,
)]
class EntityTestMulRevPub extends EntityTestMulRev implements EntityPublishedInterface {

  use EntityPublishedTrait;

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    return parent::baseFieldDefinitions($entity_type) + static::publishedBaseFieldDefinitions($entity_type);
  }

}
