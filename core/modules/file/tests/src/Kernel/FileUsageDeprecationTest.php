<?php

namespace Drupal\Tests\file\Kernel;

use Drupal\file\Entity\File;
use Drupal\file\FileUsage\DatabaseFileUsageBackend;
use Drupal\KernelTests\KernelTestBase;

/**
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
   * @expectDeprecation() file_get_file_references() is deprecated in drupal:9.3.0 and is removed from drupal:10.0.0. Use \Drupal\file\FileUsage\FileUsageInterface::getReferences() instead. See https://www.drupal.org/node/3035357.
   * @see file_get_file_references()
   */
  public function testFileGetFileReferencesDeprecation() {
    $file = File::create(['uri' => 'public://test.txt']);
    file_get_file_references($file);
  }

  /**
   * @expectDeprecation() file_field_find_file_reference_column() is deprecated in drupal:9.3.0 and is removed from drupal:10.0.0. There is no replacement for this function. See https://www.drupal.org/node/3035357.
   * @see file_field_find_file_reference_column()
   */
  public function testFileFieldFindFileReferenceColumnDeprecation() {
    $definition = $this->container->get('entity_field.manager')->getBaseFieldDefinitions('file')['uri'];
    file_field_find_file_reference_column($definition);
  }

  /**
   * @expectDeprecation() The $config_factory parameter will become required in drupal:10.0.0. See https://www.drupal.org/node/3035357.
   * @covers \Drupal\file\FileUsage\FileUsageBase::__construct
   */
  public function testFileUsageBaseMissingConfigFactoryParameter() {
    new DatabaseFileUsageBackend(
      $this->container->get('database'),
      'file_usage',
      NULL,
      $this->container->get('entity_type.manager'),
      $this->container->get('entity_field.manager')
    );
  }

  /**
   * @expectDeprecation() The $entity_type_manager parameter will become required in drupal:10.0.0. See https://www.drupal.org/node/3035357.
   * @covers \Drupal\file\FileUsage\FileUsageBase::__construct
   */
  public function testFileUsageBaseMissingEntityTypeManagerParameter() {
    new DatabaseFileUsageBackend(
      $this->container->get('database'),
      'file_usage',
      $this->container->get('config.factory'),
      NULL,
      $this->container->get('entity_field.manager')
    );
  }

  /**
   * @expectDeprecation() The $entity_field_manager parameter will become required in drupal:10.0.0. See https://www.drupal.org/node/3035357.
   * @covers \Drupal\file\FileUsage\FileUsageBase::__construct
   */
  public function testFileUsageBaseMissingEntityFieldManagerParameter() {
    new DatabaseFileUsageBackend(
      $this->container->get('database'),
      'file_usage',
      $this->container->get('config.factory'),
      $this->container->get('entity_type.manager'),
      NULL
    );
  }

  /**
   * @expectDeprecation() Using drupal_static_reset() with 'file_get_file_references' as parameter is deprecated in drupal:9.3.0 and will be removed in drupal:10.0.0. See https://www.drupal.org/node/3035357.
   * @see drupal_static_reset()
   */
  public function testFileGetFileReferencesCacheResetDeprecation() {
    drupal_static_reset('file_get_file_references');
  }

  /**
   * @expectDeprecation() \Drupal\file\FileAccessControlHandler::getFileReferences() is deprecated in drupal:9.3.0 and is removed from drupal:10.0.0. There is no replacement for this function. See https://www.drupal.org/node/3035357.
   * @covers \Drupal\file\FileAccessControlHandler::getFileReferences
   */
  public function testGetFileReferencesDeprecation() {
    /** @var \Drupal\Core\Entity\EntityAccessControlHandlerInterface $access_controller */
    $access_controller = $this->container->get('entity_type.manager')->getHandler('file', 'access');
    $deprecated_method = new \ReflectionMethod($access_controller, 'getFileReferences');
    $deprecated_method->setAccessible(TRUE);
    $file = File::create(['uri' => 'public://test.txt']);
    $deprecated_method->invoke($access_controller, $file);
  }

}
