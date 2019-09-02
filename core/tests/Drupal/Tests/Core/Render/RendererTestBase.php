<?php

/**
 * @file
 * Contains \Drupal\Tests\Core\Render\RendererTestBase.
 */

namespace Drupal\Tests\Core\Render;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Cache\Context\ContextCacheKeys;
use Drupal\Core\Cache\MemoryBackend;
use Drupal\Core\Security\TrustedCallbackInterface;
use Drupal\Core\Render\PlaceholderGenerator;
use Drupal\Core\Render\PlaceholderingRenderCache;
use Drupal\Core\Render\Renderer;
use Drupal\Tests\UnitTestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Base class for the actual unit tests testing \Drupal\Core\Render\Renderer.
 */
abstract class RendererTestBase extends UnitTestCase {

  /**
   * The tested renderer.
   *
   * @var \Drupal\Core\Render\Renderer
   */
  protected $renderer;

  /**
   * The tested render cache.
   *
   * @var \Drupal\Core\Render\PlaceholderingRenderCache
   */
  protected $renderCache;

  /**
   * The tested placeholder generator.
   *
   * @var \Drupal\Core\Render\PlaceholderGenerator
   */
  protected $placeholderGenerator;

  /**
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * @var \Drupal\Core\Cache\CacheFactoryInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $cacheFactory;

  /**
   * @var \Drupal\Core\Cache\Context\CacheContextsManager|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $cacheContexts;

  /**
   * The mocked controller resolver.
   *
   * @var \Drupal\Core\Controller\ControllerResolverInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $controllerResolver;

  /**
   * The mocked theme manager.
   *
   * @var \Drupal\Core\Theme\ThemeManagerInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $themeManager;

  /**
   * The mocked element info.
   *
   * @var \Drupal\Core\Render\ElementInfoManagerInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $elementInfo;

  /**
   * @var \Drupal\Core\Cache\CacheBackendInterface
   */
  protected $memoryCache;

  /**
   * The simulated "current" user role, for use in tests with cache contexts.
   *
   * @var string
   */
  protected $currentUserRole;

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
    'auto_placeholder_conditions' => [
      'max-age' => 0,
      'contexts' => ['session', 'user'],
      'tags' => ['current-temperature'],
    ],
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->controllerResolver = $this->createMock('Drupal\Core\Controller\ControllerResolverInterface');
    $this->themeManager = $this->createMock('Drupal\Core\Theme\ThemeManagerInterface');
    $this->elementInfo = $this->createMock('Drupal\Core\Render\ElementInfoManagerInterface');
    $this->elementInfo->expects($this->any())
      ->method('getInfo')
      ->willReturnCallback(function ($type) {
        switch ($type) {
          case 'details':
            $info = ['#theme_wrappers' => ['details']];
            break;
          case 'link':
            $info = ['#theme' => 'link'];
            break;
          default:
            $info = [];
        }
        $info['#defaults_loaded'] = TRUE;
        return $info;
      });
    $this->requestStack = new RequestStack();
    $request = new Request();
    $request->server->set('REQUEST_TIME', $_SERVER['REQUEST_TIME']);
    $this->requestStack->push($request);
    $this->cacheFactory = $this->createMock('Drupal\Core\Cache\CacheFactoryInterface');
    $this->cacheContextsManager = $this->getMockBuilder('Drupal\Core\Cache\Context\CacheContextsManager')
      ->disableOriginalConstructor()
      ->getMock();
    $this->cacheContextsManager->method('assertValidTokens')->willReturn(TRUE);
    $current_user_role = &$this->currentUserRole;
    $this->cacheContextsManager->expects($this->any())
      ->method('convertTokensToKeys')
      ->willReturnCallback(function ($context_tokens) use (&$current_user_role) {
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
        return new ContextCacheKeys($keys, new CacheableMetadata());
      });
    $this->placeholderGenerator = new PlaceholderGenerator($this->rendererConfig);
    $this->renderCache = new PlaceholderingRenderCache($this->requestStack, $this->cacheFactory, $this->cacheContextsManager, $this->placeholderGenerator);
    $this->renderer = new Renderer($this->controllerResolver, $this->themeManager, $this->elementInfo, $this->placeholderGenerator, $this->renderCache, $this->requestStack, $this->rendererConfig);

    $container = new ContainerBuilder();
    $container->set('cache_contexts_manager', $this->cacheContextsManager);
    $container->set('render_cache', $this->renderCache);
    $container->set('renderer', $this->renderer);
    \Drupal::setContainer($container);
  }

  /**
   * Generates a random context value for the placeholder tests.
   *
   * The #context array used by the placeholder #lazy_builder callback will
   * generally be used to provide metadata like entity IDs, field machine names,
   * paths, etc. for JavaScript replacement of content or assets. In this test,
   * the #lazy_builder callback PlaceholdersTest::callback() renders the context
   * inside test HTML, so using any random string would sometimes cause random
   * test failures because the test output would be unparseable. Instead, we
   * provide random tokens for replacement.
   *
   * @see PlaceholdersTest::callback()
   * @see https://www.drupal.org/node/2151609
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
    $this->memoryCache = $this->memoryCache ?: new MemoryBackend();

    $this->cacheFactory->expects($this->atLeastOnce())
      ->method('get')
      ->with('render')
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
   * @param string $bin
   *   The expected cache bin.
   */
  protected function assertRenderCacheItem($cid, $data, $bin = 'render') {
    $cache_backend = $this->cacheFactory->get($bin);
    $cached = $cache_backend->get($cid);
    $this->assertNotFalse($cached, sprintf('Expected cache item "%s" exists.', $cid));
    if ($cached !== FALSE) {
      $this->assertEquals($data, $cached->data, sprintf('Cache item "%s" has the expected data.', $cid));
      $this->assertSame(Cache::mergeTags($data['#cache']['tags'], ['rendered']), $cached->tags, "The cache item's cache tags also has the 'rendered' cache tag.");
    }
  }

}


class PlaceholdersTest implements TrustedCallbackInterface {

  /**
   * #lazy_builder callback; attaches setting, generates markup.
   *
   * @param string $animal
   *   An animal.
   *
   * @return array
   *   A renderable array.
   */
  public static function callback($animal, $use_animal_as_array_key = FALSE) {
    $value = $animal;
    if ($use_animal_as_array_key) {
      $value = [$animal => TRUE];
    }
    return [
      '#markup' => '<p>This is a rendered placeholder!</p>',
      '#attached' => [
        'drupalSettings' => [
          'dynamic_animal' => $value,
        ],
      ],
    ];
  }

  /**
   * #lazy_builder callback; attaches setting, generates markup, user-specific.
   *
   * @param string $animal
   *   An animal.
   *
   * @return array
   *   A renderable array.
   */
  public static function callbackPerUser($animal) {
    $build = static::callback($animal);
    $build['#cache']['contexts'][] = 'user';
    return $build;
  }

  /**
   * #lazy_builder callback; attaches setting, generates markup, cache tag.
   *
   * @param string $animal
   *   An animal.
   *
   * @return array
   *   A renderable array.
   */
  public static function callbackTagCurrentTemperature($animal) {
    $build = static::callback($animal);
    $build['#cache']['tags'][] = 'current-temperature';
    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public static function trustedCallbacks() {
    return ['callbackTagCurrentTemperature', 'callbackPerUser', 'callback'];
  }

}
