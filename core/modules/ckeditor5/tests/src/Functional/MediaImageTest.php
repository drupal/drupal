<?php

namespace Drupal\Tests\ckeditor5\Functional;

use Drupal\ckeditor5\Plugin\Editor\CKEditor5;
use Drupal\editor\Entity\Editor;
use Drupal\file\Entity\File;
use Drupal\filter\Entity\FilterFormat;
use Drupal\media\Entity\Media;
use Drupal\Tests\BrowserTestBase;
use Drupal\Tests\ckeditor5\Traits\SynchronizeCsrfTokenSeedTrait;
use Drupal\Tests\media\Traits\MediaTypeCreationTrait;
use Drupal\Tests\TestFileCreationTrait;
use Drupal\user\RoleInterface;
use Symfony\Component\Validator\ConstraintViolation;

/**
 * Tests that image media types are determined correctly.
 *
 * @group ckeditor5
 * @internal
 */
class MediaImageTest extends BrowserTestBase {

  use TestFileCreationTrait;
  use MediaTypeCreationTrait;
  use SynchronizeCsrfTokenSeedTrait;

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'system',
    'user',
    'filter',
    'editor',
    'ckeditor5',
    'media',
  ];

  /**
   * The sample image media entity to use for testing.
   *
   * @var \Drupal\media\MediaInterface
   */
  protected $mediaImage;

  /**
   * The sample file media entity to use for testing.
   *
   * @var \Drupal\media\MediaInterface
   */
  protected $mediaFile;

  /**
   * The editor instance to use for testing.
   *
   * @var \Drupal\editor\Entity\Editor
   */
  protected $editor;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $filtered_html_format = FilterFormat::create([
      'format' => 'filtered_html',
      'name' => 'Filtered HTML',
      'weight' => 0,
      'filters' => [
        'filter_html' => [
          'id' => 'filter_html',
          'status' => TRUE,
          'weight' => -10,
          'settings' => [
            'allowed_html' => "<p> <br> <drupal-media data-entity-type data-entity-uuid alt>",
            'filter_html_help' => TRUE,
            'filter_html_nofollow' => TRUE,
          ],
        ],
        'media_embed' => ['status' => TRUE],
      ],
      'roles' => [RoleInterface::AUTHENTICATED_ID],
    ]);
    $filtered_html_format->save();
    $this->editor = Editor::create([
      'format' => 'filtered_html',
      'editor' => 'ckeditor5',
      'settings' => [
        'toolbar' => [
          'items' => [],
        ],
      ],
    ]);
    $this->editor->save();
    $this->assertSame([], array_map(
      function (ConstraintViolation $v) {
        return (string) $v->getMessage();
      },
      iterator_to_array(CKEditor5::validatePair($this->editor, $filtered_html_format))
    ));

    // Create a sample media entity to be embedded.
    $this->createMediaType('image', ['id' => 'image']);
    File::create([
      'uri' => $this->getTestFiles('image')[0]->uri,
    ])->save();
    $this->mediaImage = Media::create([
      'bundle' => 'image',
      'name' => 'Screaming hairy armadillo',
      'field_media_image' => [
        [
          'target_id' => 1,
          'alt' => 'default alt',
          'title' => 'default title',
        ],
      ],
    ]);
    $this->mediaImage->save();

    $this->createMediaType('file', ['id' => 'file']);
    File::create([
      'uri' => $this->getTestFiles('text')[0]->uri,
    ])->save();
    $this->mediaFile = Media::create([
      'bundle' => 'file',
      'name' => 'Information about screaming hairy armadillo',
      'field_media_file' => [
        [
          'target_id' => 2,
        ],
      ],
    ]);
    $this->mediaFile->save();

    $this->adminUser = $this->drupalCreateUser([
      'use text format filtered_html',
    ]);
    $this->drupalLogin($this->adminUser);
  }

  /**
   * Tests that media entity with image field is recognized.
   */
  public function testIsMediaImage() {
    $path = '/ckeditor5/filtered_html/is-media-image';
    $token = $this->container->get('csrf_token')->get(ltrim($path, '/'));
    $uuid = $this->mediaImage->uuid();

    $this->drupalGet($path, ['query' => ['token' => $token]]);
    $this->assertSession()->statusCodeEquals(400);

    $this->drupalGet($path, ['query' => ['uuid' => $uuid, 'token' => $token]]);
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSame(json_encode(TRUE), $this->getSession()->getPage()->getContent());

    $this->drupalGet($path, ['query' => ['uuid' => $this->mediaFile->uuid(), 'token' => $token]]);
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSame(json_encode(FALSE), $this->getSession()->getPage()->getContent());

    // Ensure that unpublished media returns 403.
    $this->mediaImage->setUnpublished()->save();
    $this->drupalGet($path, ['query' => ['uuid' => $uuid, 'token' => $token]]);
    $this->assertSession()->statusCodeEquals(403);

    // Ensure that users that don't have access to the filter format receive
    // either 404 or 403.
    $this->drupalLogout();
    $token = $this->container->get('csrf_token')->get(ltrim($path, '/'));
    $this->drupalGet($path, ['token' => $token]);
    $this->assertSession()->statusCodeEquals(400);

    $this->drupalGet($path, ['query' => ['uuid' => $uuid, 'token' => $token]]);
    $this->assertSession()->statusCodeEquals(403);

    $this->mediaImage->setPublished()->save();
    $this->drupalGet($path, ['query' => ['uuid' => $uuid, 'token' => $token]]);
    $this->assertSession()->statusCodeEquals(403);
  }

}
