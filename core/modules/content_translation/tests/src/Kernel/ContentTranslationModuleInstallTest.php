<?php

namespace Drupal\Tests\content_translation\Kernel;

use Drupal\entity_test\Entity\EntityTestWithBundle;
use Drupal\KernelTests\KernelTestBase;
use Drupal\language\Entity\ConfigurableLanguage;

/**
 * Tests content translation for modules that provide translatable bundles.
 *
 * @group content_translation
 */
class ContentTranslationModuleInstallTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'content_translation',
    'content_translation_test',
    'entity_test',
    'language',
    'user',
  ];

  /**
   * The content translation manager.
   *
   * @var \Drupal\content_translation\ContentTranslationManagerInterface
   */
  protected $contentTranslationManager;

  /**
   * The language code of the source language for this test.
   *
   * @var string
   */
  protected $sourceLangcode = 'en';

  /**
   * The language code of the translation language for this test.
   *
   * @var string
   */
  protected $translationLangcode = 'af';

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->installEntitySchema('entity_test_with_bundle');
    ConfigurableLanguage::createFromLangcode($this->translationLangcode)->save();

    $this->contentTranslationManager = $this->container->get('content_translation.manager');
  }

  /**
   * Tests that content translation fields are created upon module installation.
   */
  public function testFieldUpdates() {
    // The module ships a translatable bundle of the 'entity_test_with_bundle'
    // entity type.
    $this->installConfig(['content_translation_test']);

    $entity = EntityTestWithBundle::create([
      'type' => 'test',
      'langcode' => $this->sourceLangcode,
    ]);
    $entity->save();

    // Add a translation with some translation metadata.
    $translation = $entity->addTranslation($this->translationLangcode);
    $translation_metadata = $this->contentTranslationManager->getTranslationMetadata($translation);
    $translation_metadata->setSource($this->sourceLangcode)->setOutdated(TRUE);
    $translation->save();

    // Make sure the translation metadata has been saved correctly.
    $entity = EntityTestWithBundle::load($entity->id());
    $translation = $entity->getTranslation($this->translationLangcode);
    $translation_metadata = $this->contentTranslationManager->getTranslationMetadata($translation);
    $this->assertSame($this->sourceLangcode, $translation_metadata->getSource());
    $this->assertTrue($translation_metadata->isOutdated());
  }

}
