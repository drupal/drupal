<?php

/**
 * @file
 * Contains Drupal\system\Tests\Path\AliasTest.
 */

namespace Drupal\system\Tests\Path;

use Drupal\Core\Cache\MemoryCounterBackend;
use Drupal\Core\Path\AliasStorage;
use Drupal\Core\Database\Database;
use Drupal\Core\Path\AliasManager;
use Drupal\Core\Path\AliasWhitelist;

/**
 * Tests path alias CRUD and lookup functionality.
 */
class AliasTest extends PathUnitTestBase {

  public static function getInfo() {
    return array(
      'name' => t('Path Alias Unit Tests'),
      'description' => t('Tests path alias CRUD and lookup functionality.'),
      'group' => t('Path API'),
    );
  }

  function testCRUD() {
    //Prepare database table.
    $connection = Database::getConnection();
    $this->fixtures->createTables($connection);

    //Create Path object.
    $aliasStorage = new AliasStorage($connection, $this->container->get('module_handler'));

    $aliases = $this->fixtures->sampleUrlAliases();

    //Create a few aliases
    foreach ($aliases as $idx => $alias) {
      $aliasStorage->save($alias['source'], $alias['alias'], $alias['langcode']);

      $result = $connection->query('SELECT * FROM {url_alias} WHERE source = :source AND alias= :alias AND langcode = :langcode', array(':source' => $alias['source'], ':alias' => $alias['alias'], ':langcode' => $alias['langcode']));
      $rows = $result->fetchAll();

      $this->assertEqual(count($rows), 1, format_string('Created an entry for %alias.', array('%alias' => $alias['alias'])));

      //Cache the pid for further tests.
      $aliases[$idx]['pid'] = $rows[0]->pid;
    }

    //Load a few aliases
    foreach ($aliases as $alias) {
      $pid = $alias['pid'];
      $loadedAlias = $aliasStorage->load(array('pid' => $pid));
      $this->assertEqual($loadedAlias, $alias, format_string('Loaded the expected path with pid %pid.', array('%pid' => $pid)));
    }

    //Update a few aliases
    foreach ($aliases as $alias) {
      $aliasStorage->save($alias['source'], $alias['alias'] . '_updated', $alias['langcode'], $alias['pid']);

      $result = $connection->query('SELECT pid FROM {url_alias} WHERE source = :source AND alias= :alias AND langcode = :langcode', array(':source' => $alias['source'], ':alias' => $alias['alias'] . '_updated', ':langcode' => $alias['langcode']));
      $pid = $result->fetchField();

      $this->assertEqual($pid, $alias['pid'], format_string('Updated entry for pid %pid.', array('%pid' => $pid)));
    }

    //Delete a few aliases
    foreach ($aliases as $alias) {
      $pid = $alias['pid'];
      $aliasStorage->delete(array('pid' => $pid));

      $result = $connection->query('SELECT * FROM {url_alias} WHERE pid = :pid', array(':pid' => $pid));
      $rows = $result->fetchAll();

      $this->assertEqual(count($rows), 0, format_string('Deleted entry with pid %pid.', array('%pid' => $pid)));
    }
  }

  function testLookupPath() {
    //Prepare database table.
    $connection = Database::getConnection();
    $this->fixtures->createTables($connection);

    //Create AliasManager and Path object.
    $aliasManager = $this->container->get('path.alias_manager');
    $aliasStorage = new AliasStorage($connection, $this->container->get('module_handler'));

    // Test the situation where the source is the same for multiple aliases.
    // Start with a language-neutral alias, which we will override.
    $path = array(
      'source' => "user/1",
      'alias' => 'foo',
    );

    $aliasStorage->save($path['source'], $path['alias']);
    $this->assertEqual($aliasManager->getAliasByPath($path['source']), $path['alias'], 'Basic alias lookup works.');
    $this->assertEqual($aliasManager->getPathByAlias($path['alias']), $path['source'], 'Basic source lookup works.');

    // Create a language specific alias for the default language (English).
    $path = array(
      'source' => "user/1",
      'alias' => "users/Dries",
      'langcode' => 'en',
    );
    $aliasStorage->save($path['source'], $path['alias'], $path['langcode']);
    // Hook that clears cache is not executed with unit tests.
    \Drupal::service('path.alias_manager')->cacheClear();
    $this->assertEqual($aliasManager->getAliasByPath($path['source']), $path['alias'], 'English alias overrides language-neutral alias.');
    $this->assertEqual($aliasManager->getPathByAlias($path['alias']), $path['source'], 'English source overrides language-neutral source.');

    // Create a language-neutral alias for the same path, again.
    $path = array(
      'source' => "user/1",
      'alias' => 'bar',
    );
    $aliasStorage->save($path['source'], $path['alias']);
    $this->assertEqual($aliasManager->getAliasByPath($path['source']), "users/Dries", 'English alias still returned after entering a language-neutral alias.');

    // Create a language-specific (xx-lolspeak) alias for the same path.
    $path = array(
      'source' => "user/1",
      'alias' => 'LOL',
      'langcode' => 'xx-lolspeak',
    );
    $aliasStorage->save($path['source'], $path['alias'], $path['langcode']);
    $this->assertEqual($aliasManager->getAliasByPath($path['source']), "users/Dries", 'English alias still returned after entering a LOLspeak alias.');
    // The LOLspeak alias should be returned if we really want LOLspeak.
    $this->assertEqual($aliasManager->getAliasByPath($path['source'], 'xx-lolspeak'), 'LOL', 'LOLspeak alias returned if we specify xx-lolspeak to the alias manager.');

    // Create a new alias for this path in English, which should override the
    // previous alias for "user/1".
    $path = array(
      'source' => "user/1",
      'alias' => 'users/my-new-path',
      'langcode' => 'en',
    );
    $aliasStorage->save($path['source'], $path['alias'], $path['langcode']);
    // Hook that clears cache is not executed with unit tests.
    $aliasManager->cacheClear();
    $this->assertEqual($aliasManager->getAliasByPath($path['source']), $path['alias'], 'Recently created English alias returned.');
    $this->assertEqual($aliasManager->getPathByAlias($path['alias']), $path['source'], 'Recently created English source returned.');

    // Remove the English aliases, which should cause a fallback to the most
    // recently created language-neutral alias, 'bar'.
    $aliasStorage->delete(array('langcode' => 'en'));
    // Hook that clears cache is not executed with unit tests.
    $aliasManager->cacheClear();
    $this->assertEqual($aliasManager->getAliasByPath($path['source']), 'bar', 'Path lookup falls back to recently created language-neutral alias.');

    // Test the situation where the alias and language are the same, but
    // the source differs. The newer alias record should be returned.
    $aliasStorage->save('user/2', 'bar');
    // Hook that clears cache is not executed with unit tests.
    $aliasManager->cacheClear();
    $this->assertEqual($aliasManager->getPathByAlias('bar'), 'user/2', 'Newer alias record is returned when comparing two Language::LANGCODE_NOT_SPECIFIED paths with the same alias.');
  }

