<?php

namespace Drupal\Tests\language\Kernel;

use Drupal\Core\Language\LanguageInterface;
use Drupal\language\Entity\ContentLanguageSettings;
use Drupal\KernelTests\KernelTestBase;

/**
 * Tests default language code is properly generated for entities.
 *
 * @group language
 */
class EntityDefaultLanguageTest extends KernelTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = [
    'language',
    'node',
    'field',
    'text',
    'user',
    'system',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->installEntitySchema('user');

    // Activate Spanish language, so there are two languages activated.
    $language = $this->container->get('entity_type.manager')->getStorage('configurable_language')->create([
      'id' => 'es',
      'label' => 'Spanish',
    ]);
    $language->save();

    // Create a new content type which has Undefined language by default.
    $this->createContentType('content_und', LanguageInterface::LANGCODE_NOT_SPECIFIED);
    // Create a new content type which has Spanish language by default.
    $this->createContentType('content_es', 'es');
  }

  /**
   * Tests that default language code is properly set for new nodes.
   */
  public function testEntityTranslationDefaultLanguageViaCode() {
    // With language module activated, and a content type that is configured to
    // have no language by default, a new node of this content type will have
    // "und" language code when language is not specified.
    $node = $this->createNode('content_und');
    $this->assertEquals(LanguageInterface::LANGCODE_NOT_SPECIFIED, $node->langcode->value);
    // With language module activated, and a content type that is configured to
    // have no language by default, a new node of this content type will have
    // "es" language code when language is specified as "es".
    $node = $this->createNode('content_und', 'es');
    $this->assertEquals('es', $node->langcode->value);

    // With language module activated, and a content type that is configured to
    // have language "es" by default, a new node of this content type will have
    // "es" language code when language is not specified.
    $node = $this->createNode('content_es');
    $this->assertEquals('es', $node->langcode->value);
    // With language module activated, and a content type that is configured to
    // have language "es" by default, a new node of this content type will have
    // "en" language code when language "en" is specified.
    $node = $this->createNode('content_es', 'en');
    $this->assertEquals('en', $node->langcode->value);

    // Disable language module.
    $this->disableModules(['language']);

    // With language module disabled, and a content type that is configured to
    // have no language specified by default, a new node of this content type
    // will have site's default language code when language is not specified.
    $node = $this->createNode('content_und');
    $this->assertEquals('en', $node->langcode->value);
    // With language module disabled, and a content type that is configured to
    // have no language specified by default, a new node of this type will have
    // "es" language code when language "es" is specified.
    $node = $this->createNode('content_und', 'es');
    $this->assertEquals('es', $node->langcode->value);

    // With language module disabled, and a content type that is configured to
    // have language "es" by default, a new node of this type will have site's
    // default language code when language is not specified.
    $node = $this->createNode('content_es');
    $this->assertEquals('en', $node->langcode->value);
    // With language module disabled, and a content type that is configured to
    // have language "es" by default, a new node of this type will have "en"
    // language code when language "en" is specified.
    $node = $this->createNode('content_es', 'en');
    $this->assertEquals('en', $node->langcode->value);
  }

  /**
   * Creates a new node content type.
   *
   * @param string $name
   *   The content type name.
   * @param string $langcode
   *   Default language code of the nodes of this type.
   */
  protected function createContentType($name, $langcode) {
    $content_type = $this->container->get('entity_type.manager')->getStorage('node_type')->create([
      'name' => 'Test ' . $name,
      'title_label' => 'Title',
      'type' => $name,
      'create_body' => FALSE,
    ]);
    $content_type->save();
    ContentLanguageSettings::loadByEntityTypeBundle('node', $name)
      ->setLanguageAlterable(FALSE)
      ->setDefaultLangcode($langcode)
      ->save();

  }

  /**
   * Creates a new node of given type and language using Entity API.
   *
   * @param string $type
   *   The node content type.
   * @param string $langcode
   *   (optional) Language code to pass to entity create.
   *
   * @return \Drupal\node\NodeInterface
   *   The node created.
   */
  protected function createNode($type, $langcode = NULL) {
    $values = [
      'type' => $type,
      'title' => $this->randomString(),
    ];
    if (!empty($langcode)) {
      $values['langcode'] = $langcode;
    }
    $node = $this->container->get('entity_type.manager')->getStorage('node')->create($values);
    return $node;
  }

}
