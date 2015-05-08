<?php

/**
 * @file
 * Contains \Drupal\language\Tests\EntityUrlLanguageTest.
 */

namespace Drupal\language\Tests;

use Drupal\entity_test\Entity\EntityTest;
use Drupal\language\Entity\ConfigurableLanguage;
use Drupal\simpletest\KernelTestBase;

/**
 * Tests the language of entity URLs.
 * @group language
 */
class EntityUrlLanguageTest extends KernelTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['language', 'entity_test', 'user', 'system'];

  protected function setUp() {
    parent::setUp();

    $this->installEntitySchema('entity_test');
    $this->installEntitySchema('configurable_language');
    $this->installSchema('system', 'router');
    \Drupal::service('router.builder')->rebuild();

    // In order to reflect the changes for a multilingual site in the container
    // we have to rebuild it.
    ConfigurableLanguage::create(['id' => 'es'])->save();
    ConfigurableLanguage::create(['id' => 'fr'])->save();

    $this->config('language.types')->setData([
      'configurable' => ['language_interface'],
      'negotiation' => ['language_interface' => ['enabled' => ['language-url' => 0]]],
    ])->save();
    $this->config('language.negotiation')->setData([
      'url' => [
        'source' => 'path_prefix',
        'prefixes' => ['en' => 'en', 'es' => 'es', 'fr' => 'fr']
      ],
    ])->save();
    $this->kernel->rebuildContainer();
    $this->container = $this->kernel->getContainer();
    \Drupal::setContainer($this->container);
  }

  /**
   * Ensures that entity URLs in a language have the right language prefix.
   */
  public function testEntityUrlLanguage() {
    $entity = EntityTest::create();
    $entity->addTranslation('es', ['name' => 'name spanish']);
    $entity->addTranslation('fr', ['name' => 'name french']);
    $entity->save();

    $this->assertTrue(strpos($entity->urlInfo()->toString(), '/en/entity_test/' . $entity->id()) !== FALSE);
    $this->assertTrue(strpos($entity->getTranslation('es')->urlInfo()->toString(), '/es/entity_test/' . $entity->id()) !== FALSE);
    $this->assertTrue(strpos($entity->getTranslation('fr')->urlInfo()->toString(), '/fr/entity_test/' . $entity->id()) !== FALSE);
  }

}
