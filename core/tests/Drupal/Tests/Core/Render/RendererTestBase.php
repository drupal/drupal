<?php

/**
 * @file
 * Contains \Drupal\Tests\Core\Render\RendererTestBase.
 */

namespace Drupal\Tests\Core\Render;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Cache\MemoryBackend;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Render\Element;
use Drupal\Core\Render\Renderer;
use Drupal\Tests\UnitTestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Base class for the actual unit tests testing \Drupal\Core\Render\Renderer.
 */
class RendererTestBase extends UnitTestCase {

  /**
   * The tested renderer.
   *
   * @var \Drupal\Core\Render\Renderer
   */
  protected $renderer;

  /**
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * @var \Drupal\Core\Cache\CacheFactoryInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $cacheFactory;

  /**
   * @var \Drupal\Core\Cache\CacheContextsManager|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $cacheContexts;

  /**
   * The mocked controller resolver.
   *
   * @var \Drupal\Core\Controller\ControllerResolverInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $controllerResolver;

  /**
   * The mocked theme manager.
   *
   * @var \Drupal\Core\Theme\ThemeManagerInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $themeManager;

  /**
   * The mocked element info.
   *
   * @var \Drupal\Core\Render\ElementInfoManagerInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $elementInfo;

  /**
   * @var \Drupal\Core\Cache\CacheBackendInterface
   */
  protected $memoryCache;

  /**
   * The mocked renderer configuration.
   *
   * @var array
   */
  protected $rendererConfig = [
    'required_cache_contexts' => [
      'languages:language_interface',
      'theme',
    ],
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->controllerResolver = $this->getMock('Drupal\Core\Controller\ControllerResolverInterface');
    $this->themeManager = $this->getMock('Drupal\Core\Theme\ThemeManagerInterface');
    $this->elementInfo = $this->getMock('Drupal\Core\Render\ElementInfoManagerInterface');
    $this->requestStack = new RequestStack();
    $this->cacheFactory = $this->getMock('Drupal\Core\Cache\CacheFactoryInterface');
    $this->cacheContextsManager = $this->getMockBuilder('Drupal\Core\Cache\CacheContextsManager')
      ->disableOriginalConstructor()
      ->getMock();
    $this->cacheContextsManager->expects($this->any())
      ->method('convertTokensToKeys')
      ->willReturnCallback(function($context_tokens) {
        global $current_user_role;
        $keys = [];
        foreach ($context_tokens as $context_id) {
          switch ($context_id) {
            case 'user.roles':
              $keys[] = 'r.' . $current_user_role;
              break;
            case 'languages:language_interface':
              $keys[] = 'en';
              break;
            case 'theme':
              $keys[] = 'stark';
              break;
            default:
              $keys[] = $context_id;
          }
        }
        return $keys;
      });
    $this->renderer = new Renderer($this->controllerResolver, $this->themeManager, $this->elementInfo, $this->requestStack, $this->cacheFactory, $this->cacheContextsManager, $this->rendererConfig);

    $container = new ContainerBuilder();
    $container->set('cache_contexts_manager', $this->cacheContextsManager);
    $container->set('renderer', $this->renderer);
    \Drupal::setContainer($container);
  }

  /**
   * Generates a random context value for the post-render cache tests.
   *
   * The #context array used by the post-render cache callback will generally
   * be used to provide metadata like entity IDs, field machine names, paths,
   * etc. for JavaScript replacement of content or assets. In this test, the
   * callbacks PostRenderCache::callback() and PostRenderCache::placeholder()
   * render the context inside test HTML, so using any random string would
   * sometimes cause random test failures because the test output would be
   * unparseable. Instead, we provide random tokens for replacement.
   *
   * @see PostRenderCache::callback()
   * @see PostRenderCache::placeholder()
   * @see https://drupal.org/node/2151609
   */
  protected function randomContextValue() {
    $tokens = ['llama', 'alpaca', 'camel', 'moose', 'elk'];
    return $tokens[mt_rand(0, 4)];
  }

  /**
   * Sets up a render cache back-end that is asserted to be never used.
   */
  protected function setUpUnusedCache() {
    $this->cacheFactory->expects($this->never())
      ->method('get');
  }

  /**
   * Sets up a memory-based render cache back-end.
   */
  protected function setupMemoryCache() {
    $this->memoryCache = $this->memoryCache ?: new MemoryBackend('render');

    $this->cacheFactory->expects($this->atLeastOnce())
      ->method('get')
      ->willReturn($this->memoryCache);
  }

  /**
   * Sets up a request object on the request stack.
   *
   * @param string $method
   *   The HTTP method to use for the request. Defaults to 'GET'.
   */
  protected function setUpRequest($method = 'GET') {
    $request = Request::create('/', $method);
    // Ensure that the request time is set as expected.
    $request->server->set('REQUEST_TIME', (int) $_SERVER['REQUEST_TIME']);
    $this->requestStack->push($request);
  }

  /**
   * Asserts a render cache item.
   *
   * @param string $cid
   *   The expected cache ID.
   * @param mixed $data
   *   The expected data for that cache ID.
   */
  protected function assertRenderCacheItem($cid, $data) {
    $cached = $this->memoryCache->get($cid);
    $this->assertNotFalse($cached, sprintf('Expected cache item "%s" exists.', $cid));
    if ($cached !== FALSE) {
      $this->assertEquals($data, $cached->data, sprintf('Cache item "%s" has the expected data.', $cid));
      $this->assertSame(Cache::mergeTags($data['#cache']['tags'], ['rendered']), $cached->tags, "The cache item's cache tags also has the 'rendered' cache tag.");
    }
  }

}


class PostRenderCache {

  /**
   * #post_render_cache callback; modifies #markup, #attached and #context_test.
   *
   * @param array $element
   *  A render array with the following keys:
   *    - #markup
   *    - #attached
   * @param array $context
   *  An array with the following keys:
   *    - foo: contains a random string.
   *
   * @return array $element
   *   The updated $element.
   */
  public static function callback(array $element, array $context) {
    // Override #markup.
    $element['#markup'] = '<p>overridden</p>';

    // Extend #attached.
    if (!isset($element['#attached']['drupalSettings']['common_test'])) {
      $element['#attached']['drupalSettings']['common_test'] = [];
    }
    $element['#attached']['drupalSettings']['common_test'] += $context;

    // Set new property.
    $element['#context_test'] = $context;

    return $element;
  }

  /**
   * #post_render_cache callback; replaces placeholder, extends #attached.
   *
   * @param array $element
   *   The renderable array that contains the to be replaced placeholder.
   * @param array $context
   *  An array with the following keys:
   *    - bar: contains a random string.
   *
   * @return array
   *   A render array.
   */
  public static function placeholder(array $element, array $context) {
    $placeholder = \Drupal::service('renderer')->generateCachePlaceholder(__NAMESPACE__ . '\\PostRenderCache::placeholder', $context);
    $replace_element = array(
      '#markup' => '<bar>' . $context['bar'] . '</bar>',
      '#attached' => array(
        'drupalSettings' => [
          'common_test' => $context,
        ],
      ),
    );
    $markup = \Drupal::service('renderer')->render($replace_element);
    $element['#markup'] = str_replace($placeholder, $markup, $element['#markup']);

    return $element;
  }

}
