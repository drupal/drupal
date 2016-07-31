<?php

namespace Drupal\Tests\hal\Kernel;

use Drupal\Core\Cache\MemoryBackend;
use Drupal\file\Entity\File;
use Drupal\hal\Encoder\JsonEncoder;
use Drupal\hal\Normalizer\FieldItemNormalizer;
use Drupal\hal\Normalizer\FileEntityNormalizer;
use Drupal\rest\LinkManager\LinkManager;
use Drupal\rest\LinkManager\RelationLinkManager;
use Drupal\rest\LinkManager\TypeLinkManager;
use Symfony\Component\Serializer\Serializer;


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
  public static $modules = array('file');

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->installEntitySchema('file');

    $entity_manager = \Drupal::entityManager();
    $link_manager = new LinkManager(new TypeLinkManager(new MemoryBackend('default'), \Drupal::moduleHandler(), \Drupal::service('config.factory'), \Drupal::service('request_stack'), \Drupal::service('entity_type.bundle.info')), new RelationLinkManager(new MemoryBackend('default'), $entity_manager, \Drupal::moduleHandler(), \Drupal::service('config.factory'), \Drupal::service('request_stack')));

    // Set up the mock serializer.
    $normalizers = array(
      new FieldItemNormalizer(),
      new FileEntityNormalizer($entity_manager, \Drupal::httpClient(), $link_manager, \Drupal::moduleHandler()),
    );

    $encoders = array(
      new JsonEncoder(),
    );
    $this->serializer = new Serializer($normalizers, $encoders);
  }


  /**
   * Tests the normalize function.
   */
  public function testNormalize() {
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

    $expected_array = array(
      'uri' => array(
        array(
          'value' => file_create_url($file->getFileUri())),
      ),
    );

    $normalized = $this->serializer->normalize($file, $this->format);
    $this->assertEqual($normalized['uri'], $expected_array['uri'], 'URI is normalized.');

  }

}
