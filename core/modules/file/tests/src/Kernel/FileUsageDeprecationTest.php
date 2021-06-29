<?php

namespace Drupal\Tests\file\Kernel;

use Drupal\file\Entity\File;
use Drupal\file\FileUsage\DatabaseFileUsageBackend;
use Drupal\KernelTests\KernelTestBase;

/**
 * Tests deprecated file usage methods.
 *
 * @group file
 * @group legacy
 */
class FileUsageDeprecationTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'file',
    'user',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->installSchema('file', ['file_usage']);
    $this->installEntitySchema('file');
  }

  /**
   * Test the file_get_file_references() deprecation.
   */
  public function testFileGetFileReferencesDeprecation(): void {
    $this->expectDeprecation('file_get_file_references() is deprecated in drupal:9.3.0 and is removed from drupal:10.0.0. Use \Drupal\file\FileUsage\FileUsageInterface::getReferences() instead. See https://www.drupal.org/node/3035357.');
    $file = File::create(['uri' => 'public://test.txt']);
    file_get_file_references($file);
  }

  /**
   * Test the file_field_find_file_reference_column() deprecation.
   */
  public function testFileFieldFindFileReferenceColumnDeprecation(): void {
    $this->expectDeprecation('file_field_find_file_reference_column() is deprecated in drupal:9.3.0 and is removed from drupal:10.0.0. There is no replacement for this function. See https://www.drupal.org/node/3035357.');
    $definition = $this->container->get('entity_field.manager')->getBaseFieldDefinitions('file')['uri'];
    file_field_find_file_reference_column($definition);
  }

  /**
   * Test the FileUsageBase constructor parameter deprecation.
   */
  public function testFileUsageBaseMissingEntityTypeManagerParameter(): void {
    $this->expectDeprecation('Calling FileUsageBase::__construct() without the $entity_type_manager argument is deprecated in drupal:9.3.0 and the $entity_type_manager argument will be required in drupal:10.0.0. See https://www.drupal.org/node/3035357.');
    new DatabaseFileUsageBackend(
      $this->container->get('database'),
      'file_usage',
      $this->container->get('config.factory'),
      NULL,
      $this->container->get('entity_field.manager')
    );
  }

  /**
   * Test the FileUsageBase constructor parameter deprecation.
   */
  public function testFileUsageBaseMissingEntityFieldManagerParameter(): void {
    $this->expectDeprecation('Calling FileUsageBase::__construct() without the $entity_field_manager argument is deprecated in drupal:9.3.0 and the $entity_field_manager argument will be required in drupal:10.0.0. See https://www.drupal.org/node/3035357.');
    new DatabaseFileUsageBackend(
      $this->container->get('database'),
      'file_usage',
      $this->container->get('config.factory'),
      $this->container->get('entity_type.manager'),
      NULL
    );
  }

  /**
   * Test the drupal_static_reset function deprecation.
   */
  public function testFileGetFileReferencesCacheResetDeprecation(): void {
    $this->expectDeprecation("Using drupal_static_reset() with 'file_get_file_references' as parameter is deprecated in drupal:9.3.0 and will be removed in drupal:10.0.0. See https://www.drupal.org/node/3035357.");
    drupal_static_reset('file_get_file_references');
  }

  /**
   * Test the FileAccessControlHandler::getFileReferences() deprecation.
   */
  public function testGetFileReferencesDeprecation(): void {
    $this->expectDeprecation('\Drupal\file\FileAccessControlHandler::getFileReferences() is deprecated in drupal:9.3.0 and is removed from drupal:10.0.0. There is no replacement for this function. See https://www.drupal.org/node/3035357.');
    /** @var \Drupal\Core\Entity\EntityAccessControlHandlerInterface $access_controller */
    $access_controller = $this->container->get('entity_type.manager')->getHandler('file', 'access');
    $deprecated_method = new \ReflectionMethod($access_controller, 'getFileReferences');
    $deprecated_method->setAccessible(TRUE);
    $file = File::create(['uri' => 'public://test.txt']);
    $deprecated_method->invoke($access_controller, $file);
  }

}
