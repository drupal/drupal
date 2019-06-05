<?php

namespace Drupal\Tests\content_moderation\Functional\Update;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\FunctionalTests\Update\UpdatePathTestBase;

/**
 * Test updating the ContentModerationState entity default revisions.
 *
 * @group Update
 * @group legacy
 * @see content_moderation_post_update_update_cms_default_revisions
 */
class DefaultContentModerationStateRevisionUpdateTest extends UpdatePathTestBase {

  /**
   * {@inheritdoc}
   */
  protected function setDatabaseDumpFiles() {
    $this->databaseDumpFiles = [
      __DIR__ . '/../../../../../system/tests/fixtures/update/drupal-8.4.0.bare.standard.php.gz',
      __DIR__ . '/../../../fixtures/update/drupal-8.4.0-content_moderation_installed.php',
    ];
  }

  /**
   * Test updating the default revision.
   */
  public function testUpdateDefaultRevision() {
    // Include the database fixture required to test updating the default
    // revision. This is excluded from  ::setDatabaseDumpFiles so that we can
    // test the same post_update hook with no test content enabled.
    require __DIR__ . '/../../../fixtures/update/drupal-8.default-cms-entity-id-2941736.php';

    $this->runUpdates();

    foreach (['node', 'block_content'] as $entity_type_id) {
      $draft_pending_revision = $this->getEntityByLabel($entity_type_id, 'draft pending revision');
      $this->assertFalse($draft_pending_revision->isLatestRevision());
      $this->assertCompositeEntityMatchesDefaultRevisionId($draft_pending_revision);

      $published_default_revision = $this->getEntityByLabel($entity_type_id, 'published default revision');
      $this->assertTrue($published_default_revision->isLatestRevision());
      $this->assertCompositeEntityMatchesDefaultRevisionId($published_default_revision);

      $archived_default_revision = $this->getEntityByLabel($entity_type_id, 'archived default revision');
      $this->assertTrue($archived_default_revision->isLatestRevision());
      $this->assertCompositeEntityMatchesDefaultRevisionId($archived_default_revision);
    }
  }

  /**
   * Test the post_update hook when no entity types are being moderated.
   */
  public function testNoEntitiesUnderModeration() {
    // If any errors occur during the post_update hook, the test case will fail.
    $this->runUpdates();
  }

  /**
   * Assert for the given entity, the default revision ID matches.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The entity to use for the assertion.
   */
  protected function assertCompositeEntityMatchesDefaultRevisionId(ContentEntityInterface $entity) {
    $entity_type_manager = $this->container->get('entity_type.manager');
    $entity_list = $entity_type_manager->getStorage('content_moderation_state')
      ->loadByProperties([
        'content_entity_type_id' => $entity->getEntityTypeId(),
        'content_entity_id' => $entity->id(),
      ]);
    $content_moderation_state_entity = array_shift($entity_list);
    $this->assertEquals($entity->getLoadedRevisionId(), $content_moderation_state_entity->content_entity_revision_id->value);

    // Check that the data table records were updated correctly.
    /** @var \Drupal\Core\Database\Connection $database */
    $database = $this->container->get('database');
    $query = 'SELECT * FROM {content_moderation_state_field_data} WHERE id = :id';
    $records = $database->query($query, [':id' => $content_moderation_state_entity->id()])
      ->fetchAllAssoc('langcode');
    foreach ($records as $langcode => $record) {
      /** @var \Drupal\Core\Entity\ContentEntityInterface $translation */
      $translation = $content_moderation_state_entity->getTranslation($langcode);
      foreach ((array) $record as $field_name => $value) {
        if ($translation->hasField($field_name)) {
          $items = $translation->get($field_name)->getValue();
          $this->assertEquals(current($items[0]), $value);
        }
      }
    }
  }

  /**
   * Load an entity by label.
   *
   * @param string $entity_type_id
   *   The entity type ID.
   * @param string $label
   *   The label of the entity to load.
   *
   * @return \Drupal\Core\Entity\ContentEntityInterface
   *   The loaded entity.
   */
  protected function getEntityByLabel($entity_type_id, $label) {
    $entity_type_manager = $this->container->get('entity_type.manager');
    $label_field = $entity_type_manager->getDefinition($entity_type_id)->getKey('label');
    $entity_list = $entity_type_manager->getStorage($entity_type_id)
      ->loadByProperties([$label_field => $label]);
    return array_shift($entity_list);
  }

}
