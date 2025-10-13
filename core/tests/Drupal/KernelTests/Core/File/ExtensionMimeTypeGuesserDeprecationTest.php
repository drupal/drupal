<?php

declare(strict_types=1);

namespace Drupal\KernelTests\Core\File;

use Drupal\Core\File\MimeType\ExtensionMimeTypeGuesser;
use Drupal\KernelTests\KernelTestBase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\IgnoreDeprecations;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests that deprecation messages are raised for deprecations.
 *
 * @todo Remove this class once deprecations are removed.
 */
#[Group('file')]
#[IgnoreDeprecations]
#[CoversClass(ExtensionMimeTypeGuesser::class)]
#[RunTestsInSeparateProcesses]
class ExtensionMimeTypeGuesserDeprecationTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['system', 'file_deprecated_test'];

  /**
   * Tests that deprecations are raised for missing constructor arguments.
   *
   * @legacy-covers \Drupal\Core\File\MimeType\ExtensionMimeTypeGuesser::__construct
   */
  #[IgnoreDeprecations]
  public function testConstructorDeprecation(): void {
    $this->expectDeprecation(
      'Calling Drupal\Core\File\MimeType\ExtensionMimeTypeGuesser::__construct() with the $map argument as an instance of \Drupal\Core\Extension\ModuleHandlerInterface is deprecated in drupal:11.2.0 and an instance of \Drupal\Core\File\MimeType\MimeTypeMapInterface is required in drupal:12.0.0. See https://www.drupal.org/node/3494040'
    );

    new ExtensionMimeTypeGuesser(
      \Drupal::service('module_handler'),
    );
  }

}
