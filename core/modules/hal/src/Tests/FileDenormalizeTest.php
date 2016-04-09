<?php

namespace Drupal\hal\Tests;

use Drupal\file\Entity\File;
use Drupal\simpletest\WebTestBase;

/**
 * Tests that file entities can be denormalized in HAL.
 *
 * @group hal
 * @see \Drupal\hal\Normalizer\FileEntityNormalizer
 */
class FileDenormalizeTest extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('hal', 'file', 'node');

  /**
   * Tests file entity denormalization.
   */
  public function testFileDenormalize() {
    $file_params = array(
      'filename' => 'test_1.txt',
      'uri' => 'public://test_1.txt',
      'filemime' => 'text/plain',
      'status' => FILE_STATUS_PERMANENT,
    );
    // Create a new file entity.
    $file = File::create($file_params);
    file_put_contents($file->getFileUri(), 'hello world');
    $file->save();

    $serializer = \Drupal::service('serializer');
    $normalized_data = $serializer->normalize($file, 'hal_json');
    $denormalized = $serializer->denormalize($normalized_data, 'Drupal\file\Entity\File', 'hal_json');

    $this->assertTrue($denormalized instanceof File, 'A File instance was created.');

    $this->assertIdentical('temporary://' . $file->getFilename(), $denormalized->getFileUri(), 'The expected file URI was found.');
    $this->assertTrue(file_exists($denormalized->getFileUri()), 'The temporary file was found.');

    $this->assertIdentical($file->uuid(), $denormalized->uuid(), 'The expected UUID was found');
    $this->assertIdentical($file->getMimeType(), $denormalized->getMimeType(), 'The expected MIME type was found.');
    $this->assertIdentical($file->getFilename(), $denormalized->getFilename(), 'The expected filename was found.');
    $this->assertTrue($denormalized->isPermanent(), 'The file has a permanent status.');

    // Try to denormalize with the file uri only.
    $file_name = 'test_2.txt';
    $file_path = 'public://' . $file_name;

    file_put_contents($file_path, 'hello world');
    $file_uri = file_create_url($file_path);

    $data = array(
      'uri' => array(
        array('value' => $file_uri),
      ),
    );

    $denormalized = $serializer->denormalize($data, 'Drupal\file\Entity\File', 'hal_json');

    $this->assertIdentical('temporary://' . $file_name, $denormalized->getFileUri(), 'The expected file URI was found.');
    $this->assertTrue(file_exists($denormalized->getFileUri()), 'The temporary file was found.');

    $this->assertIdentical('text/plain', $denormalized->getMimeType(), 'The expected MIME type was found.');
    $this->assertIdentical($file_name, $denormalized->getFilename(), 'The expected filename was found.');
    $this->assertFalse($denormalized->isPermanent(), 'The file has a permanent status.');
  }

}
