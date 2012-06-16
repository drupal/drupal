<?php

/**
 * @file
 * Definition of Drupal\locale\Tests\LocaleContentTest.
 */

namespace Drupal\locale\Tests;

use Drupal\simpletest\WebTestBase;

/**
 * Functional tests for multilingual support on nodes.
 */
class LocaleContentTest extends WebTestBase {
  protected $profile = 'standard';

  public static function getInfo() {
    return array(
      'name' => 'Content language settings',
      'description' => 'Checks you can enable multilingual support on content types and configure a language for a node.',
      'group' => 'Locale',
    );
  }

  function setUp() {
    parent::setUp('locale');
  }

  /**
   * Verifies that machine name fields are always LTR.
   */
  function testMachineNameLTR() {
    // User to add and remove language.
    $admin_user = $this->drupalCreateUser(array('administer languages', 'administer content types', 'access administration pages'));

    // Log in as admin.
    $this->drupalLogin($admin_user);

    // Verify that the machine name field is LTR for a new content type.
    $this->drupalGet('admin/structure/types/add');
    $this->assertFieldByXpath('//input[@name="type" and @dir="ltr"]', NULL, 'The machine name field is LTR when no additional language is configured.');

    // Install the Arabic language (which is RTL) and configure as the default.
    $edit = array();
    $edit['predefined_langcode'] = 'ar';
    $this->drupalPost('admin/config/regional/language/add', $edit, t('Add language'));

    $edit = array();
    $edit['site_default'] = 'ar';
    $this->drupalPost(NULL, $edit, t('Save configuration'));

    // Verify that the machine name field is still LTR for a new content type.
    $this->drupalGet('admin/structure/types/add');
    $this->assertFieldByXpath('//input[@name="type" and @dir="ltr"]', NULL, 'The machine name field is LTR when the default language is RTL.');
  }

  /**
   * Test if a content type can be set to multilingual and language is present.
   */
  function testContentTypeLanguageConfiguration() {
    global $base_url;

    // User to add and remove language.
    $admin_user = $this->drupalCreateUser(array('administer languages', 'administer content types', 'access administration pages'));
    // User to create a node.
    $web_user = $this->drupalCreateUser(array('create article content', 'create page content', 'edit any page content'));

    // Add custom language.
    $this->drupalLogin($admin_user);
    // Code for the language.
    $langcode = 'xx';
    // The English name for the language.
    $name = $this->randomName(16);
    $edit = array(
      'predefined_langcode' => 'custom',
      'langcode' => $langcode,
      'name' => $name,
      'direction' => '0',
    );
    $this->drupalPost('admin/config/regional/language/add', $edit, t('Add custom language'));

    // Set "Basic page" content type to use multilingual support.
    $this->drupalGet('admin/structure/types/manage/page');
    $this->assertText(t('Language settings'), t('Multilingual support fieldset present on content type configuration form.'));
    $edit = array(
      'node_type_language_hidden' => FALSE,
    );
    $this->drupalPost('admin/structure/types/manage/page', $edit, t('Save content type'));
    $this->assertRaw(t('The content type %type has been updated.', array('%type' => 'Basic page')), t('Basic page content type has been updated.'));
    $this->drupalLogout();

    // Verify language selection is not present on add article form.
    $this->drupalLogin($web_user);
    $this->drupalGet('node/add/article');
    // Verify language select list is not present.
    $this->assertNoFieldByName('language', NULL, t('Language select not present on add article form.'));

    // Verify language selection appears on add "Basic page" form.
    $this->drupalGet('node/add/page');
    // Verify language select list is present.
    $this->assertFieldByName('langcode', NULL, t('Language select present on add Basic page form.'));
    // Ensure language appears.
    $this->assertText($name, t('Language present.'));

    // Create "Basic page" content.
    $node_title = $this->randomName();
    $node_body =  $this->randomName();
    $edit = array(
      'type' => 'page',
      'title' => $node_title,
      'body' => array($langcode => array(array('value' => $node_body))),
      'langcode' => $langcode,
    );
    $node = $this->drupalCreateNode($edit);
    // Edit the content and ensure correct language is selected.
    $path = 'node/' . $node->nid . '/edit';
    $this->drupalGet($path);
    $this->assertRaw('<option value="' . $langcode . '" selected="selected">' .  $name . '</option>', t('Correct language selected.'));
    // Ensure we can change the node language.
    $edit = array(
      'langcode' => 'en',
    );
    $this->drupalPost($path, $edit, t('Save'));
    $this->assertRaw(t('%title has been updated.', array('%title' => $node_title)), t('Basic page content updated.'));

    $this->drupalLogout();
  }

