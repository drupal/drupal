<?php

namespace Drupal\Tests\system\Kernel\Theme;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\KernelTests\KernelTestBase;
use Drupal\Component\Render\MarkupInterface;

/**
 * Tests low-level theme functions.
 *
 * @group Theme
 */
class ThemeTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['theme_test', 'node', 'system'];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    \Drupal::service('theme_installer')->install(['test_theme']);
  }

  /**
   * Tests attribute merging.
   *
   * Render arrays that use a render element and templates (and hence call
   * template_preprocess()) must ensure the attributes at different occasions
   * are all merged correctly:
   *   - $variables['attributes'] as passed in to the theme hook implementation.
   *   - the render element's #attributes
   *   - any attributes set in the template's preprocessing function
   */
  public function testAttributeMerging() {
    $theme_test_render_element = [
      'elements' => [
        '#attributes' => ['data-foo' => 'bar'],
      ],
      'attributes' => [
        'id' => 'bazinga',
      ],
    ];
    $this->assertThemeOutput('theme_test_render_element', $theme_test_render_element, '<div id="bazinga" data-foo="bar" data-variables-are-preprocessed></div>' . "\n");
  }

  /**
   * Tests that ThemeManager renders the expected data types.
   */
  public function testThemeDataTypes() {
    // theme_test_false is an implemented theme hook so \Drupal::theme() service
    // should return a string or an object that implements MarkupInterface,
    // even though the theme function itself can return anything.
    $foos = ['null' => NULL, 'false' => FALSE, 'integer' => 1, 'string' => 'foo', 'empty_string' => ''];
    foreach ($foos as $type => $example) {
      $output = \Drupal::theme()->render('theme_test_foo', ['foo' => $example]);
      $this->assertTrue($output instanceof MarkupInterface || is_string($output), new FormattableMarkup('\Drupal::theme() returns an object that implements MarkupInterface or a string for data type @type.', ['@type' => $type]));
      if ($output instanceof MarkupInterface) {
        $this->assertSame((string) $example, $output->__toString());
      }
      elseif (is_string($output)) {
        $this->assertSame('', $output, 'A string will be return when the theme returns an empty string.');
      }
    }

    // suggestionnotimplemented is not an implemented theme hook so \Drupal::theme() service
    // should return FALSE instead of a string.
    $output = \Drupal::theme()->render(['suggestionnotimplemented'], []);
    $this->assertFalse($output, '\Drupal::theme() returns FALSE when a hook suggestion is not implemented.');
  }

  /**
   * Tests function theme_get_suggestions() for SA-CORE-2009-003.
   */
  public function testThemeSuggestions() {
    // Set the front page as something random otherwise the CLI
    // test runner fails.
    $this->config('system.site')->set('page.front', '/nobody-home')->save();
    $args = ['node', '1', 'edit'];
    $suggestions = theme_get_suggestions($args, 'page');
    $this->assertEquals(['page__node', 'page__node__%', 'page__node__1', 'page__node__edit'], $suggestions, 'Found expected node edit page suggestions');
    // Check attack vectors.
    $args = ['node', '\\1'];
    $suggestions = theme_get_suggestions($args, 'page');
    $this->assertEquals(['page__node', 'page__node__%', 'page__node__1'], $suggestions, 'Removed invalid \\ from suggestions');
    $args = ['node', '1/'];
    $suggestions = theme_get_suggestions($args, 'page');
    $this->assertEquals(['page__node', 'page__node__%', 'page__node__1'], $suggestions, 'Removed invalid / from suggestions');
    $args = ['node', "1\0"];
    $suggestions = theme_get_suggestions($args, 'page');
    $this->assertEquals(['page__node', 'page__node__%', 'page__node__1'], $suggestions, 'Removed invalid \\0 from suggestions');
    // Define path with hyphens to be used to generate suggestions.
    $args = ['node', '1', 'hyphen-path'];
    $result = ['page__node', 'page__node__%', 'page__node__1', 'page__node__hyphen_path'];
    $suggestions = theme_get_suggestions($args, 'page');
    $this->assertEquals($result, $suggestions, 'Found expected page suggestions for paths containing hyphens.');
  }

  /**
   * Tests the listInfo() function.
   */
  public function testListThemes() {
    $this->container->get('theme_installer')->install(['test_subtheme']);
    $theme_handler = $this->container->get('theme_handler');
    $themes = $theme_handler->listInfo();

    // Check if ThemeHandlerInterface::listInfo() retrieves enabled themes.
    $this->assertSame(1, $themes['test_theme']->status, 'Installed theme detected');

    // Check if ThemeHandlerInterface::listInfo() returns disabled themes.
    // Check for base theme and subtheme lists.
    $base_theme_list = ['test_basetheme' => 'Theme test base theme'];
    $sub_theme_list = ['test_subsubtheme' => 'Theme test subsubtheme', 'test_subtheme' => 'Theme test subtheme'];

    $this->assertSame($sub_theme_list, $themes['test_basetheme']->sub_themes, 'Base theme\'s object includes list of subthemes.');
    $this->assertSame($base_theme_list, $themes['test_subtheme']->base_themes, 'Subtheme\'s object includes list of base themes.');
    // Check for theme engine in subtheme.
    $this->assertSame('twig', $themes['test_subtheme']->engine, 'Subtheme\'s object includes the theme engine.');
    // Check for theme engine prefix.
    $this->assertSame('twig', $themes['test_basetheme']->prefix, 'Base theme\'s object includes the theme engine prefix.');
    $this->assertSame('twig', $themes['test_subtheme']->prefix, 'Subtheme\'s object includes the theme engine prefix.');
  }

  /**
   * Tests child element rendering for 'render element' theme hooks.
   */
  public function testDrupalRenderChildren() {
    $element = [
      '#theme' => 'theme_test_render_element_children',
      'child' => [
        '#markup' => 'Foo',
      ],
    ];
    $this->assertThemeOutput('theme_test_render_element_children', $element, 'Foo', "\Drupal::service('renderer')->render() avoids #theme recursion loop when rendering a render element.");

    $element = [
      '#theme_wrappers' => ['theme_test_render_element_children'],
      'child' => [
        '#markup' => 'Foo',
      ],
    ];
    $this->assertThemeOutput('theme_test_render_element_children', $element, 'Foo', "\Drupal::service('renderer')->render() avoids #theme_wrappers recursion loop when rendering a render element.");
  }

  /**
   * Tests drupal_find_theme_templates().
   */
  public function testFindThemeTemplates() {
    $registry = $this->container->get('theme.registry')->get();
    $templates = drupal_find_theme_templates($registry, '.html.twig', drupal_get_path('theme', 'test_theme'));
    $this->assertEquals('node--1', $templates['node__1']['template'], 'Template node--1.html.twig was found in test_theme.');
  }

}
