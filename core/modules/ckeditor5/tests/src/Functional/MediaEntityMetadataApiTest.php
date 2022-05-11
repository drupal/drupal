<?php

namespace Drupal\Tests\ckeditor5\Functional;

use Drupal\ckeditor5\Plugin\Editor\CKEditor5;
use Drupal\Core\Entity\Entity\EntityViewMode;
use Drupal\editor\Entity\Editor;
use Drupal\file\Entity\File;
use Drupal\filter\Entity\FilterFormat;
use Drupal\language\Entity\ConfigurableLanguage;
use Drupal\language\Entity\ContentLanguageSettings;
use Drupal\media\Entity\Media;
use Drupal\Tests\BrowserTestBase;
use Drupal\Tests\ckeditor5\Traits\SynchronizeCsrfTokenSeedTrait;
use Drupal\Tests\media\Traits\MediaTypeCreationTrait;
use Drupal\Tests\TestFileCreationTrait;
use Drupal\user\RoleInterface;
use Symfony\Component\Validator\ConstraintViolation;

/**
 * Tests the media entity metadata API.
 *
 * @group ckeditor5
 * @internal
 */
class MediaEntityMetadataApiTest extends BrowserTestBase {

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
   * @var \Drupal\Component\Uuid\UuidInterface
   */
  protected $uuidService;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->uuidService = $this->container->get('uuid');
    EntityViewMode::create([
      'id' => 'media.view_mode_1',
      'targetEntityType' => 'media',
      'status' => TRUE,
      'enabled' => TRUE,
      'label' => 'View Mode 1',
    ])->save();
    EntityViewMode::create([
      'id' => 'media.view_mode_2',
      'targetEntityType' => 'media',
      'status' => TRUE,
      'enabled' => TRUE,
      'label' => 'View Mode 2',
    ])->save();

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
            'allowed_html' => "<p> <br> <drupal-media data-entity-type data-entity-uuid data-view-mode alt>",
            'filter_html_help' => TRUE,
            'filter_html_nofollow' => TRUE,
          ],
        ],
        'media_embed' => [
          'id' => 'media_embed',
          'status' => TRUE,
          'settings' => [
            'default_view_mode' => 'view_mode_1',
            'allowed_view_modes' => [
              'view_mode_1' => 'view_mode_1',
              'view_mode_2' => 'view_mode_2',
            ],
            'allowed_media_types' => [],
          ],
        ],
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
        'plugins' => [
          'media_media' => [
            'allow_view_mode_override' => TRUE,
          ],
        ],
      ],
    ]);
    $this->editor->save();
    $filtered_html_format->setFilterConfig('media_embed', [
      'status' => TRUE,
      'settings' => [
        'default_view_mode' => 'view_mode_1',
        'allowed_media_types' => [],
        'allowed_view_modes' => [
          'view_mode_1' => 'view_mode_1',
          'view_mode_2' => 'view_mode_2',
        ],
      ],
    ])->save();

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
   * Tests the media entity metadata API.
   */
  public function testApi() {
    $path = '/ckeditor5/filtered_html/media-entity-metadata';
    $token = $this->container->get('csrf_token')->get(ltrim($path, '/'));
    $uuid = $this->mediaImage->uuid();

    $this->drupalGet($path, ['query' => ['token' => $token]]);
    $this->assertSession()->statusCodeEquals(400);

    $this->drupalGet($path, ['query' => ['uuid' => $uuid, 'token' => $token]]);
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSame(json_encode(["type" => "image", 'imageSourceMetadata' => ['alt' => 'default alt']]), $this->getSession()->getPage()->getContent());

    $this->mediaImage->set('field_media_image', [
      'target_id' => 1,
      'alt' => '',
      'title' => 'default title',
    ])->save();
    $this->drupalGet($path, ['query' => ['uuid' => $uuid, 'token' => $token]]);
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSame(json_encode(['type' => 'image', 'imageSourceMetadata' => ['alt' => '']]), $this->getSession()->getPage()->getContent());

    $this->drupalGet($path, ['query' => ['uuid' => $this->mediaFile->uuid(), 'token' => $token]]);
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSame(json_encode(['type' => 'file']), $this->getSession()->getPage()->getContent());

    // Ensure that unpublished media returns 403.
    $this->mediaImage->setUnpublished()->save();
    $this->drupalGet($path, ['query' => ['uuid' => $uuid, 'token' => $token]]);
    $this->assertSession()->statusCodeEquals(403);

    // Ensure that valid, but non-existing UUID returns 404.
    $this->drupalGet($path, ['query' => ['uuid' => $this->uuidService->generate(), 'token' => $token]]);
    $this->assertSession()->statusCodeEquals(404);

    // Ensure that invalid UUID returns 400.
    $this->drupalGet($path, ['query' => ['uuid' => '🦙', 'token' => $token]]);
    $this->assertSession()->statusCodeEquals(400);

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

  /**
   * Tests the media entity metadata API with translations.
   */
  public function testApiTranslation(): void {
    $this->container->get('module_installer')->install(['language', 'content_translation']);
    $this->resetAll();
    ConfigurableLanguage::create(['id' => 'fi'])->save();
    $this->container->get('config.factory')->getEditable('language.negotiation')
      ->set('url.source', 'path_prefix')
      ->set('url.prefixes.fi', 'fi')
      ->save();
    $this->rebuildContainer();
    ContentLanguageSettings::loadByEntityTypeBundle('media', 'image')
      ->setDefaultLangcode('en')
      ->setLanguageAlterable(TRUE)
      ->save();
    $media_fi = Media::load($this->mediaImage->id())->addTranslation('fi');
    $media_fi->field_media_image->setValue([
      [
        'target_id' => '1',
        // cSpell:disable-next-line
        'alt' => 'oletus alt-teksti kuvalle',
      ],
    ]);
    $media_fi->save();
    $uuid = $this->mediaImage->uuid();

    $path = '/ckeditor5/filtered_html/media-entity-metadata';
    $token = $this->container->get('csrf_token')->get(ltrim($path, '/'));

    // Ensure that translation is returned when language is specified.
    $this->drupalGet($path, ['query' => ['uuid' => $uuid, 'token' => $token], 'language' => $media_fi->language()]);
    $this->assertSession()->statusCodeEquals(200);
    // cSpell:disable-next-line
    $this->assertSame(json_encode(['type' => 'image', 'imageSourceMetadata' => ['alt' => 'oletus alt-teksti kuvalle']]), $this->getSession()->getPage()->getContent());

    // Ensure that default translation is returned when no language is
    // specified.
    $this->drupalGet($path, ['query' => ['uuid' => $uuid, 'token' => $token]]);
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSame(json_encode(['type' => 'image', 'imageSourceMetadata' => ['alt' => 'default alt']]), $this->getSession()->getPage()->getContent());
  }

}
