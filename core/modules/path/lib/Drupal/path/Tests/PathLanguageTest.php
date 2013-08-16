<?php

/**
 * @file
 * Definition of Drupal\path\Tests\PathLanguageTest.
 */

namespace Drupal\path\Tests;

use Drupal\Core\Language\Language;

/**
 * Tests URL aliases for translated nodes.
 */
class PathLanguageTest extends PathTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('path', 'locale', 'translation');

  public static function getInfo() {
    return array(
      'name' => 'Path aliases with translated nodes',
      'description' => 'Confirm that paths work with translated nodes',
      'group' => 'Path',
    );
  }

  function setUp() {
    parent::setUp();

    // Create and login user.
    $this->web_user = $this->drupalCreateUser(array('edit any page content', 'create page content', 'administer url aliases', 'create url aliases', 'administer languages', 'translate all content', 'access administration pages', 'administer content types'));
    $this->drupalLogin($this->web_user);

    // Enable French language.
    $edit = array();
    $edit['predefined_langcode'] = 'fr';

    $this->drupalPost('admin/config/regional/language/add', $edit, t('Add language'));

    // Enable URL language detection and selection.
    $edit = array('language_interface[enabled][language-url]' => 1);
    $this->drupalPost('admin/config/regional/language/detection', $edit, t('Save settings'));
  }

  /**
   * Test alias functionality through the admin interfaces.
   */
  function testAliasTranslation() {
    // Set 'page' content type to enable translation.
    $edit = array(
      'language_configuration[language_show]' => TRUE,
    );
    $this->drupalPost('admin/structure/types/manage/page', $edit, t('Save content type'));
    $this->assertRaw(t('The content type %type has been updated.', array('%type' => 'Basic page')), 'Basic page content type has been updated.');
    variable_set('node_type_language_translation_enabled_page', TRUE);

    $english_node = $this->drupalCreateNode(array('type' => 'page'));
    $english_alias = $this->randomName();

    // Edit the node to set language and path.
    $edit = array();
    $edit['langcode'] = 'en';
    $edit['path[alias]'] = $english_alias;
    $this->drupalPost('node/' . $english_node->id() . '/edit', $edit, t('Save'));

    // Confirm that the alias works.
    $this->drupalGet($english_alias);
    $this->assertText($english_node->label(), 'Alias works.');

    // Translate the node into French.
    $this->drupalGet('node/' . $english_node->id() . '/translate');
    $this->clickLink(t('Add translation'));
    $edit = array();
    $langcode = Language::LANGCODE_NOT_SPECIFIED;
    $edit["title"] = $this->randomName();
    $edit["body[$langcode][0][value]"] = $this->randomName();
    $french_alias = $this->randomName();
    $edit['path[alias]'] = $french_alias;
    $this->drupalPost(NULL, $edit, t('Save'));

    // Clear the path lookup cache.
    $this->container->get('path.alias_manager')->cacheClear();

    // Languages are cached on many levels, and we need to clear those caches.
    drupal_static_reset('language_list');
    $this->rebuildContainer();
    $languages = language_list();

    // Ensure the node was created.
    $french_node = $this->drupalGetNodeByTitle($edit["title"]);
    $this->assertTrue(($french_node), 'Node found in database.');

    // Confirm that the alias works.
    $this->drupalGet('fr/' . $edit['path[alias]']);
    $this->assertText($french_node->label(), 'Alias for French translation works.');

    // Confirm that the alias is returned by url().
    $url = $this->container->get('url_generator')->generateFromPath('node/' . $french_node->id(), array('language' => $languages[$french_node->language()->id]));

    $this->assertTrue(strpos($url, $edit['path[alias]']), 'URL contains the path alias.');

    // Confirm that the alias works even when changing language negotiation
    // options. Enable User language detection and selection over URL one.
    $edit = array(
      'language_interface[enabled][language-user]' => 1,
      'language_interface[weight][language-user]' => -9,
      'language_interface[enabled][language-url]' => 1,
      'language_interface[weight][language-url]' => -8,
    );
    $this->drupalPost('admin/config/regional/language/detection', $edit, t('Save settings'));

    // Change user language preference.
    $edit = array('preferred_langcode' => 'fr');
    $this->drupalPost("user/" . $this->web_user->id() . "/edit", $edit, t('Save'));

    // Check that the English alias works. In this situation French is the
    // current UI and content language, while URL language is English (since we
    // do not have a path prefix we fall back to the site's default language).
    // We need to ensure that the user language preference is not taken into
    // account while determining the path alias language, because if this
    // happens we have no way to check that the path alias is valid: there is no
    // path alias for French matching the english alias. So the alias manager
    // needs to use the URL language to check whether the alias is valid.
    $this->drupalGet($english_alias);
    $this->assertText($english_node->label(), 'Alias for English translation works.');

    // Check that the French alias works.
    $this->drupalGet("fr/$french_alias");
    $this->assertText($french_node->label(), 'Alias for French translation works.');

    // Disable URL language negotiation.
    $edit = array('language_interface[enabled][language-url]' => FALSE);
    $this->drupalPost('admin/config/regional/language/detection', $edit, t('Save settings'));

    // Check that the English alias still works.
    $this->drupalGet($english_alias);
    $this->assertText($english_node->label(), 'Alias for English translation works.');

    // Check that the French alias is not available. We check the unprefixed
    // alias because we disabled URL language negotiation above. In this
    // situation only aliases in the default language and language neutral ones
    // should keep working.
    $this->drupalGet($french_alias);
    $this->assertResponse(404, 'Alias for French translation is unavailable when URL language negotiation is disabled.');

    // The alias manager has an internal path lookup cache. Check to see that
    // it has the appropriate contents at this point.
    $this->container->get('path.alias_manager')->cacheClear();
    $french_node_path = $this->container->get('path.alias_manager')->getSystemPath($french_alias, $french_node->language()->id);
    $this->assertEqual($french_node_path, 'node/' . $french_node->id(), 'Normal path works.');
    // Second call should return the same path.
    $french_node_path = $this->container->get('path.alias_manager')->getSystemPath($french_alias, $french_node->language()->id);
    $this->assertEqual($french_node_path, 'node/' . $french_node->id(), 'Normal path is the same.');

    // Confirm that the alias works.
    $french_node_alias = $this->container->get('path.alias_manager')->getPathAlias('node/' . $french_node->id(), $french_node->language()->id);
    $this->assertEqual($french_node_alias, $french_alias, 'Alias works.');
    // Second call should return the same alias.
    $french_node_alias = $this->container->get('path.alias_manager')->getPathAlias('node/' . $french_node->id(), $french_node->language()->id);
    $this->assertEqual($french_node_alias, $french_alias, 'Alias is the same.');
  }
}
