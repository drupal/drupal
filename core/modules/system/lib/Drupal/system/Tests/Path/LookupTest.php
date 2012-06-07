<?php

/**
 * @file
 * Definition of Drupal\system\Tests\Path\LookupTest.
 */

namespace Drupal\system\Tests\Path;

use Drupal\simpletest\WebTestBase;

/**
 * Unit test for drupal_lookup_path().
 */
class LookupTest extends WebTestBase {
  public static function getInfo() {
    return array(
      'name' => t('Path lookup'),
      'description' => t('Tests that drupal_lookup_path() returns correct paths.'),
      'group' => t('Path API'),
    );
  }

  /**
   * Test that drupal_lookup_path() returns the correct path.
   */
  function testDrupalLookupPath() {
    $account = $this->drupalCreateUser();
    $uid = $account->uid;
    $name = $account->name;

    // Test the situation where the source is the same for multiple aliases.
    // Start with a language-neutral alias, which we will override.
    $path = array(
      'source' => "user/$uid",
      'alias' => 'foo',
    );
    path_save($path);
    $this->assertEqual(drupal_lookup_path('alias', $path['source']), $path['alias'], t('Basic alias lookup works.'));
    $this->assertEqual(drupal_lookup_path('source', $path['alias']), $path['source'], t('Basic source lookup works.'));

    // Create a language specific alias for the default language (English).
    $path = array(
      'source' => "user/$uid",
      'alias' => "users/$name",
      'langcode' => 'en',
    );
    path_save($path);
    $this->assertEqual(drupal_lookup_path('alias', $path['source']), $path['alias'], t('English alias overrides language-neutral alias.'));
    $this->assertEqual(drupal_lookup_path('source', $path['alias']), $path['source'], t('English source overrides language-neutral source.'));

    // Create a language-neutral alias for the same path, again.
    $path = array(
      'source' => "user/$uid",
      'alias' => 'bar',
    );
    path_save($path);
    $this->assertEqual(drupal_lookup_path('alias', $path['source']), "users/$name", t('English alias still returned after entering a language-neutral alias.'));

    // Create a language-specific (xx-lolspeak) alias for the same path.
    $path = array(
      'source' => "user/$uid",
      'alias' => 'LOL',
      'langcode' => 'xx-lolspeak',
    );
    path_save($path);
    $this->assertEqual(drupal_lookup_path('alias', $path['source']), "users/$name", t('English alias still returned after entering a LOLspeak alias.'));
    // The LOLspeak alias should be returned if we really want LOLspeak.
    $this->assertEqual(drupal_lookup_path('alias', $path['source'], 'xx-lolspeak'), 'LOL', t('LOLspeak alias returned if we specify xx-lolspeak to drupal_lookup_path().'));

    // Create a new alias for this path in English, which should override the
    // previous alias for "user/$uid".
    $path = array(
      'source' => "user/$uid",
      'alias' => 'users/my-new-path',
      'langcode' => 'en',
    );
    path_save($path);
    $this->assertEqual(drupal_lookup_path('alias', $path['source']), $path['alias'], t('Recently created English alias returned.'));
    $this->assertEqual(drupal_lookup_path('source', $path['alias']), $path['source'], t('Recently created English source returned.'));

    // Remove the English aliases, which should cause a fallback to the most
    // recently created language-neutral alias, 'bar'.
    db_delete('url_alias')
      ->condition('langcode', 'en')
      ->execute();
    drupal_clear_path_cache();
    $this->assertEqual(drupal_lookup_path('alias', $path['source']), 'bar', t('Path lookup falls back to recently created language-neutral alias.'));

    // Test the situation where the alias and language are the same, but
    // the source differs. The newer alias record should be returned.
    $account2 = $this->drupalCreateUser();
    $path = array(
      'source' => 'user/' . $account2->uid,
      'alias' => 'bar',
    );
    path_save($path);
    $this->assertEqual(drupal_lookup_path('source', $path['alias']), $path['source'], t('Newer alias record is returned when comparing two LANGUAGE_NOT_SPECIFIED paths with the same alias.'));
  }
}
