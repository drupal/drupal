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
  public static $modules = ['file'];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
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
      'status' => FILE_STATUS_PERMANENT,
    ];
    // Create a new file entity.
    $file = File::create($file_params);
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
    $this->assertEqual($normalized['uri'], $expected_array['uri'], 'URI is normalized.');

  }

}
