<?php

namespace Drupal\Tests\image\Functional\ImageEffect;

use Drupal\Core\File\FileSystemInterface;
use Drupal\image\Entity\ImageStyle;
use Drupal\Tests\BrowserTestBase;

/**
 * Tests for the Convert image effect.
 *
 * @group image
 */
class ConvertTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['image'];

  /**
   * Tests that files stored in the root folder are converted properly.
   */
  public function testConvertFileInRoot() {
    // Create the test image style with a Convert effect.
    $image_style = ImageStyle::create([
      'name' => 'image_effect_test',
      'label' => 'Image Effect Test',
    ]);
    $this->assertEquals(SAVED_NEW, $image_style->save());
    $image_style->addImageEffect([
      'id' => 'image_convert',
      'data' => [
        'extension' => 'jpeg',
      ],
    ]);
    $this->assertEquals(SAVED_UPDATED, $image_style->save());

    // Create a copy of a test image file in root.
    $test_uri = 'public://image-test-do.png';
    \Drupal::service('file_system')->copy('core/tests/fixtures/files/image-test.png', $test_uri, FileSystemInterface::EXISTS_REPLACE);
    $this->assertFileExists($test_uri);

    // Execute the image style on the test image via a GET request.
    $derivative_uri = 'public://styles/image_effect_test/public/image-test-do.png.jpeg';
    $this->assertFileDoesNotExist($derivative_uri);
    $url = \Drupal::service('file_url_generator')->transformRelative($image_style->buildUrl($test_uri));
    $this->drupalGet($this->getAbsoluteUrl($url));
    $this->assertSession()->statusCodeEquals(200);
    $this->assertFileExists($derivative_uri);
  }

}
