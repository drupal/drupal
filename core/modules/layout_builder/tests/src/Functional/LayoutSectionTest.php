<?php

namespace Drupal\Tests\layout_builder\Functional;

use Drupal\Core\Entity\Entity\EntityViewDisplay;
use Drupal\language\Entity\ConfigurableLanguage;
use Drupal\layout_builder\Section;
use Drupal\layout_builder\SectionComponent;
use Drupal\Tests\BrowserTestBase;

/**
 * Tests the rendering of a layout section field.
 *
 * @group layout_builder
 */
class LayoutSectionTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['layout_builder', 'node', 'block_test'];

  /**
   * The name of the layout section field.
   *
   * @var string
   */
  protected $fieldName = 'layout_builder__layout';

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->createContentType([
      'type' => 'bundle_with_section_field',
    ]);
    $this->createContentType([
      'type' => 'bundle_without_section_field',
    ]);

    layout_builder_add_layout_section_field('node', 'bundle_with_section_field');
    $display = EntityViewDisplay::load('node.bundle_with_section_field.default');
    $display->setThirdPartySetting('layout_builder', 'allow_custom', TRUE);
    $display->save();

    $this->drupalLogin($this->drupalCreateUser([
      'configure any layout',
    ], 'foobar'));
  }

  /**
   * Provides test data for ::testLayoutSectionFormatter().
   */
  public function providerTestLayoutSectionFormatter() {
    $data = [];
    $data['block_with_global_context'] = [
      [
        [
          'section' => new Section('layout_onecol', [], [
            'baz' => new SectionComponent('baz', 'content', [
              'id' => 'test_context_aware',
              'context_mapping' => [
                'user' => '@user.current_user_context:current_user',
              ],
            ]),
          ]),
        ],
      ],
      [
        '.layout--onecol',
        '#test_context_aware--username',
      ],
      [
        'foobar',
        'User context found',
      ],
      'user',
      'user:2',
      'UNCACHEABLE',
    ];
    $data['block_with_entity_context'] = [
      [
        [
          'section' => new Section('layout_onecol', [], [
            'baz' => new SectionComponent('baz', 'content', [
              'id' => 'field_block:node:body',
              'context_mapping' => [
                'entity' => 'layout_builder.entity',
              ],
            ]),
          ]),
        ],
      ],
      [
        '.layout--onecol',
        '.field--name-body',
      ],
      [
        'Body',
        'The node body',
      ],
      '',
      '',
      'MISS',
    ];
    $data['single_section_single_block'] = [
      [
        [
          'section' => new Section('layout_onecol', [], [
            'baz' => new SectionComponent('baz', 'content', [
              'id' => 'system_powered_by_block',
            ]),
          ]),
        ],
      ],
      '.layout--onecol',
      'Powered by',
      '',
      '',
      'MISS',
    ];
    $data['multiple_sections'] = [
      [
        [
          'section' => new Section('layout_onecol', [], [
            'baz' => new SectionComponent('baz', 'content', [
              'id' => 'system_powered_by_block',
            ]),
          ]),
        ],
        [
          'section' => new Section('layout_twocol', [], [
            'foo' => new SectionComponent('foo', 'first', [
              'id' => 'test_block_instantiation',
              'display_message' => 'foo text',
            ]),
            'bar' => new SectionComponent('bar', 'second', [
              'id' => 'test_block_instantiation',
              'display_message' => 'bar text',
            ]),
          ]),
        ],
      ],
      [
        '.layout--onecol',
        '.layout--twocol',
      ],
      [
        'Powered by',
        'foo text',
        'bar text',
      ],
      'user.permissions',
      '',
      'MISS',
    ];
    return $data;
  }

  /**
   * Tests layout_section formatter output.
   *
   * @dataProvider providerTestLayoutSectionFormatter
   */
  public function testLayoutSectionFormatter($layout_data, $expected_selector, $expected_content, $expected_cache_contexts, $expected_cache_tags, $expected_dynamic_cache) {
    $node = $this->createSectionNode($layout_data);

    $this->drupalGet($node->toUrl('canonical'));
    $this->assertLayoutSection($expected_selector, $expected_content, $expected_cache_contexts, $expected_cache_tags, $expected_dynamic_cache);

    $this->drupalGet($node->toUrl('layout-builder'));
    $this->assertLayoutSection($expected_selector, $expected_content, $expected_cache_contexts, $expected_cache_tags, 'UNCACHEABLE');
  }

  /**
   * Tests the access checking of the section formatter.
   */
  public function testLayoutSectionFormatterAccess() {
    $node = $this->createSectionNode([
      [
        'section' => new Section('layout_onecol', [], [
          'baz' => new SectionComponent('baz', 'content', [
            'id' => 'test_access',
          ]),
        ]),
      ],
    ]);

    // Restrict access to the block.
    $this->container->get('state')->set('test_block_access', FALSE);

    $this->drupalGet($node->toUrl('canonical'));
    $this->assertLayoutSection('.layout--onecol', NULL, '', '', 'UNCACHEABLE');
    // Ensure the block was not rendered.
    $this->assertSession()->pageTextNotContains('Hello test world');

    // Grant access to the block, and ensure it was rendered.
    $this->container->get('state')->set('test_block_access', TRUE);
    $this->drupalGet($node->toUrl('canonical'));
    $this->assertLayoutSection('.layout--onecol', 'Hello test world', '', '', 'UNCACHEABLE');
  }

  /**
   * Tests the multilingual support of the section formatter.
   */
  public function testMultilingualLayoutSectionFormatter() {
    $this->container->get('module_installer')->install(['content_translation']);
    $this->rebuildContainer();

    ConfigurableLanguage::createFromLangcode('es')->save();
    $this->container->get('content_translation.manager')->setEnabled('node', 'bundle_with_section_field', TRUE);

    $entity = $this->createSectionNode([
      [
        'section' => new Section('layout_onecol', [], [
          'baz' => new SectionComponent('baz', 'content', [
            'id' => 'system_powered_by_block',
          ]),
        ]),
      ],
    ]);
    $entity->addTranslation('es', [
      'title' => 'Translated node title',
      $this->fieldName => [
        [
          'section' => new Section('layout_twocol', [], [
            'foo' => new SectionComponent('foo', 'first', [
              'id' => 'test_block_instantiation',
              'display_message' => 'foo text',
            ]),
            'bar' => new SectionComponent('bar', 'second', [
              'id' => 'test_block_instantiation',
              'display_message' => 'bar text',
            ]),
          ]),
        ],
      ],
    ]);
    $entity->save();

    $this->drupalGet($entity->toUrl('canonical'));
    $this->assertLayoutSection('.layout--onecol', 'Powered by');
    $this->drupalGet($entity->toUrl('canonical')->setOption('prefix', 'es/'));
    $this->assertLayoutSection('.layout--twocol', ['foo text', 'bar text']);
  }

  /**
   * Ensures that the entity title is displayed.
   */
  public function testLayoutPageTitle() {
    $this->drupalPlaceBlock('page_title_block');
    $node = $this->createSectionNode([]);

    $this->drupalGet($node->toUrl('layout-builder'));
    $this->assertSession()->titleEquals('Edit layout for The node title | Drupal');
    $this->assertEquals('Edit layout for The node title', $this->cssSelect('h1.page-title')[0]->getText());
  }

  /**
   * Tests that no Layout link shows without a section field.
   */
  public function testLayoutUrlNoSectionField() {
    $node = $this->createNode([
      'type' => 'bundle_without_section_field',
      'title' => 'The node title',
      'body' => [
        [
          'value' => 'The node body',
        ],
      ],
    ]);
    $node->save();
    $this->drupalGet($node->toUrl('layout-builder'));
    $this->assertSession()->statusCodeEquals(404);
  }

  /**
   * Asserts the output of a layout section.
   *
   * @param string|array $expected_selector
   *   A selector or list of CSS selectors to find.
   * @param string|array $expected_content
   *   A string or list of strings to find.
   * @param string $expected_cache_contexts
   *   A string of cache contexts to be found in the header.
   * @param string $expected_cache_tags
   *   A string of cache tags to be found in the header.
   * @param string $expected_dynamic_cache
   *   The expected dynamic cache header. Either 'HIT', 'MISS' or 'UNCACHEABLE'.
   */
  protected function assertLayoutSection($expected_selector, $expected_content, $expected_cache_contexts = '', $expected_cache_tags = '', $expected_dynamic_cache = 'MISS') {
    $assert_session = $this->assertSession();
    // Find the given selector.
    foreach ((array) $expected_selector as $selector) {
      $element = $this->cssSelect($selector);
      $this->assertNotEmpty($element);
    }

    // Find the given content.
    foreach ((array) $expected_content as $content) {
      $assert_session->pageTextContains($content);
    }
    if ($expected_cache_contexts) {
      $assert_session->responseHeaderContains('X-Drupal-Cache-Contexts', $expected_cache_contexts);
    }
    if ($expected_cache_tags) {
      $assert_session->responseHeaderContains('X-Drupal-Cache-Tags', $expected_cache_tags);
    }
    $assert_session->responseHeaderEquals('X-Drupal-Dynamic-Cache', $expected_dynamic_cache);
  }

  /**
   * Creates a node with a section field.
   *
   * @param array $section_values
   *   An array of values for a section field.
   *
   * @return \Drupal\node\NodeInterface
   *   The node object.
   */
  protected function createSectionNode(array $section_values) {
    return $this->createNode([
      'type' => 'bundle_with_section_field',
      'title' => 'The node title',
      'body' => [
        [
          'value' => 'The node body',
        ],
      ],
      $this->fieldName => $section_values,
    ]);
  }

}
