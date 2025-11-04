<?php

declare(strict_types=1);

namespace Drupal\KernelTests\Core\Image;

use Drupal\Core\Image\ImageFactory;
use Drupal\system\Plugin\ImageToolkit\GDToolkit;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RequiresPhpExtension;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

// cspell:ignore iccp

/**
 * GD image toolkit image manipulation of PNG images.
 */
#[CoversClass(GDToolkit::class)]
#[Group('Image')]
#[RequiresPhpExtension('gd')]
#[RunTestsInSeparateProcesses]
class GdToolkitPngImageManipulationTest extends GdToolkitImageManipulationTestBase {

  /**
   * {@inheritdoc}
   */
  protected string $sourceTestImage = 'image-test.png';

  /**
   * Tests that GD doesn't trigger warnings for iCCP sRGB profiles.
   *
   * If image is saved with 'sRGB IEC61966-2.1' sRGB profile, GD will trigger
   * a warning about an incorrect sRGB profile'.
   */
  #[DataProvider('pngImageProvider')]
  public function testIncorrectIccpSrgbProfile(string $image_uri): void {
    $warning_detected = FALSE;
    // @see https://github.com/sebastianbergmann/phpunit/issues/5062
    $error_handler = static function () use (&$warning_detected): void {
      $warning_detected = TRUE;
    };
    // $error_level is intentionally set to 0. It's required for PHP '@'
    // suppression not to trigger Drupal error handler. In that case native
    // PHP handler will be called and Drupal's will serve like a notification.
    set_error_handler($error_handler, 0);

    $image_factory = $this->container->get('image.factory');
    \assert($image_factory instanceof ImageFactory);

    $image = $image_factory->get($image_uri, 'gd');
    // We need to do any image manipulation to trigger GD profile loading.
    $image->resize('100', '100');

    self::assertFalse($warning_detected);

    restore_error_handler();
  }

  /**
   * Provides a list of PNG image URIs for testing.
   *
   * @return \Generator
   *   The test data.
   */
  public static function pngImageProvider(): \Generator {
    yield 'valid image 1' => ['core/tests/fixtures/files/image-1.png'];
    yield 'valid image 2' => ['core/tests/fixtures/files/image-test.png'];
    yield 'PNG with iCCP profile' => [
      'core/tests/fixtures/files/image-test-iccp-profile.png',
    ];
  }

}
