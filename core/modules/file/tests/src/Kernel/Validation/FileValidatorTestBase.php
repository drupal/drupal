<?php

declare(strict_types=1);

namespace Drupal\Tests\file\Kernel\Validation;

use Drupal\file\Entity\File;
use Drupal\file\FileInterface;
use Drupal\file\Validation\FileValidatorInterface;
use Drupal\KernelTests\KernelTestBase;

/**
 * Provides a base file constraint validator test.
 */
abstract class FileValidatorTestBase extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['file', 'user', 'system'];

  /**
   * The file.
   *
   * @var \Drupal\file\FileInterface
   */
  protected FileInterface $file;

  /**
   * The file validator.
   *
   * @var \Drupal\file\Validation\FileValidatorInterface
   */
  protected FileValidatorInterface $validator;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->installConfig(['system']);
    $this->installEntitySchema('file');
    $this->installEntitySchema('user');
    $this->installSchema('file', ['file_usage']);

    $uri = 'public://druplicon.txt';
    $this->file = File::create([
      'uid' => 1,
      'uri' => $uri,
      'filename' => 'druplicon.txt',
      'filemime' => 'text/plain',
      'filesize' => 1000,
    ]);
    $this->file->setPermanent();
    $this->validator = $this->container->get('file.validator');

  }

}