  /**
   * Tests the alias whitelist.
   */
  function testWhitelist() {
    // Prepare database table.
    $connection = Database::getConnection();
    $this->fixtures->createTables($connection);

    $memoryCounterBackend = new MemoryCounterBackend('default');

    // Create AliasManager and Path object.
    $aliasStorage = new AliasStorage($connection, $this->container->get('module_handler'));
    $whitelist = new AliasWhitelist('path_alias_whitelist', $memoryCounterBackend, $this->container->get('lock'), $this->container->get('state'), $aliasStorage);
    $aliasManager = new AliasManager($aliasStorage, $whitelist, $this->container->get('language_manager'), $memoryCounterBackend);

    // No alias for user and admin yet, so should be NULL.
    $this->assertNull($whitelist->get('user'));
    $this->assertNull($whitelist->get('admin'));

    // Non-existing path roots should be NULL too. Use a length of 7 to avoid
    // possible conflict with random aliases below.
    $this->assertNull($whitelist->get($this->randomName()));

    // Add an alias for user/1, user should get whitelisted now.
    $aliasStorage->save('user/1', $this->randomName());
    $aliasManager->cacheClear();
    $this->assertTrue($whitelist->get('user'));
    $this->assertNull($whitelist->get('admin'));
    $this->assertNull($whitelist->get($this->randomName()));

    // Add an alias for admin, both should get whitelisted now.
    $aliasStorage->save('admin/something', $this->randomName());
    $aliasManager->cacheClear();
    $this->assertTrue($whitelist->get('user'));
    $this->assertTrue($whitelist->get('admin'));
    $this->assertNull($whitelist->get($this->randomName()));

    // Remove the user alias again, whitelist entry should be removed.
    $aliasStorage->delete(array('source' => 'user/1'));
    $aliasManager->cacheClear();
    $this->assertNull($whitelist->get('user'));
    $this->assertTrue($whitelist->get('admin'));
    $this->assertNull($whitelist->get($this->randomName()));

    // Destruct the whitelist so that the caches are written.
    $whitelist->destruct();
    $this->assertEqual($memoryCounterBackend->getCounter('set', 'path_alias_whitelist'), 1);
    $memoryCounterBackend->resetCounter();

    // Re-initialize the whitelist using the same cache backend, should load
    // from cache.
    $whitelist = new AliasWhitelist('path_alias_whitelist', $memoryCounterBackend, $this->container->get('lock'), $this->container->get('state'), $aliasStorage);
    $this->assertNull($whitelist->get('user'));
    $this->assertTrue($whitelist->get('admin'));
    $this->assertNull($whitelist->get($this->randomName()));
    $this->assertEqual($memoryCounterBackend->getCounter('get', 'path_alias_whitelist'), 1);
    $this->assertEqual($memoryCounterBackend->getCounter('set', 'path_alias_whitelist'), 0);

    // Destruct the whitelist, should not attempt to write the cache again.
    $whitelist->destruct();
    $this->assertEqual($memoryCounterBackend->getCounter('get', 'path_alias_whitelist'), 1);
    $this->assertEqual($memoryCounterBackend->getCounter('set', 'path_alias_whitelist'), 0);
  }

}
