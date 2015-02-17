<?php

/**
 * @file
 * Definition of Drupal\language\Tests\LanguageConfigurationTest.
 */

namespace Drupal\language\Tests;

use Drupal\Core\Language\LanguageInterface;
use Drupal\simpletest\WebTestBase;

/**
 * Adds and configures languages to check negotiation changes.
 *
 * @group language
 */
class LanguageConfigurationTest extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('language');

  /**
   * Functional tests for adding, editing and deleting languages.
   */
  function testLanguageConfiguration() {

    // User to add and remove language.
    $admin_user = $this->drupalCreateUser(array('administer languages', 'access administration pages'));
    $this->drupalLogin($admin_user);

    // Check if the Default English language has no path prefix.
    $this->drupalGet('admin/config/regional/language/detection/url');
    $this->assertFieldByXPath('//input[@name="prefix[en]"]', '', 'Default English has no path prefix.');

    // Add predefined language.
    $edit = array(
      'predefined_langcode' => 'fr',
    );
    $this->drupalPostForm('admin/config/regional/language/add', $edit, 'Add language');
    $this->assertText('French');
    $this->assertUrl(\Drupal::url('entity.configurable_language.collection', [], ['absolute' => TRUE]), [], 'Correct page redirection.');
    // Langcode for Languages is always 'en'.
    $language = $this->config('language.entity.fr')->get();
    $this->assertEqual($language['langcode'], 'en');

    // Check if the Default English language has no path prefix.
    $this->drupalGet('admin/config/regional/language/detection/url');
    $this->assertFieldByXPath('//input[@name="prefix[en]"]', '', 'Default English has no path prefix.');
    // Check if French has a path prefix.
    $this->drupalGet('admin/config/regional/language/detection/url');
    $this->assertFieldByXPath('//input[@name="prefix[fr]"]', 'fr', 'French has a path prefix.');

    // Check if we can change the default language.
    $this->drupalGet('admin/config/regional/language');
    $this->assertFieldChecked('edit-site-default-language-en', 'English is the default language.');

    // Change the default language.
    $edit = array(
      'site_default_language' => 'fr',
    );
    $this->drupalPostForm(NULL, $edit, t('Save configuration'));
    $this->rebuildContainer();
    $this->assertFieldChecked('edit-site-default-language-fr', 'Default language updated.');
    $this->assertUrl(\Drupal::url('entity.configurable_language.collection', [], ['absolute' => TRUE, 'langcode' => 'fr']), [], 'Correct page redirection.');

    // Check if a valid language prefix is added after changing the default
    // language.
    $this->drupalGet('admin/config/regional/language/detection/url');
    $this->assertFieldByXPath('//input[@name="prefix[en]"]', 'en', 'A valid path prefix has been added to the previous default language.');
    // Check if French still has a path prefix.
    $this->drupalGet('admin/config/regional/language/detection/url');
    $this->assertFieldByXPath('//input[@name="prefix[fr]"]', 'fr', 'French still has a path prefix.');

    // Check that prefix can be changed.
    $edit = array(
      'prefix[fr]' => 'french',
    );
    $this->drupalPostForm(NULL, $edit, t('Save configuration'));
    $this->assertFieldByXPath('//input[@name="prefix[fr]"]', 'french', 'French path prefix has changed.');


    // Change default negotiation language.
    $this->config('language.negotiation')->set('selected_langcode', 'fr')->save();
    // Check that the prefix of a language that is not the negotiation one
    // cannot be changed to empty string.
    $edit = array(
      'prefix[en]' => '',
    );
    $this->drupalPostForm(NULL, $edit, t('Save configuration'));
    $this->assertText(t('The prefix may only be left blank for the selected negotiation fallback language.'));

    //  Check that prefix cannot be changed to contain a slash.
    $edit = array(
      'prefix[en]' => 'foo/bar',
    );
    $this->drupalPostForm(NULL, $edit, t('Save configuration'));
    $this->assertText(t('The prefix may not contain a slash.'), 'English prefix cannot be changed to contain a slash.');

    // Remove English language and add a new Language to check if langcode of
    // Language entity is 'en'.
    $this->drupalPostForm('admin/config/regional/language/delete/en', array(), t('Delete'));
    $this->rebuildContainer();
    $this->assertRaw(t('The %language (%langcode) language has been removed.', array('%language' => 'English', '%langcode' => 'en')));
    $edit = array(
      'predefined_langcode' => 'de',
    );
    $this->drupalPostForm('admin/config/regional/language/add', $edit, 'Add language');
    $language = $this->config('language.entity.de')->get();
    $this->assertEqual($language['langcode'], 'en');
  }

  /**
   * Functional tests for setting system language weight on adding, editing and deleting languages.
   */
  function testLanguageConfigurationWeight() {
    // User to add and remove language.
    $admin_user = $this->drupalCreateUser(array('administer languages', 'access administration pages'));
    $this->drupalLogin($admin_user);
    $this->checkConfigurableLanguageWeight();

    // Add predefined language.
    $edit = array(
      'predefined_langcode' => 'fr',
    );
    $this->drupalPostForm('admin/config/regional/language/add', $edit, 'Add language');
    $this->checkConfigurableLanguageWeight('after adding new language');

    // Re-ordering languages.
    $edit = array(
      'languages[en][weight]' => $this->getHighestConfigurableLanguageWeight() + 1,
    );
    $this->drupalPostForm('admin/config/regional/language', $edit, 'Save configuration');
    $this->checkConfigurableLanguageWeight('after re-ordering');

    // Remove predefined language.
    $edit = array(
      'confirm' => 1,
    );
    $this->drupalPostForm('admin/config/regional/language/delete/fr', $edit, 'Delete');
    $this->checkConfigurableLanguageWeight('after deleting a language');
  }

  /**
   * Validates system languages are ordered after configurable languages.
   *
   * @param string $state
   *   (optional) A string for customizing assert messages, containing the
   *   description of the state of the check, for example: 'after re-ordering'.
   *   Defaults to 'by default'.
   */
  protected function checkConfigurableLanguageWeight($state = 'by default') {
    // Reset language list.
    \Drupal::languageManager()->reset();
    $max_configurable_language_weight = $this->getHighestConfigurableLanguageWeight();
    $replacements = array('@event' => $state);
    foreach (\Drupal::languageManager()->getLanguages(LanguageInterface::STATE_LOCKED) as $locked_language) {
      $replacements['%language'] = $locked_language->getName();
      $this->assertTrue($locked_language->getWeight() > $max_configurable_language_weight, format_string('System language %language has higher weight than configurable languages @event', $replacements));
    }
  }

  /**
   * Helper to get maximum weight of configurable (unlocked) languages.
   *
   * @return int
   *   Maximum weight of configurable languages.
   */
  protected function getHighestConfigurableLanguageWeight(){
    $max_weight = 0;

    /* @var $languages \Drupal\Core\Language\LanguageInterface[] */
    $languages = entity_load_multiple('configurable_language', NULL, TRUE);
    foreach ($languages as $language) {
      if (!$language->isLocked()) {
        $max_weight = max($max_weight, $language->getWeight());
      }
    }

    return $max_weight;
  }

}
