<?php

namespace Drupal\Tests\hal\Kernel;

use Drupal\file\Entity\File;

/**
 * Tests that file entities can be normalized in HAL.
 *
 * @group hal
 */
class FileNormalizeTest extends NormalizerTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = ['file'];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->installEntitySchema('file');
  }

  /**
   * Tests the normalize function.
   */
  public function testNormalize() {
    $file_params = [
      'filename' => 'test_1.txt',
      'uri' => 'public://test_1.txt',
      'filemime' => 'text/plain',
    ];
    // Create a new file entity.
    $file = File::create($file_params);
    $file->setPermanent();
    file_put_contents($file->getFileUri(), 'hello world');
    $file->save();

    $expected_array = [
      'uri' => [
        [
          'value' => $file->getFileUri(),
          'url' => $file->createFileUrl(),
        ],
      ],
    ];

    $normalized = $this->serializer->normalize($file, $this->format);
    $this->assertEquals($expected_array['uri'], $normalized['uri'], 'URI is normalized.');

  }

}
