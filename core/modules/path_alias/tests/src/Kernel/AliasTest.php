<?php

declare(strict_types=1);

namespace Drupal\Tests\path_alias\Kernel;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Cache\MemoryCounterBackend;
use Drupal\Core\Language\LanguageInterface;
use Drupal\KernelTests\KernelTestBase;
use Drupal\path_alias\AliasManager;
use Drupal\path_alias\AliasPrefixList;
use Drupal\Tests\Traits\Core\PathAliasTestTrait;

/**
 * Tests path alias CRUD and lookup functionality.
 *
 * @coversDefaultClass \Drupal\path_alias\AliasRepository
 *
 * @group path_alias
 */
class AliasTest extends KernelTestBase {

  use PathAliasTestTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['path_alias'];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // The alias prefix list expects that the menu path roots are set by a
    // menu router rebuild.
    \Drupal::state()->set('router.path_roots', ['user', 'admin']);

    $this->installEntitySchema('path_alias');
  }

  /**
   * @covers ::preloadPathAlias
   */
  public function testPreloadPathAlias(): void {
    $path_alias_repository = $this->container->get('path_alias.repository');

    // Every interesting language combination:
    // Just unspecified.
    $this->createPathAlias('/und/src', '/und/alias', LanguageInterface::LANGCODE_NOT_SPECIFIED);
    // Just a single language.
    $this->createPathAlias('/en/src', '/en/alias', 'en');
    // A single language, plus unspecified.
    $this->createPathAlias('/en-und/src', '/en-und/und', LanguageInterface::LANGCODE_NOT_SPECIFIED);
    $this->createPathAlias('/en-und/src', '/en-und/en', 'en');
    // Multiple languages.
    $this->createPathAlias('/en-xx-lolspeak/src', '/en-xx-lolspeak/en', 'en');
    $this->createPathAlias('/en-xx-lolspeak/src', '/en-xx-lolspeak/xx-lolspeak', 'xx-lolspeak');
    // A duplicate alias for the same path. This is later, so should be
    // preferred.
    $this->createPathAlias('/en-xx-lolspeak/src', '/en-xx-lolspeak/en-dup', 'en');
    // Multiple languages, plus unspecified.
    $this->createPathAlias('/en-xx-lolspeak-und/src', '/en-xx-lolspeak-und/und', LanguageInterface::LANGCODE_NOT_SPECIFIED);
    $this->createPathAlias('/en-xx-lolspeak-und/src', '/en-xx-lolspeak-und/en', 'en');
    $this->createPathAlias('/en-xx-lolspeak-und/src', '/en-xx-lolspeak-und/xx-lolspeak', 'xx-lolspeak');

    // Queries for unspecified language aliases.
    // Ask for an empty array, get all results.
    $this->assertEquals(
      [
        '/und/src' => '/und/alias',
        '/en-und/src' => '/en-und/und',
        '/en-xx-lolspeak-und/src' => '/en-xx-lolspeak-und/und',
      ],
      $path_alias_repository->preloadPathAlias([], LanguageInterface::LANGCODE_NOT_SPECIFIED)
    );
    // Ask for nonexistent source.
    $this->assertEquals(
      [],
      $path_alias_repository->preloadPathAlias(['/nonexistent'], LanguageInterface::LANGCODE_NOT_SPECIFIED));
    // Ask for each saved source, individually.
    $this->assertEquals(
      ['/und/src' => '/und/alias'],
      $path_alias_repository->preloadPathAlias(['/und/src'], LanguageInterface::LANGCODE_NOT_SPECIFIED)
    );
    $this->assertEquals(
      [],
      $path_alias_repository->preloadPathAlias(['/en/src'], LanguageInterface::LANGCODE_NOT_SPECIFIED)
    );
    $this->assertEquals(
      ['/en-und/src' => '/en-und/und'],
      $path_alias_repository->preloadPathAlias(['/en-und/src'], LanguageInterface::LANGCODE_NOT_SPECIFIED)
    );
    $this->assertEquals(
      [],
      $path_alias_repository->preloadPathAlias(['/en-xx-lolspeak/src'], LanguageInterface::LANGCODE_NOT_SPECIFIED)
    );
    $this->assertEquals(
      ['/en-xx-lolspeak-und/src' => '/en-xx-lolspeak-und/und'],
      $path_alias_repository->preloadPathAlias(['/en-xx-lolspeak-und/src'], LanguageInterface::LANGCODE_NOT_SPECIFIED)
    );
    // Ask for multiple sources, all that are known.
    $this->assertEquals(
      [
        '/und/src' => '/und/alias',
        '/en-und/src' => '/en-und/und',
        '/en-xx-lolspeak-und/src' => '/en-xx-lolspeak-und/und',
      ],
      $path_alias_repository->preloadPathAlias(
        [
          '/nonexistent',
          '/und/src',
          '/en/src',
          '/en-und/src',
          '/en-xx-lolspeak/src',
          '/en-xx-lolspeak-und/src',
        ],
        LanguageInterface::LANGCODE_NOT_SPECIFIED
      )
    );
    // Ask for multiple sources, just a subset.
    $this->assertEquals(
      [
        '/und/src' => '/und/alias',
        '/en-xx-lolspeak-und/src' => '/en-xx-lolspeak-und/und',
      ],
      $path_alias_repository->preloadPathAlias(
        [
          '/und/src',
          '/en-xx-lolspeak/src',
          '/en-xx-lolspeak-und/src',
        ],
        LanguageInterface::LANGCODE_NOT_SPECIFIED
      )
    );

    // Queries for English aliases.
    // Ask for an empty array, get all results.
    $this->assertEquals(
      [
        '/und/src' => '/und/alias',
        '/en/src' => '/en/alias',
        '/en-und/src' => '/en-und/en',
        '/en-xx-lolspeak/src' => '/en-xx-lolspeak/en-dup',
        '/en-xx-lolspeak-und/src' => '/en-xx-lolspeak-und/en',
      ],
      $path_alias_repository->preloadPathAlias([], 'en')
    );
    // Ask for nonexistent source.
    $this->assertEquals(
      [],
      $path_alias_repository->preloadPathAlias(['/nonexistent'], 'en'));
    // Ask for each saved source, individually.
    $this->assertEquals(
      ['/und/src' => '/und/alias'],
      $path_alias_repository->preloadPathAlias(['/und/src'], 'en')
    );
    $this->assertEquals(
      ['/en/src' => '/en/alias'],
      $path_alias_repository->preloadPathAlias(['/en/src'], 'en')
    );
    $this->assertEquals(
      ['/en-und/src' => '/en-und/en'],
      $path_alias_repository->preloadPathAlias(['/en-und/src'], 'en')
    );
    $this->assertEquals(
      ['/en-xx-lolspeak/src' => '/en-xx-lolspeak/en-dup'],
      $path_alias_repository->preloadPathAlias(['/en-xx-lolspeak/src'], 'en')
    );
    $this->assertEquals(
      ['/en-xx-lolspeak-und/src' => '/en-xx-lolspeak-und/en'],
      $path_alias_repository->preloadPathAlias(['/en-xx-lolspeak-und/src'], 'en')
    );
    // Ask for multiple sources, all that are known.
    $this->assertEquals(
      [
        '/und/src' => '/und/alias',
        '/en/src' => '/en/alias',
        '/en-und/src' => '/en-und/en',
        '/en-xx-lolspeak/src' => '/en-xx-lolspeak/en-dup',
        '/en-xx-lolspeak-und/src' => '/en-xx-lolspeak-und/en',
      ],
      $path_alias_repository->preloadPathAlias(
        [
          '/nonexistent',
          '/und/src',
          '/en/src',
          '/en-und/src',
          '/en-xx-lolspeak/src',
          '/en-xx-lolspeak-und/src',
        ],
        'en'
      )
    );
    // Ask for multiple sources, just a subset.
    $this->assertEquals(
      [
        '/und/src' => '/und/alias',
        '/en-xx-lolspeak/src' => '/en-xx-lolspeak/en-dup',
        '/en-xx-lolspeak-und/src' => '/en-xx-lolspeak-und/en',
      ],
      $path_alias_repository->preloadPathAlias(
        [
          '/und/src',
          '/en-xx-lolspeak/src',
          '/en-xx-lolspeak-und/src',
        ],
        'en'
      )
    );

    // Queries for xx-lolspeak aliases.
    // Ask for an empty array, get all results.
    $this->assertEquals(
      [
        '/und/src' => '/und/alias',
        '/en-und/src' => '/en-und/und',
        '/en-xx-lolspeak/src' => '/en-xx-lolspeak/xx-lolspeak',
        '/en-xx-lolspeak-und/src' => '/en-xx-lolspeak-und/xx-lolspeak',
      ],
      $path_alias_repository->preloadPathAlias([], 'xx-lolspeak')
    );
    // Ask for nonexistent source.
    $this->assertEquals(
      [],
      $path_alias_repository->preloadPathAlias(['/nonexistent'], 'xx-lolspeak'));
    // Ask for each saved source, individually.
    $this->assertEquals(
      ['/und/src' => '/und/alias'],
      $path_alias_repository->preloadPathAlias(['/und/src'], 'xx-lolspeak')
    );
    $this->assertEquals(
      [],
      $path_alias_repository->preloadPathAlias(['/en/src'], 'xx-lolspeak')
    );
    $this->assertEquals(
      ['/en-und/src' => '/en-und/und'],
      $path_alias_repository->preloadPathAlias(['/en-und/src'], 'xx-lolspeak')
    );
    $this->assertEquals(
      ['/en-xx-lolspeak/src' => '/en-xx-lolspeak/xx-lolspeak'],
      $path_alias_repository->preloadPathAlias(['/en-xx-lolspeak/src'], 'xx-lolspeak')
    );
    $this->assertEquals(
      ['/en-xx-lolspeak-und/src' => '/en-xx-lolspeak-und/xx-lolspeak'],
      $path_alias_repository->preloadPathAlias(['/en-xx-lolspeak-und/src'], 'xx-lolspeak')
    );
    // Ask for multiple sources, all that are known.
    $this->assertEquals(
      [
        '/und/src' => '/und/alias',
        '/en-und/src' => '/en-und/und',
        '/en-xx-lolspeak/src' => '/en-xx-lolspeak/xx-lolspeak',
        '/en-xx-lolspeak-und/src' => '/en-xx-lolspeak-und/xx-lolspeak',
      ],
      $path_alias_repository->preloadPathAlias(
        [
          '/nonexistent',
          '/und/src',
          '/en/src',
          '/en-und/src',
          '/en-xx-lolspeak/src',
          '/en-xx-lolspeak-und/src',
        ],
        'xx-lolspeak'
      )
    );
    // Ask for multiple sources, just a subset.
    $this->assertEquals(
      [
        '/und/src' => '/und/alias',
        '/en-xx-lolspeak/src' => '/en-xx-lolspeak/xx-lolspeak',
        '/en-xx-lolspeak-und/src' => '/en-xx-lolspeak-und/xx-lolspeak',
      ],
      $path_alias_repository->preloadPathAlias(
        [
          '/und/src',
          '/en-xx-lolspeak/src',
          '/en-xx-lolspeak-und/src',
        ],
        'xx-lolspeak'
      )
    );
  }

  /**
   * @covers ::lookupBySystemPath
   */
  public function testLookupBySystemPath(): void {
    $this->createPathAlias('/test-source-Case', '/test-alias');

    $path_alias_repository = $this->container->get('path_alias.repository');
    $this->assertEquals('/test-alias', $path_alias_repository->lookupBySystemPath('/test-source-Case', LanguageInterface::LANGCODE_NOT_SPECIFIED)['alias']);
    $this->assertEquals('/test-alias', $path_alias_repository->lookupBySystemPath('/test-source-case', LanguageInterface::LANGCODE_NOT_SPECIFIED)['alias']);
  }

  /**
   * @covers ::lookupByAlias
   */
  public function testLookupByAlias(): void {
    $this->createPathAlias('/test-source', '/test-alias-Case');

    $path_alias_repository = $this->container->get('path_alias.repository');
    $this->assertEquals('/test-source', $path_alias_repository->lookupByAlias('/test-alias-Case', LanguageInterface::LANGCODE_NOT_SPECIFIED)['path']);
    $this->assertEquals('/test-source', $path_alias_repository->lookupByAlias('/test-alias-case', LanguageInterface::LANGCODE_NOT_SPECIFIED)['path']);
  }

  /**
   * @covers \Drupal\path_alias\AliasManager::getPathByAlias
   * @covers \Drupal\path_alias\AliasManager::getAliasByPath
   */
  public function testLookupPath(): void {
    // Create AliasManager and Path object.
    $aliasManager = $this->container->get('path_alias.manager');

    // Test the situation where the source is the same for multiple aliases.
    // Start with a language-neutral alias, which we will override.
    $path_alias = $this->createPathAlias('/user/1', '/foo');
    $this->assertEquals($path_alias->getAlias(), $aliasManager->getAliasByPath($path_alias->getPath()), 'Basic alias lookup works.');
    $this->assertEquals($path_alias->getPath(), $aliasManager->getPathByAlias($path_alias->getAlias()), 'Basic source lookup works.');

    // Create a language specific alias for the default language (English).
    $path_alias = $this->createPathAlias('/user/1', '/users/Dries', 'en');

    $this->assertEquals($path_alias->getAlias(), $aliasManager->getAliasByPath($path_alias->getPath()), 'English alias overrides language-neutral alias.');
    $this->assertEquals($path_alias->getPath(), $aliasManager->getPathByAlias($path_alias->getAlias()), 'English source overrides language-neutral source.');

    // Create a language-neutral alias for the same path, again.
    $path_alias = $this->createPathAlias('/user/1', '/bar');
    $this->assertEquals("/users/Dries", $aliasManager->getAliasByPath($path_alias->getPath()), 'English alias still returned after entering a language-neutral alias.');

    // Create a language-specific (xx-lolspeak) alias for the same path.
    $path_alias = $this->createPathAlias('/user/1', '/LOL', 'xx-lolspeak');
    $this->assertEquals("/users/Dries", $aliasManager->getAliasByPath($path_alias->getPath()), 'English alias still returned after entering a LOLspeak alias.');
    // The LOLspeak alias should be returned if we really want LOLspeak.
    $this->assertEquals('/LOL', $aliasManager->getAliasByPath($path_alias->getPath(), 'xx-lolspeak'), 'LOLspeak alias returned if we specify xx-lolspeak to the alias manager.');

    // Create a new alias for this path in English, which should override the
    // previous alias for "user/1".
    $path_alias = $this->createPathAlias('/user/1', '/users/my-new-path', 'en');
    $this->assertEquals($path_alias->getAlias(), $aliasManager->getAliasByPath($path_alias->getPath()), 'Recently created English alias returned.');
    $this->assertEquals($path_alias->getPath(), $aliasManager->getPathByAlias($path_alias->getAlias()), 'Recently created English source returned.');

    // Remove the English aliases, which should cause a fallback to the most
    // recently created language-neutral alias, 'bar'.
    $path_alias_storage = $this->container->get('entity_type.manager')->getStorage('path_alias');
    $entities = $path_alias_storage->loadByProperties(['langcode' => 'en']);
    $path_alias_storage->delete($entities);
    $this->assertEquals('/bar', $aliasManager->getAliasByPath($path_alias->getPath()), 'Path lookup falls back to recently created language-neutral alias.');

    // Test the situation where the alias and language are the same, but
    // the source differs. The newer alias record should be returned.
    $this->createPathAlias('/user/2', '/bar');
    $aliasManager->cacheClear();
    $this->assertEquals('/user/2', $aliasManager->getPathByAlias('/bar'), 'Newer alias record is returned when comparing two LanguageInterface::LANGCODE_NOT_SPECIFIED paths with the same alias.');
  }

  /**
   * Tests the alias prefix.
   */
  public function testPrefixList(): void {
    $memoryCounterBackend = new MemoryCounterBackend(\Drupal::service(TimeInterface::class));

    // Create AliasManager and Path object.
    $prefix_list = new AliasPrefixList('path_alias_prefix_list', $memoryCounterBackend, $this->container->get('lock'), $this->container->get('state'), $this->container->get('path_alias.repository'));
    $aliasManager = new AliasManager($this->container->get('path_alias.repository'), $prefix_list, $this->container->get('language_manager'), $memoryCounterBackend, $this->container->get(TimeInterface::class));

    // No alias for user and admin yet, so should be NULL.
    $this->assertNull($prefix_list->get('user'));
    $this->assertNull($prefix_list->get('admin'));

    // Non-existing path roots should be NULL too. Use a length of 7 to avoid
    // possible conflict with random aliases below.
    $this->assertNull($prefix_list->get($this->randomMachineName()));

    // Add an alias for user/1, user should get cached now.
    $this->createPathAlias('/user/1', '/' . $this->randomMachineName());
    $aliasManager->cacheClear();
    $this->assertTrue($prefix_list->get('user'));
    $this->assertNull($prefix_list->get('admin'));
    $this->assertNull($prefix_list->get($this->randomMachineName()));

    // Add an alias for admin, both should get cached now.
    $this->createPathAlias('/admin/something', '/' . $this->randomMachineName());
    $aliasManager->cacheClear();
    $this->assertTrue($prefix_list->get('user'));
    $this->assertTrue($prefix_list->get('admin'));
    $this->assertNull($prefix_list->get($this->randomMachineName()));

    // Remove the user alias again, prefix list entry should be removed.
    $path_alias_storage = $this->container->get('entity_type.manager')->getStorage('path_alias');
    $entities = $path_alias_storage->loadByProperties(['path' => '/user/1']);
    $path_alias_storage->delete($entities);
    $aliasManager->cacheClear();
    $this->assertNull($prefix_list->get('user'));
    $this->assertTrue($prefix_list->get('admin'));
    $this->assertNull($prefix_list->get($this->randomMachineName()));

    // Destruct the prefix list so that the caches are written.
    $prefix_list->destruct();
    $this->assertEquals(1, $memoryCounterBackend->getCounter('set', 'path_alias_prefix_list'));
    $memoryCounterBackend->resetCounter();

    // Re-initialize the prefix list using the same cache backend, should load
    // from cache.
    $prefix_list = new AliasPrefixList('path_alias_prefix_list', $memoryCounterBackend, $this->container->get('lock'), $this->container->get('state'), $this->container->get('path_alias.repository'));
    $this->assertNull($prefix_list->get('user'));
    $this->assertTrue($prefix_list->get('admin'));
    $this->assertNull($prefix_list->get($this->randomMachineName()));
    $this->assertEquals(1, $memoryCounterBackend->getCounter('get', 'path_alias_prefix_list'));
    $this->assertEquals(0, $memoryCounterBackend->getCounter('set', 'path_alias_prefix_list'));

    // Destruct the prefix list, should not attempt to write the cache again.
    $prefix_list->destruct();
    $this->assertEquals(1, $memoryCounterBackend->getCounter('get', 'path_alias_prefix_list'));
    $this->assertEquals(0, $memoryCounterBackend->getCounter('set', 'path_alias_prefix_list'));
  }

  /**
   * Tests situation where the prefix list  cache is deleted mid-request.
   */
  public function testPrefixListCacheDeletionMidRequest() {
    $memoryCounterBackend = new MemoryCounterBackend(\Drupal::service(TimeInterface::class));

    // Create AliasManager and Path object.
    $prefix_list = new AliasPrefixList('path_alias_prefix_list', $memoryCounterBackend, $this->container->get('lock'), $this->container->get('state'), $this->container->get('path_alias.repository'));

    // Prefix list cache should not exist at all yet.
    $this->assertFalse($memoryCounterBackend->get('path_alias_prefix_list'));

    // Add some aliases for both menu routes we have.
    $this->createPathAlias('/admin/something', '/' . $this->randomMachineName());
    $this->createPathAlias('/user/something', '/' . $this->randomMachineName());

    // Lookup admin path in prefix list. It will query the DB and figure out
    // that it indeed has an alias, and add it to the internal prefix list and
    // flag it to be persisted to cache.
    $this->assertTrue($prefix_list->get('admin'));

    // Destruct the prefix list so it persists its cache.
    $prefix_list->destruct();
    $this->assertEquals(1, $memoryCounterBackend->getCounter('set', 'path_alias_prefix_list'));
    // Cache data should have data for 'user' and 'admin', even though just
    // 'admin' was looked up. This is because the cache is primed with all
    // menu router base paths.
    $this->assertEquals(['user' => FALSE, 'admin' => TRUE], $memoryCounterBackend->get('path_alias_prefix_list')->data);
    $memoryCounterBackend->resetCounter();

    // Re-initialize the prefix list and lookup an alias for the 'user' path.
    // Prefix list should load data from its cache, see that it hasn't done a
    // check for 'user' yet, perform the check, then mark the result to be
    // persisted to cache.
    $prefix_list = new AliasPrefixList('path_alias_prefix_list', $memoryCounterBackend, $this->container->get('lock'), $this->container->get('state'), $this->container->get('path_alias.repository'));
    $this->assertTrue($prefix_list->get('user'));

    // Delete the prefix list cache. This could happen from an outside process,
    // like a code deployment that performs a cache rebuild.
    $memoryCounterBackend->delete('path_alias_prefix_list');

    // Destruct prefix list so it attempts to save the prefix list data to
    // cache. However it should recognize that the previous cache entry was
    // deleted from underneath it and not save anything to cache, to protect
    // from cache corruption.
    $prefix_list->destruct();
    $this->assertEquals(0, $memoryCounterBackend->getCounter('set', 'path_alias_prefix_list'));
    $this->assertFalse($memoryCounterBackend->get('path_alias_prefix_list'));
    $memoryCounterBackend->resetCounter();
  }

}
