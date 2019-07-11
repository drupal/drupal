<?php

namespace Drupal\KernelTests\Core\Path;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\Core\Cache\MemoryCounterBackend;
use Drupal\Core\Path\AliasStorage;
use Drupal\Core\Database\Database;
use Drupal\Core\Path\AliasManager;
use Drupal\Core\Path\AliasWhitelist;

/**
 * Tests path alias CRUD and lookup functionality.
 *
 * @group Path
 */
class AliasTest extends PathUnitTestBase {

  public function testCRUD() {
    // Prepare database table.
    $connection = Database::getConnection();
    $this->fixtures->createTables($connection);

    // Create Path object.
    $aliasStorage = new AliasStorage($connection, $this->container->get('module_handler'));

    $aliases = $this->fixtures->sampleUrlAliases();

    // Create a few aliases
    foreach ($aliases as $idx => $alias) {
      $aliasStorage->save($alias['source'], $alias['alias'], $alias['langcode']);

      $result = $connection->query('SELECT * FROM {url_alias} WHERE source = :source AND alias= :alias AND langcode = :langcode', [':source' => $alias['source'], ':alias' => $alias['alias'], ':langcode' => $alias['langcode']]);
      $rows = $result->fetchAll();

      $this->assertEqual(count($rows), 1, new FormattableMarkup('Created an entry for %alias.', ['%alias' => $alias['alias']]));

      // Cache the pid for further tests.
      $aliases[$idx]['pid'] = $rows[0]->pid;
    }

    // Load a few aliases
    foreach ($aliases as $alias) {
      $pid = $alias['pid'];
      $loadedAlias = $aliasStorage->load(['pid' => $pid]);
      $this->assertEqual($loadedAlias, $alias, new FormattableMarkup('Loaded the expected path with pid %pid.', ['%pid' => $pid]));
    }

    // Load alias by source path.
    $loadedAlias = $aliasStorage->load(['source' => '/node/1']);
    $this->assertEqual($loadedAlias['alias'], '/alias_for_node_1_und', 'The last created alias loaded by default.');

    // Update a few aliases
    foreach ($aliases as $alias) {
      $fields = $aliasStorage->save($alias['source'], $alias['alias'] . '_updated', $alias['langcode'], $alias['pid']);

      $this->assertEqual($alias['alias'], $fields['original']['alias']);

      $result = $connection->query('SELECT pid FROM {url_alias} WHERE source = :source AND alias= :alias AND langcode = :langcode', [':source' => $alias['source'], ':alias' => $alias['alias'] . '_updated', ':langcode' => $alias['langcode']]);
      $pid = $result->fetchField();

      $this->assertEqual($pid, $alias['pid'], new FormattableMarkup('Updated entry for pid %pid.', ['%pid' => $pid]));
    }

    // Delete a few aliases
    foreach ($aliases as $alias) {
      $pid = $alias['pid'];
      $aliasStorage->delete(['pid' => $pid]);

      $result = $connection->query('SELECT * FROM {url_alias} WHERE pid = :pid', [':pid' => $pid]);
      $rows = $result->fetchAll();

      $this->assertEqual(count($rows), 0, new FormattableMarkup('Deleted entry with pid %pid.', ['%pid' => $pid]));
    }
  }

