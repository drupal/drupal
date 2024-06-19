<?php

declare(strict_types=1);

namespace Drupal\Tests\media_library\FunctionalJavascript;

use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\file\Entity\File;
use Drupal\FunctionalJavascriptTests\WebDriverTestBase;
use Drupal\language\Entity\ConfigurableLanguage;
use Drupal\media\Entity\Media;
use Drupal\Tests\field\Traits\EntityReferenceFieldCreationTrait;
use Drupal\Tests\media\Traits\MediaTypeCreationTrait;
use Drupal\Tests\TestFileCreationTrait;

/**
 * Tests media library for translatable media.
 *
 * @group media_library
 */
class TranslationsTest extends WebDriverTestBase {

  use EntityReferenceFieldCreationTrait;
  use MediaTypeCreationTrait;
  use TestFileCreationTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'content_translation',
    'field',
    'media',
    'media_library',
    'node',
    'views',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Create some languages.
    foreach (['nl', 'es'] as $langcode) {
      ConfigurableLanguage::createFromLangcode($langcode)->save();
    }

    // Create an image media type and article node type.
    $this->createMediaType('image', ['id' => 'image']);
    $this->drupalCreateContentType(['type' => 'article', 'name' => 'Article']);

    // Make the media translatable and ensure the change is picked up.
    \Drupal::service('content_translation.manager')->setEnabled('media', 'image', TRUE);

    // Create a media reference field on articles.
    $this->createEntityReferenceField(
      'node',
      'article',
      'field_media',
      'Media',
      'media',
      'default',
      ['target_bundles' => ['image']],
      FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED
    );
    // Add the media field to the form display.
    \Drupal::service('entity_display.repository')->getFormDisplay('node', 'article')
      ->setComponent('field_media', ['type' => 'media_library_widget'])
      ->save();

    // Create a file to user for our images.
    $image = File::create([
      'uri' => $this->getTestFiles('image')[0]->uri,
    ]);
    $image->setPermanent();
    $image->save();

    // Create a translated and untranslated media item in each language.
    // cSpell:disable
    $media_items = [
      ['nl' => 'Eekhoorn', 'es' => 'Ardilla'],
      ['es' => 'Zorro', 'nl' => 'Vos'],
      ['nl' => 'Hert'],
      ['es' => 'Tejón'],
    ];
    // cSpell:enable
    foreach ($media_items as $translations) {
      $default_langcode = key($translations);
      $default_name = array_shift($translations);

      $media = Media::create([
        'name' => $default_name,
        'bundle' => 'image',
        'field_media_image' => $image,
        'langcode' => $default_langcode,
      ]);
      foreach ($translations as $langcode => $name) {
        $media->addTranslation($langcode, ['name' => $name]);
      }
      $media->save();
    }

    $user = $this->drupalCreateUser([
      'access administration pages',
      'access content',
      'access media overview',
      'edit own article content',
      'create article content',
      'administer media',
    ]);
    $this->drupalLogin($user);
  }

  /**
   * Tests the media library widget shows all media only once.
   */
  public function testMediaLibraryTranslations(): void {
    $assert_session = $this->assertSession();
    $page = $this->getSession()->getPage();

    // All translations should be shown in the administration overview,
    // regardless of the interface language.
    $this->drupalGet('nl/admin/content/media-grid');
    $assert_session->elementsCount('css', '.js-media-library-item', 6);
    $media_items = $page->findAll('css', '.js-media-library-item-preview + div');
    $media_names = [];
    foreach ($media_items as $media_item) {
      $media_names[] = $media_item->getText();
    }
    sort($media_names);
    // cSpell:disable-next-line
    $this->assertSame(['Ardilla', 'Eekhoorn', 'Hert', 'Tejón', 'Vos', 'Zorro'], $media_names);

    $this->drupalGet('es/admin/content/media-grid');
    $assert_session->elementsCount('css', '.js-media-library-item', 6);
    $media_items = $page->findAll('css', '.js-media-library-item-preview + div');
    $media_names = [];
    foreach ($media_items as $media_item) {
      $media_names[] = $media_item->getText();
    }
    sort($media_names);
    // cSpell:disable-next-line
    $this->assertSame(['Ardilla', 'Eekhoorn', 'Hert', 'Tejón', 'Vos', 'Zorro'], $media_names);

    // All media should only be shown once, and should be shown in the interface
    // language.
    $this->drupalGet('nl/node/add/article');
    $assert_session->elementExists('css', '.js-media-library-open-button[name^="field_media"]')->click();
    $assert_session->waitForText('Add or select media');
    $assert_session->elementsCount('css', '.js-media-library-item', 4);
    $media_items = $page->findAll('css', '.js-media-library-item-preview + div');
    $media_names = [];
    foreach ($media_items as $media_item) {
      $media_names[] = $media_item->getText();
    }
    sort($media_names);
    // cSpell:disable-next-line
    $this->assertSame(['Eekhoorn', 'Hert', 'Tejón', 'Vos'], $media_names);

    $this->drupalGet('es/node/add/article');
    $assert_session->elementExists('css', '.js-media-library-open-button[name^="field_media"]')->click();
    $assert_session->waitForText('Add or select media');
    $assert_session->elementsCount('css', '.js-media-library-item', 4);
    $media_items = $page->findAll('css', '.js-media-library-item-preview + div');
    $media_names = [];
    foreach ($media_items as $media_item) {
      $media_names[] = $media_item->getText();
    }
    sort($media_names);
    // cSpell:disable-next-line
    $this->assertSame(['Ardilla', 'Hert', 'Tejón', 'Zorro'], $media_names);
  }

}
