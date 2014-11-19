<?php

/**
 * @file
 * Contains \Drupal\language\src\Tests\LanguageBlockSettingsVisibilityTest.
 */

namespace Drupal\language\Tests;

use Drupal\simpletest\WebTestBase;

/**
 * Tests that the language settings on block config appears correctly.
 *
 * @group language
 */
class LanguageBlockSettingsVisibilityTest extends WebTestBase {

  public static $modules = array('block', 'language');

  public function testUnnecessaryLanguageSettingsVisibility() {
    $admin_user = $this->drupalCreateUser(array('administer languages', 'access administration pages', 'administer blocks'));
    $this->drupalLogin($admin_user);
    $this->drupalPostForm('admin/config/regional/language/add', array('predefined_langcode' => 'hu'), t('Add language'));
    $this->drupalGet('admin/structure/block/add/system_menu_block:admin/stark');
    $this->assertNoFieldByXPath('//input[@id="edit-visibility-language-langcodes-und"]', NULL, '\'Not specified\' option does not appear at block config, language settings section.');
    $this->assertNoFieldByXpath('//input[@id="edit-visibility-language-langcodes-zxx"]', NULL, '\'Not applicable\' option does not appear at block config, language settings section.');
    $this->assertFieldByXPath('//input[@id="edit-visibility-language-langcodes-en"]', NULL, '\'English\' option appears at block config, language settings section.');
    $this->assertFieldByXpath('//input[@id="edit-visibility-language-langcodes-hu"]', NULL, '\'Hungarian\' option appears at block config, language settings section.');
  }
}
