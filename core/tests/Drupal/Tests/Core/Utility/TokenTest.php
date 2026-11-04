<?php

declare(strict_types=1);

namespace Drupal\Tests\Core\Utility;

use Drupal\Component\Utility\Html;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Cache\CacheTagsInvalidatorInterface;
use Drupal\Core\Cache\Context\CacheContextsManager;
use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Render\BubbleableMetadata;
use Drupal\Core\Render\Markup;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\Utility\Token;
use Drupal\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\MockObject\Stub;

/**
 * Tests Drupal\Core\Utility\Token.
 */
#[CoversClass(Token::class)]
#[Group('Utility')]
class TokenTest extends UnitTestCase {

  /**
   * The cache used for testing.
   */
  protected CacheBackendInterface&Stub $cache;

  /**
   * The language manager used for testing.
   */
  protected LanguageManagerInterface&Stub $languageManager;

  /**
   * The module handler service used for testing.
   */
  protected ModuleHandlerInterface&Stub $moduleHandler;

  /**
   * The language interface used for testing.
   */
  protected LanguageInterface&Stub $language;

  /**
   * The token service under test.
   *
   * @var \Drupal\Core\Utility\Token|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $token;

  /**
   * The cache tags invalidator.
   */
  protected CacheTagsInvalidatorInterface&Stub $cacheTagsInvalidator;

  /**
   * The cache contexts manager.
   *
   * @var \Drupal\Core\Cache\Context\CacheContextsManager
   */
  protected $cacheContextManager;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->cache = $this->createStub(CacheBackendInterface::class);

    $this->languageManager = $this->createStub(LanguageManagerInterface::class);

    $this->moduleHandler = $this->createStub(ModuleHandlerInterface::class);

    $this->language = $this->createStub(LanguageInterface::class);

    $this->cacheTagsInvalidator = $this->createStub(CacheTagsInvalidatorInterface::class);

    $this->token = new Token($this->moduleHandler, $this->cache, $this->languageManager, $this->cacheTagsInvalidator, $this->createStub(RendererInterface::class));

