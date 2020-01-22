<?php

namespace Drupal\Tests\system\Functional\Update;

use Drupal\FunctionalTests\Update\UpdatePathTestBase;
use Drupal\path_alias\Entity\PathAlias;

/**
 * Tests the conversion of path aliases to entities.
 *
 * @group Update
 * @group legacy
 */
class PathAliasToEntityUpdateTest extends UpdatePathTestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected function setDatabaseDumpFiles() {
    $this->databaseDumpFiles = [
      __DIR__ . '/../../../../tests/fixtures/update/drupal-8.filled.standard.php.gz',
      __DIR__ . '/../../../../tests/fixtures/update/drupal-8.convert-path-aliases-to-entities-2336597.php',
    ];
  }

  /**
   * Tests the conversion of path aliases to entities.
   *
   * @see system_update_8803()
   * @see system_update_8804()
   */
  public function testConversionToEntities() {
    $database = \Drupal::database();
    $schema = $database->schema();
    $this->assertTrue($schema->tableExists('url_alias'));

    $query = $database->select('url_alias');
    $query->addField('url_alias', 'pid', 'id');
    $query->addField('url_alias', 'source', 'path');
    $query->addField('url_alias', 'alias');
    $query->addField('url_alias', 'langcode');

    // Path aliases did not have a 'status' value before the conversion to
    // entities, but we're adding it here to ensure that the field was installed
    // and populated correctly.
    $query->addExpression('1', 'status');
    $original_records = $query->execute()->fetchAllAssoc('id');

    // drupal-8.filled.standard.php.gz contains one URL alias and
    // drupal-8.convert-path-aliases-to-entities-2336597.php adds another four.
    $url_alias_count = 5;
    $this->assertCount($url_alias_count, $original_records);

    $this->runUpdates();

    /** @var \Drupal\Core\Extension\ModuleHandlerInterface $module_handler */
    $module_handler = $this->container->get('module_handler');
    $this->assertTrue($module_handler->moduleExists('path_alias'));

    $entity_type = \Drupal::entityDefinitionUpdateManager()->getEntityType('path_alias');
    $this->assertEquals('path_alias', $entity_type->getProvider());
    $this->assertEquals(PathAlias::class, $entity_type->getClass());

    $field_storage_definitions = \Drupal::service('entity.last_installed_schema.repository')->getLastInstalledFieldStorageDefinitions('path_alias');
    $this->assertEquals('path_alias', $field_storage_definitions['id']->getProvider());
    $this->assertEquals('path_alias', $field_storage_definitions['revision_id']->getProvider());
    $this->assertEquals('path_alias', $field_storage_definitions['langcode']->getProvider());
    $this->assertEquals('path_alias', $field_storage_definitions['uuid']->getProvider());
    $this->assertEquals('path_alias', $field_storage_definitions['status']->getProvider());
    $this->assertEquals('path_alias', $field_storage_definitions['path']->getProvider());
    $this->assertEquals('path_alias', $field_storage_definitions['alias']->getProvider());

    // Check that the 'path_alias' entity tables have been created and the
    // 'url_alias' table has been deleted.
    $this->assertTrue($schema->tableExists('path_alias'));
    $this->assertTrue($schema->tableExists('path_alias_revision'));
    $this->assertFalse($schema->tableExists('url_alias'));

    // Check that we have a backup of the old table.
    $this->assertCount(1, $schema->findTables('old_%_url_alias'));

    $path_alias_count = \Drupal::entityTypeManager()->getStorage('path_alias')->loadMultiple();
    $this->assertCount($url_alias_count, $path_alias_count);

    // Make sure that existing aliases still work.
    $assert_session = $this->assertSession();
    $this->drupalGet('test-article');
    $assert_session->responseContains('/node/1');
    $assert_session->pageTextContains('Test Article - New title');

    $this->drupalGet('test-article-new-alias');
    $assert_session->responseContains('/node/1');
    $assert_session->pageTextContains('Test Article - New title');

    $this->drupalGet('test-alias-for-any-language');
    $assert_session->responseContains('/node/8');
    $assert_session->pageTextContains('Test title');

    $this->drupalGet('test-alias-in-english');
    $assert_session->responseContains('/node/8');
    $assert_session->pageTextContains('Test title');

    $spanish = \Drupal::languageManager()->getLanguage('es');

    $this->drupalGet('test-alias-for-any-language', ['language' => $spanish]);
    $assert_session->responseContains('/es/node/8');
    $assert_session->pageTextContains('Test title Spanish');

    $this->drupalGet('test-alias-in-spanish', ['language' => $spanish]);
    $assert_session->responseContains('/es/node/8');
    $assert_session->pageTextContains('Test title Spanish');

    // Check that correct data was written in both the base and the revision
    // tables.
    $base_table_records = $database->select('path_alias')
      ->fields('path_alias', ['id', 'path', 'alias', 'langcode', 'status'])
      ->execute()->fetchAllAssoc('id');
    $this->assertEquals($original_records, $base_table_records);

    $revision_table_records = $database->select('path_alias_revision')
      ->fields('path_alias_revision', ['id', 'path', 'alias', 'langcode', 'status'])
      ->execute()->fetchAllAssoc('id');
    $this->assertEquals($original_records, $revision_table_records);
  }

}
