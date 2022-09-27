<?php

namespace Drupal\Tests\media\Kernel;

use Drupal\language\Entity\ConfigurableLanguage;

/**
 * Tests that media embeds are translated based on text (host entity) language.
 *
 * @coversDefaultClass \Drupal\media\Plugin\Filter\MediaEmbed
 * @group media
 */
class MediaEmbedFilterTranslationTest extends MediaEmbedFilterTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'language',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    ConfigurableLanguage::createFromLangcode('pt-br')->save();
    // Reload the entity to ensure it is aware of the newly created language.
    $this->embeddedEntity = $this->container->get('entity_type.manager')
      ->getStorage('media')
      ->load($this->embeddedEntity->id());

    $this->embeddedEntity->addTranslation('pt-br')
      ->set('field_media_image', [
        'target_id' => $this->image->id(),
        'alt' => 'pt-br alt',
        'title' => 'pt-br title',
      ])->save();
  }

  /**
   * Tests that the expected embedded media entity translation is selected.
   *
   * @dataProvider providerTranslationSituations
   */
  public function testTranslationSelection($text_langcode, $expected_title_langcode) {
    $text = $this->createEmbedCode([
      'data-entity-type' => 'media',
      'data-entity-uuid' => static::EMBEDDED_ENTITY_UUID,
    ]);

    $result = $this->processText($text, $text_langcode, ['media_embed']);
    $this->setRawContent($result->getProcessedText());

    $this->assertSame(
      $this->embeddedEntity->getTranslation($expected_title_langcode)->field_media_image->alt,
      (string) $this->cssSelect('img')[0]->attributes()['alt']
    );
    // Verify that the filtered text does not vary by translation-related cache
    // contexts: a particular translation of the embedded entity is selected
    // based on the host entity's language, which should require a cache context
    // to be associated. (The host entity's language may itself be selected
    // based on the request context, but that is of no concern to this filter.)
    $this->assertEqualsCanonicalizing($result->getCacheContexts(), ['timezone', 'user.permissions']);
  }

  /**
   * Data provider for testTranslationSelection().
   */
  public function providerTranslationSituations() {
    $embedded_entity_translation_languages = ['en', 'pt-br'];

    foreach (['en', 'pt-br', 'nl'] as $text_langcode) {
      // The text language (which is set to the host entity's language) must be
      // respected in selecting a translation. If that translation does not
      // exist, it falls back to the default translation of the embedded entity.
      $match_or_fallback_langcode = in_array($text_langcode, $embedded_entity_translation_languages)
        ? $text_langcode
        : 'en';
      yield "text_langcode=$text_langcode ⇒ $match_or_fallback_langcode" => [
        $text_langcode,
        $match_or_fallback_langcode,
      ];
    }
  }

}
