<?php

declare(strict_types=1);

namespace Drupal\Tests\image\Functional;

use Drupal\Core\StreamWrapper\PublicStream;
use Drupal\Core\Url;
use Drupal\file\Entity\File;
use Drupal\image\Entity\ImageStyle;
use Drupal\image\ImageStyleInterface;
use Drupal\Tests\BrowserTestBase;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests access control for downloading image styles.
 */
#[Group('image')]
#[RunTestsInSeparateProcesses]
class ImageStyleDownloadAccessControlTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['image', 'image_module_test'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * The file system.
   *
   * @var \Drupal\Core\File\FileSystemInterface
   */
  protected $fileSystem;

  /**
   * The image style.
   *
   * @var \Drupal\image\ImageStyleInterface
   */
  protected $style;

  /**
   * {@inheritdoc}
   */
  protected function prepareEnvironment(): void {
    parent::prepareEnvironment();
    // @see static::testPrivateSchemeWithinPublic()
    $this->privateFilesDirectory = $this->publicFilesDirectory . '/private';
  }

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->fileSystem = $this->container->get('file_system');

    $this->style = ImageStyle::create([
      'name' => 'style_foo',
      'label' => $this->randomString(),
    ]);
    $this->style->save();

    // Access control must be respected even when this setting is TRUE.
    $this->config('image.settings')
      ->set('allow_insecure_derivatives', TRUE)
      ->save();
  }

  /**
   * Ensures that private:// access is forbidden through image.style_public.
   */
  public function testPrivateThroughPublicRoute(): void {
    $this->fileSystem->copy(\Drupal::root() . '/core/tests/fixtures/files/image-1.png', 'private://image.png');

    // Manually create the file record for the private:// file as we want it
    // to be temporary to pass hook_download() acl's.
    $values = [
      'uid' => $this->rootUser->id(),
      'status' => 0,
      'filename' => 'image.png',
      'uri' => 'private://image.png',
      'filesize' => filesize('private://image.png'),
      'filemime' => 'image/png',
    ];
    $private_file = File::create($values);
    $private_file->save();
    $this->assertNotFalse(getimagesize($private_file->getFileUri()));

    $token = $this->style->getPathToken('private://image.png');
    $public_route_private_scheme = Url::fromRoute(
      'image.style_public',
      [
        'image_style' => $this->style->id(),
        'scheme' => 'private',
      ],
    )
      ->setAbsolute(TRUE);

    $generate_url = $public_route_private_scheme->toString() . '/image.png?itok=' . $token;

    $this->drupalLogin($this->rootUser);
    $this->drupalGet($generate_url);

    $this->drupalGet(PublicStream::basePath() . '/styles/' . $this->style->id() . '/private/image.png');
    $this->assertSession()->statusCodeEquals(403);
  }

  /**
   * Tests that hook_file_download runs for non-public scheme derivatives.
   */
  public function testNonPublicSchemeInvokesFileDownloadHook(): void {
    $this->fileSystem->copy(\Drupal::root() . '/core/tests/fixtures/files/image-1.png', 'image-test-dummy://image.png');

    $token = $this->style->getPathToken('image-test-dummy://image.png');
    $url = Url::fromRoute('image_module_test.image_style_protected', [
      'image_style' => $this->style->id(),
      'scheme' => 'image-test-dummy',
    ], [
      'query' => [
        'file' => 'image.png',
        ImageStyleInterface::TOKEN => $token,
      ],
      'absolute' => TRUE,
    ])->toString();

    // The hook denies by default, so the derivative must not be served.
    $this->drupalGet($url);
    $this->assertSession()->statusCodeEquals(403);

    // Set the state variable so that file download hook in image_module_test
    // allows the access of the file.
    \Drupal::state()->set('image_module_test.allow_image_test_dummy', TRUE);
    $this->drupalGet($url);
    $this->assertSession()->statusCodeEquals(200);
  }

  /**
   * Ensures that public:// access is forbidden through image.style.private.
   */
  public function testPublicThroughPrivateRoute(): void {
    $this->fileSystem->copy(\Drupal::root() . '/core/tests/fixtures/files/image-1.png', 'public://image.png');
    $token = $this->style->getPathToken('public://image.png');
    $private_route_public_scheme = Url::fromRoute(
      'image.style_private',
      [
        'image_style' => $this->style->id(),
        'scheme' => 'public',
      ],
    )
      ->setAbsolute(TRUE);

    $generate_url = $private_route_public_scheme->toString() . '/image.png?itok=' . $token;
    $this->drupalGet($generate_url);

    $this->assertSession()->statusCodeEquals(403);
  }

}