  public function testLookupPath() {
    // Prepare database table.
    $connection = Database::getConnection();
    $this->fixtures->createTables($connection);

    // Create AliasManager and Path object.
    $aliasManager = $this->container->get('path.alias_manager');
    $aliasStorage = new AliasStorage($connection, $this->container->get('module_handler'));

    // Test the situation where the source is the same for multiple aliases.
    // Start with a language-neutral alias, which we will override.
    $path = [
      'source' => "/user/1",
      'alias' => '/foo',
    ];

    $aliasStorage->save($path['source'], $path['alias']);
    $this->assertEqual($aliasManager->getAliasByPath($path['source']), $path['alias'], 'Basic alias lookup works.');
    $this->assertEqual($aliasManager->getPathByAlias($path['alias']), $path['source'], 'Basic source lookup works.');

    // Create a language specific alias for the default language (English).
    $path = [
      'source' => "/user/1",
      'alias' => "/users/Dries",
      'langcode' => 'en',
    ];
    $aliasStorage->save($path['source'], $path['alias'], $path['langcode']);
    // Hook that clears cache is not executed with unit tests.
    \Drupal::service('path.alias_manager')->cacheClear();
    $this->assertEqual($aliasManager->getAliasByPath($path['source']), $path['alias'], 'English alias overrides language-neutral alias.');
    $this->assertEqual($aliasManager->getPathByAlias($path['alias']), $path['source'], 'English source overrides language-neutral source.');

    // Create a language-neutral alias for the same path, again.
    $path = [
      'source' => "/user/1",
      'alias' => '/bar',
    ];
    $aliasStorage->save($path['source'], $path['alias']);
    $this->assertEqual($aliasManager->getAliasByPath($path['source']), "/users/Dries", 'English alias still returned after entering a language-neutral alias.');

    // Create a language-specific (xx-lolspeak) alias for the same path.
    $path = [
      'source' => "/user/1",
      'alias' => '/LOL',
      'langcode' => 'xx-lolspeak',
    ];
    $aliasStorage->save($path['source'], $path['alias'], $path['langcode']);
    $this->assertEqual($aliasManager->getAliasByPath($path['source']), "/users/Dries", 'English alias still returned after entering a LOLspeak alias.');
    // The LOLspeak alias should be returned if we really want LOLspeak.
    $this->assertEqual($aliasManager->getAliasByPath($path['source'], 'xx-lolspeak'), '/LOL', 'LOLspeak alias returned if we specify xx-lolspeak to the alias manager.');

    // Create a new alias for this path in English, which should override the
    // previous alias for "user/1".
    $path = [
      'source' => "/user/1",
      'alias' => '/users/my-new-path',
      'langcode' => 'en',
    ];
    $aliasStorage->save($path['source'], $path['alias'], $path['langcode']);
    // Hook that clears cache is not executed with unit tests.
    $aliasManager->cacheClear();
    $this->assertEqual($aliasManager->getAliasByPath($path['source']), $path['alias'], 'Recently created English alias returned.');
    $this->assertEqual($aliasManager->getPathByAlias($path['alias']), $path['source'], 'Recently created English source returned.');

    // Remove the English aliases, which should cause a fallback to the most
    // recently created language-neutral alias, 'bar'.
    $aliasStorage->delete(['langcode' => 'en']);
    // Hook that clears cache is not executed with unit tests.
    $aliasManager->cacheClear();
    $this->assertEqual($aliasManager->getAliasByPath($path['source']), '/bar', 'Path lookup falls back to recently created language-neutral alias.');

    // Test the situation where the alias and language are the same, but
    // the source differs. The newer alias record should be returned.
    $aliasStorage->save('/user/2', '/bar');
    // Hook that clears cache is not executed with unit tests.
    $aliasManager->cacheClear();
    $this->assertEqual($aliasManager->getPathByAlias('/bar'), '/user/2', 'Newer alias record is returned when comparing two LanguageInterface::LANGCODE_NOT_SPECIFIED paths with the same alias.');
  }

  /**
   * Tests the alias whitelist.
   */
  public function testWhitelist() {
    // Prepare database table.
    $connection = Database::getConnection();
    $this->fixtures->createTables($connection);

    $memoryCounterBackend = new MemoryCounterBackend();

    // Create AliasManager and Path object.
    $aliasStorage = new AliasStorage($connection, $this->container->get('module_handler'));
    $whitelist = new AliasWhitelist('path_alias_whitelist', $memoryCounterBackend, $this->container->get('lock'), $this->container->get('state'), $aliasStorage);
    $aliasManager = new AliasManager($aliasStorage, $whitelist, $this->container->get('language_manager'), $memoryCounterBackend);

    // No alias for user and admin yet, so should be NULL.
    $this->assertNull($whitelist->get('user'));
    $this->assertNull($whitelist->get('admin'));

    // Non-existing path roots should be NULL too. Use a length of 7 to avoid
    // possible conflict with random aliases below.
    $this->assertNull($whitelist->get($this->randomMachineName()));

    // Add an alias for user/1, user should get whitelisted now.
    $aliasStorage->save('/user/1', '/' . $this->randomMachineName());
    $aliasManager->cacheClear();
    $this->assertTrue($whitelist->get('user'));
    $this->assertNull($whitelist->get('admin'));
    $this->assertNull($whitelist->get($this->randomMachineName()));

    // Add an alias for admin, both should get whitelisted now.
    $aliasStorage->save('/admin/something', '/' . $this->randomMachineName());
    $aliasManager->cacheClear();
    $this->assertTrue($whitelist->get('user'));
    $this->assertTrue($whitelist->get('admin'));
    $this->assertNull($whitelist->get($this->randomMachineName()));

    // Remove the user alias again, whitelist entry should be removed.
    $aliasStorage->delete(['source' => '/user/1']);
    $aliasManager->cacheClear();
    $this->assertNull($whitelist->get('user'));
    $this->assertTrue($whitelist->get('admin'));
    $this->assertNull($whitelist->get($this->randomMachineName()));

    // Destruct the whitelist so that the caches are written.
    $whitelist->destruct();
    $this->assertEqual($memoryCounterBackend->getCounter('set', 'path_alias_whitelist'), 1);
    $memoryCounterBackend->resetCounter();

    // Re-initialize the whitelist using the same cache backend, should load
    // from cache.
    $whitelist = new AliasWhitelist('path_alias_whitelist', $memoryCounterBackend, $this->container->get('lock'), $this->container->get('state'), $aliasStorage);
    $this->assertNull($whitelist->get('user'));
    $this->assertTrue($whitelist->get('admin'));
    $this->assertNull($whitelist->get($this->randomMachineName()));
    $this->assertEqual($memoryCounterBackend->getCounter('get', 'path_alias_whitelist'), 1);
    $this->assertEqual($memoryCounterBackend->getCounter('set', 'path_alias_whitelist'), 0);

    // Destruct the whitelist, should not attempt to write the cache again.
    $whitelist->destruct();
    $this->assertEqual($memoryCounterBackend->getCounter('get', 'path_alias_whitelist'), 1);
    $this->assertEqual($memoryCounterBackend->getCounter('set', 'path_alias_whitelist'), 0);
  }

