<?php

declare(strict_types=1);

namespace Drupal\entity_test_revlog\Entity;

use Drupal\Core\Entity\Attribute\ContentEntityType;
use Drupal\Core\Entity\Routing\RevisionHtmlRouteProvider;
use Drupal\Core\Entity\Routing\DefaultHtmlRouteProvider;
use Drupal\Core\Entity\Form\RevisionRevertForm;
use Drupal\Core\Entity\Form\RevisionDeleteForm;
use Drupal\Core\Entity\ContentEntityForm;
use Drupal\entity_test_revlog\EntityTestRevlogAccessControlHandler;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Defines the test entity class.
 */
#[ContentEntityType(
  id: 'entity_test_mul_revlog',
  label: new TranslatableMarkup('Test entity - data table, revisions log'),
  entity_keys: [
    'id' => 'id',
    'uuid' => 'uuid',
    'revision' => 'revision_id',
    'bundle' => 'type',
    'label' => 'name',
    'langcode' => 'langcode',
  ],
  handlers: [
    'access' => EntityTestRevlogAccessControlHandler::class,
    'form' => [
      'default' => ContentEntityForm::class,
      'revision-delete' => RevisionDeleteForm::class,
      'revision-revert' => RevisionRevertForm::class,
    ],
    'route_provider' => [
      'html' => DefaultHtmlRouteProvider::class,
      'revision' => RevisionHtmlRouteProvider::class,
    ],
  ],
  links: [
    'add-form' => '/entity_test_mul_revlog/add',
    'canonical' => '/entity_test_mul_revlog/manage/{entity_test_mul_revlog}',
    'delete-form' => '/entity_test/delete/entity_test_mul_revlog/{entity_test_mul_revlog}',
    'edit-form' => '/entity_test_mul_revlog/manage/{entity_test_mul_revlog}/edit',
    'revision' => '/entity_test_mul_revlog/{entity_test_mul_revlog}/revision/{entity_test_mul_revlog_revision}/view',
    'revision-delete-form' => '/entity_test_mul_revlog/{entity_test_mul_revlog}/revision/{entity_test_mul_revlog_revision}/delete',
    'revision-revert-form' => '/entity_test_mul_revlog/{entity_test_mul_revlog}/revision/{entity_test_mul_revlog_revision}/revert',
    'version-history' => '/entity_test_mul_revlog/{entity_test_mul_revlog}/revisions',
  ],
  base_table: 'entity_test_mul_revlog',
  data_table: 'entity_test_mul_revlog_field_data',
  revision_table: 'entity_test_mul_revlog_revision',
  revision_data_table: 'entity_test_mul_revlog_field_revision',
  translatable: TRUE,
  revision_metadata_keys: [
    'revision_user' => 'revision_user',
    'revision_created' => 'revision_created',
    'revision_log_message' => 'revision_log_message',
  ],
)]
class EntityTestMulWithRevisionLog extends EntityTestWithRevisionLog {

}
