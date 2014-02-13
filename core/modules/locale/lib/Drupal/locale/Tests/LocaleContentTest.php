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

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('node', 'locale');

  public static function getInfo() {
    return array(
      'name' => 'Content language settings',
      'description' => 'Checks you can enable multilingual support on content types and configure a language for a node.',
      'group' => 'Locale',
    );
  }

  /**
   * Verifies that machine name fields are always LTR.
   */
  function testMachineNameLTR() {
    // User to add and remove language.
    $admin_user = $this->drupalCreateUser(array('administer languages', 'administer content types', 'access administration pages', 'administer site configuration'));

    // Log in as admin.
    $this->drupalLogin($admin_user);

    // Verify that the machine name field is LTR for a new content type.
    $this->drupalGet('admin/structure/types/add');
    $this->assertFieldByXpath('//input[@name="type" and @dir="ltr"]', NULL, 'The machine name field is LTR when no additional language is configured.');

    // Install the Arabic language (which is RTL) and configure as the default.
    $edit = array();
    $edit['predefined_langcode'] = 'ar';
    $this->drupalPostForm('admin/config/regional/language/add', $edit, t('Add language'));

    $edit = array(
      'site_default_language' => 'ar',
    );
    $this->drupalPostForm('admin/config/regional/settings', $edit, t('Save configuration'));

    // Verify that the machine name field is still LTR for a new content type.
    $this->drupalGet('admin/structure/types/add');
    $this->assertFieldByXpath('//input[@name="type" and @dir="ltr"]', NULL, 'The machine name field is LTR when the default language is RTL.');
  }

  /**
   * Test if a content type can be set to multilingual and language is present.
   */
  function testContentTypeLanguageConfiguration() {
    $type1 = $this->drupalCreateContentType();
    $type2 = $this->drupalCreateContentType();

    // User to add and remove language.
    $admin_user = $this->drupalCreateUser(array('administer languages', 'administer content types', 'access administration pages'));
    // User to create a node.
    $web_user = $this->drupalCreateUser(array("create {$type1->type} content", "create {$type2->type} content", "edit any {$type2->type} content"));

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
    $this->drupalPostForm('admin/config/regional/language/add', $edit, t('Add custom language'));

    // Set the content type to use multilingual support.
    $this->drupalGet("admin/structure/types/manage/{$type2->type}");
    $this->assertText(t('Language settings'), 'Multilingual support widget present on content type configuration form.');
    $edit = array(
      'language_configuration[language_show]' => TRUE,
    );
    $this->drupalPostForm("admin/structure/types/manage/{$type2->type}", $edit, t('Save content type'));
    $this->assertRaw(t('The content type %type has been updated.', array('%type' => $type2->name)));
    $this->drupalLogout();
    \Drupal::languageManager()->reset();

    // Verify language selection is not present on the node add form.
    $this->drupalLogin($web_user);
    $this->drupalGet("node/add/{$type1->type}");
    // Verify language select list is not present.
    $this->assertNoFieldByName('language', NULL, 'Language select not present on the node add form.');

    // Verify language selection appears on the node add form.
    $this->drupalGet("node/add/{$type2->type}");
    // Verify language select list is present.
    $this->assertFieldByName('langcode', NULL, 'Language select present on the node add form.');
    // Ensure language appears.
    $this->assertText($name, 'Language present.');

    // Create a node.
    $node_title = $this->randomName();
    $node_body =  $this->randomName();
    $edit = array(
      'type' => $type2->type,
      'title' => $node_title,
      'body' => array(array('value' => $node_body)),
      'langcode' => $langcode,
    );
    $node = $this->drupalCreateNode($edit);
    // Edit the content and ensure correct language is selected.
    $path = 'node/' . $node->id() . '/edit';
    $this->drupalGet($path);
    $this->assertRaw('<option value="' . $langcode . '" selected="selected">' .  $name . '</option>', 'Correct language selected.');
    // Ensure we can change the node language.
    $edit = array(
      'langcode' => 'en',
    );
    $this->drupalPostForm($path, $edit, t('Save'));
    $this->assertRaw(t('%title has been updated.', array('%title' => $node_title)));

    $this->drupalLogout();
  }

  /**
   * Test if a dir and lang tags exist in node's attributes.
   */
  function testContentTypeDirLang() {
    $type = $this->drupalCreateContentType();

    // User to add and remove language.
    $admin_user = $this->drupalCreateUser(array('administer languages', 'administer content types', 'access administration pages'));
    // User to create a node.
    $web_user = $this->drupalCreateUser(array("create {$type->type} content", "edit own {$type->type} content"));

    // Login as admin.
    $this->drupalLogin($admin_user);

    // Install Arabic language.
    $edit = array();
    $edit['predefined_langcode'] = 'ar';
    $this->drupalPostForm('admin/config/regional/language/add', $edit, t('Add language'));
    \Drupal::languageManager()->reset();

    // Install Spanish language.
    $edit = array();
    $edit['predefined_langcode'] = 'es';
    $this->drupalPostForm('admin/config/regional/language/add', $edit, t('Add language'));

    // Set the content type to use multilingual support.
    $this->drupalGet("admin/structure/types/manage/{$type->type}");
    $edit = array(
      'language_configuration[language_show]' => TRUE,
    );
    $this->drupalPostForm("admin/structure/types/manage/{$type->type}", $edit, t('Save content type'));
    $this->assertRaw(t('The content type %type has been updated.', array('%type' => $type->name)));
    $this->drupalLogout();

    // Login as web user to add new node.
    $this->drupalLogin($web_user);

    // Create three nodes: English, Arabic and Spanish.
    $nodes = array();
    foreach (array('en', 'es', 'ar') as $langcode) {
      $nodes[$langcode] = $this->drupalCreateNode(array(
        'langcode' => $langcode,
        'type' => $type->type,
        'promote' => NODE_PROMOTED,
      ));
    }


    // Check if English node does not have lang tag.
    $this->drupalGet('node/' . $nodes['en']->id());
    $pattern = '|id="node-' . $nodes['en']->id() . '"[^<>]*lang="en"|';
    $this->assertNoPattern($pattern, 'The lang tag has not been assigned to the English node.');

    // Check if English node does not have dir tag.
    $pattern = '|id="node-' . $nodes['en']->id() . '"[^<>]*dir="ltr"|';
    $this->assertNoPattern($pattern, 'The dir tag has not been assigned to the English node.');

    // Check if Arabic node has lang="ar" & dir="rtl" tags.
    $this->drupalGet('node/' . $nodes['ar']->id());
    $pattern = '|id="node-' . $nodes['ar']->id() . '"[^<>]*lang="ar" dir="rtl"|';
    $this->assertPattern($pattern, 'The lang and dir tags have been assigned correctly to the Arabic node.');

    // Check if Spanish node has lang="es" tag.
    $this->drupalGet('node/' . $nodes['es']->id());
    $pattern = '|id="node-' . $nodes['es']->id() . '"[^<>]*lang="es"|';
    $this->assertPattern($pattern, 'The lang tag has been assigned correctly to the Spanish node.');

    // Check if Spanish node does not have dir="ltr" tag.
    $pattern = '|id="node-' . $nodes['es']->id() . '"[^<>]*lang="es" dir="ltr"|';
    $this->assertNoPattern($pattern, 'The dir tag has not been assigned to the Spanish node.');
  }


  /**
   *  Test filtering Node content by language.
   */
  function testNodeAdminLanguageFilter() {
    \Drupal::moduleHandler()->install(array('views'));
    // User to add and remove language.
    $admin_user = $this->drupalCreateUser(array('administer languages', 'access administration pages', 'access content overview', 'administer nodes', 'bypass node access'));

    // Log in as admin.
    $this->drupalLogin($admin_user);

    // Enable multiple languages.
    $this->drupalPostForm('admin/config/regional/language/edit/en', array('locale_translate_english' => TRUE), t('Save language'));
    $this->drupalPostForm('admin/config/regional/language/add', array('predefined_langcode' => 'zh-hant'), t('Add language'));

    // Create two nodes: English and Chinese.
    $node_en = $this->drupalCreateNode(array('langcode' => 'en'));
    $node_zh_hant = $this->drupalCreateNode(array('langcode' => 'zh-hant'));

    // Verify filtering by language.
    $this->drupalGet('admin/content', array('query' => array('langcode' => 'zh-hant')));
    $this->assertLinkByHref('node/' . $node_zh_hant->id() . '/edit');
    $this->assertNoLinkByHref('node/' . $node_en->id() . '/edit');
  }

}
