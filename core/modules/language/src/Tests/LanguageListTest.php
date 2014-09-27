<?php

/**
 * @file
 * Definition of Drupal\language\Tests\LanguageListTest.
 */

namespace Drupal\language\Tests;

use Drupal\language\Entity\ConfigurableLanguage;
use Drupal\simpletest\WebTestBase;
use Drupal\Core\Language\Language;
use Drupal\Core\Language\LanguageInterface;

/**
 * Adds a new language and tests changing its status and the default language.
 *
 * @group language
 */
class LanguageListTest extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('language');

  /**
   * Functional tests for adding, editing and deleting languages.
   */
  function testLanguageList() {

    // User to add and remove language.
    $admin_user = $this->drupalCreateUser(array('administer languages', 'access administration pages', 'administer site configuration'));
    $this->drupalLogin($admin_user);

    // Add predefined language.
    $edit = array(
      'predefined_langcode' => 'fr',
    );
    $this->drupalPostForm('admin/config/regional/language/add', $edit, t('Add language'));
    $this->assertText('French', 'Language added successfully.');
    $this->assertUrl(\Drupal::url('language.admin_overview', [], ['absolute' => TRUE]));

    // Add custom language.
    $langcode = 'xx';
    $name = $this->randomMachineName(16);
    $edit = array(
      'predefined_langcode' => 'custom',
      'langcode' => $langcode,
      'label' => $name,
      'direction' => Language::DIRECTION_LTR,
    );
    $this->drupalPostForm('admin/config/regional/language/add', $edit, t('Add custom language'));
    $this->assertUrl(\Drupal::url('language.admin_overview', [], ['absolute' => TRUE]));
    $this->assertRaw('"edit-languages-' . $langcode .'-weight"', 'Language code found.');
    $this->assertText(t($name), 'Test language added.');

    $language = \Drupal::service('language_manager')->getLanguage($langcode);
    $english = \Drupal::service('language_manager')->getLanguage('en');

    // Check if we can change the default language.
    $path = 'admin/config/regional/settings';
    $this->drupalGet($path);
    $this->assertOptionSelected('edit-site-default-language', 'en', 'English is the default language.');
    // Change the default language.
    $edit = array(
      'site_default_language' => $langcode,
    );
    $this->drupalPostForm(NULL, $edit, t('Save configuration'));
    $this->rebuildContainer();
    $this->assertNoOptionSelected('edit-site-default-language', 'en', 'Default language updated.');
    $this->assertUrl(\Drupal::url('system.regional_settings', [], ['absolute' => TRUE, 'language' => $language]));

    // Ensure we can't delete the default language.
    $this->drupalGet('admin/config/regional/language/delete/' . $langcode);
    $this->assertUrl(\Drupal::url('language.admin_overview', [], ['absolute' => TRUE, 'language' => $language]));
    $this->assertText(t('The default language cannot be deleted.'), 'Failed to delete the default language.');

    // Ensure 'Edit' link works.
    $this->clickLink(t('Edit'));
    $this->assertTitle(t('Edit language | Drupal'), 'Page title is "Edit language".');
    // Edit a language.
    $name = $this->randomMachineName(16);
    $edit = array(
      'label' => $name,
    );
    $this->drupalPostForm('admin/config/regional/language/edit/' . $langcode, $edit, t('Save language'));
    $this->assertRaw($name, 'The language has been updated.');
    $this->assertUrl(\Drupal::url('language.admin_overview', [], ['absolute' => TRUE, 'language' => $language]));

    // Change back the default language.
    $edit = array(
      'site_default_language' => 'en',
    );
    $this->drupalPostForm($path, $edit, t('Save configuration'));
    $this->rebuildContainer();
    // Ensure 'delete' link works.
    $this->drupalGet('admin/config/regional/language');
    $this->clickLink(t('Delete'));
    $this->assertText(t('Are you sure you want to delete the language'), '"Delete" link is correct.');
    // Delete a language.
    $this->drupalGet('admin/config/regional/language/delete/' . $langcode);
    // First test the 'cancel' link.
    $this->clickLink(t('Cancel'));
    $this->assertUrl(\Drupal::url('language.admin_overview', [], ['absolute' => TRUE, 'language' => $english]));
    $this->assertRaw($name, 'The language was not deleted.');
    // Delete the language for real. This a confirm form, we do not need any
    // fields changed.
    $this->drupalPostForm('admin/config/regional/language/delete/' . $langcode, array(), t('Delete'));
    // We need raw here because %language and %langcode will add HTML.
    $t_args = array('%language' => $name, '%langcode' => $langcode);
    $this->assertRaw(t('The %language (%langcode) language has been removed.', $t_args), 'The test language has been removed.');
    $this->assertUrl(\Drupal::url('language.admin_overview', [], ['absolute' => TRUE, 'language' => $english]));
    // Verify that language is no longer found.
    $this->drupalGet('admin/config/regional/language/delete/' . $langcode);
    $this->assertResponse(404, 'Language no longer found.');

    // Delete French.
    $this->drupalPostForm('admin/config/regional/language/delete/fr', array(), t('Delete'));
    // Make sure the "language_count" state has been updated correctly.
    $this->rebuildContainer();
    // We need raw here because %language and %langcode will add HTML.
    $t_args = array('%language' => 'French', '%langcode' => 'fr');
    $this->assertRaw(t('The %language (%langcode) language has been removed.', $t_args), 'The French language has been removed.');
    $this->assertUrl(\Drupal::url('language.admin_overview', [], ['absolute' => TRUE]));
    // Verify that language is no longer found.
    $this->drupalGet('admin/config/regional/language/delete/fr');
    $this->assertResponse(404, 'Language no longer found.');
    // Make sure the "language_count" state has not changed.

    // Ensure we can delete the English language. Right now English is the only
    // language so we must add a new language and make it the default before
    // deleting English.
    $langcode = 'xx';
    $name = $this->randomMachineName(16);
    $edit = array(
      'predefined_langcode' => 'custom',
      'langcode' => $langcode,
      'label' => $name,
      'direction' => Language::DIRECTION_LTR,
    );
    $this->drupalPostForm('admin/config/regional/language/add', $edit, t('Add custom language'));
    $this->assertUrl(\Drupal::url('language.admin_overview', [], ['absolute' => TRUE]));
    $this->assertText($name, 'Name found.');

    // Check if we can change the default language.
    $path = 'admin/config/regional/settings';
    $this->drupalGet($path);
    $this->assertOptionSelected('edit-site-default-language', 'en', 'English is the default language.');
    // Change the default language.
    $edit = array(
      'site_default_language' => $langcode,
    );
    $this->drupalPostForm(NULL, $edit, t('Save configuration'));
    $this->rebuildContainer();
    $this->assertNoOptionSelected('edit-site-default-language', 'en', 'Default language updated.');
    $this->assertUrl(\Drupal::url('system.regional_settings', [], ['absolute' => TRUE, 'language' => $language]));

    $this->drupalPostForm('admin/config/regional/language/delete/en', array(), t('Delete'));
    // We need raw here because %language and %langcode will add HTML.
    $t_args = array('%language' => 'English', '%langcode' => 'en');
    $this->assertRaw(t('The %language (%langcode) language has been removed.', $t_args), 'The English language has been removed.');
    $this->rebuildContainer();

    // Ensure we can't delete a locked language.
    $this->drupalGet('admin/config/regional/language/delete/und');
    $this->assertResponse(403, 'Can not delete locked language');
  }

  /**
   * Functional tests for the language states (locked or configurable).
   */
  function testLanguageStates() {
    // Add some languages, and also lock some of them.
    ConfigurableLanguage::create(array('label' => $this->randomMachineName(), 'id' => 'l1'))->save();
    ConfigurableLanguage::create(array('label' => $this->randomMachineName(), 'id' => 'l2', 'locked' => TRUE))->save();
    ConfigurableLanguage::create(array('label' => $this->randomMachineName(), 'id' => 'l3'))->save();
    ConfigurableLanguage::create(array('label' => $this->randomMachineName(), 'id' => 'l4', 'locked' => TRUE))->save();
    $expected_locked_languages = array('l4' => 'l4', 'l2' => 'l2', 'und' => 'und', 'zxx' => 'zxx');
    $expected_all_languages = array('l4' => 'l4', 'l3' => 'l3', 'l2' => 'l2', 'l1' => 'l1', 'en' => 'en', 'und' => 'und', 'zxx' => 'zxx');
    $expected_conf_languages = array('l3' => 'l3', 'l1' => 'l1', 'en' => 'en');

    $locked_languages = $this->container->get('language_manager')->getLanguages(LanguageInterface::STATE_LOCKED);
    $this->assertEqual(array_diff_key($expected_locked_languages, $locked_languages), array(), 'Locked languages loaded correctly.');

    $all_languages = $this->container->get('language_manager')->getLanguages(LanguageInterface::STATE_ALL);
    $this->assertEqual(array_diff_key($expected_all_languages, $all_languages), array(), 'All languages loaded correctly.');

    $conf_languages = $this->container->get('language_manager')->getLanguages();
    $this->assertEqual(array_diff_key($expected_conf_languages, $conf_languages), array(), 'Configurable languages loaded correctly.');
  }
}
