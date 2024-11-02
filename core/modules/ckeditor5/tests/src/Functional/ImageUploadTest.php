<?php

declare(strict_types=1);

namespace Drupal\Tests\ckeditor5\Functional;

use Drupal\Core\Url;
use Drupal\editor\Entity\Editor;
use Drupal\filter\Entity\FilterFormat;
use Drupal\Tests\BrowserTestBase;
use Drupal\Tests\ckeditor5\Traits\SynchronizeCsrfTokenSeedTrait;
use Drupal\Tests\jsonapi\Functional\JsonApiRequestTestTrait;
use Drupal\Tests\TestFileCreationTrait;
use Drupal\user\RoleInterface;
use GuzzleHttp\RequestOptions;
use Psr\Http\Message\ResponseInterface;

/**
 * Test image upload.
 *
 * @group ckeditor5
 * @internal
 */
class ImageUploadTest extends BrowserTestBase {

  use JsonApiRequestTestTrait;
  use TestFileCreationTrait;
  use SynchronizeCsrfTokenSeedTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'editor',
    'filter',
    'ckeditor5',
  ];

  /**
   * A user without any particular permissions to be used in testing.
   *
   * @var \Drupal\user\Entity\User
   */
  protected $user;

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->user = $this->drupalCreateUser();
    $this->drupalLogin($this->user);
  }

  /**
   * Tests using the file upload route with a disallowed extension.
   */
  public function testUploadFileExtension(): void {
    $this->createBasicFormat();
    $this->createEditorWithUpload([
      'status' => TRUE,
      'scheme' => 'public',
      'directory' => 'inline-images',
      'max_size' => NULL,
      'max_dimensions' => [
        'width' => NULL,
        'height' => NULL,
      ],
    ]);

    $url = $this->getUploadUrl();
    $image_file = file_get_contents(current($this->getTestFiles('image'))->uri);
    $non_image_file = file_get_contents(current($this->getTestFiles('php'))->uri);
    $response = $this->uploadRequest($url, $non_image_file, 'test.php');
    $this->assertSame(422, $response->getStatusCode());

    $response = $this->uploadRequest($url, $image_file, 'test.jpg');
    $this->assertSame(201, $response->getStatusCode());
  }

  /**
   * Tests using the file upload route with a file size larger than allowed.
   */
  public function testFileUploadLargerFileSize(): void {
    $this->createBasicFormat();
    $this->createEditorWithUpload([
      'status' => TRUE,
      'scheme' => 'public',
      'directory' => 'inline-images',
      'max_size' => 30000,
      'max_dimensions' => [
        'width' => NULL,
        'height' => NULL,
      ],
    ]);

    $url = $this->getUploadUrl();
    $images = $this->getTestFiles('image');
    $large_image = $this->getTestImageByStat($images, 'size', function ($size) {
      return $size > 30000;
    });
    $small_image = $this->getTestImageByStat($images, 'size', function ($size) {
      return $size < 30000;
    });

    $response = $this->uploadRequest($url, file_get_contents($large_image->uri), 'large.jpg');
    $this->assertSame(422, $response->getStatusCode());

    $response = $this->uploadRequest($url, file_get_contents($small_image->uri), 'small.jpg');
    $this->assertSame(201, $response->getStatusCode());
  }

  /**
   * Test that lock is removed after a failed validation.
   *
   * @see https://www.drupal.org/project/drupal/issues/3184974
   */
  public function testLockAfterFailedValidation(): void {
    $this->createBasicFormat();
    $this->createEditorWithUpload([
      'status' => TRUE,
      'scheme' => 'public',
      'directory' => 'inline-images',
      'max_size' => 30000,
      'max_dimensions' => [
        'width' => NULL,
        'height' => NULL,
      ],
    ]);

    $url = $this->getUploadUrl();
    $images = $this->getTestFiles('image');
    $large_image = $this->getTestImageByStat($images, 'size', function ($size) {
      return $size > 30000;
    });
    $small_image = $this->getTestImageByStat($images, 'size', function ($size) {
      return $size < 30000;
    });
    $response = $this->uploadRequest($url, file_get_contents($large_image->uri), 'same.jpg');
    $this->assertSame(422, $response->getStatusCode());

    $response = $this->uploadRequest($url, file_get_contents($small_image->uri), 'same.jpg');
    $this->assertSame(201, $response->getStatusCode());
  }

  /**
   * Make upload request to a controller.
   *
   * @param \Drupal\Core\Url $url
   *   The URL for the request.
   * @param string $file_contents
   *   File contents.
   * @param string $file_name
   *   Name of the file.
   *
   * @return \Psr\Http\Message\ResponseInterface
   *   The response.
   */
  protected function uploadRequest(Url $url, string $file_contents, string $file_name): ResponseInterface {
    $request_options[RequestOptions::HEADERS] = [
      'Accept' => 'application/json',
    ];
    $request_options[RequestOptions::MULTIPART] = [
      [
        'name' => 'upload',
        'filename' => $file_name,
        'contents' => $file_contents,
      ],
    ];

    return $this->request('POST', $url, $request_options);
  }

  /**
   * Provides the image upload URL.
   *
   * @return \Drupal\Core\Url
   *   The upload image URL for the basic_html format.
   */
  protected function getUploadUrl() {
    $token = $this->container->get('csrf_token')->get('ckeditor5/upload-image/basic_html');
    return Url::fromRoute('ckeditor5.upload_image', ['editor' => 'basic_html'], ['query' => ['token' => $token]]);
  }

  /**
   * Create a basic_html text format for the editor to reference.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  protected function createBasicFormat(): void {
    $basic_html_format = FilterFormat::create([
      'format' => 'basic_html',
      'name' => 'Basic HTML',
      'weight' => 1,
      'filters' => [
        'filter_html_escape' => ['status' => 1],
      ],
      'roles' => [RoleInterface::AUTHENTICATED_ID],
    ]);
    $basic_html_format->save();
  }

  /**
   * Create an editor entity with image_upload config.
   *
   * @param array $upload_config
   *   The editor image_upload config.
   *
   * @return \Drupal\Core\Entity\EntityBase|\Drupal\Core\Entity\EntityInterface
   *   The text editor entity.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  protected function createEditorWithUpload(array $upload_config) {
    $editor = Editor::create([
      'editor' => 'ckeditor5',
      'format' => 'basic_html',
      'settings' => [
        'toolbar' => [
          'items' => [
            'drupalInsertImage',
          ],
        ],
        'plugins' => [
          'ckeditor5_imageResize' => [
            'allow_resize' => FALSE,
          ],
        ],
      ],
      'image_upload' => $upload_config,
    ]);
    $editor->save();

    return $editor;
  }

  /**
   * Return the first image matching $condition.
   *
   * @param array $images
   *   Images created with getTestFiles().
   * @param string $stat
   *   A key in the array returned from stat().
   * @param callable $condition
   *   A function to compare a value of the image file.
   *
   * @return object|bool
   *   Objects with 'uri', 'filename', and 'name' properties.
   */
  protected function getTestImageByStat(array $images, string $stat, callable $condition) {
    return current(array_filter($images, function ($image) use ($condition, $stat) {
      $stats = stat($image->uri);
      return $condition($stats[$stat]);
    }));
  }

}
