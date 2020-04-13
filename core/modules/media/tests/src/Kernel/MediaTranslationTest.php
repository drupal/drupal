<?php

namespace Drupal\Tests\media\Kernel;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\language\Entity\ConfigurableLanguage;
use Drupal\content_translation\ContentTranslationHandler;

/**
 * Tests multilanguage fields logic.
 *
 * @group media
 */
class MediaTranslationTest extends MediaKernelTestBase {

  /**
   * Modules to install.
   *
   * @var array
   */
  protected static $modules = ['language' , 'content_translation'];

  /**
   * The test media translation type.
   *
   * @var \Drupal\media\MediaTypeInterface
   */
  protected $testTranslationMediaType;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->installConfig(['language']);

    // Create a test media type for translations.
    $this->testTranslationMediaType = $this->createMediaType('test_translation');

    for ($i = 0; $i < 3; ++$i) {
      $language_id = 'l' . $i;
      ConfigurableLanguage::create([
        'id' => $language_id,
        'label' => $this->randomString(),
      ])->save();
      file_put_contents('public://' . $language_id . '.png', '');
    }
  }

  /**
   * Test translatable fields storage/retrieval.
   */
  public function testTranslatableFieldSaveLoad() {
    /** @var \Drupal\Core\Entity\EntityTypeInterface $entity_type */
    $entity_type = $this->container->get('entity_type.manager')->getDefinition('media');
    $this->assertTrue($entity_type->isTranslatable(), 'Media is translatable.');

    // Check if the translation handler uses the content_translation handler.
    $translation_handler_class = $entity_type->getHandlerClass('translation');
    $this->assertEquals(ContentTranslationHandler::class, $translation_handler_class, 'Translation handler is set to use the content_translation handler.');

    // Prepare the field translations.
    $source_field_definition = $this->testTranslationMediaType->getSource()->getSourceFieldDefinition($this->testTranslationMediaType);
    $source_field_storage = $source_field_definition->getFieldStorageDefinition();
    /** @var \Drupal\Core\Entity\Sql\SqlContentEntityStorage $media_storage */
    $media_storage = $this->container->get('entity_type.manager')->getStorage('media');
    /** @var \Drupal\media\Entity\Media $media */
    $media = $media_storage->create([
      'bundle' => $this->testTranslationMediaType->id(),
      'name' => 'Unnamed',
    ]);

    $field_translations = [];
    $available_langcodes = array_keys($this->container->get('language_manager')->getLanguages());
    $media->set('langcode', reset($available_langcodes));
    foreach ($available_langcodes as $langcode) {
      $values = [];
      for ($i = 0; $i < $source_field_storage->getCardinality(); $i++) {
        $values[$i]['value'] = $this->randomString();
      }
      $field_translations[$langcode] = $values;
      $translation = $media->hasTranslation($langcode) ? $media->getTranslation($langcode) : $media->addTranslation($langcode);
      $translation->{$source_field_definition->getName()}->setValue($field_translations[$langcode]);
    }

    // Save and reload the field translations.
    $media->save();
    $media_storage->resetCache();
    $media = $media_storage->load($media->id());

    // Check if the correct source field values were saved/loaded.
    foreach ($field_translations as $langcode => $items) {
      /** @var \Drupal\media\MediaInterface $media_translation */
      $media_translation = $media->getTranslation($langcode);
      $result = TRUE;
      foreach ($items as $delta => $item) {
        $result = $result && $item['value'] == $media_translation->{$source_field_definition->getName()}[$delta]->value;
      }
      $this->assertTrue($result, new FormattableMarkup('%language translation field value not correct.', ['%language' => $langcode]));
      $this->assertSame('public://' . $langcode . '.png', $media_translation->getSource()->getMetadata($media_translation, 'thumbnail_uri'), new FormattableMarkup('%language translation thumbnail metadata attribute is not correct.', ['%language' => $langcode]));
      $this->assertSame('public://' . $langcode . '.png', $media_translation->get('thumbnail')->entity->getFileUri(), new FormattableMarkup('%language translation thumbnail value is not correct.', ['%language' => $langcode]));
      $this->assertEquals('Test Thumbnail ' . $langcode, $media_translation->getSource()->getMetadata($media_translation, 'test_thumbnail_alt'), new FormattableMarkup('%language translation thumbnail alt metadata attribute is not correct.', ['%language' => $langcode]));
      $this->assertSame('Test Thumbnail ' . $langcode, $media_translation->get('thumbnail')->alt, new FormattableMarkup('%language translation thumbnail alt value is not correct.', ['%language' => $langcode]));
    }
  }

}
