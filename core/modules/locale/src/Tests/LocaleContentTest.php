<?php

/**
 * @file
 * Contains \Drupal\locale\Tests\LocaleContentTest.
 */

namespace Drupal\locale\Tests;

use Drupal\simpletest\WebTestBase;
use Drupal\Core\Language\LanguageInterface;

/**
 * Tests you can enable multilingual support on content types and configure a
 * language for a node.
 *
 * @group locale
 */
class LocaleContentTest extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('node', 'locale');

  /**
   * Verifies that machine name fields are always LTR.
   */
  public function testMachineNameLTR() {
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
    $this->drupalPostForm('admin/config/regional/language', $edit, t('Save configuration'));

    // Verify that the machine name field is still LTR for a new content type.
    $this->drupalGet('admin/structure/types/add');
    $this->assertFieldByXpath('//input[@name="type" and @dir="ltr"]', NULL, 'The machine name field is LTR when the default language is RTL.');
  }

  /**
   * Test if a content type can be set to multilingual and language is present.
   */
  public function testContentTypeLanguageConfiguration() {
    $type1 = $this->drupalCreateContentType();
    $type2 = $this->drupalCreateContentType();

    // User to add and remove language.
    $admin_user = $this->drupalCreateUser(array('administer languages', 'administer content types', 'access administration pages'));
    // User to create a node.
    $web_user = $this->drupalCreateUser(array("create {$type1->id()} content", "create {$type2->id()} content", "edit any {$type2->id()} content"));

    // Add custom language.
    $this->drupalLogin($admin_user);
    // Code for the language.
    $langcode = 'xx';
    // The English name for the language.
    $name = $this->randomMachineName(16);
    $edit = array(
      'predefined_langcode' => 'custom',
      'langcode' => $langcode,
      'label' => $name,
      'direction' => LanguageInterface::DIRECTION_LTR,
    );
    $this->drupalPostForm('admin/config/regional/language/add', $edit, t('Add custom language'));

    // Set the content type to use multilingual support.
    $this->drupalGet("admin/structure/types/manage/{$type2->id()}");
    $this->assertText(t('Language settings'), 'Multilingual support widget present on content type configuration form.');
    $edit = array(
      'language_configuration[language_alterable]' => TRUE,
    );
    $this->drupalPostForm("admin/structure/types/manage/{$type2->id()}", $edit, t('Save content type'));
    $this->assertRaw(t('The content type %type has been updated.', array('%type' => $type2->label())));
    $this->drupalLogout();
    \Drupal::languageManager()->reset();

    // Verify language selection is not present on the node add form.
    $this->drupalLogin($web_user);
    $this->drupalGet("node/add/{$type1->id()}");
    // Verify language select list is not present.
    $this->assertNoFieldByName('langcode[0][value]', NULL, 'Language select not present on the node add form.');

    // Verify language selection appears on the node add form.
    $this->drupalGet("node/add/{$type2->id()}");
    // Verify language select list is present.
    $this->assertFieldByName('langcode[0][value]', NULL, 'Language select present on the node add form.');
    // Ensure language appears.
    $this->assertText($name, 'Language present.');

    // Create a node.
    $node_title = $this->randomMachineName();
    $node_body = $this->randomMachineName();
    $edit = array(
      'type' => $type2->id(),
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
      'langcode[0][value]' => 'en',
    );
    $this->drupalPostForm($path, $edit, t('Save'));
    $this->assertRaw(t('%title has been updated.', array('%title' => $node_title)));

    $this->drupalLogout();
  }

  /**
   * Test if a dir and lang tags exist in node's attributes.
   */
  public function testContentTypeDirLang() {
    $type = $this->drupalCreateContentType();

    // User to add and remove language.
    $admin_user = $this->drupalCreateUser(array('administer languages', 'administer content types', 'access administration pages'));
    // User to create a node.
    $web_user = $this->drupalCreateUser(array("create {$type->id()} content", "edit own {$type->id()} content"));

    // Login as admin.
    $this->drupalLogin($admin_user);

    // Install Arabic language.
    $edit = array();
    $edit['predefined_langcode'] = 'ar';
    $this->drupalPostForm('admin/config/regional/language/add', $edit, t('Add language'));

    // Install Spanish language.
    $edit = array();
    $edit['predefined_langcode'] = 'es';
    $this->drupalPostForm('admin/config/regional/language/add', $edit, t('Add language'));
    \Drupal::languageManager()->reset();

    // Set the content type to use multilingual support.
    $this->drupalGet("admin/structure/types/manage/{$type->id()}");
    $edit = array(
      'language_configuration[language_alterable]' => TRUE,
    );
    $this->drupalPostForm("admin/structure/types/manage/{$type->id()}", $edit, t('Save content type'));
    $this->assertRaw(t('The content type %type has been updated.', array('%type' => $type->label())));
    $this->drupalLogout();

    // Login as web user to add new node.
    $this->drupalLogin($web_user);

    // Create three nodes: English, Arabic and Spanish.
    $nodes = array();
    foreach (array('en', 'es', 'ar') as $langcode) {
      $nodes[$langcode] = $this->drupalCreateNode(array(
        'langcode' => $langcode,
        'type' => $type->id(),
        'promote' => NODE_PROMOTED,
      ));
    }

    // Check if English node does not have lang tag.
    $this->drupalGet('node/' . $nodes['en']->id());
    $element = $this->cssSelect('article.node[lang="en"]');
    $this->assertTrue(empty($element), 'The lang tag has not been assigned to the English node.');

    // Check if English node does not have dir tag.
    $element = $this->cssSelect('article.node[dir="ltr"]');
    $this->assertTrue(empty($element), 'The dir tag has not been assigned to the English node.');

    // Check if Arabic node has lang="ar" & dir="rtl" tags.
    $this->drupalGet('node/' . $nodes['ar']->id());
    $element = $this->cssSelect('article.node[lang="ar"][dir="rtl"]');
    $this->assertTrue(!empty($element), 'The lang and dir tags have been assigned correctly to the Arabic node.');

    // Check if Spanish node has lang="es" tag.
    $this->drupalGet('node/' . $nodes['es']->id());
    $element = $this->cssSelect('article.node[lang="es"]');
    $this->assertTrue(!empty($element), 'The lang tag has been assigned correctly to the Spanish node.');

    // Check if Spanish node does not have dir="ltr" tag.
    $element = $this->cssSelect('article.node[lang="es"][dir="ltr"]');
    $this->assertTrue(empty($element), 'The dir tag has not been assigned to the Spanish node.');
  }

}
