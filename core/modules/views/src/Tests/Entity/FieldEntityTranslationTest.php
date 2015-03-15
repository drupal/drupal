<?php

/**
 * @file
 * Contains \Drupal\views\Tests\Entity\FieldEntityTranslationTest.
 */

namespace Drupal\views\Tests\Entity;

use Drupal\Core\Language\Language;
use Drupal\language\Entity\ConfigurableLanguage;
use Drupal\node\Entity\Node;
use Drupal\node\Entity\NodeType;
use Drupal\views\Tests\ViewTestBase;
use Symfony\Component\CssSelector\CssSelector;

/**
 * Tests the rendering of fields (base fields) and their translations.
 *
 * @group views
 */
class FieldEntityTranslationTest extends ViewTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['language', 'locale', 'content_translation', 'node'];

  /**
   * {@inheritdoc}
   */
  public static $testViews = ['test_entity_field_renderers'];

  /**
   * {@inheritdoc}
   */
  protected function setUp($import_test_views = TRUE) {
    parent::setUp($import_test_views);

    $node_type = NodeType::create([
      'type' => 'article',
      'label' => 'Article',
    ]);
    $node_type->save();

    /** @var \Drupal\content_translation\ContentTranslationManagerInterface $content_translation_manager */
    $content_translation_manager = \Drupal::service('content_translation.manager');

    $content_translation_manager->setEnabled('node', 'article', 'title');
    $content_translation_manager->setEnabled('node', 'article', 'sticky');
    $content_translation_manager->setEnabled('node', 'article', 'published');

    $language = ConfigurableLanguage::create([
      'id' => 'es',
      'label' => 'Spanish',
    ]);
    $language->save();
    // Rebuild the container to setup the language path processors.
    $this->rebuildContainer();
  }

  /**
   * Tests that different translation mechanisms can be used for base fields.
   */
  public function testTranslationRows() {
    $node = Node::create([
      'type' => 'article',
      'title' => 'example EN',
      'sticky' => false,
    ]);
    $node->save();

    $translation = $node->getTranslation('es');
    $translation->title->value = 'example ES';
    $translation->sticky->value = true;
    $translation->save();

    $this->drupalGet('test_entity_field_renderers/entity_translation');
    $this->assertRows(
    [
      [
        'title' => 'example EN',
        'sticky' => 'Off',
      ],
      [
        'title' => 'example ES',
        'sticky' => 'On',
      ],
    ]);

    $this->drupalGet('test_entity_field_renderers/entity_default');
    $this->assertRows(
      [
        [
          'title' => 'example EN',
          'sticky' => 'Off',
        ],
        [
          'title' => 'example EN',
          'sticky' => 'Off',
        ],
      ]);

    $this->drupalGet('test_entity_field_renderers/site_default');
    $this->assertRows(
      [
        [
          'title' => 'example EN',
          'sticky' => 'Off',
        ],
        [
          'title' => 'example EN',
          'sticky' => 'Off',
        ],
      ]);

    $this->drupalGet('test_entity_field_renderers/language_interface');
    $this->assertRows(
      [
        [
          'title' => 'example EN',
          'sticky' => 'Off',
        ],
        [
          'title' => 'example EN',
          'sticky' => 'Off',
        ],
      ]);

    $this->drupalGet('test_entity_field_renderers/language_interface', ['language' => new Language(['id' => 'es'])]);
    $this->assertRows(
      [
        [
          'title' => 'example ES',
          'sticky' => 'On',
        ],
        [
          'title' => 'example ES',
          'sticky' => 'On',
        ],
      ]);

    $this->drupalGet('test_entity_field_renderers/en');
    $this->assertRows(
      [
        [
          'title' => 'example EN',
          'sticky' => 'Off',
        ],
        [
          'title' => 'example EN',
          'sticky' => 'Off',
        ],
      ]);

    $this->drupalGet('test_entity_field_renderers/es');
    $this->assertRows(
      [
        [
          'title' => 'example ES',
          'sticky' => 'On',
        ],
        [
          'title' => 'example ES',
          'sticky' => 'On',
        ],
      ]);
  }

  /**
   * Ensures that the rendered results are working as expected.
   *
   * @param array $expected
   *   The expected rows of the result.
   */
  protected function assertRows($expected = []) {
    $actual = [];
    $rows = $this->cssSelect('div.views-row');
    foreach ($rows as $row) {
      $actual[] = [
        'title' => (string) $row->xpath(CssSelector::toXPath('.views-field-title span.field-content a'))[0],
        'sticky' => (string) $row->xpath(CssSelector::toXPath('.views-field-sticky span.field-content'))[0],
      ];
    }
    $this->assertEqual($actual, $expected);
  }

}
