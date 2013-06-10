<?php

/**
 * @file
 * Definition of Drupal\translation\Tests\TranslationTest.
 */

namespace Drupal\translation\Tests;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Language\Language;
use Drupal\simpletest\WebTestBase;

/**
 * Functional tests for the Translation module.
 */
class TranslationTest extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('translation', 'translation_test', 'block');

  protected $book;

  public static function getInfo() {
    return array(
      'name' => 'Translation functionality',
      'description' => 'Create a basic page with translation, modify the page outdating translation, and update translation.',
      'group' => 'Translation'
    );
  }

  function setUp() {
    parent::setUp();

    $this->drupalCreateContentType(array('type' => 'page', 'name' => 'Basic page'));

    // Setup users.
    $this->admin_user = $this->drupalCreateUser(array('bypass node access', 'administer nodes', 'administer languages', 'administer content types', 'administer blocks', 'access administration pages', 'translate all content'));
    $this->translator = $this->drupalCreateUser(array('create page content', 'edit own page content', 'translate all content'));
    $this->limited_translator = $this->drupalCreateUser(array('create page content', 'edit own page content', 'translate own content'));

    $this->drupalLogin($this->admin_user);

    // Add languages.
    $this->addLanguage('en');
    $this->addLanguage('es');

    // Set "Basic page" content type to use multilingual support with
    // translation.
    $this->drupalGet('admin/structure/types/manage/page');
    $edit = array('language_configuration[language_show]' => TRUE, 'node_type_language_translation_enabled' => TRUE);
    $this->drupalPost('admin/structure/types/manage/page', $edit, t('Save content type'));
    $this->assertRaw(t('The content type %type has been updated.', array('%type' => 'Basic page')), 'Basic page content type has been updated.');

    // Enable the language switcher block.
    $this->drupalPlaceBlock('language_block:language_interface');

    // Reset static caches in our local language environment.
    $this->resetCaches();

    $this->drupalLogin($this->translator);
  }

  /**
   * Creates, modifies, and updates a basic page with a translation.
   */
  function testContentTranslation() {
    // Create Basic page in English.
    $node_title = $this->randomName();
    $node_body =  $this->randomName();
    $node = $this->createPage($node_title, $node_body, 'en');
    // Unpublish the original node to check that this has no impact on the
    // translation overview page, publish it again afterwards.
    $this->drupalLogin($this->admin_user);
    $this->drupalPost('node/' . $node->nid . '/edit', array(), t('Save and unpublish'));
    $this->drupalGet('node/' . $node->nid . '/translate');
    $this->drupalPost('node/' . $node->nid . '/edit', array(), t('Save and publish'));
    $this->drupalLogin($this->translator);

    // Check that the "add translation" link uses a localized path.
    $languages = language_list();
    $prefixes = language_negotiation_url_prefixes();
    $this->drupalGet('node/' . $node->nid . '/translate');
    $this->assertLinkByHref($prefixes['es'] . '/node/add/' . $node->type, 0, format_string('The "add translation" link for %language points to the localized path of the target language.', array('%language' => $languages['es']->name)));

    // Submit translation in Spanish.
    $node_translation_title = $this->randomName();
    $node_translation_body = $this->randomName();
    $node_translation = $this->createTranslation($node, $node_translation_title, $node_translation_body, 'es');

    // Check that the "edit translation" and "view node" links use localized
    // paths.
    $this->drupalGet('node/' . $node->nid . '/translate');
    $this->assertLinkByHref($prefixes['es'] . '/node/' . $node_translation->nid . '/edit', 0, format_string('The "edit" link for the translation in %language points to the localized path of the translation language.', array('%language' => $languages['es']->name)));
    $this->assertLinkByHref($prefixes['es'] . '/node/' . $node_translation->nid, 0, format_string('The "view" link for the translation in %language points to the localized path of the translation language.', array('%language' => $languages['es']->name)));

    // Attempt to submit a duplicate translation by visiting the node/add page
    // with identical query string.
    $this->drupalGet('node/add/page', array('query' => array('translation' => $node->nid, 'target' => 'es')));
    $this->assertRaw(t('A translation of %title in %language already exists', array('%title' => $node_title, '%language' => $languages['es']->name)), 'Message regarding attempted duplicate translation is displayed.');

    // Attempt a resubmission of the form - this emulates using the back button
    // to return to the page then resubmitting the form without a refresh.
    $edit = array();
    $langcode = Language::LANGCODE_NOT_SPECIFIED;
    $edit["title"] = $this->randomName();
    $edit["body[$langcode][0][value]"] = $this->randomName();
    $this->drupalPost('node/add/page', $edit, t('Save'), array('query' => array('translation' => $node->nid, 'language' => 'es')));
    $duplicate = $this->drupalGetNodeByTitle($edit["title"]);
    $this->assertEqual($duplicate->tnid, 0, 'The node does not have a tnid.');

    // Update original and mark translation as outdated.
    $node_body = $this->randomName();
    $node->body[Language::LANGCODE_NOT_SPECIFIED][0]['value'] = $node_body;
    $edit = array();
    $edit["body[$langcode][0][value]"] = $node_body;
    $edit['translation[retranslate]'] = TRUE;
    $this->drupalPost('node/' . $node->nid . '/edit', $edit, t('Save'));
    $this->assertRaw(t('Basic page %title has been updated.', array('%title' => $node_title)), 'Original node updated.');

    // Check to make sure that interface shows translation as outdated.
    $this->drupalGet('node/' . $node->nid . '/translate');
    $this->assertRaw('<span class="marker">' . t('outdated') . '</span>', 'Translation marked as outdated.');

    // Update translation and mark as updated.
    $edit = array();
    $edit["body[$langcode][0][value]"] = $this->randomName();
    $edit['translation[status]'] = FALSE;
    $this->drupalPost('node/' . $node_translation->nid . '/edit', $edit, t('Save'));
    $this->assertRaw(t('Basic page %title has been updated.', array('%title' => $node_translation_title)), 'Translated node updated.');

    // Confirm that language neutral is an option for translators when there are
    // disabled languages.
    $this->drupalGet('node/add/page');
    $this->assertFieldByXPath('//select[@name="langcode"]//option', Language::LANGCODE_NOT_SPECIFIED, 'Language neutral is available in language selection with disabled languages.');
    $node2 = $this->createPage($this->randomName(), $this->randomName(), Language::LANGCODE_NOT_SPECIFIED);
    $this->assertRaw($node2->body[Language::LANGCODE_NOT_SPECIFIED][0]['value'], 'Language neutral content created with disabled languages available.');

    // Leave just one language installed and check that the translation overview
    // page is still accessible.
    $this->drupalLogin($this->admin_user);
    $this->drupalPost('admin/config/regional/language/delete/es', array(), t('Delete'));
    $this->drupalLogin($this->translator);
    $this->drupalGet('node/' . $node->nid . '/translate');
    $this->assertRaw(t('Translations of %title', array('%title' => $node->label())), 'Translation overview page available with only one language enabled.');
  }

  /**
   * Checks that the language switch links behave properly.
   */
  function testLanguageSwitchLinks() {
    // Create a Basic page in English and its translation in Spanish.
    $node = $this->createPage($this->randomName(), $this->randomName(), 'en');
    $translation_es = $this->createTranslation($node, $this->randomName(), $this->randomName(), 'es');

    // Check that language switch links are correctly shown for languages
    // we have translations for.
    $this->assertLanguageSwitchLinks($node, $translation_es);
    $this->assertLanguageSwitchLinks($translation_es, $node);

    // Check that links to the displayed translation appear only in the language
    // switcher block.
    $this->assertLanguageSwitchLinks($node, $node, FALSE, 'node');
    $this->assertLanguageSwitchLinks($node, $node, TRUE, 'block-language');

    // Unpublish the Spanish translation to check that the related language
    // switch link is not shown.
    $this->drupalLogin($this->admin_user);
    $this->drupalPost("node/$translation_es->nid/edit", array(), t('Save and unpublish'));
    $this->drupalLogin($this->translator);
    $this->assertLanguageSwitchLinks($node, $translation_es, FALSE);

    // Check that content translation links are shown even when no language
    // negotiation is configured.
    $this->drupalLogin($this->admin_user);
    $edit = array('language_interface[enabled][language-url]' => FALSE);
    $this->drupalPost('admin/config/regional/language/detection', $edit, t('Save settings'));
    $this->resetCaches();
    $this->drupalPost("node/$translation_es->nid/edit", array(), t('Save and publish'));
    $this->drupalLogin($this->translator);
    $this->assertLanguageSwitchLinks($node, $translation_es, TRUE, 'node');
  }

  /**
   * Tests that the language switcher block alterations work as intended.
   */
  function testLanguageSwitcherBlockIntegration() {
    // Add Italian to have three items in the language switcher block.
    $this->drupalLogin($this->admin_user);
    $this->addLanguage('it');
    $this->resetCaches();
    $this->drupalLogin($this->translator);

    // Create a Basic page in English.
    $type = 'block-language';
    $node = $this->createPage($this->randomName(), $this->randomName(), 'en');
    $this->assertLanguageSwitchLinks($node, $node, TRUE, $type);
    $this->assertLanguageSwitchLinks($node, $this->emptyNode('es'), TRUE, $type);
    $this->assertLanguageSwitchLinks($node, $this->emptyNode('it'), TRUE, $type);

    // Create the Spanish translation.
    $translation_es = $this->createTranslation($node, $this->randomName(), $this->randomName(), 'es');
    $this->assertLanguageSwitchLinks($node, $node, TRUE, $type);
    $this->assertLanguageSwitchLinks($node, $translation_es, TRUE, $type);
    $this->assertLanguageSwitchLinks($node, $this->emptyNode('it'), TRUE, $type);

    // Create the Italian translation.
    $translation_it = $this->createTranslation($node, $this->randomName(), $this->randomName(), 'it');
    $this->assertLanguageSwitchLinks($node, $node, TRUE, $type);
    $this->assertLanguageSwitchLinks($node, $translation_es, TRUE, $type);
    $this->assertLanguageSwitchLinks($node, $translation_it, TRUE, $type);

    // Create a language neutral node and check that the language switcher is
    // left untouched.
    $node2 = $this->createPage($this->randomName(), $this->randomName(), Language::LANGCODE_NOT_SPECIFIED);
    $node2_en = (object) array('nid' => $node2->nid, 'langcode' => 'en');
    $node2_es = (object) array('nid' => $node2->nid, 'langcode' => 'es');
    $node2_it = (object) array('nid' => $node2->nid, 'langcode' => 'it');
    $this->assertLanguageSwitchLinks($node2_en, $node2_en, TRUE, $type);
    $this->assertLanguageSwitchLinks($node2_en, $node2_es, TRUE, $type);
    $this->assertLanguageSwitchLinks($node2_en, $node2_it, TRUE, $type);

    // Disable translation support to check that the language switcher is left
    // untouched only for new nodes.
    $this->drupalLogin($this->admin_user);
    $edit = array('language_configuration[language_show]' => FALSE, 'node_type_language_translation_enabled' => FALSE);
    $this->drupalPost('admin/structure/types/manage/page', $edit, t('Save content type'));
    $this->drupalLogin($this->translator);

    // Existing translations trigger alterations even if translation support is
    // disabled.
    $this->assertLanguageSwitchLinks($node, $node, TRUE, $type);
    $this->assertLanguageSwitchLinks($node, $translation_es, TRUE, $type);
    $this->assertLanguageSwitchLinks($node, $translation_it, TRUE, $type);

    // Check that new nodes with a language assigned do not trigger language
    // switcher alterations when translation support is disabled.
    $node = $this->createPage($this->randomName(), $this->randomName());
    $node_es = (object) array('nid' => $node->nid, 'langcode' => 'es');
    $node_it = (object) array('nid' => $node->nid, 'langcode' => 'it');
    $this->assertLanguageSwitchLinks($node, $node, TRUE, $type);
    $this->assertLanguageSwitchLinks($node, $node_es, TRUE, $type);
    $this->assertLanguageSwitchLinks($node, $node_it, TRUE, $type);
  }

  /**
   * Checks that users with "translate own content" role only can translate own content.
   */
  function testTranslateOwnContentRole() {
    // Create a Basic page in English and its translation in Spanish with user
    // that has "translate own content" role.
    $this->drupalLogin($this->limited_translator);
    $node = $this->createPage($this->randomName(), $this->randomName(), 'en');
    $this->assertLinkByHref('node/' . $node->nid . '/translate', 0, 'User with "translate own content" role can see translate link');
    $this->drupalGet('node/' . $node->nid . '/translate');
    $this->assertResponse(200, 'User with "translate own content" role can get translate page');
    $translation_es = $this->createTranslation($node, $this->randomName(), $this->randomName(), 'es');

    // Create a page as translator user.
    $this->drupalLogin($this->translator);
    $node = $this->createPage($this->randomName(), $this->randomName(), 'en');
    // Change to limited_translator and check that translate links aren't shown.
    $this->drupalLogin($this->limited_translator);
    $this->assertNoLinkByHref('node/' . $node->nid . '/translate', 'User with "translate own content" role can\'t see translate link');
    // Check if user with "translate own content" role can see translate page
    // from other user's node.
    $this->drupalGet('node/' . $node->nid . '/translate');
    $this->assertResponse(403, 'User with "translate own content" role can\'t get translate page');

    // Try to change to translate with "brute force".
    $this->drupalGet('node/add/page', array('query' => array('translation' => $node->nid, 'target' => 'es')));
    $this->assertResponse(403, 'User with "translate own content" role can\'t get create translate page');
  }

  /**
   * Resets static caches to make the test code match the client-side behavior.
   */
  function resetCaches() {
    drupal_static_reset('language_list');
    $this->rebuildContainer();
  }

  /**
   * Returns an empty node data structure.
   *
   * @param $langcode
   *   The language code.
   *
   * @return
   *   An empty node data structure.
   */
  function emptyNode($langcode) {
    return (object) array('nid' => NULL, 'langcode' => $langcode);
  }

  /**
   * Installs the specified language, or enables it if it is already installed.
   *
   * @param $langcode
   *   The language code to check.
   */
  function addLanguage($langcode) {
    // Check to make sure that language has not already been installed.
    $this->drupalGet('admin/config/regional/language');

    if (strpos($this->drupalGetContent(), 'languages[' . $langcode . ']') === FALSE) {
      // Doesn't have language installed so add it.
      $edit = array();
      $edit['predefined_langcode'] = $langcode;
      $this->drupalPost('admin/config/regional/language/add', $edit, t('Add language'));

      // Make sure we are not using a stale list.
      drupal_static_reset('language_list');
      $languages = language_list();
      $this->assertTrue(array_key_exists($langcode, $languages), 'Language was installed successfully.');

      if (array_key_exists($langcode, $languages)) {
        $this->assertRaw(t('The language %language has been created and can now be used.', array('%language' => $languages[$langcode]->name)), 'Language has been created.');
      }
    }
    else {
      // It's installed. No need to do anything.
      $this->assertTrue(TRUE, 'Language [' . $langcode . '] already installed.');
    }
  }

  /**
   * Creates a "Basic page" in the specified language.
   *
   * @param $title
   *   The title of a basic page in the specified language.
   * @param $body
   *   The body of a basic page in the specified language.
   * @param $langcode
   *   (optional) Language code.
   *
   * @return
   *   A node object.
   */
  function createPage($title, $body, $langcode = NULL) {
    $edit = array();
    $field_langcode = Language::LANGCODE_NOT_SPECIFIED;
    $edit["title"] = $title;
    $edit["body[$field_langcode][0][value]"] = $body;
    if (!empty($langcode)) {
      $edit['langcode'] = $langcode;
    }
    $this->drupalPost('node/add/page', $edit, t('Save'));
    $this->assertRaw(t('Basic page %title has been created.', array('%title' => $title)), 'Basic page created.');

    // Check to make sure the node was created.
    $node = $this->drupalGetNodeByTitle($title);
    $this->assertTrue($node, 'Node found in database.');

    return $node;
  }

  /**
   * Creates a translation for a basic page in the specified language.
   *
   * @param \Drupal\Core\Entity\EntityInterface $node
   *   The basic page to create the translation for.
   * @param $title
   *   The title of a basic page in the specified language.
   * @param $body
   *   The body of a basic page in the specified language.
   * @param $langcode
   *   (optional) Language code.
   *
   * @return
   *   Translation object.
   */
  function createTranslation(EntityInterface $node, $title, $body, $langcode) {
    $this->drupalGet('node/add/page', array('query' => array('translation' => $node->nid, 'target' => $langcode)));

    $field_langcode = Language::LANGCODE_NOT_SPECIFIED;
    $body_key = "body[$field_langcode][0][value]";
    $this->assertFieldByXPath('//input[@id="edit-title"]', $node->label(), "Original title value correctly populated.");
    $this->assertFieldByXPath("//textarea[@name='$body_key']", $node->body[Language::LANGCODE_NOT_SPECIFIED][0]['value'], "Original body value correctly populated.");

    $edit = array();
    $edit["title"] = $title;
    $edit[$body_key] = $body;
    $this->drupalPost(NULL, $edit, t('Save'));
    $this->assertRaw(t('Basic page %title has been created.', array('%title' => $title)), 'Translation created.');

    // Check to make sure that translation was successful.
    $translation = $this->drupalGetNodeByTitle($title);
    $this->assertTrue($translation, 'Node found in database.');
    $this->assertTrue($translation->tnid == $node->nid, 'Translation set id correctly stored.');

    return $translation;
  }

  /**
   * Asserts an element identified by the given XPath has the given content.
   *
   * @param $xpath
   *   The XPath used to find the element.
   * @param array $arguments
   *   An array of arguments with keys in the form ':name' matching the
   *   placeholders in the query. The values may be either strings or numeric
   *   values.
   * @param $value
   *   The text content of the matched element to assert.
   * @param $message
   *   The message to display.
   * @param $group
   *   The group this message belongs to.
   *
   * @return
   *   TRUE on pass, FALSE on fail.
   */
  function assertContentByXPath($xpath, array $arguments = array(), $value = NULL, $message = '', $group = 'Other') {
    $found = $this->findContentByXPath($xpath, $arguments, $value);
    return $this->assertTrue($found, $message, $group);
  }

  /**
   * Tests whether the specified language switch links are found.
   *
   * @param $node
   *   The node to display.
   * @param $translation
   *   The translation whose link has to be checked.
   * @param $find
   *   TRUE if the link must be present in the node page.
   * @param $types
   *   The page areas to be checked.
   *
   * @return
   *   TRUE if the language switch links are found, FALSE if not.
   */
  function assertLanguageSwitchLinks($node, $translation, $find = TRUE, $types = NULL) {
    if (empty($types)) {
      $types = array('node', 'block-language');
    }
    elseif (is_string($types)) {
      $types = array($types);
    }

    $result = TRUE;
    $languages = language_list();
    $page_language = $languages[$node->langcode];
    $translation_language = $languages[$translation->langcode];
    $url = url("node/$translation->nid", array('language' => $translation_language));

    $this->drupalGet("node/$node->nid", array('language' => $page_language));

    foreach ($types as $type) {
      $args = array('%translation_language' => $translation_language->name, '%page_language' => $page_language->name, '%type' => $type);
      if ($find) {
        $message = format_string('[%page_language] Language switch item found for %translation_language language in the %type page area.', $args);
      }
      else {
        $message = format_string('[%page_language] Language switch item not found for %translation_language language in the %type page area.', $args);
      }

      // node uses the article tag.
      $tag = $type == 'node' ? 'article' : 'div';

      if (!empty($translation->nid)) {
        $xpath = '//' . $tag . '[contains(@class, :type)]//a[@href=:url]';
      }
      else {
        $xpath = '//' . $tag . '[contains(@class, :type)]//span[contains(@class, "locale-untranslated")]';
      }

      $found = $this->findContentByXPath($xpath, array(':type' => $type, ':url' => $url), $translation_language->name);
      $result = $this->assertTrue($found == $find, $message) && $result;
    }

    return $result;
  }

  /**
   * Searches for elements matching the given xpath and value.
   *
   * @param $xpath
   *   The XPath used to find the element.
   * @param array $arguments
   *   An array of arguments with keys in the form ':name' matching the
   *   placeholders in the query. The values may be either strings or numeric
   *   values.
   * @param $value
   *   The text content of the matched element to assert.
   *
   * @return
   *   TRUE if found, otherwise FALSE.
   */
  function findContentByXPath($xpath, array $arguments = array(), $value = NULL) {
    $elements = $this->xpath($xpath, $arguments);

    $found = TRUE;
    if ($value && $elements) {
      $found = FALSE;
      foreach ($elements as $element) {
        if ((string) $element == $value) {
          $found = TRUE;
          break;
        }
      }
    }

    return $elements && $found;
  }
}
