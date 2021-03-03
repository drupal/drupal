<?php

namespace Drupal\Tests\file\Functional\Update;

use Drupal\Core\Database\Database;
use Drupal\field\Entity\FieldConfig;
use Drupal\FunctionalTests\Update\UpdatePathTestBase;

/**
 * Tests file_post_update_add_txt_if_allows_insecure_extensions().
 *
 * @group Update
 * @group legacy
 */
class FileFieldFileExtensionsUpdateTest extends UpdatePathTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['file'];

  /**
   * {@inheritdoc}
   */
  protected function setDatabaseDumpFiles() {
    $this->databaseDumpFiles = [
      __DIR__ . '/../../../../../system/tests/fixtures/update/drupal-8.8.0.bare.standard.php.gz',
    ];
  }

  /**
   * Tests adding txt extension to field that allow insecure extensions.
   */
  public function testInsecureUpdatesNotAllowed() {
    $this->setAllowedExtensions('php jpg');
    $this->runUpdates();
    $this->assertSession()->statusCodeEquals('200');
    $field = FieldConfig::load('node.article.field_image');
    $this->assertSame('php jpg txt', $field->getSetting('file_extensions'));
  }

  /**
   * Tests file fields that permit all extensions.
   */
  public function testAllFileTypesAllowed() {
    $this->setAllowedExtensions('');
    $this->runUpdates();
    $this->assertSession()->statusCodeEquals('200');
    $field = FieldConfig::load('node.article.field_image');
    $this->assertSame('', $field->getSetting('file_extensions'));
  }

  /**
   * Tests update when insecure uploads are allowed.
   */
  public function testInsecureUpdatesAllowed() {
    $this->setAllowedExtensions('php');

    // Do direct database updates to avoid dependencies.
    $connection = Database::getConnection();
    $config = $connection->select('config')
      ->fields('config', ['data'])
      ->condition('collection', '')
      ->condition('name', 'system.file')
      ->execute()
      ->fetchField();
    $config = unserialize($config);
    $config['allow_insecure_uploads'] = TRUE;
    $connection->update('config')
      ->fields([
        'data' => serialize($config),
      ])
      ->condition('collection', '')
      ->condition('name', 'system.file')
      ->execute();

    $this->runUpdates();
    $this->assertSession()->pageTextContains('The system is configured to allow insecure file uploads. No file field updates are necessary.');
    $this->assertSession()->statusCodeEquals('200');
    $field = FieldConfig::load('node.article.field_image');
    $this->assertSame('php', $field->getSetting('file_extensions'));
  }

  /**
   * Sets the allowed extensions on the article image field.
   *
   * @param string $allowed_extensions
   *   The list of allowed extensions.
   */
  protected function setAllowedExtensions(string $allowed_extensions) {
    // Do direct database updates to avoid dependencies.
    $connection = Database::getConnection();

    $config = $connection->select('config')
      ->fields('config', ['data'])
      ->condition('collection', '')
      ->condition('name', 'field.field.node.article.field_image')
      ->execute()
      ->fetchField();
    $config = unserialize($config);
    $this->assertArrayHasKey('file_extensions', $config['settings']);
    $config['settings']['file_extensions'] = $allowed_extensions;
    $connection->update('config')
      ->fields([
        'data' => serialize($config),
      ])
      ->condition('collection', '')
      ->condition('name', 'field.field.node.article.field_image')
      ->execute();
  }

}