    $container = new ContainerBuilder();
    $this->cacheContextManager = new CacheContextsManager($container, [
      'current_user',
      'custom_context',
    ]);
    $container->set('cache_contexts_manager', $this->cacheContextManager);
    \Drupal::setContainer($container);
  }

  /**
   * Reinitializes the cache as a mock object.
   */
  protected function setUpMockCache(): void {
    $this->cache = $this->createMock(CacheBackendInterface::class);
    $reflection = new \ReflectionProperty($this->token, 'cache');
    $reflection->setValue($this->token, $this->cache);
  }

  /**
   * Reinitializes the cache tags invalidator as a mock object.
   */
  protected function setUpMockCacheTagsInvalidator(): void {
    $this->cacheTagsInvalidator = $this->createMock(CacheTagsInvalidatorInterface::class);
    $reflection = new \ReflectionProperty($this->token, 'cacheTagsInvalidator');
    $reflection->setValue($this->token, $this->cacheTagsInvalidator);
  }

  /**
   * Reinitializes the language manager as a mock object.
   */
  protected function setUpMockLanguageManager(): void {
    $this->languageManager = $this->createMock(LanguageManagerInterface::class);
    $reflection = new \ReflectionProperty($this->token, 'languageManager');
    $reflection->setValue($this->token, $this->languageManager);
  }

  /**
   * Reinitializes the module handler as a mock object.
   */
  protected function setUpMockModuleHandler(): void {
    $this->moduleHandler = $this->createMock(ModuleHandlerInterface::class);
    $reflection = new \ReflectionProperty($this->token, 'moduleHandler');
    $reflection->setValue($this->token, $this->moduleHandler);
  }

  /**
   * Tests get info.
   */
  public function testGetInfo(): void {
    $this->setUpMockCache();
    $this->setUpMockLanguageManager();
    $this->setUpMockModuleHandler();

    $token_info = [
      'types' => [
        'foo' => [
          'name' => $this->randomMachineName(),
        ],
      ],
    ];

    $this->language = $this->createMock(LanguageInterface::class);
    $this->language->expects($this->atLeastOnce())
      ->method('getId')
      ->willReturn($this->randomMachineName());

    $this->languageManager->expects($this->once())
      ->method('getCurrentLanguage')
      ->with(LanguageInterface::TYPE_CONTENT)
      ->willReturn($this->language);

    // The persistent cache must only be hit once, after which the info is
    // cached statically.
    $this->cache->expects($this->once())
      ->method('get');
    $this->cache->expects($this->once())
      ->method('set')
      ->with('token_info:' . $this->language->getId(), $token_info);

    $this->moduleHandler->expects($this->once())
      ->method('invokeAll')
      ->with('token_info')
      ->willReturn($token_info);
    $this->moduleHandler->expects($this->once())
      ->method('alter')
      ->with('token_info', $token_info);

    // Get the information for the first time. The cache should be checked, the
    // hooks invoked, and the info should be set to the cache should.
    $this->token->getInfo();
    // Get the information for the second time. The data must be returned from
    // the static cache, so the persistent cache must not be accessed and the
    // hooks must not be invoked.
    $this->token->getInfo();
  }

  /**
   * Tests replace with bubbleable metadata object.
   */
  public function testReplaceWithBubbleableMetadataObject(): void {
    $this->moduleHandler
      ->method('invokeAll')
      ->willReturn(['[node:title]' => 'hello world']);

    $bubbleable_metadata = new BubbleableMetadata();
    $bubbleable_metadata->setCacheContexts(['current_user']);
    $bubbleable_metadata->setCacheMaxAge(12);

    $node = $this->prophesize('Drupal\node\NodeInterface');
    $node->getCacheTags()->willReturn(['node:1']);
    $node->getCacheContexts()->willReturn(['custom_context']);
    $node->getCacheMaxAge()->willReturn(10);
    $node = $node->reveal();

    $result = $this->token->replace('[node:title]', ['node' => $node], [], $bubbleable_metadata);
    $this->assertEquals('hello world', $result);

    $this->assertEquals(['node:1'], $bubbleable_metadata->getCacheTags());
    $this->assertEquals([
      'current_user',
      'custom_context',
    ], $bubbleable_metadata->getCacheContexts());
    $this->assertEquals(10, $bubbleable_metadata->getCacheMaxAge());
  }

  /**
   * Tests replace with hook tokens with bubbleable metadata.
   */
  public function testReplaceWithHookTokensWithBubbleableMetadata(): void {
    $this->moduleHandler
      ->method('invokeAll')
      ->willReturnCallback(function ($hook_name, $args): array {
        $cacheable_metadata = $args[4];
        $cacheable_metadata->addCacheContexts(['custom_context']);
        $cacheable_metadata->addCacheTags(['node:1']);
        $cacheable_metadata->setCacheMaxAge(10);

        return ['[node:title]' => 'hello world'];
      });

    $node = $this->prophesize('Drupal\node\NodeInterface');
    $node->getCacheContexts()->willReturn([]);
    $node->getCacheTags()->willReturn([]);
    $node->getCacheMaxAge()->willReturn(14);
    $node = $node->reveal();

    $bubbleable_metadata = new BubbleableMetadata();
    $bubbleable_metadata->setCacheContexts(['current_user']);
    $bubbleable_metadata->setCacheMaxAge(12);

    $result = $this->token->replace('[node:title]', ['node' => $node], [], $bubbleable_metadata);
    $this->assertEquals('hello world', $result);
    $this->assertEquals(['node:1'], $bubbleable_metadata->getCacheTags());
    $this->assertEquals([
      'current_user',
      'custom_context',
    ], $bubbleable_metadata->getCacheContexts());
    $this->assertEquals(10, $bubbleable_metadata->getCacheMaxAge());
  }

  /**
   * Tests replace with hook tokens alter with bubbleable metadata.
   *
   * @see ::replace
   * @see ::replace
   */
  public function testReplaceWithHookTokensAlterWithBubbleableMetadata(): void {
    $this->moduleHandler
      ->method('invokeAll')
      ->willReturn([]);

    $this->moduleHandler
      ->method('alter')
      ->willReturnCallback(function ($hook_name, array &$replacements, array $context, BubbleableMetadata $bubbleable_metadata): void {
        $replacements['[node:title]'] = 'hello world';
        $bubbleable_metadata->addCacheContexts(['custom_context']);
        $bubbleable_metadata->addCacheTags(['node:1']);
        $bubbleable_metadata->setCacheMaxAge(10);
      });

    $node = $this->prophesize('Drupal\node\NodeInterface');
    $node->getCacheContexts()->willReturn([]);
    $node->getCacheTags()->willReturn([]);
    $node->getCacheMaxAge()->willReturn(14);
    $node = $node->reveal();

    $bubbleable_metadata = new BubbleableMetadata();
    $bubbleable_metadata->setCacheContexts(['current_user']);
    $bubbleable_metadata->setCacheMaxAge(12);

    $result = $this->token->replace('[node:title]', ['node' => $node], [], $bubbleable_metadata);
    $this->assertEquals('hello world', $result);
    $this->assertEquals(['node:1'], $bubbleable_metadata->getCacheTags());
    $this->assertEquals([
      'current_user',
      'custom_context',
    ], $bubbleable_metadata->getCacheContexts());
    $this->assertEquals(10, $bubbleable_metadata->getCacheMaxAge());
  }

  /**
   * Tests reset info.
   */
  public function testResetInfo(): void {
    $this->setUpMockCacheTagsInvalidator();

    $this->cacheTagsInvalidator->expects($this->once())
      ->method('invalidateTags')
      ->with(['token_info']);

    $this->token->resetInfo();
  }

  /**
   * Tests replace escaping.
   */
  #[DataProvider('providerTestReplaceEscaping')]
  public function testReplaceEscaping($string, array $tokens, $expected): void {
    $this->moduleHandler
      ->method('invokeAll')
      ->willReturnCallback(function ($type, $args) {
        return $args[2]['tokens'];
      });

    $result = $this->token->replace($string, ['tokens' => $tokens]);
    $this->assertIsString($result);
    $this->assertEquals($expected, $result);
  }

  public static function providerTestReplaceEscaping(): array {
    $data = [];

    // No tokens. The first argument to Token::replace() should not be escaped.
    $data['no-tokens'] = ['muh', [], 'muh'];
    $data['html-in-string'] = ['<h1>Giraffe</h1>', [], '<h1>Giraffe</h1>'];
    $data['html-in-string-quote'] = ['<h1>Giraffe"</h1>', [], '<h1>Giraffe"</h1>'];

    $data['simple-placeholder-with-plain-text'] = [
      '<h1>[token:meh]</h1>',
      ['[token:meh]' => 'Giraffe"'],
      '<h1>' . Html::escape('Giraffe"') . '</h1>',
    ];

    $data['simple-placeholder-with-safe-html'] = [
      '<h1>[token:meh]</h1>',
      ['[token:meh]' => Markup::create('<em>Emphasized</em>')],
      '<h1><em>Emphasized</em></h1>',
    ];

    return $data;
  }

  /**
   * Tests replace plain.
   */
  public function testReplacePlain(): void {
    $this->setupSiteTokens();
    $base = 'Wow, great "[site:name]" has a slogan "[site:slogan]"';
    $plain = $this->token->replacePlain($base);
    $this->assertEquals($plain, 'Wow, great "Your <best> buys" has a slogan "We are best"');
  }

  /**
   * Scans dummy text, then tests the output.
   */
  public function testScan(): void {
    // Define text with valid and not valid, fake and existing token-like
    // strings.
    $text = 'First a [valid:simple], but dummy token, and a dummy [valid:token with: spaces].';
    $text .= 'Then a [not valid:token].';
    $text .= 'Then an [:empty token type].';
    $text .= 'Then an [empty token:].';
    $text .= 'Then a totally empty token: [:].';
    $text .= 'Last an existing token: [node:author:name].';
    $token_wannabes = $this->token->scan($text);

    $this->assertTrue(isset($token_wannabes['valid']['simple']), 'A simple valid token has been matched.');
    $this->assertTrue(isset($token_wannabes['valid']['token with: spaces']), 'A valid token with space characters in the token name has been matched.');
    $this->assertFalse(isset($token_wannabes['not valid']), 'An invalid token with spaces in the token type has not been matched.');
    $this->assertFalse(isset($token_wannabes['empty token']), 'An empty token has not been matched.');
    $this->assertFalse(isset($token_wannabes['']['empty token type']), 'An empty token type has not been matched.');
    $this->assertFalse(isset($token_wannabes['']['']), 'An empty token and type has not been matched.');
    $this->assertTrue(isset($token_wannabes['node']), 'An existing valid token has been matched.');
  }

  /**
   * Sets up the token library to return site tokens.
   */
  protected function setupSiteTokens(): void {
    // The site name is plain text, but the slogan is markup.
    $tokens = [
      '[site:name]' => 'Your <best> buys',
      '[site:slogan]' => Markup::create('We are <b>best</b>'),
    ];

    $this->moduleHandler
      ->method('invokeAll')
      ->willReturn($tokens);
  }

}
