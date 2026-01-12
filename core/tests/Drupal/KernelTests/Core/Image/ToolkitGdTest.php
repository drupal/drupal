<?php

declare(strict_types=1);

namespace Drupal\KernelTests\Core\Image;

use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Image\ImageFactory;
use Drupal\KernelTests\KernelTestBase;
use Drupal\system\Plugin\ImageToolkit\GDToolkit;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RequiresPhpExtension;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

// cspell:ignore imagecreatefrom
/**
 * Tests for the GD image toolkit.
 */
#[CoversClass(GDToolkit::class)]
#[Group('Image')]
#[RequiresPhpExtension('gd')]
#[RunTestsInSeparateProcesses]
class ToolkitGdTest extends KernelTestBase {

  /**
   * The image factory service.
   */
  protected ImageFactory $imageFactory;

  /**
   * A directory where test image files can be saved to.
   */
  protected string $directory;

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['system'];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->installConfig(['system']);

    // Set the image factory service.
    $this->imageFactory = $this->container->get('image.factory');
    $this->assertEquals('gd', $this->imageFactory->getToolkitId(), 'The image factory is set to use the \'gd\' image toolkit.');

    // Prepare a directory for test file results.
    $this->directory = 'public://image_test';
    \Drupal::service('file_system')->prepareDirectory($this->directory, FileSystemInterface::CREATE_DIRECTORY);
  }

  /**
   * Tests supported extensions.
   *
   * @legacy-covers ::getSupportedExtensions
   * @legacy-covers ::extensionToImageType
   */
  public function testSupportedExtensions(): void {
    // Test the list of supported extensions.
    $expected_extensions = ['png', 'gif', 'jpeg', 'jpg', 'jpe', 'webp', 'avif'];
    $this->assertEqualsCanonicalizing($expected_extensions, $this->imageFactory->getSupportedExtensions());

    // Test that the supported extensions map to correct internal GD image
    // types.
    $expected_image_types = [
      'png' => IMAGETYPE_PNG,
      'gif' => IMAGETYPE_GIF,
      'jpeg' => IMAGETYPE_JPEG,
      'jpg' => IMAGETYPE_JPEG,
      'jpe' => IMAGETYPE_JPEG,
      'webp' => IMAGETYPE_WEBP,
      'avif' => IMAGETYPE_AVIF,
    ];
    $image = $this->imageFactory->get();
    foreach ($expected_image_types as $extension => $expected_image_type) {
      $this->assertSame($expected_image_type, $image->getToolkit()->extensionToImageType($extension));
    }
  }

  /**
   * Data provider for ::testCreateImageFromScratch().
   */
  public static function providerSupportedImageTypes(): array {
    return [
      [IMAGETYPE_PNG],
      [IMAGETYPE_GIF],
      [IMAGETYPE_JPEG],
      [IMAGETYPE_WEBP],
      [IMAGETYPE_AVIF],
    ];
  }

  /**
   * Tests that GD functions for the image type are available.
   */
  #[DataProvider('providerSupportedImageTypes')]
  public function testGdFunctionsExist(int $type): void {
    $extension = image_type_to_extension($type, FALSE);
    $this->assertTrue(function_exists("imagecreatefrom$extension"), "imagecreatefrom$extension should exist.");
    $this->assertTrue(function_exists("image$extension"), "image$extension should exist.");
  }

  /**
   * Tests creation of image from scratch, and saving to storage.
   */
  #[DataProvider('providerSupportedImageTypes')]
  public function testCreateImageFromScratch(int $type): void {
    // Build an image from scratch.
    $image = $this->imageFactory->get();
    $image->createNew(50, 20, image_type_to_extension($type, FALSE), '#ffff00');
    $file = 'from_null' . image_type_to_extension($type);
    $file_path = $this->directory . '/' . $file;
    $this->assertSame(50, $image->getWidth());
    $this->assertSame(20, $image->getHeight());
    $this->assertSame(image_type_to_mime_type($type), $image->getMimeType());
    $this->assertTrue($image->save($file_path), "Image '$file' should have been saved successfully, but it has not.");

    // Reload and check saved image.
    $image_reloaded = $this->imageFactory->get($file_path);
    $this->assertTrue($image_reloaded->isValid());
    $this->assertSame(50, $image_reloaded->getWidth());
    $this->assertSame(20, $image_reloaded->getHeight());
    $this->assertSame(image_type_to_mime_type($type), $image_reloaded->getMimeType());
    if ($image_reloaded->getToolkit()->getType() == IMAGETYPE_GIF) {
      $this->assertSame('#ffff00', $image_reloaded->getToolkit()->getTransparentColor(), "Image '$file' after reload should have color channel set to #ffff00, but it has not.");
    }
    else {
      $this->assertNull($image_reloaded->getToolkit()->getTransparentColor(), "Image '$file' after reload should have no color channel set, but it has.");
    }
  }

  /**
   * Tests failures of the 'create_new' operation.
   */
  public function testCreateNewFailures(): void {
    $image = $this->imageFactory->get();
    $image->createNew(-50, 20);
    $this->assertFalse($image->isValid(), 'CreateNew with negative width fails.');
    $image->createNew(50, 20, 'foo');
    $this->assertFalse($image->isValid(), 'CreateNew with invalid extension fails.');
    $image->createNew(50, 20, 'gif', '#foo');
    $this->assertFalse($image->isValid(), 'CreateNew with invalid color hex string fails.');
    $image->createNew(50, 20, 'gif', '#ff0000');
    $this->assertTrue($image->isValid(), 'CreateNew with valid arguments validates the Image.');
  }

  /**
   * Tests calling a missing image operation plugin.
   */
  public function testMissingOperation(): void {
    // Load up a fresh image.
    $image = $this->imageFactory->get('core/tests/fixtures/files/image-test.png');
    $this->assertTrue($image->isValid(), "Image 'image-test.png' after load should be valid, but it is not.");

    // Try perform a missing toolkit operation.
    $this->assertFalse($image->apply('missing_op', []), 'Calling a missing image toolkit operation plugin should fail, but it did not.');
  }

  /**
   * Tests get requirements.
   */
  public function testGetRequirements(): void {
    $this->assertEquals([
      'version' => [
        'title' => 'GD library',
        'value' => gd_info()['GD Version'],
        'description' => sprintf(
          "Supported image file formats: %s.",
          implode(', ', ['GIF', 'JPEG', 'PNG', 'WEBP', 'AVIF']),
        ),
      ],
    ], $this->imageFactory->get()->getToolkit()->getRequirements());
  }

}
