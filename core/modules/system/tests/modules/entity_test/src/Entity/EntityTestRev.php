<?php

declare(strict_types=1);

namespace Drupal\entity_test\Entity;

use Drupal\Core\Entity\Attribute\ContentEntityType;
use Drupal\Core\Entity\Routing\DefaultHtmlRouteProvider;
use Drupal\Core\Entity\Routing\RevisionHtmlRouteProvider;
use Drupal\Core\Entity\Form\RevisionRevertForm;
use Drupal\Core\Entity\Form\RevisionDeleteForm;
use Drupal\Core\Entity\Form\DeleteMultipleForm;
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
  id: 'entity_test_rev',
  label: new TranslatableMarkup('Test entity - revisions'),
  entity_keys: [
    'id' => 'id',
    'uuid' => 'uuid',
    'revision' => 'revision_id',
    'bundle' => 'type',
    'label' => 'name',
    'langcode' => 'langcode',
  ],
  handlers: [
    'access' => EntityTestAccessControlHandler::class,
    'view_builder' => TestViewBuilder::class,
    'form' => [
      'default' => EntityTestForm::class,
      'delete' => EntityTestDeleteForm::class,
      'delete-multiple-confirm' => DeleteMultipleForm::class,
      'revision-delete' => RevisionDeleteForm::class,
      'revision-revert' => RevisionRevertForm::class,
    ],
    'views_data' => EntityViewsData::class,
    'route_provider' => [
      'html' => DefaultHtmlRouteProvider::class,
      'revision' => RevisionHtmlRouteProvider::class,
    ],
  ],
  links: [
    'add-form' => '/entity_test_rev/add',
    'canonical' => '/entity_test_rev/manage/{entity_test_rev}',
    'delete-form' => '/entity_test/delete/entity_test_rev/{entity_test_rev}',
    'delete-multiple-form' => '/entity_test_rev/delete_multiple',
    'edit-form' => '/entity_test_rev/manage/{entity_test_rev}/edit',
    'revision' => '/entity_test_rev/{entity_test_rev}/revision/{entity_test_rev_revision}/view',
    'revision-delete-form' => '/entity_test_rev/{entity_test_rev}/revision/{entity_test_rev_revision}/delete',
    'revision-revert-form' => '/entity_test_rev/{entity_test_rev}/revision/{entity_test_rev_revision}/revert',
    'version-history' => '/entity_test_rev/{entity_test_rev}/revisions',
  ],
  admin_permission: 'administer entity_test content',
  base_table: 'entity_test_rev',
  revision_table: 'entity_test_rev_revision', show_revision_ui: TRUE,
)]
class EntityTestRev extends EntityTest {

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['name']->setRevisionable(TRUE);
    $fields['user_id']->setRevisionable(TRUE);

    $fields['non_rev_field'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Non Revisionable Field'))
      ->setDescription(t('A non-revisionable test field.'))
      ->setRevisionable(FALSE)
      ->setTranslatable(TRUE)
      ->setCardinality(1)
      ->setReadOnly(TRUE);

    return $fields;
  }

}
