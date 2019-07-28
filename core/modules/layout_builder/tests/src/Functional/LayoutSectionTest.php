<?php

namespace Drupal\Tests\layout_builder\Functional;

use Drupal\layout_builder\Entity\LayoutBuilderEntityViewDisplay;
use Drupal\layout_builder\Plugin\SectionStorage\OverridesSectionStorage;
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
  public static $modules = ['field_ui', 'layout_builder', 'node', 'block_test'];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->createContentType([
      'type' => 'bundle_without_section_field',
    ]);
    $this->createContentType([
      'type' => 'bundle_with_section_field',
    ]);

    LayoutBuilderEntityViewDisplay::load('node.bundle_with_section_field.default')
      ->enableLayoutBuilder()
      ->setOverridable()
      ->save();

    $this->drupalLogin($this->drupalCreateUser([
      'configure any layout',
      'administer node display',
      'administer node fields',
      'administer content types',
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
              'id' => 'field_block:node:bundle_with_section_field:body',
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

    $canonical_url = $node->toUrl('canonical');
    $this->drupalGet($canonical_url);
    $this->assertLayoutSection($expected_selector, $expected_content, $expected_cache_contexts, $expected_cache_tags, $expected_dynamic_cache);

    $this->drupalGet($canonical_url->toString() . '/layout');
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
   * Ensures that the entity title is displayed.
   */
  public function testLayoutPageTitle() {
    $this->drupalPlaceBlock('page_title_block');
    $node = $this->createSectionNode([]);

    $this->drupalGet($node->toUrl('canonical')->toString() . '/layout');
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

    $this->drupalGet($node->toUrl('canonical')->toString() . '/layout');
    $this->assertSession()->statusCodeEquals(404);
  }

  /**
   * Tests that deleting a field removes it from the layout.
   */
  public function testLayoutDeletingField() {
    $assert_session = $this->assertSession();

    $this->drupalGet('/admin/structure/types/manage/bundle_with_section_field/display/default/layout');
    $assert_session->statusCodeEquals(200);
    $assert_session->elementExists('css', '.field--name-body');

    // Delete the field from both bundles.
    $this->drupalGet('/admin/structure/types/manage/bundle_without_section_field/fields/node.bundle_without_section_field.body/delete');
    $this->submitForm([], 'Delete');
    $this->drupalGet('/admin/structure/types/manage/bundle_with_section_field/display/default/layout');
    $assert_session->statusCodeEquals(200);
    $assert_session->elementExists('css', '.field--name-body');

    $this->drupalGet('/admin/structure/types/manage/bundle_with_section_field/fields/node.bundle_with_section_field.body/delete');
    $this->submitForm([], 'Delete');
    $this->drupalGet('/admin/structure/types/manage/bundle_with_section_field/display/default/layout');
    $assert_session->statusCodeEquals(200);
    $assert_session->elementNotExists('css', '.field--name-body');
  }

  /**
   * Tests that deleting a bundle removes the layout.
   */
  public function testLayoutDeletingBundle() {
    $assert_session = $this->assertSession();

    $display = LayoutBuilderEntityViewDisplay::load('node.bundle_with_section_field.default');
    $this->assertInstanceOf(LayoutBuilderEntityViewDisplay::class, $display);

    $this->drupalPostForm('/admin/structure/types/manage/bundle_with_section_field/delete', [], 'Delete');
    $assert_session->statusCodeEquals(200);

    $display = LayoutBuilderEntityViewDisplay::load('node.bundle_with_section_field.default');
    $this->assertNull($display);
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
      OverridesSectionStorage::FIELD_NAME => $section_values,
    ]);
  }

}
