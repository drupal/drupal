<?php

namespace Drupal\Tests\system\Kernel\Common;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Language\Language;
use Drupal\Core\Link;
use Drupal\Core\Render\RenderContext;
use Drupal\Core\Url;
use Drupal\KernelTests\KernelTestBase;

/**
 * Confirm that \Drupal\Core\Url,
 * \Drupal\Component\Utility\UrlHelper::filterQueryParameters(),
 * \Drupal\Component\Utility\UrlHelper::buildQuery(), and
 * \Drupal\Core\Utility\LinkGeneratorInterface::generate()
 * work correctly with various input.
 *
 * @group Common
 */
class UrlTest extends KernelTestBase {

  protected static $modules = ['common_test', 'url_alter_test'];

  /**
   * Confirms that invalid URLs are filtered in link generating functions.
   */
  public function testLinkXSS() {
    // Test link generator.
    $text = $this->randomMachineName();
    $path = "<SCRIPT>alert('XSS')</SCRIPT>";
    $encoded_path = "3CSCRIPT%3Ealert%28%27XSS%27%29%3C/SCRIPT%3E";

    $link = Link::fromTextAndUrl($text, Url::fromUserInput('/' . $path))->toString();
    $this->assertStringContainsString($encoded_path, $link, new FormattableMarkup('XSS attack @path was filtered by \Drupal\Core\Utility\LinkGeneratorInterface::generate().', ['@path' => $path]));
    $this->assertStringNotContainsString($path, $link, new FormattableMarkup('XSS attack @path was filtered by \Drupal\Core\Utility\LinkGeneratorInterface::generate().', ['@path' => $path]));

    // Test \Drupal\Core\Url.
    $link = Url::fromUri('base:' . $path)->toString();
    $this->assertStringContainsString($encoded_path, $link, new FormattableMarkup('XSS attack @path was filtered by #theme', ['@path' => $path]));
    $this->assertStringNotContainsString($path, $link, new FormattableMarkup('XSS attack @path was filtered by #theme', ['@path' => $path]));
  }

  /**
   * Tests that #type=link bubbles outbound route/path processors' metadata.
   */
  public function testLinkBubbleableMetadata() {
    \Drupal::service('module_installer')->install(['user']);

    $cases = [
      ['Regular link', 'internal:/user', [], ['contexts' => [], 'tags' => [], 'max-age' => Cache::PERMANENT], []],
      ['Regular link, absolute', 'internal:/user', ['absolute' => TRUE], ['contexts' => ['url.site'], 'tags' => [], 'max-age' => Cache::PERMANENT], []],
      ['Route processor link', 'route:system.run_cron', [], ['contexts' => ['session'], 'tags' => [], 'max-age' => Cache::PERMANENT], ['placeholders' => []]],
      ['Route processor link, absolute', 'route:system.run_cron', ['absolute' => TRUE], ['contexts' => ['url.site', 'session'], 'tags' => [], 'max-age' => Cache::PERMANENT], ['placeholders' => []]],
      ['Path processor link', 'internal:/user/1', [], ['contexts' => [], 'tags' => ['user:1'], 'max-age' => Cache::PERMANENT], []],
      ['Path processor link, absolute', 'internal:/user/1', ['absolute' => TRUE], ['contexts' => ['url.site'], 'tags' => ['user:1'], 'max-age' => Cache::PERMANENT], []],
    ];

    foreach ($cases as $case) {
      list($title, $uri, $options, $expected_cacheability, $expected_attachments) = $case;
      $expected_cacheability['contexts'] = Cache::mergeContexts($expected_cacheability['contexts'], ['languages:language_interface', 'theme', 'user.permissions']);
      $link = [
        '#type' => 'link',
        '#title' => $title,
        '#options' => $options,
        '#url' => Url::fromUri($uri),
      ];
      \Drupal::service('renderer')->renderRoot($link);
      $this->assertEquals($expected_cacheability, $link['#cache']);
      $this->assertEquals($expected_attachments, $link['#attached']);
    }
  }

