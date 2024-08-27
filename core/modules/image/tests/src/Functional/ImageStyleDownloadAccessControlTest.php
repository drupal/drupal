<?php

declare(strict_types=1);

namespace Drupal\Tests\image\Functional;

use Drupal\Core\StreamWrapper\PublicStream;
use Drupal\Core\Url;
use Drupal\file\Entity\File;
use Drupal\image\Entity\ImageStyle;
use Drupal\Tests\BrowserTestBase;

/**
 * Tests access control for downloading image styles.
 *
 * @group image
 */
class ImageStyleDownloadAccessControlTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['image'];

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