  /**
   * Tests situation where the whitelist cache is deleted mid-request.
   */
  public function testWhitelistCacheDeletionMidRequest() {
    // Prepare database table.
    $connection = Database::getConnection();
    $this->fixtures->createTables($connection);

    $memoryCounterBackend = new MemoryCounterBackend();

    // Create AliasManager and Path object.
    $aliasStorage = new AliasStorage($connection, $this->container->get('module_handler'));
    $whitelist = new AliasWhitelist('path_alias_whitelist', $memoryCounterBackend, $this->container->get('lock'), $this->container->get('state'), $aliasStorage);
    $aliasManager = new AliasManager($aliasStorage, $whitelist, $this->container->get('language_manager'), $memoryCounterBackend);

    // Whitelist cache should not exist at all yet.
    $this->assertFalse($memoryCounterBackend->get('path_alias_whitelist'));

    // Add some aliases for both menu routes we have.
    $aliasStorage->save('/admin/something', '/' . $this->randomMachineName());
    $aliasStorage->save('/user/something', '/' . $this->randomMachineName());
    $aliasManager->cacheClear();

    // Lookup admin path in whitelist. It will query the DB and figure out
    // that it indeed has an alias, and add it to the internal whitelist and
    // flag it to be persisted to cache.
    $this->assertTrue($whitelist->get('admin'));

    // Destruct the whitelist so it persists its cache.
    $whitelist->destruct();
    $this->assertEquals($memoryCounterBackend->getCounter('set', 'path_alias_whitelist'), 1);
    // Cache data should have data for 'user' and 'admin', even though just
    // 'admin' was looked up. This is because the cache is primed with all
    // menu router base paths.
    $this->assertEquals(['user' => FALSE, 'admin' => TRUE], $memoryCounterBackend->get('path_alias_whitelist')->data);
    $memoryCounterBackend->resetCounter();

    // Re-initialize the the whitelist and lookup an alias for the 'user' path.
    // Whitelist should load data from its cache, see that it hasn't done a
    // check for 'user' yet, perform the check, then mark the result to be
    // persisted to cache.
    $whitelist = new AliasWhitelist('path_alias_whitelist', $memoryCounterBackend, $this->container->get('lock'), $this->container->get('state'), $aliasStorage);
    $this->assertTrue($whitelist->get('user'));

    // Delete the whitelist cache. This could happen from an outside process,
    // like a code deployment that performs a cache rebuild.
    $memoryCounterBackend->delete('path_alias_whitelist');

    // Destruct whitelist so it attempts to save the whitelist data to cache.
    // However it should recognize that the previous cache entry was deleted
    // from underneath it and not save anything to cache, to protect from
    // cache corruption.
    $whitelist->destruct();
    $this->assertEquals($memoryCounterBackend->getCounter('set', 'path_alias_whitelist'), 0);
    $this->assertFalse($memoryCounterBackend->get('path_alias_whitelist'));
    $memoryCounterBackend->resetCounter();
  }

}
