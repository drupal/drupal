<?php

/**
 * @file
 * Definition of Drupal\system\Tests\Upgrade\LanguageUpgradePathTest.
 */

namespace Drupal\system\Tests\Upgrade;

use Drupal\Core\Database\DatabaseException;

use Drupal\Core\Language\Language;

/**
 * Tests upgrading a filled database with language data.
 *
 * Loads a filled installation of Drupal 7 with language data and runs the
 * upgrade process on it.
 */
class LanguageUpgradePathTest extends UpgradePathTestBase {
  public static function getInfo() {
    return array(
      'name'  => 'Language upgrade test',
      'description'  => 'Upgrade tests with language data.',
      'group' => 'Upgrade path',
    );
  }

  public function setUp() {
    // Path to the database dump files.
    $this->databaseDumpFiles = array(
      drupal_get_path('module', 'system') . '/tests/upgrade/drupal-7.filled.standard_all.database.php.gz',
      drupal_get_path('module', 'system') . '/tests/upgrade/drupal-7.language.database.php',
    );
    parent::setUp();
  }

  /**
   * Tests a successful upgrade.
   */
  public function testLanguageUpgrade() {
    db_update('users')->fields(array('language' => 'ca'))->condition('uid', '1')->execute();
    $this->assertTrue($this->performUpgrade(), 'The upgrade was completed successfully.');

    // Check that the configuration for the 'Catalan' language is correct.
    $config = $this->container->get('config.factory')->get('language.entity.ca')->get();
    // We cannot predict the value of the UUID, we just check it's present.
    $this->assertFalse(empty($config['uuid']));
    unset($config['uuid']);
    $this->assertEqual($config, array(
      'id' => 'ca',
      'label' => 'Catalan',
      'direction' => 0,
      'weight' => 0,
      'locked' => 0,
      'langcode' => 'en',
    ));

    // Ensure Catalan was properly upgraded to be the new default language.
    $this->assertTrue(language_default()->id == 'ca', 'Catalan is the default language');
    $languages = language_list(Language::STATE_ALL);
    foreach ($languages as $language) {
      $this->assertTrue($language->default == ($language->id == 'ca'), format_string('@language default property properly set', array('@language' => $language->name)));
    }

    // Check that both comments display on the node.
    $this->drupalGet('node/50');
    $this->assertText('Node title 50', 'Node 50 displayed after update.');
    $this->assertText('First test comment', 'Comment 1 displayed after update.');
    $this->assertText('Reply to first test comment', 'Comment 2 displayed after update.');

    // Directly check the comment language property on the first comment.
    $comment = db_query('SELECT * FROM {comment} WHERE cid = :cid', array(':cid' => 1))->fetchObject();
    $this->assertTrue($comment->langcode == 'und', 'Comment 1 language code found.');

    // Ensure that the language switcher has been correctly upgraded. We need to
    // assert the expected HTML id because the block might appear even if the
    // language negotiation settings are not properly upgraded.
    // @todo Blocks are not being upgraded.
    //   $this->assertTrue($this->xpath('//div[@id="block-language-language-interface"]'), 'The language switcher block is being correctly showed.');

    // Test that the 'language' property was properly renamed to 'langcode'.
    $language_none_nid = 50;
    $spanish_nid = 51;
    // Check directly for the node langcode.
    $this->assertEqual(node_load($language_none_nid)->language()->id, Language::LANGCODE_NOT_SPECIFIED, "'language' property was renamed to 'langcode' for Language::LANGCODE_NOT_SPECIFIED node.");
    $this->assertEqual(node_load($spanish_nid)->language()->id, 'ca', "'language' property was renamed to 'langcode' for Catalan node.");

    // Check for node content type settings upgrade.
    $this->drupalGet('node/add/article');
    $this->assertField('langcode', 'There is a language selector.');
    $this->drupalGet('node/add/page');
    $this->assertNoField('langcode', 'There is no language selector.');

    // Check that the user language value was retained in both langcode and
    // preferred_langcode.
    $user = db_query('SELECT * FROM {users} WHERE uid = :uid', array(':uid' => 1))->fetchObject();
    $this->assertEqual($user->langcode, 'ca');
    $this->assertEqual($user->preferred_langcode, 'ca');

    // A langcode property was added to vocabularies and terms. Check that
    // existing vocabularies and terms got assigned the site default language.
    $vocabulary = entity_load('taxonomy_vocabulary', 'tags');
    $this->assertEqual($vocabulary->langcode, 'ca');
    $term = db_query('SELECT * FROM {taxonomy_term_data} WHERE tid = :tid', array(':tid' => 1))->fetchObject();
    $this->assertEqual($term->langcode, 'ca');

    // A langcode property was added to files. Check that existing files got
    // assigned Language::LANGCODE_NOT_SPECIFIED.
    $file = db_query('SELECT * FROM {file_managed} WHERE fid = :fid', array(':fid' => 1))->fetchObject();
    $this->assertEqual($file->langcode, Language::LANGCODE_NOT_SPECIFIED);

    // Check if language negotiation weights were renamed properly. This is a
    // reproduction of the previous weights from the dump.
    $expected_weights = array(
      'language-url' => '-8',
      'language-session' => '-6',
      'language-user' => '-4',
      'language-browser' => '-2',
      'language-selected' => '10',
    );
    // Check that locale_language_providers_weight_language is correctly
    // renamed.
    $current_weights = update_variable_get('language_negotiation_methods_weight_language_interface', array());
    $this->assertTrue(serialize($expected_weights) == serialize($current_weights), 'Language negotiation method weights upgraded.');
    $this->assertTrue(isset($current_weights['language-selected']), 'Language-selected is present.');
    $this->assertFalse(isset($current_weights['language-default']), 'Language-default is not present.');

    // @todo We only need language.inc here because LANGUAGE_NEGOTIATION_SELECTED
    //   is defined there. Remove this line once that has been converted to a class
    //   constant.
    require_once DRUPAL_ROOT . '/core/includes/language.inc';

    // Check that negotiation callback was added to language_negotiation_language_interface.
    $language_negotiation_language_interface = update_variable_get('language_negotiation_language_interface', NULL);
    $this->assertTrue(isset($language_negotiation_language_interface[LANGUAGE_NEGOTIATION_SELECTED]['callbacks']['negotiation']), 'Negotiation callback was added to language_negotiation_language_interface.');

    // Look up migrated plural string.
    $source_string = db_query('SELECT * FROM {locales_source} WHERE lid = 22')->fetchObject();
    $this->assertEqual($source_string->source, implode(LOCALE_PLURAL_DELIMITER, array('1 byte', '@count bytes')));

    $translation_string = db_query("SELECT * FROM {locales_target} WHERE lid = 22 AND language = 'hr'")->fetchObject();
    $this->assertEqual($translation_string->translation, implode(LOCALE_PLURAL_DELIMITER, array('@count bajt', '@count bajta', '@count bajtova')));
    $this->assertTrue(!isset($translation_string->plural), 'Chained plural indicator removed.');
    $this->assertTrue(!isset($translation_string->plid), 'Chained plural indicator removed.');

    $source_string = db_query('SELECT * FROM {locales_source} WHERE lid IN (23, 24)')->fetchObject();
    $this->assertTrue(empty($source_string), 'Individual plural variant source removed');
    $translation_string = db_query("SELECT * FROM {locales_target} WHERE lid IN (23, 24)")->fetchObject();
    $this->assertTrue(empty($translation_string), 'Individual plural variant translation removed');

    $translation_string = db_query("SELECT * FROM {locales_target} WHERE lid = 22 AND language = 'ca'")->fetchObject();
    $this->assertEqual($translation_string->translation, implode(LOCALE_PLURAL_DELIMITER, array('1 byte', '@count bytes')));

    // Ensure that re-indexing search for a specific language does not fail. It
    // does not matter if the sid exists on not. This tests whether or not
    // search_update_8001() has added the langcode fields.
    try {
      search_reindex(1, 'node', FALSE, 'ca');
      $this->pass("Calling search_reindex succeeds after upgrade.");
    }
    catch (DatabaseException $e) {
      $this->fail("Calling search_reindex fails after upgrade.");
    }
  }

  /**
   * Tests language domain upgrade path.
   */
  public function testLanguageUrlUpgrade() {
    $language_domain = 'ca.example.com';
    db_update('languages')->fields(array('domain' => 'http://' . $language_domain . ':8888'))->condition('language', 'ca')->execute();
    $this->variable_set('locale_language_negotiation_url_part', 1);

    $this->assertTrue($this->performUpgrade(), 'The upgrade was completed successfully.');

    language_negotiation_include();
    $domains = language_negotiation_url_domains();
    $this->assertTrue($domains['ca'] == $language_domain, 'Language domain for Catalan properly upgraded.');
  }

  /**
   * Tests upgrading translations without plurals.
   */
  public function testLanguageNoPluralsUpgrade() {
    // Remove all plural translations from the database.
    db_delete('locales_target')->condition('plural', 0, '<>')->execute();

    $this->assertTrue($this->performUpgrade(), 'The upgrade was completed successfully.');

    // Check if locale_update_8005() is succesfully completed by checking
    // whether index 'plural' has been removed.
    $this->assertFalse(db_index_exists('locales_target', 'plural'), 'Translations without plurals upgraded.');
  }

}
