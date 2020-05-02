<?php

namespace Drupal\Tests\path\Kernel;

use Drupal\content_translation_test\Entity\EntityTestTranslatableUISkip;
use Drupal\KernelTests\KernelTestBase;
use Drupal\language\Entity\ConfigurableLanguage;

/**
 * Tests path alias deletion when there is no canonical link template.
 *
 * @group path
 */
class PathNoCanonicalLinkTest extends KernelTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'path',
    'content_translation_test',
    'language',
    'entity_test',
    'user',
    'system',
  ];

  protected function setUp() {
    parent::setUp();

    $this->installEntitySchema('entity_test');
    $this->installEntitySchema('entity_test_mul');
    \Drupal::service('router.builder')->rebuild();

    // Adding german language.
    ConfigurableLanguage::create(['id' => 'de'])->save();

    $this->config('language.types')->setData([
      'configurable' => ['language_interface'],
      'negotiation' => ['language_interface' => ['enabled' => ['language-url' => 0]]],
    ])->save();
  }

  /**
   * Tests for no canonical link templates.
   */
  public function testNoCanonicalLinkTemplate() {
    $entity_type = EntityTestTranslatableUISkip::create([
      'name' => 'name english',
      'language' => 'en',
    ]);
    $entity_type->save();

    $entity_type->addTranslation('de', ['name' => 'name german']);
    $entity_type->save();
    $this->assertEqual(count($entity_type->getTranslationLanguages()), 2);

    $entity_type->removeTranslation('de');
    $entity_type->save();
    $this->assertEqual(count($entity_type->getTranslationLanguages()), 1);
  }

}