  /**
   * Tests that default and custom attributes are handled correctly on links.
   */
  public function testLinkAttributes() {
    /** @var \Drupal\Core\Render\RendererInterface $renderer */
    $renderer = $this->container->get('renderer');

    // Test that hreflang is added when a link has a known language.
    $language = new Language(['id' => 'fr', 'name' => 'French']);
    $hreflang_link = [
      '#type' => 'link',
      '#options' => [
        'language' => $language,
      ],
      '#url' => Url::fromUri('https://www.drupal.org'),
      '#title' => 'bar',
    ];
    $langcode = $language->getId();

    // Test that the default hreflang handling for links does not override a
    // hreflang attribute explicitly set in the render array.
    $hreflang_override_link = $hreflang_link;
    $hreflang_override_link['#options']['attributes']['hreflang'] = 'foo';

    $rendered = $renderer->renderRoot($hreflang_link);
    $this->assertTrue($this->hasAttribute('hreflang', $rendered, $langcode), new FormattableMarkup('hreflang attribute with value @langcode is present on a rendered link when langcode is provided in the render array.', ['@langcode' => $langcode]));

    $rendered = $renderer->renderRoot($hreflang_override_link);
    $this->assertTrue($this->hasAttribute('hreflang', $rendered, 'foo'), new FormattableMarkup('hreflang attribute with value @hreflang is present on a rendered link when @hreflang is provided in the render array.', ['@hreflang' => 'foo']));

    // Test adding a custom class in links produced by
    // \Drupal\Core\Utility\LinkGeneratorInterface::generate() and #type 'link'.
    // Test the link generator.
    $class_l = $this->randomMachineName();
    $link_l = Link::fromTextAndUrl($this->randomMachineName(), Url::fromRoute('common_test.destination', [], ['attributes' => ['class' => [$class_l]]]))->toString();
    $this->assertTrue($this->hasAttribute('class', $link_l, $class_l), new FormattableMarkup('Custom class @class is present on link when requested by Link::toString()', ['@class' => $class_l]));

    // Test #type.
    $class_theme = $this->randomMachineName();
    $type_link = [
      '#type' => 'link',
      '#title' => $this->randomMachineName(),
      '#url' => Url::fromRoute('common_test.destination'),
      '#options' => [
        'attributes' => [
          'class' => [$class_theme],
        ],
      ],
    ];
    $link_theme = $renderer->renderRoot($type_link);
    $this->assertTrue($this->hasAttribute('class', $link_theme, $class_theme), new FormattableMarkup('Custom class @class is present on link when requested by #type', ['@class' => $class_theme]));
  }

  /**
   * Tests that link functions support render arrays as 'text'.
   */
  public function testLinkRenderArrayText() {
    /** @var \Drupal\Core\Render\RendererInterface $renderer */
    $renderer = $this->container->get('renderer');

    // Build a link with the link generator for reference.
    $l = Link::fromTextAndUrl('foo', Url::fromUri('https://www.drupal.org'))->toString();

    // Test a renderable array passed to the link generator.
    $renderer->executeInRenderContext(new RenderContext(), function () use ($renderer, $l) {
      $renderable_text = ['#markup' => 'foo'];
      $l_renderable_text = \Drupal::service('link_generator')->generate($renderable_text, Url::fromUri('https://www.drupal.org'));
      $this->assertEquals($l, $l_renderable_text);
    });

    // Test a themed link with plain text 'text'.
    $type_link_plain_array = [
      '#type' => 'link',
      '#title' => 'foo',
      '#url' => Url::fromUri('https://www.drupal.org'),
    ];
    $type_link_plain = $renderer->renderRoot($type_link_plain_array);
    $this->assertEquals($l, $type_link_plain);

    // Build a themed link with renderable 'text'.
    $type_link_nested_array = [
      '#type' => 'link',
      '#title' => ['#markup' => 'foo'],
      '#url' => Url::fromUri('https://www.drupal.org'),
    ];
    $type_link_nested = $renderer->renderRoot($type_link_nested_array);
    $this->assertEquals($l, $type_link_nested);
  }

  /**
   * Checks for class existence in link.
   *
   * @param $attribute
   * @param $link
   *   URL to search.
   * @param $class
   *   Element class to search for.
   *
   * @return bool
   *   TRUE if the class is found, FALSE otherwise.
   */
  private function hasAttribute($attribute, $link, $class) {
    return (bool) preg_match('|' . $attribute . '="([^\"\s]+\s+)*' . $class . '|', $link);
  }

