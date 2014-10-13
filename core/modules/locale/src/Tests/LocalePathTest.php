<?php

/**
 * @file
 * Definition of Drupal\locale\Tests\LocalePathTest.
 */

namespace Drupal\locale\Tests;

use Drupal\Core\Language\LanguageInterface;
use Drupal\simpletest\WebTestBase;

/**
 * Tests you can configure a language for individual URL aliases.
 *
 * @group locale
 */
class LocalePathTest extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('node', 'locale', 'path', 'views');

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->drupalCreateContentType(array('type' => 'page', 'name' => 'Basic page'));
    \Drupal::config('system.site')->set('page.front', 'node')->save();
  }

  /**
   * Test if a language can be associated with a path alias.
   */
  public function testPathLanguageConfiguration() {
    // User to add and remove language.
    $admin_user = $this->drupalCreateUser(array('administer languages', 'create page content', 'administer url aliases', 'create url aliases', 'access administration pages', 'access content overview'));

    // Add custom language.
    $this->drupalLogin($admin_user);
    // Code for the language.
    $langcode = 'xx';
    // The English name for the language.
    $name = $this->randomMachineName(16);
    // The domain prefix.
    $prefix = $langcode;
    $edit = array(
      'predefined_langcode' => 'custom',
      'langcode' => $langcode,
      'label' => $name,
      'direction' => LanguageInterface::DIRECTION_LTR,
    );
    $this->drupalPostForm('admin/config/regional/language/add', $edit, t('Add custom language'));

    // Set path prefix.
    $edit = array("prefix[$langcode]" => $prefix);
    $this->drupalPostForm('admin/config/regional/language/detection/url', $edit, t('Save configuration'));

    // Check that the "xx" front page is readily available because path prefix
    // negotiation is pre-configured.
    $this->drupalGet($prefix);
    $this->assertText(t('Welcome to Drupal'), 'The "xx" front page is readibly available.');

    // Create a node.
    $node = $this->drupalCreateNode(array('type' => 'page'));

    // Create a path alias in default language (English).
    $path = 'admin/config/search/path/add';
    $english_path = $this->randomMachineName(8);
    $edit = array(
      'source'   => 'node/' . $node->id(),
      'alias'    => $english_path,
      'langcode' => 'en',
    );
    $this->drupalPostForm($path, $edit, t('Save'));

    // Create a path alias in new custom language.
    $custom_language_path = $this->randomMachineName(8);
    $edit = array(
      'source'   => 'node/' . $node->id(),
      'alias'    => $custom_language_path,
      'langcode' => $langcode,
    );
    $this->drupalPostForm($path, $edit, t('Save'));

    // Confirm English language path alias works.
    $this->drupalGet($english_path);
    $this->assertText($node->label(), 'English alias works.');

    // Confirm custom language path alias works.
    $this->drupalGet($prefix . '/' . $custom_language_path);
    $this->assertText($node->label(), 'Custom language alias works.');

    // Create a custom path.
    $custom_path = $this->randomMachineName(8);

    // Check priority of language for alias by source path.
    $edit = array(
      'source'   => 'node/' . $node->id(),
      'alias'    => $custom_path,
      'langcode' => LanguageInterface::LANGCODE_NOT_SPECIFIED,
    );
    $this->container->get('path.alias_storage')->save($edit['source'], $edit['alias'], $edit['langcode']);
    $lookup_path = $this->container->get('path.alias_manager')->getAliasByPath('node/' . $node->id(), 'en');
    $this->assertEqual($english_path, $lookup_path, 'English language alias has priority.');
    // Same check for language 'xx'.
    $lookup_path = $this->container->get('path.alias_manager')->getAliasByPath('node/' . $node->id(), $prefix);
    $this->assertEqual($custom_language_path, $lookup_path, 'Custom language alias has priority.');
    $this->container->get('path.alias_storage')->delete($edit);

    // Create language nodes to check priority of aliases.
    $first_node = $this->drupalCreateNode(array('type' => 'page', 'promote' => 1, 'langcode' => 'en'));
    $second_node = $this->drupalCreateNode(array('type' => 'page', 'promote' => 1, 'langcode' => LanguageInterface::LANGCODE_NOT_SPECIFIED));

    // Assign a custom path alias to the first node with the English language.
    $edit = array(
      'source'   => 'node/' . $first_node->id(),
      'alias'    => $custom_path,
      'langcode' => $first_node->language()->getId(),
    );
    $this->container->get('path.alias_storage')->save($edit['source'], $edit['alias'], $edit['langcode']);

    // Assign a custom path alias to second node with
    // LanguageInterface::LANGCODE_NOT_SPECIFIED.
    $edit = array(
      'source'   => 'node/' . $second_node->id(),
      'alias'    => $custom_path,
      'langcode' => $second_node->language()->getId(),
    );
    $this->container->get('path.alias_storage')->save($edit['source'], $edit['alias'], $edit['langcode']);

    // Test that both node titles link to our path alias.
    $this->drupalGet('admin/content');
    $custom_path_url = base_path() . $GLOBALS['script_path'] . $custom_path;
    $elements = $this->xpath('//a[@href=:href and normalize-space(text())=:title]', array(':href' => $custom_path_url, ':title' => $first_node->label()));
    $this->assertTrue(!empty($elements), 'First node links to the path alias.');
    $elements = $this->xpath('//a[@href=:href and normalize-space(text())=:title]', array(':href' => $custom_path_url, ':title' => $second_node->label()));
    $this->assertTrue(!empty($elements), 'Second node links to the path alias.');

    // Confirm that the custom path leads to the first node.
    $this->drupalGet($custom_path);
    $this->assertText($first_node->label(), 'Custom alias returns first node.');

    // Confirm that the custom path with prefix leads to the second node.
    $this->drupalGet($prefix . '/' . $custom_path);
    $this->assertText($second_node->label(), 'Custom alias with prefix returns second node.');

  }
}