  /**
   * Test if a dir and lang tags exist in node's attributes.
   */
  function testContentTypeDirLang() {
    // User to add and remove language.
    $admin_user = $this->drupalCreateUser(array('administer languages', 'administer content types', 'access administration pages'));
    // User to create a node.
    $web_user = $this->drupalCreateUser(array('create article content', 'edit own article content'));

    // Login as admin.
    $this->drupalLogin($admin_user);

    // Install Arabic language.
    $edit = array();
    $edit['predefined_langcode'] = 'ar';
    $this->drupalPost('admin/config/regional/language/add', $edit, t('Add language'));

    // Install Spanish language.
    $edit = array();
    $edit['predefined_langcode'] = 'es';
    $this->drupalPost('admin/config/regional/language/add', $edit, t('Add language'));

    // Set "Article" content type to use multilingual support.
    $this->drupalGet('admin/structure/types/manage/article');
    $edit = array(
      'node_type_language_hidden' => FALSE,
    );
    $this->drupalPost('admin/structure/types/manage/article', $edit, t('Save content type'));
    $this->assertRaw(t('The content type %type has been updated.', array('%type' => 'Article')), t('Article content type has been updated.'));
    $this->drupalLogout();

    // Login as web user to add new article.
    $this->drupalLogin($web_user);

    // Create three nodes: English, Arabic and Spanish.
    $node_en = $this->createNodeArticle('en');
    $node_ar = $this->createNodeArticle('ar');
    $node_es = $this->createNodeArticle('es');

    $this->drupalGet('node');

    // Check if English node does not have lang tag.
    $pattern = '|id="node-' . $node_en->nid . '"[^<>]*lang="en"|';
    $this->assertNoPattern($pattern, t('The lang tag has not been assigned to the English node.'));

    // Check if English node does not have dir tag.
    $pattern = '|id="node-' . $node_en->nid . '"[^<>]*dir="ltr"|';
    $this->assertNoPattern($pattern, t('The dir tag has not been assigned to the English node.'));

    // Check if Arabic node has lang="ar" & dir="rtl" tags.
    $pattern = '|id="node-' . $node_ar->nid . '"[^<>]*lang="ar" dir="rtl"|';
    $this->assertPattern($pattern, t('The lang and dir tags have been assigned correctly to the Arabic node.'));

    // Check if Spanish node has lang="es" tag.
    $pattern = '|id="node-' . $node_es->nid . '"[^<>]*lang="es"|';
    $this->assertPattern($pattern, t('The lang tag has been assigned correctly to the Spanish node.'));

    // Check if Spanish node does not have dir="ltr" tag.
    $pattern = '|id="node-' . $node_es->nid . '"[^<>]*lang="es" dir="ltr"|';
    $this->assertNoPattern($pattern, t('The dir tag has not been assigned to the Spanish node.'));

    $this->drupalLogout();
  }

  /**
   * Create node in a specific language.
   */
  protected function createNodeArticle($langcode) {
    $this->drupalGet('node/add/article');
    $node_title = $this->randomName();
    $node_body =  $this->randomName();
    $edit = array(
      'type' => 'article',
      'title' => $node_title,
      'body' => array($langcode => array(array('value' => $node_body))),
      'langcode' => $langcode,
      'promote' => 1,
    );
    return $this->drupalCreateNode($edit);
  }
}