  /**
   * Tests UrlHelper::filterQueryParameters().
   */
  public function testDrupalGetQueryParameters() {
    $original = [
      'a' => 1,
      'b' => [
        'd' => 4,
        'e' => [
          'f' => 5,
        ],
      ],
      'c' => 3,
    ];

    // First-level exclusion.
    $result = $original;
    unset($result['b']);
    $this->assertEquals(UrlHelper::filterQueryParameters($original, ['b']), $result, "'b' was removed.");

    // Second-level exclusion.
    $result = $original;
    unset($result['b']['d']);
    $this->assertEquals(UrlHelper::filterQueryParameters($original, ['b[d]']), $result, "'b[d]' was removed.");

    // Third-level exclusion.
    $result = $original;
    unset($result['b']['e']['f']);
    $this->assertEquals(UrlHelper::filterQueryParameters($original, ['b[e][f]']), $result, "'b[e][f]' was removed.");

    // Multiple exclusions.
    $result = $original;
    unset($result['a'], $result['b']['e'], $result['c']);
    $this->assertEquals(UrlHelper::filterQueryParameters($original, ['a', 'b[e]', 'c']), $result, "'a', 'b[e]', 'c' were removed.");
  }

  /**
   * Tests UrlHelper::parse().
   */
  public function testDrupalParseUrl() {
    // Relative, absolute, and external URLs, without/with explicit script path,
    // without/with Drupal path.
    foreach (['', '/', 'https://www.drupal.org/'] as $absolute) {
      foreach (['', 'index.php/'] as $script) {
        foreach (['', 'foo/bar'] as $path) {
          $url = $absolute . $script . $path . '?foo=bar&bar=baz&baz#foo';
          $expected = [
            'path' => $absolute . $script . $path,
            'query' => ['foo' => 'bar', 'bar' => 'baz', 'baz' => ''],
            'fragment' => 'foo',
          ];
          $this->assertEquals($expected, UrlHelper::parse($url), 'URL parsed correctly.');
        }
      }
    }

    // Relative URL that is known to confuse parse_url().
    $url = 'foo/bar:1';
    $result = [
      'path' => 'foo/bar:1',
      'query' => [],
      'fragment' => '',
    ];
    $this->assertEquals($result, UrlHelper::parse($url), 'Relative URL parsed correctly.');

    // Test that drupal can recognize an absolute URL. Used to prevent attack vectors.
    $url = 'https://www.drupal.org/foo/bar?foo=bar&bar=baz&baz#foo';
    $this->assertTrue(UrlHelper::isExternal($url), 'Correctly identified an external URL.');

    // Test that UrlHelper::parse() does not allow spoofing a URL to force a malicious redirect.
    $parts = UrlHelper::parse('forged:http://cwe.mitre.org/data/definitions/601.html');
    $this->assertFalse(UrlHelper::isValid($parts['path'], TRUE), '\Drupal\Component\Utility\UrlHelper::isValid() correctly parsed a forged URL.');
  }

  /**
   * Tests external URL handling.
   */
  public function testExternalUrls() {
    $test_url = 'https://www.drupal.org/';

    // Verify external URL can contain a fragment.
    $url = $test_url . '#drupal';
    $result = Url::fromUri($url)->toString();
    $this->assertEquals($url, $result, 'External URL with fragment works without a fragment in $options.');

    // Verify fragment can be overridden in an external URL.
    $url = $test_url . '#drupal';
    $fragment = $this->randomMachineName(10);
    $result = Url::fromUri($url, ['fragment' => $fragment])->toString();
    $this->assertEquals($test_url . '#' . $fragment, $result, 'External URL fragment is overridden with a custom fragment in $options.');

    // Verify external URL can contain a query string.
    $url = $test_url . '?drupal=awesome';
    $result = Url::fromUri($url)->toString();
    $this->assertEquals($url, $result);

    // Verify external URL can contain a query string with an integer key.
    $url = $test_url . '?120=1';
    $result = Url::fromUri($url)->toString();
    $this->assertEquals($url, $result);

    // Verify external URL can be extended with a query string.
    $url = $test_url;
    $query = ['awesome' => 'drupal'];
    $result = Url::fromUri($url, ['query' => $query])->toString();
    $this->assertSame('https://www.drupal.org/?awesome=drupal', $result);

    // Verify query string can be extended in an external URL.
    $url = $test_url . '?drupal=awesome';
    $query = ['awesome' => 'drupal'];
    $result = Url::fromUri($url, ['query' => $query])->toString();
    $this->assertEquals('https://www.drupal.org/?drupal=awesome&awesome=drupal', $result);
  }

}
