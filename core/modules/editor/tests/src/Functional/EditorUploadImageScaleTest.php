<?php

namespace Drupal\Tests\editor\Functional;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\editor\Entity\Editor;
use Drupal\filter\Entity\FilterFormat;
use Drupal\Tests\BrowserTestBase;
use Drupal\Tests\TestFileCreationTrait;

/**
 * Tests scaling of inline images.
 *
 * @group editor
 */
class EditorUploadImageScaleTest extends BrowserTestBase {

  use TestFileCreationTrait;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['editor', 'editor_test'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * A user with permission as administer for testing.
   *
   * @var \Drupal\user\Entity\User
   */
  protected $adminUser;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    // Add text format.
    FilterFormat::create([
      'format' => 'basic_html',
      'name' => 'Basic HTML',
      'weight' => 0,
    ])->save();

    // Set up text editor.
    Editor::create([
      'format' => 'basic_html',
      'editor' => 'unicorn',
      'image_upload' => [
        'status' => TRUE,
        'scheme' => 'public',
        'directory' => 'inline-images',
        'max_size' => '',
        'max_dimensions' => [
          'width' => NULL,
          'height' => NULL,
        ],
      ],
    ])->save();

    // Create admin user.
    $this->adminUser = $this->drupalCreateUser(['administer filters', 'use text format basic_html']);
    $this->drupalLogin($this->adminUser);
  }

  /**
   * Tests scaling of inline images.
   */
  public function testEditorUploadImageScale() {
    // Generate testing images.
    $testing_image_list = $this->getTestFiles('image');

    // Case 1: no max dimensions set: uploaded image not scaled.
    $test_image = $testing_image_list[0];
    list($image_file_width, $image_file_height) = $this->getTestImageInfo($test_image->uri);
    $max_width = NULL;
    $max_height = NULL;
    $this->setMaxDimensions($max_width, $max_height);
    $this->assertSavedMaxDimensions($max_width, $max_height);
    list($uploaded_image_file_width, $uploaded_image_file_height) = $this->uploadImage($test_image->uri);
    $this->assertEqual($uploaded_image_file_width, $image_file_width);
    $this->assertEqual($uploaded_image_file_height, $image_file_height);
    $this->assertNoRaw((string) new FormattableMarkup('The image was resized to fit within the maximum allowed dimensions of %dimensions pixels.', ['%dimensions' => $max_width . 'x' . $max_height]));

    // Case 2: max width smaller than uploaded image: image scaled down.
    $test_image = $testing_image_list[1];
    list($image_file_width, $image_file_height) = $this->getTestImageInfo($test_image->uri);
    $max_width = $image_file_width - 5;
    $max_height = $image_file_height;
    $this->setMaxDimensions($max_width, $max_height);
    $this->assertSavedMaxDimensions($max_width, $max_height);
    list($uploaded_image_file_width, $uploaded_image_file_height) = $this->uploadImage($test_image->uri);
    $this->assertEqual($uploaded_image_file_width, $max_width);
    $this->assertEqual($uploaded_image_file_height, $uploaded_image_file_height * ($uploaded_image_file_width / $max_width));
    $this->assertRaw((string) new FormattableMarkup('The image was resized to fit within the maximum allowed dimensions of %dimensions pixels.', ['%dimensions' => $max_width . 'x' . $max_height]));

    // Case 3: max height smaller than uploaded image: image scaled down.
    $test_image = $testing_image_list[2];
    list($image_file_width, $image_file_height) = $this->getTestImageInfo($test_image->uri);
    $max_width = $image_file_width;
    $max_height = $image_file_height - 5;
    $this->setMaxDimensions($max_width, $max_height);
    $this->assertSavedMaxDimensions($max_width, $max_height);
    list($uploaded_image_file_width, $uploaded_image_file_height) = $this->uploadImage($test_image->uri);
    $this->assertEqual($uploaded_image_file_width, $uploaded_image_file_width * ($uploaded_image_file_height / $max_height));
    $this->assertEqual($uploaded_image_file_height, $max_height);
    $this->assertRaw((string) new FormattableMarkup('The image was resized to fit within the maximum allowed dimensions of %dimensions pixels.', ['%dimensions' => $max_width . 'x' . $max_height]));

    // Case 4: max dimensions greater than uploaded image: image not scaled.
    $test_image = $testing_image_list[3];
    list($image_file_width, $image_file_height) = $this->getTestImageInfo($test_image->uri);
    $max_width = $image_file_width + 5;
    $max_height = $image_file_height + 5;
    $this->setMaxDimensions($max_width, $max_height);
    $this->assertSavedMaxDimensions($max_width, $max_height);
    list($uploaded_image_file_width, $uploaded_image_file_height) = $this->uploadImage($test_image->uri);
    $this->assertEqual($uploaded_image_file_width, $image_file_width);
    $this->assertEqual($uploaded_image_file_height, $image_file_height);
    $this->assertNoRaw((string) new FormattableMarkup('The image was resized to fit within the maximum allowed dimensions of %dimensions pixels.', ['%dimensions' => $max_width . 'x' . $max_height]));

    // Case 5: only max width dimension was provided and it was smaller than
    // uploaded image: image scaled down.
    $test_image = $testing_image_list[4];
    list($image_file_width, $image_file_height) = $this->getTestImageInfo($test_image->uri);
    $max_width = $image_file_width - 5;
    $max_height = NULL;
    $this->setMaxDimensions($max_width, $max_height);
    $this->assertSavedMaxDimensions($max_width, $max_height);
    list($uploaded_image_file_width, $uploaded_image_file_height) = $this->uploadImage($test_image->uri);
    $this->assertEqual($uploaded_image_file_width, $max_width);
    $this->assertEqual($uploaded_image_file_height, $uploaded_image_file_height * ($uploaded_image_file_width / $max_width));
    $this->assertRaw((string) new FormattableMarkup('The image was resized to fit within the maximum allowed width of %width pixels.', ['%width' => $max_width]));

    // Case 6: only max height dimension was provided and it was smaller than
    // uploaded image: image scaled down.
    $test_image = $testing_image_list[5];
    list($image_file_width, $image_file_height) = $this->getTestImageInfo($test_image->uri);
    $max_width = NULL;
    $max_height = $image_file_height - 5;
    $this->setMaxDimensions($max_width, $max_height);
    $this->assertSavedMaxDimensions($max_width, $max_height);
    list($uploaded_image_file_width, $uploaded_image_file_height) = $this->uploadImage($test_image->uri);
    $this->assertEqual($uploaded_image_file_width, $uploaded_image_file_width * ($uploaded_image_file_height / $max_height));
    $this->assertEqual($uploaded_image_file_height, $max_height);
    $this->assertRaw((string) new FormattableMarkup('The image was resized to fit within the maximum allowed height of %height pixels.', ['%height' => $max_height]));
  }

