<?php

namespace Drupal\KernelTests\Core\Asset;

use Drupal\Component\Serialization\Json;
use Drupal\Component\Utility\Unicode;
use Drupal\Core\Asset\AttachedAssets;
use Drupal\KernelTests\KernelTestBase;

/**
 * Tests #attached assets: attached asset libraries and JavaScript settings.
 *
 * i.e. tests:
 *
 * @code
 * $build['#attached']['library'] = …
 * $build['#attached']['drupalSettings'] = …
 * @endcode
 *
 * @group Common
 * @group Asset
 */
class AttachedAssetsTest extends KernelTestBase {

  /**
   * The asset resolver service.
   *
   * @var \Drupal\Core\Asset\AssetResolverInterface
   */
  protected $assetResolver;

  /**
   * The renderer service.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * {@inheritdoc}
   */
  public static $modules = ['language', 'simpletest', 'common_test', 'system'];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->container->get('router.builder')->rebuild();

    $this->assetResolver = $this->container->get('asset.resolver');
    $this->renderer = $this->container->get('renderer');
  }

  /**
   * Tests that default CSS and JavaScript is empty.
   */
  public function testDefault() {
    $assets = new AttachedAssets();
    $this->assertEqual([], $this->assetResolver->getCssAssets($assets, FALSE), 'Default CSS is empty.');
    list($js_assets_header, $js_assets_footer) = $this->assetResolver->getJsAssets($assets, FALSE);
    $this->assertEqual([], $js_assets_header, 'Default header JavaScript is empty.');
    $this->assertEqual([], $js_assets_footer, 'Default footer JavaScript is empty.');
  }

  /**
   * Tests non-existing libraries.
   */
  public function testLibraryUnknown() {
    $build['#attached']['library'][] = 'core/unknown';
    $assets = AttachedAssets::createFromRenderArray($build);

    $this->assertSame([], $this->assetResolver->getJsAssets($assets, FALSE)[0], 'Unknown library was not added to the page.');
  }

  /**
   * Tests adding a CSS and a JavaScript file.
   */
  public function testAddFiles() {
    $build['#attached']['library'][] = 'common_test/files';
    $assets = AttachedAssets::createFromRenderArray($build);

    $css = $this->assetResolver->getCssAssets($assets, FALSE);
    $js = $this->assetResolver->getJsAssets($assets, FALSE)[1];
    $this->assertTrue(array_key_exists('core/modules/system/tests/modules/common_test/bar.css', $css), 'CSS files are correctly added.');
    $this->assertTrue(array_key_exists('core/modules/system/tests/modules/common_test/foo.js', $js), 'JavaScript files are correctly added.');

    $css_render_array = \Drupal::service('asset.css.collection_renderer')->render($css);
    $js_render_array = \Drupal::service('asset.js.collection_renderer')->render($js);
    $rendered_css = $this->renderer->renderPlain($css_render_array);
    $rendered_js = $this->renderer->renderPlain($js_render_array);
    $query_string = $this->container->get('state')->get('system.css_js_query_string') ?: '0';
    $this->assertNotIdentical(strpos($rendered_css, '<link rel="stylesheet" href="' . file_url_transform_relative(file_create_url('core/modules/system/tests/modules/common_test/bar.css')) . '?' . $query_string . '" media="all" />'), FALSE, 'Rendering an external CSS file.');
    $this->assertNotIdentical(strpos($rendered_js, '<script src="' . file_url_transform_relative(file_create_url('core/modules/system/tests/modules/common_test/foo.js')) . '?' . $query_string . '"></script>'), FALSE, 'Rendering an external JavaScript file.');
  }

  /**
   * Tests adding JavaScript settings.
   */
  public function testAddJsSettings() {
    // Add a file in order to test default settings.
    $build['#attached']['library'][] = 'core/drupalSettings';
    $assets = AttachedAssets::createFromRenderArray($build);

    $this->assertEqual([], $assets->getSettings(), 'JavaScript settings on $assets are empty.');
    $javascript = $this->assetResolver->getJsAssets($assets, FALSE)[1];
    $this->assertTrue(array_key_exists('currentPath', $javascript['drupalSettings']['data']['path']), 'The current path JavaScript setting is set correctly.');
    $this->assertTrue(array_key_exists('currentPath', $assets->getSettings()['path']), 'JavaScript settings on $assets are resolved after retrieving JavaScript assets, and are equal to the returned JavaScript settings.');

    $assets->setSettings(['drupal' => 'rocks', 'dries' => 280342800]);
    $javascript = $this->assetResolver->getJsAssets($assets, FALSE)[1];
    $this->assertEqual(280342800, $javascript['drupalSettings']['data']['dries'], 'JavaScript setting is set correctly.');
    $this->assertEqual('rocks', $javascript['drupalSettings']['data']['drupal'], 'The other JavaScript setting is set correctly.');
  }

  /**
   * Tests adding external CSS and JavaScript files.
   */
  public function testAddExternalFiles() {
    $build['#attached']['library'][] = 'common_test/external';
    $assets = AttachedAssets::createFromRenderArray($build);

    $css = $this->assetResolver->getCssAssets($assets, FALSE);
    $js = $this->assetResolver->getJsAssets($assets, FALSE)[1];
    $this->assertTrue(array_key_exists('http://example.com/stylesheet.css', $css), 'External CSS files are correctly added.');
    $this->assertTrue(array_key_exists('http://example.com/script.js', $js), 'External JavaScript files are correctly added.');

    $css_render_array = \Drupal::service('asset.css.collection_renderer')->render($css);
    $js_render_array = \Drupal::service('asset.js.collection_renderer')->render($js);
    $rendered_css = $this->renderer->renderPlain($css_render_array);
    $rendered_js = $this->renderer->renderPlain($js_render_array);
    $this->assertNotIdentical(strpos($rendered_css, '<link rel="stylesheet" href="http://example.com/stylesheet.css" media="all" />'), FALSE, 'Rendering an external CSS file.');
    $this->assertNotIdentical(strpos($rendered_js, '<script src="http://example.com/script.js"></script>'), FALSE, 'Rendering an external JavaScript file.');
  }

  /**
   * Tests adding JavaScript files with additional attributes.
   */
  public function testAttributes() {
    $build['#attached']['library'][] = 'common_test/js-attributes';
    $assets = AttachedAssets::createFromRenderArray($build);

    $js = $this->assetResolver->getJsAssets($assets, FALSE)[1];
    $js_render_array = \Drupal::service('asset.js.collection_renderer')->render($js);
    $rendered_js = $this->renderer->renderPlain($js_render_array);
    $expected_1 = '<script src="http://example.com/deferred-external.js" foo="bar" defer></script>';
    $expected_2 = '<script src="' . file_url_transform_relative(file_create_url('core/modules/system/tests/modules/common_test/deferred-internal.js')) . '?v=1" defer bar="foo"></script>';
    $this->assertNotIdentical(strpos($rendered_js, $expected_1), FALSE, 'Rendered external JavaScript with correct defer and random attributes.');
    $this->assertNotIdentical(strpos($rendered_js, $expected_2), FALSE, 'Rendered internal JavaScript with correct defer and random attributes.');
  }

  /**
   * Tests that attributes are maintained when JS aggregation is enabled.
   */
  public function testAggregatedAttributes() {
    $build['#attached']['library'][] = 'common_test/js-attributes';
    $assets = AttachedAssets::createFromRenderArray($build);

    $js = $this->assetResolver->getJsAssets($assets, TRUE)[1];
    $js_render_array = \Drupal::service('asset.js.collection_renderer')->render($js);
    $rendered_js = $this->renderer->renderPlain($js_render_array);
    $expected_1 = '<script src="http://example.com/deferred-external.js" foo="bar" defer></script>';
    $expected_2 = '<script src="' . file_url_transform_relative(file_create_url('core/modules/system/tests/modules/common_test/deferred-internal.js')) . '?v=1" defer bar="foo"></script>';
    $this->assertNotIdentical(strpos($rendered_js, $expected_1), FALSE, 'Rendered external JavaScript with correct defer and random attributes.');
    $this->assertNotIdentical(strpos($rendered_js, $expected_2), FALSE, 'Rendered internal JavaScript with correct defer and random attributes.');
  }

  /**
   * Integration test for CSS/JS aggregation.
   */
  public function testAggregation() {
    $build['#attached']['library'][] = 'core/drupal.timezone';
    $build['#attached']['library'][] = 'core/drupal.vertical-tabs';
    $assets = AttachedAssets::createFromRenderArray($build);

    $this->assertEqual(1, count($this->assetResolver->getCssAssets($assets, TRUE)), 'There is a sole aggregated CSS asset.');

    list($header_js, $footer_js) = $this->assetResolver->getJsAssets($assets, TRUE);
    $this->assertEqual([], \Drupal::service('asset.js.collection_renderer')->render($header_js), 'There are 0 JavaScript assets in the header.');
    $rendered_footer_js = \Drupal::service('asset.js.collection_renderer')->render($footer_js);
    $this->assertEqual(2, count($rendered_footer_js), 'There are 2 JavaScript assets in the footer.');
    $this->assertEqual('drupal-settings-json', $rendered_footer_js[0]['#attributes']['data-drupal-selector'], 'The first of the two JavaScript assets in the footer has drupal settings.');
    $this->assertEqual(0, strpos($rendered_footer_js[1]['#attributes']['src'], base_path()), 'The second of the two JavaScript assets in the footer has the sole aggregated JavaScript asset.');
  }

  /**
   * Tests JavaScript settings.
   */
  public function testSettings() {
    $build = [];
    $build['#attached']['library'][] = 'core/drupalSettings';
    // Nonsensical value to verify if it's possible to override path settings.
    $build['#attached']['drupalSettings']['path']['pathPrefix'] = 'yarhar';
    $assets = AttachedAssets::createFromRenderArray($build);

    $js = $this->assetResolver->getJsAssets($assets, FALSE)[1];
    $js_render_array = \Drupal::service('asset.js.collection_renderer')->render($js);
    // Cast to string since this returns a \Drupal\Core\Render\Markup object.
    $rendered_js = (string) $this->renderer->renderPlain($js_render_array);

    // Parse the generated drupalSettings <script> back to a PHP representation.
    $startToken = '{';
    $endToken = '}';
    $start = strpos($rendered_js, $startToken);
    $end = strrpos($rendered_js, $endToken);
    // Convert to a string, as $renderer_js is a \Drupal\Core\Render\Markup
    // object.
    $json  = Unicode::substr($rendered_js, $start, $end - $start + 1);
    $parsed_settings = Json::decode($json);

    // Test whether the settings for core/drupalSettings are available.
    $this->assertTrue(isset($parsed_settings['path']['baseUrl']), 'drupalSettings.path.baseUrl is present.');
    $this->assertIdentical($parsed_settings['path']['pathPrefix'], 'yarhar', 'drupalSettings.path.pathPrefix is present and has the correct (overridden) value.');
    $this->assertIdentical($parsed_settings['path']['currentPath'], '', 'drupalSettings.path.currentPath is present and has the correct value.');
    $this->assertIdentical($parsed_settings['path']['currentPathIsAdmin'], FALSE, 'drupalSettings.path.currentPathIsAdmin is present and has the correct value.');
    $this->assertIdentical($parsed_settings['path']['isFront'], FALSE, 'drupalSettings.path.isFront is present and has the correct value.');
    $this->assertIdentical($parsed_settings['path']['currentLanguage'], 'en', 'drupalSettings.path.currentLanguage is present and has the correct value.');

    // Tests whether altering JavaScript settings via hook_js_settings_alter()
    // is working as expected.
    // @see common_test_js_settings_alter()
    $this->assertIdentical($parsed_settings['pluralDelimiter'], '☃');
    $this->assertIdentical($parsed_settings['foo'], 'bar');
  }

  /**
   * Tests JS assets depending on the 'core/<head>' virtual library.
   */
  public function testHeaderHTML() {
    $build['#attached']['library'][] = 'common_test/js-header';
    $assets = AttachedAssets::createFromRenderArray($build);

    $js = $this->assetResolver->getJsAssets($assets, FALSE)[0];
    $js_render_array = \Drupal::service('asset.js.collection_renderer')->render($js);
    $rendered_js = $this->renderer->renderPlain($js_render_array);
    $query_string = $this->container->get('state')->get('system.css_js_query_string') ?: '0';
    $this->assertNotIdentical(strpos($rendered_js, '<script src="' . file_url_transform_relative(file_create_url('core/modules/system/tests/modules/common_test/header.js')) . '?' . $query_string . '"></script>'), FALSE, 'The JS asset in common_test/js-header appears in the header.');
    $this->assertNotIdentical(strpos($rendered_js, '<script src="' . file_url_transform_relative(file_create_url('core/misc/drupal.js'))), FALSE, 'The JS asset of the direct dependency (core/drupal) of common_test/js-header appears in the header.');
    $this->assertNotIdentical(strpos($rendered_js, '<script src="' . file_url_transform_relative(file_create_url('core/assets/vendor/domready/ready.min.js'))), FALSE, 'The JS asset of the indirect dependency (core/domready) of common_test/js-header appears in the header.');
  }

  /**
   * Tests that for assets with cache = FALSE, Drupal sets preprocess = FALSE.
   */
  public function testNoCache() {
    $build['#attached']['library'][] = 'common_test/no-cache';
    $assets = AttachedAssets::createFromRenderArray($build);

    $js = $this->assetResolver->getJsAssets($assets, FALSE)[1];
    $this->assertFalse($js['core/modules/system/tests/modules/common_test/nocache.js']['preprocess'], 'Setting cache to FALSE sets preprocess to FALSE when adding JavaScript.');
  }

  /**
   * Tests adding JavaScript within conditional comments.
   *
   * @see \Drupal\Core\Render\Element\HtmlTag::preRenderConditionalComments()
   */
  public function testBrowserConditionalComments() {
    $default_query_string = $this->container->get('state')->get('system.css_js_query_string') ?: '0';

    $build['#attached']['library'][] = 'common_test/browsers';
    $assets = AttachedAssets::createFromRenderArray($build);

    $js = $this->assetResolver->getJsAssets($assets, FALSE)[1];
    $js_render_array = \Drupal::service('asset.js.collection_renderer')->render($js);
    $rendered_js = $this->renderer->renderPlain($js_render_array);
    $expected_1 = "<!--[if lte IE 8]>\n" . '<script src="' . file_url_transform_relative(file_create_url('core/modules/system/tests/modules/common_test/old-ie.js')) . '?' . $default_query_string . '"></script>' . "\n<![endif]-->";
    $expected_2 = "<!--[if !IE]><!-->\n" . '<script src="' . file_url_transform_relative(file_create_url('core/modules/system/tests/modules/common_test/no-ie.js')) . '?' . $default_query_string . '"></script>' . "\n<!--<![endif]-->";

    $this->assertNotIdentical(strpos($rendered_js, $expected_1), FALSE, 'Rendered JavaScript within downlevel-hidden conditional comments.');
    $this->assertNotIdentical(strpos($rendered_js, $expected_2), FALSE, 'Rendered JavaScript within downlevel-revealed conditional comments.');
  }

  /**
   * Tests JavaScript versioning.
   */
  public function testVersionQueryString() {
    $build['#attached']['library'][] = 'core/backbone';
    $build['#attached']['library'][] = 'core/domready';
    $assets = AttachedAssets::createFromRenderArray($build);

    $js = $this->assetResolver->getJsAssets($assets, FALSE)[1];
    $js_render_array = \Drupal::service('asset.js.collection_renderer')->render($js);

    $rendered_js = $this->renderer->renderPlain($js_render_array);
    $this->assertTrue(strpos($rendered_js, 'core/assets/vendor/backbone/backbone-min.js?v=1.2.3') > 0 && strpos($rendered_js, 'core/assets/vendor/domready/ready.min.js?v=1.0.8') > 0, 'JavaScript version identifiers correctly appended to URLs');
  }

  /**
   * Tests JavaScript and CSS asset ordering.
   */
  public function testRenderOrder() {
    $build['#attached']['library'][] = 'common_test/order';
    $assets = AttachedAssets::createFromRenderArray($build);

    // Construct the expected result from the regex.
    $expected_order_js = [
      "-8_1",
      "-8_2",
      "-8_3",
      "-8_4",
      // The external script.
      "-5_1",
      "-3_1",
      "-3_2",
      "0_1",
      "0_2",
      "0_3",
    ];

    // Retrieve the rendered JavaScript and test against the regex.
    $js = $this->assetResolver->getJsAssets($assets, FALSE)[1];
    $js_render_array = \Drupal::service('asset.js.collection_renderer')->render($js);
    $rendered_js = $this->renderer->renderPlain($js_render_array);
    $matches = [];
    if (preg_match_all('/weight_([-0-9]+_[0-9]+)/', $rendered_js, $matches)) {
      $result = $matches[1];
    }
    else {
      $result = [];
    }
    $this->assertIdentical($result, $expected_order_js, 'JavaScript is added in the expected weight order.');

    // Construct the expected result from the regex.
    $expected_order_css = [
      // Base.
      'base_weight_-101_1',
      'base_weight_-8_1',
      'layout_weight_-101_1',
      'base_weight_0_1',
      'base_weight_0_2',
      // Layout.
      'layout_weight_-8_1',
      'component_weight_-101_1',
      'layout_weight_0_1',
      'layout_weight_0_2',
      // Component.
      'component_weight_-8_1',
      'state_weight_-101_1',
      'component_weight_0_1',
      'component_weight_0_2',
      // State.
      'state_weight_-8_1',
      'theme_weight_-101_1',
      'state_weight_0_1',
      'state_weight_0_2',
      // Theme.
      'theme_weight_-8_1',
      'theme_weight_0_1',
      'theme_weight_0_2',
    ];

    // Retrieve the rendered CSS and test against the regex.
    $css = $this->assetResolver->getCssAssets($assets, FALSE);
    $css_render_array = \Drupal::service('asset.css.collection_renderer')->render($css);
    $rendered_css = $this->renderer->renderPlain($css_render_array);
    $matches = [];
    if (preg_match_all('/([a-z]+)_weight_([-0-9]+_[0-9]+)/', $rendered_css, $matches)) {
      $result = $matches[0];
    }
    else {
      $result = [];
    }
    $this->assertIdentical($result, $expected_order_css, 'CSS is added in the expected weight order.');
  }

  /**
   * Tests rendering the JavaScript with a file's weight above jQuery's.
   */
  public function testRenderDifferentWeight() {
    // If a library contains assets A and B, and A is listed first, then B can
    // still make itself appear first by defining a lower weight.
    $build['#attached']['library'][] = 'core/jquery';
    $build['#attached']['library'][] = 'common_test/weight';
    $assets = AttachedAssets::createFromRenderArray($build);

    $js = $this->assetResolver->getJsAssets($assets, FALSE)[1];
    $js_render_array = \Drupal::service('asset.js.collection_renderer')->render($js);
    $rendered_js = $this->renderer->renderPlain($js_render_array);
    $this->assertTrue(strpos($rendered_js, 'lighter.css') < strpos($rendered_js, 'first.js'), 'Lighter CSS assets are rendered first.');
    $this->assertTrue(strpos($rendered_js, 'lighter.js') < strpos($rendered_js, 'first.js'), 'Lighter JavaScript assets are rendered first.');
    $this->assertTrue(strpos($rendered_js, 'before-jquery.js') < strpos($rendered_js, 'core/assets/vendor/jquery/jquery.min.js'), 'Rendering a JavaScript file above jQuery.');
  }

  /**
   * Tests altering a JavaScript's weight via hook_js_alter().
   *
   * @see simpletest_js_alter()
   */
  public function testAlter() {
    // Add both tableselect.js and simpletest.js.
    $build['#attached']['library'][] = 'core/drupal.tableselect';
    $build['#attached']['library'][] = 'simpletest/drupal.simpletest';
    $assets = AttachedAssets::createFromRenderArray($build);

    // Render the JavaScript, testing if simpletest.js was altered to be before
    // tableselect.js. See simpletest_js_alter() to see where this alteration
    // takes place.
    $js = $this->assetResolver->getJsAssets($assets, FALSE)[1];
    $js_render_array = \Drupal::service('asset.js.collection_renderer')->render($js);
    $rendered_js = $this->renderer->renderPlain($js_render_array);
    $this->assertTrue(strpos($rendered_js, 'simpletest.js') < strpos($rendered_js, 'core/misc/tableselect.js'), 'Altering JavaScript weight through the alter hook.');
  }

  /**
   * Adds a JavaScript library to the page and alters it.
   *
   * @see common_test_library_info_alter()
   */
  public function testLibraryAlter() {
    // Verify that common_test altered the title of Farbtastic.
    /** @var \Drupal\Core\Asset\LibraryDiscoveryInterface $library_discovery */
    $library_discovery = \Drupal::service('library.discovery');
    $library = $library_discovery->getLibraryByName('core', 'jquery.farbtastic');
    $this->assertEqual($library['version'], '0.0', 'Registered libraries were altered.');

    // common_test_library_info_alter() also added a dependency on jQuery Form.
    $build['#attached']['library'][] = 'core/jquery.farbtastic';
    $assets = AttachedAssets::createFromRenderArray($build);
    $js = $this->assetResolver->getJsAssets($assets, FALSE)[1];
    $js_render_array = \Drupal::service('asset.js.collection_renderer')->render($js);
    $rendered_js = $this->renderer->renderPlain($js_render_array);
    $this->assertTrue(strpos($rendered_js, 'core/assets/vendor/jquery-form/jquery.form.min.js'), 'Altered library dependencies are added to the page.');
  }

  /**
   * Dynamically defines an asset library and alters it.
   */
  public function testDynamicLibrary() {
    /** @var \Drupal\Core\Asset\LibraryDiscoveryInterface $library_discovery */
    $library_discovery = \Drupal::service('library.discovery');
    // Retrieve a dynamic library definition.
    // @see common_test_library_info_build()
    \Drupal::state()->set('common_test.library_info_build_test', TRUE);
    $library_discovery->clearCachedDefinitions();
    $dynamic_library = $library_discovery->getLibraryByName('common_test', 'dynamic_library');
    $this->assertTrue(is_array($dynamic_library));
    if ($this->assertTrue(isset($dynamic_library['version']))) {
      $this->assertSame('1.0', $dynamic_library['version']);
    }
    // Make sure the dynamic library definition could be altered.
    // @see common_test_library_info_alter()
    if ($this->assertTrue(isset($dynamic_library['dependencies']))) {
      $this->assertSame(['core/jquery'], $dynamic_library['dependencies']);
    }
  }

  /**
   * Tests that multiple modules can implement libraries with the same name.
   *
   * @see common_test.library.yml
   */
  public function testLibraryNameConflicts() {
    /** @var \Drupal\Core\Asset\LibraryDiscoveryInterface $library_discovery */
    $library_discovery = \Drupal::service('library.discovery');
    $farbtastic = $library_discovery->getLibraryByName('common_test', 'jquery.farbtastic');
    $this->assertEqual($farbtastic['version'], '0.1', 'Alternative libraries can be added to the page.');
  }

  /**
   * Tests JavaScript files that have querystrings attached get added right.
   */
  public function testAddJsFileWithQueryString() {
    $build['#attached']['library'][] = 'common_test/querystring';
    $assets = AttachedAssets::createFromRenderArray($build);

    $css = $this->assetResolver->getCssAssets($assets, FALSE);
    $js = $this->assetResolver->getJsAssets($assets, FALSE)[1];
    $this->assertTrue(array_key_exists('core/modules/system/tests/modules/common_test/querystring.css?arg1=value1&arg2=value2', $css), 'CSS file with query string is correctly added.');
    $this->assertTrue(array_key_exists('core/modules/system/tests/modules/common_test/querystring.js?arg1=value1&arg2=value2', $js), 'JavaScript file with query string is correctly added.');

    $css_render_array = \Drupal::service('asset.css.collection_renderer')->render($css);
    $rendered_css = $this->renderer->renderPlain($css_render_array);
    $js_render_array = \Drupal::service('asset.js.collection_renderer')->render($js);
    $rendered_js = $this->renderer->renderPlain($js_render_array);
    $query_string = $this->container->get('state')->get('system.css_js_query_string') ?: '0';
    $this->assertNotIdentical(strpos($rendered_css, '<link rel="stylesheet" href="' . str_replace('&', '&amp;', file_url_transform_relative(file_create_url('core/modules/system/tests/modules/common_test/querystring.css?arg1=value1&arg2=value2'))) . '&amp;' . $query_string . '" media="all" />'), FALSE, 'CSS file with query string gets version query string correctly appended..');
    $this->assertNotIdentical(strpos($rendered_js, '<script src="' . str_replace('&', '&amp;', file_url_transform_relative(file_create_url('core/modules/system/tests/modules/common_test/querystring.js?arg1=value1&arg2=value2'))) . '&amp;' . $query_string . '"></script>'), FALSE, 'JavaScript file with query string gets version query string correctly appended.');
  }

}
