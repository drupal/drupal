<?php

declare(strict_types=1);

namespace Drupal\entity_test\Entity;

use Drupal\Core\Entity\Attribute\ContentEntityType;
use Drupal\Core\Entity\Routing\DefaultHtmlRouteProvider;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\Core\Entity\EntityChangedTrait;
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
  id: 'entity_test_mul_changed',
  label: new TranslatableMarkup('Test entity - multiple changed and data table'),
  entity_keys: [
    'id' => 'id',
    'uuid' => 'uuid',
    'bundle' => 'type',
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
    'route_provider' => [
      'html' => DefaultHtmlRouteProvider::class,
    ],
    'views_data' => EntityViewsData::class,
  ],
  links: [
    'add-form' => '/entity_test_mul_changed/add/{type}',
    'add-page' => '/entity_test_mul_changed/add',
    'canonical' => '/entity_test_mul_changed/manage/{entity_test_mul_changed}',
    'edit-form' => '/entity_test_mul_changed/manage/{entity_test_mul_changed}/edit',
    'delete-form' => '/entity_test/delete/entity_test_mul_changed/{entity_test_mul_changed}',
  ],
  base_table: 'entity_test_mul_changed',
  data_table: 'entity_test_mul_changed_property',
  translatable: TRUE,
  field_ui_base_route: 'entity.entity_test_mul_changed.admin_form',
)]
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