  /**
   * Gets the dimensions of an uploaded image.
   *
   * @param string $uri
   *   The URI of the image.
   *
   * @return array
   *   An array containing the uploaded image's width and height.
   */
  protected function getTestImageInfo($uri) {
    $image_file = $this->container->get('image.factory')->get($uri);
    return [
      (int) $image_file->getWidth(),
      (int) $image_file->getHeight(),
    ];
  }

  /**
   * Sets the maximum dimensions and saves the configuration.
   *
   * @param string|int $width
   *   The width of the image.
   * @param string|int $height
   *   The height of the image.
   */
  protected function setMaxDimensions($width, $height) {
    $editor = Editor::load('basic_html');
    $image_upload_settings = $editor->getImageUploadSettings();
    $image_upload_settings['max_dimensions']['width'] = $width;
    $image_upload_settings['max_dimensions']['height'] = $height;
    $editor->setImageUploadSettings($image_upload_settings);
    $editor->save();
  }

  /**
   * Uploads an image via the editor dialog.
   *
   * @param string $uri
   *   The URI of the image.
   *
   * @return array
   *   An array containing the uploaded image's width and height.
   */
  protected function uploadImage($uri) {
    $edit = [
      'files[fid]' => \Drupal::service('file_system')->realpath($uri),
    ];
    $this->drupalGet('editor/dialog/image/basic_html');
    $this->drupalPostForm('editor/dialog/image/basic_html', $edit, t('Upload'));
    $uploaded_image_file = $this->container->get('image.factory')->get('public://inline-images/' . basename($uri));
    return [
      (int) $uploaded_image_file->getWidth(),
      (int) $uploaded_image_file->getHeight(),
    ];
  }

  /**
   * Asserts whether the saved maximum dimensions equal the ones provided.
   *
   * @param string $width
   *   The expected width of the uploaded image.
   * @param string $height
   *   The expected height of the uploaded image.
   *
   * @return bool
   */
  protected function assertSavedMaxDimensions($width, $height) {
    $image_upload_settings = Editor::load('basic_html')->getImageUploadSettings();
    $expected = [
      'width' => $image_upload_settings['max_dimensions']['width'],
      'height' => $image_upload_settings['max_dimensions']['height'],
    ];
    $same_width = $this->assertEqual($width, $expected['width'], 'Actual width of "' . $width . '" equals the expected width of "' . $expected['width'] . '"');
    $same_height = $this->assertEqual($height, $expected['height'], 'Actual height of "' . $height . '" equals the expected width of "' . $expected['height'] . '"');
    return $same_width && $same_height;
  }

}
