<?php

namespace Drupal\Tests\media\Kernel;

use Drupal\file\Entity\File;
use Drupal\KernelTests\KernelTestBase;
use Drupal\media\Entity\Media;
use Drupal\media\MediaTypeInterface;
use Drupal\Tests\media\Traits\MediaTypeCreationTrait;
use Drupal\user\Entity\User;
use org\bovigo\vfs\vfsStream;

/**
 * Base class for Media kernel tests.
 */
abstract class MediaKernelTestBase extends KernelTestBase {

  use MediaTypeCreationTrait;

  /**
   * Modules to install.
   *
   * @var array
   */
  protected static $modules = [
    'media',
    'media_test_source',
    'image',
    'user',
    'field',
    'system',
    'file',
  ];

  /**
   * The test media type.
   *
   * @var \Drupal\media\MediaTypeInterface
   */
  protected $testMediaType;

  /**
   * The test media type with constraints.
   *
   * @var \Drupal\media\MediaTypeInterface
   */
  protected $testConstraintsMediaType;

  /**
   * A user.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $user;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->installEntitySchema('user');
    $this->installEntitySchema('file');
    $this->installSchema('file', 'file_usage');
    $this->installEntitySchema('media');
    $this->installConfig(['field', 'system', 'image', 'file', 'media']);

    // Create a test media type.
    $this->testMediaType = $this->createMediaType('test');
    // Create a test media type with constraints.
    $this->testConstraintsMediaType = $this->createMediaType('test_constraints');

    $this->user = User::create([
      'name' => 'username',
      'status' => 1,
    ]);
    $this->user->save();
    $this->container->get('current_user')->setAccount($this->user);
  }

  /**
   * Helper to generate a media item.
   *
   * @param string $filename
   *   String filename with extension.
   * @param \Drupal\media\MediaTypeInterface $media_type
   *   The media type.
   *
   * @return \Drupal\media\Entity\Media
   *   A media item.
   */
  protected function generateMedia($filename, MediaTypeInterface $media_type) {
    vfsStream::setup('drupal_root');
    vfsStream::create([
      'sites' => [
        'default' => [
          'files' => [
            $filename => str_repeat('a', 3000),
          ],
        ],
      ],
    ]);

    $file = File::create([
      'uri' => 'vfs://drupal_root/sites/default/files/' . $filename,
      'uid' => $this->user->id(),
    ]);
    $file->setPermanent();
    $file->save();

    return Media::create([
      'bundle' => $media_type->id(),
      'name' => 'Mr. Jones',
      'field_media_file' => [
        'target_id' => $file->id(),
      ],
    ]);
  }

}
