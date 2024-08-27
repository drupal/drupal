<?php

declare(strict_types=1);

namespace Drupal\Tests\filter\Functional;

use Drupal\comment\Tests\CommentTestTrait;
use Drupal\Core\StreamWrapper\PublicStream;
use Drupal\filter\Entity\FilterFormat;
use Drupal\Tests\BrowserTestBase;
use Drupal\Tests\TestFileCreationTrait;

/**
 * Tests restriction of IMG tags in HTML input.
 *
 * @group filter
 */
class FilterHtmlImageSecureTest extends BrowserTestBase {

  use CommentTestTrait;
  use TestFileCreationTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['filter', 'node', 'comment'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Setup Filtered HTML text format.
    $filtered_html_format = FilterFormat::create([
      'format' => 'filtered_html',
      'name' => 'Filtered HTML',
      'filters' => [
        'filter_html' => [
          'status' => 1,
          'settings' => [
            'allowed_html' => '<img src test-attribute> <a>',
          ],
        ],
        'filter_autop' => [
          'status' => 1,
        ],
        'filter_html_image_secure' => [
          'status' => 1,
        ],
      ],
    ]);
    $filtered_html_format->save();

    // Setup users.
    $webUser = $this->drupalCreateUser([
      'access content',
      'access comments',
      'post comments',
      'skip comment approval',
      $filtered_html_format->getPermissionName(),
    ]);
    $this->drupalLogin($webUser);

    // Setup a node to comment and test on.
    $this->drupalCreateContentType(['type' => 'page', 'name' => 'Basic page']);
    // Add a comment field.
    $this->addDefaultCommentField('node', 'page');
  }

  /**
   * Tests removal of images having a non-local source.
   */
  public function testImageSource(): void {
    global $base_url;

    $node = $this->drupalCreateNode();

    $public_files_path = PublicStream::basePath();

    $http_base_url = preg_replace('/^https?/', 'http', $base_url);
    $https_base_url = preg_replace('/^https?/', 'https', $base_url);
    $files_path = base_path() . $public_files_path;
    $csrf_path = $public_files_path . '/' . implode('/', array_fill(0, substr_count($public_files_path, '/') + 1, '..'));

    $druplicon = 'core/misc/druplicon.png';
    $red_x_image = base_path() . 'core/misc/icons/e32700/error.svg';

    // Put a test image in the files directory.
    $test_images = $this->getTestFiles('image');
    $test_image = $test_images[0]->filename;

    // Put a test image in the files directory with special filename.
    // cspell:ignore tést fïle nàme
    $special_filename = 'tést fïle nàme.png';
    $special_image = rawurlencode($special_filename);
    $special_uri = str_replace($test_images[0]->filename, $special_filename, $test_images[0]->uri);
    \Drupal::service('file_system')->copy($test_images[0]->uri, $special_uri);

    // Create a list of test image sources.
    // The keys become the value of the IMG 'src' attribute, the values are the
    // expected filter conversions.
    $host = \Drupal::request()->getHost();
    $host_pattern = '|^http\://' . $host . '(\:[0-9]{0,5})|';
    $images = [
      $http_base_url . '/' . $druplicon => base_path() . $druplicon,
      $https_base_url . '/' . $druplicon => base_path() . $druplicon,
      // Test a URL that includes a port.
      preg_replace($host_pattern, 'http://' . $host . ':', $http_base_url . '/' . $druplicon) => base_path() . $druplicon,
      preg_replace($host_pattern, 'http://' . $host . ':80', $http_base_url . '/' . $druplicon) => base_path() . $druplicon,
      preg_replace($host_pattern, 'http://' . $host . ':443', $http_base_url . '/' . $druplicon) => base_path() . $druplicon,
      preg_replace($host_pattern, 'http://' . $host . ':8080', $http_base_url . '/' . $druplicon) => base_path() . $druplicon,
      base_path() . $druplicon => base_path() . $druplicon,
      $files_path . '/' . $test_image => $files_path . '/' . $test_image,
      $http_base_url . '/' . $public_files_path . '/' . $test_image => $files_path . '/' . $test_image,
      $https_base_url . '/' . $public_files_path . '/' . $test_image => $files_path . '/' . $test_image,
      $http_base_url . '/' . $public_files_path . '/' . $special_image => $files_path . '/' . $special_image,
      $https_base_url . '/' . $public_files_path . '/' . $special_image => $files_path . '/' . $special_image,
      $files_path . '/example.png' => $red_x_image,
      'http://example.com/' . $druplicon => $red_x_image,
      'https://example.com/' . $druplicon => $red_x_image,
      'javascript:druplicon.png' => $red_x_image,
      $csrf_path . '/logout' => $red_x_image,
    ];
    $comment = [];
    foreach ($images as $image => $converted) {
      // Output the image source as plain text for debugging.
      $comment[] = $image . ':';
      // Hash the image source in a custom test attribute, because it might
      // contain characters that confuse XPath.
      $comment[] = '<img src="' . $image . '" test-attribute="' . hash('sha256', $image) . '" />';
    }
    $edit = [
      'comment_body[0][value]' => implode("\n", $comment),
    ];
    $this->drupalGet('node/' . $node->id());
    $this->submitForm($edit, 'Save');
    foreach ($images as $image => $converted) {
      $found = FALSE;
      foreach ($this->xpath('//img[@test-attribute="' . hash('sha256', $image) . '"]') as $element) {
        $found = TRUE;
        if ($converted == $red_x_image) {
          $this->assertEquals($red_x_image, $element->getAttribute('src'));
          $this->assertEquals('Image removed.', $element->getAttribute('alt'));
          $this->assertEquals('This image has been removed. For security reasons, only images from the local domain are allowed.', $element->getAttribute('title'));
          $this->assertEquals('16', $element->getAttribute('height'));
          $this->assertEquals('16', $element->getAttribute('width'));
        }
        else {
          $this->assertEquals($converted, $element->getAttribute('src'));
        }
      }
      $this->assertTrue($found, "$image was found.");
    }
  }

}
