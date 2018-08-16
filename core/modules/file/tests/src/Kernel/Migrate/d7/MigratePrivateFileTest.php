<?php

namespace Drupal\Tests\file\Kernel\Migrate\d7;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Tests\migrate_drupal\Kernel\d7\MigrateDrupal7TestBase;

/**
 * Tests private files migration.
 *
 * @group file
 */
class MigratePrivateFileTest extends MigrateDrupal7TestBase {

  use FileMigrationSetupTrait;

  /**
   * {@inheritdoc}
   */
  public static $modules = ['file'];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->setSetting('file_private_path', $this->container->get('site.path') . '/private');
    $this->fileMigrationSetup();
  }

  /**
   * {@inheritdoc}
   */
  protected function getFileMigrationInfo() {
    return [
      'path' => 'private://sites/default/private/Babylon5.txt',
      'size' => '3',
      'base_path' => 'private://',
      'plugin_id' => 'd7_file_private',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function register(ContainerBuilder $container) {
    parent::register($container);
    $container->register('stream_wrapper.private', 'Drupal\Core\StreamWrapper\PrivateStream')
      ->addTag('stream_wrapper', ['scheme' => 'private']);
  }

  /**
   * Tests that all expected files are migrated.
   */
  public function testFileMigration() {
    $this->assertEntity(3, 'Babylon5.txt', 'private://Babylon5.txt', 'text/plain', '3', '1486104045', '1486104045', '1');
  }

}
