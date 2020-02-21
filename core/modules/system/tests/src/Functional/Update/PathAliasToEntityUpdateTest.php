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
   * @param bool $perform_check
   *   Whether a schema check should be performed on "path_alias" save.
   *
   * @see system_update_8803()
   * @see system_update_8804()
   *
   * @dataProvider providerConversionToEntities
   */
  public function testConversionToEntities($perform_check) {
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

    // Enable or disable the "path_alias" save schema check.
    drupal_rewrite_settings([
      'settings' => [
        'system.path_alias_schema_check' => (object) [
          'value' => $perform_check,
          'required' => TRUE,
        ],
      ],
    ]);

    // Enable our test module in a way that does not affect the subsequent
    // updates run (::rebuildAll() would).
    /** @var \Drupal\Core\Extension\ModuleInstallerInterface $module_installer */
    $module_installer = $this->container->get('module_installer');
    $module_installer->install(['system_test']);
    $this->container = \Drupal::getContainer();

    // Trigger a path alias save during the update.
    /** @var \Drupal\Core\State\StateInterface $state */
    $state = $this->container->get('state');
    $state->set('system_test.path_alias_save', TRUE);

    // If the schema check is not performed, the path alias will be saved and we
    // will get an integrity exception, so no point in checking for failed
    // updates. If the schema check is performed, a logic exception will be
    // thrown, which will be caught in our test code, so the update is expected
    // to complete successfully. In this case we do want to check for failed
    // updates.
    $this->checkFailedUpdates = $perform_check;

    $this->runUpdates();

    if (!$perform_check) {
      $error_message = $this->cssSelect('.failure')[0]->getText();
      // In order to test against multiple database drivers assert that the
      // expected exception occurs and the error contains the expected table
      // name. The specifics of the error depend on the database driver.
      $this->assertContains("Failed: Drupal\Core\Database\IntegrityConstraintViolationException", $error_message);
      $this->assertContains("path_alias", $error_message);
      // Nothing else to assert.
      return;
    }

    // Check that an exception was thrown on "path_alias" save.
    $exception_info = $state->get('system_test.path_alias_save_exception_thrown');
    $this->assertIdentical($exception_info['class'], \LogicException::class);
    $this->assertIdentical($exception_info['message'], 'Path alias "/test" ("/user") could not be saved because the "system_update_8804" database update was not applied yet.');

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

  /**
   * Data provider for ::testConversionToEntities.
   */
  public function providerConversionToEntities() {
    return [
      'Perform check on "path_alias" save' => [TRUE],
      'Do not perform check on "path_alias" save' => [FALSE],
    ];
  }

}
