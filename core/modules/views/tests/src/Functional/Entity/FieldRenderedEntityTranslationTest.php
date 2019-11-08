<?php

namespace Drupal\Tests\views\Functional\Entity;

use Drupal\Core\Language\Language;
use Drupal\Tests\views\Functional\ViewTestBase;
use Symfony\Component\CssSelector\CssSelectorConverter;

/**
 * Tests the rendering of the 'rendered_entity' field and translations.
 *
 * @group views
 */
class FieldRenderedEntityTranslationTest extends ViewTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['language', 'locale', 'content_translation', 'node'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'classy';

  /**
   * {@inheritdoc}
   */
  public static $testViews = ['test_entity_field_renderered_entity'];

  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * {@inheritdoc}
   */
  protected function setUp($import_test_views = TRUE) {
    parent::setUp($import_test_views);

    $this->entityTypeManager = $this->container->get('entity_type.manager');

    $node_type = $this->entityTypeManager->getStorage('node_type')->create([
      'type' => 'article',
      'label' => 'Article',
    ]);
    $node_type->save();

    /** @var \Drupal\content_translation\ContentTranslationManagerInterface $content_translation_manager */
    $content_translation_manager = $this->container->get('content_translation.manager');

    $content_translation_manager->setEnabled('node', 'article', TRUE);

    $language = $this->entityTypeManager->getStorage('configurable_language')->create([
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
    // First, an EN node with an ES translation.
    /** @var \Drupal\node\NodeInterface $node */
    $node = $this->entityTypeManager->getStorage('node')->create([
      'type' => 'article',
      'title' => 'example EN default',
    ]);
    $node->save();

    $translation = $node->addTranslation('es');
    $translation->title->value = 'example ES translation';
    $translation->sticky->value = TRUE;
    $translation->save();

    // Next, an ES node with an EN translation.
    $node = $this->entityTypeManager->getStorage('node')->create([
      'type' => 'article',
      'title' => 'example ES default',
      'langcode' => 'es',
    ]);
    $node->save();

    $translation = $node->addTranslation('en');
    $translation->title->value = 'example EN translation';
    $translation->sticky->value = TRUE;
    $translation->save();

    // Next an EN node with no translation.
    $node = $this->entityTypeManager->getStorage('node')->create([
      'type' => 'article',
      'title' => 'example EN no translation',
      'sticky' => FALSE,
    ]);
    $node->save();

    // Next an ES node with no translation.
    $node = $this->entityTypeManager->getStorage('node')->create([
      'type' => 'article',
      'title' => 'example ES no translation',
      'sticky' => FALSE,
      'langcode' => 'es',
    ]);
    $node->save();

    // Views are sorted first by node id ascending, and then title ascending.
    // Confirm each node and node translation renders in its own language.
    $this->drupalGet('test_entity_field_renderered_entity/entity_translation');
    $this->assertRows([
      [
        'title' => 'example EN default',
      ],
      [
        'title' => 'example ES translation',
      ],
      [
        'title' => 'example EN translation',
      ],
      [
        'title' => 'example ES default',
      ],
      [
        'title' => 'example EN no translation',
      ],
      [
        'title' => 'example ES no translation',
      ],
    ]);

    // Confirm each node and node translation renders in the default language of
    // the node.
    $this->drupalGet('test_entity_field_renderered_entity/entity_default');
    $this->assertRows([
      [
        'title' => 'example EN default',
      ],
      [
        'title' => 'example EN default',
      ],
      [
        'title' => 'example ES default',
      ],
      [
        'title' => 'example ES default',
      ],
      [
        'title' => 'example EN no translation',
      ],
      [
        'title' => 'example ES no translation',
      ],
    ]);

    // Confirm each node and node translation renders in the site's default
    // language (en), with fallback if node does not have en content.
    $this->drupalGet('test_entity_field_renderered_entity/site_default');
    $this->assertRows([
      [
        'title' => 'example EN default',
      ],
      [
        'title' => 'example EN default',
      ],
      [
        'title' => 'example EN translation',
      ],
      [
        'title' => 'example EN translation',
      ],
      [
        'title' => 'example EN no translation',
      ],
      [
        'title' => 'example ES no translation',
      ],
    ]);

    // Confirm each node and node translation renders in the site interface
    // language (en), with fallback if node does not have en content.
    $this->drupalGet('test_entity_field_renderered_entity/language_interface');
    $this->assertRows([
      [
        'title' => 'example EN default',
      ],
      [
        'title' => 'example EN default',
      ],
      [
        'title' => 'example EN translation',
      ],
      [
        'title' => 'example EN translation',
      ],
      [
        'title' => 'example EN no translation',
      ],
      [
        'title' => 'example ES no translation',
      ],
    ]);

    // Confirm each node and node translation renders in the site interface
    // language (es), with fallback if node does not have es content.
    $this->drupalGet('test_entity_field_renderered_entity/language_interface', ['language' => new Language(['id' => 'es'])]);
    $this->assertRows([
      [
        'title' => 'example ES translation',
      ],
      [
        'title' => 'example ES translation',
      ],
      [
        'title' => 'example ES default',
      ],
      [
        'title' => 'example ES default',
      ],
      [
        'title' => 'example EN no translation',
      ],
      [
        'title' => 'example ES no translation',
      ],
    ]);

    // Confirm each node and node translation renders in specified language en.
    $this->drupalGet('test_entity_field_renderered_entity/en');
    $this->assertRows([
      [
        'title' => 'example EN default',
      ],
      [
        'title' => 'example EN default',
      ],
      [
        'title' => 'example EN translation',
      ],
      [
        'title' => 'example EN translation',
      ],
      [
        'title' => 'example EN no translation',
      ],
      [
        'title' => 'example ES no translation',
      ],
    ]);

    // Confirm each node and node translation renders in specified language es.
    $this->drupalGet('test_entity_field_renderered_entity/es');
    $this->assertRows([
      [
        'title' => 'example ES translation',
      ],
      [
        'title' => 'example ES translation',
      ],
      [
        'title' => 'example ES default',
      ],
      [
        'title' => 'example ES default',
      ],
      [
        'title' => 'example EN no translation',
      ],
      [
        'title' => 'example ES no translation',
      ],
    ]);
  }

  /**
   * Ensures that the rendered results are working as expected.
   *
   * @param array $expected
   *   The expected rows of the result.
   */
  protected function assertRows(array $expected = []) {
    $actual = [];
    $rows = $this->cssSelect('div.views-row');
    foreach ($rows as $row) {
      $actual[] = [
        'title' => $row->find('xpath', (new CssSelectorConverter())->toXPath('h2 a .field--name-title'))->getText(),
      ];
    }
    $this->assertEquals($actual, $expected);
  }

}
