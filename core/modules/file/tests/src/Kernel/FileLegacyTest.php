<?php

namespace Drupal\Tests\file\Kernel;

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

}
