<?php

namespace Drupal\Tests\file\Kernel;

use Drupal\file\FileInterface;
use Drupal\Tests\field\Kernel\FieldKernelTestBase;
use Drupal\file\Entity\File;

/**
 * Tests file deprecations.
 *
 * @group file
 * @group legacy
 */
class FileLegacyTest extends FieldKernelTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['file'];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->installEntitySchema('user');
    $this->installConfig(['user']);

    $this->installEntitySchema('file');
    $this->installSchema('file', ['file_usage']);
  }

  /**
   * Tests that File::url() is deprecated.
   *
   * @expectedDeprecation File entities returning the URL to the physical file in File::url() is deprecated, use $file->createFileUrl() instead. See https://www.drupal.org/node/3019830
   */
  public function testFileUrlDeprecation() {
    file_put_contents('public://example.txt', $this->randomMachineName());
    $file = File::create([
      'uri' => 'public://example.txt',
    ]);
    $file->save();

    $this->assertEquals($file->createFileUrl(FALSE), $file->url());
  }

  /**
   * @expectedDeprecation file_load_multiple() is deprecated in Drupal 8.0.0 and will be removed before Drupal 9.0.0. Use \Drupal\file\Entity\File::loadMultiple(). See https://www.drupal.org/node/2266845
   * @expectedDeprecation file_load() is deprecated in Drupal 8.0.0 and will be removed before Drupal 9.0.0. Use \Drupal\file\Entity\File::load(). See https://www.drupal.org/node/2266845
   * @expectedDeprecation file_delete() is deprecated in Drupal 8.7.0 and will be removed before Drupal 9.0.0. Use \Drupal\Core\Entity\EntityStorageInterface::delete() instead. See https://www.drupal.org/node/3021663.
   * @expectedDeprecation file_delete_multiple() is deprecated in Drupal 8.7.0 and will be removed before Drupal 9.0.0. Use \Drupal\Core\Entity\EntityStorageInterface::delete() instead. See https://www.drupal.org/node/3021663.
   */
  public function testEntityLegacyCode() {
    // Test deprecation of file_load_multiple().
    file_put_contents('public://example.txt', $this->randomMachineName());
    $this->assertCount(0, file_load_multiple());
    File::create(['uri' => 'public://example.txt'])->save();
    $this->assertCount(1, file_load_multiple());
    File::create(['uri' => 'public://example.txt'])->save();
    $this->assertCount(2, file_load_multiple());
    File::create(['uri' => 'public://example.txt'])->save();
    $this->assertCount(3, file_load_multiple());

    // Test deprecation of file_load().
    $this->assertNull(file_load(300));
    $file_entity = file_load(1);
    $this->assertInstanceOf(FileInterface::class, $file_entity);

    // Test deprecation of file_delete().
    $this->assertNull(file_delete($file_entity->id()));

    // Test deprecation of file_delete_multiple().
    $this->assertNull(file_delete_multiple(array_keys(file_load_multiple())));
    $this->assertFileNotExists('public://example.txt');
  }

}
