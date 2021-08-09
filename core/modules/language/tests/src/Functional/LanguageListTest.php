<?php

namespace Drupal\Tests\language\Functional;

use Drupal\Core\Url;
use Drupal\language\Entity\ConfigurableLanguage;
use Drupal\Core\Language\Language;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Tests\BrowserTestBase;

/**
 * Adds a new language and tests changing its status and the default language.
 *
 * @group language
 */
class LanguageListTest extends BrowserTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = ['language'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Functional tests for adding, editing and deleting languages.
   */
  public function testLanguageList() {

    // User to add and remove language.
    $admin_user = $this->drupalCreateUser([
      'administer languages',
      'access administration pages',
    ]);
    $this->drupalLogin($admin_user);

    // Get the weight of the last language.
    $languages = \Drupal::service('language_manager')->getLanguages();
    $last_language_weight = end($languages)->getWeight();

    // Add predefined language.
    $edit = [
      'predefined_langcode' => 'fr',
    ];
    $this->drupalGet('admin/config/regional/language/add');
    $this->submitForm($edit, 'Add language');
    $this->assertSession()->pageTextContains('French');
    $this->assertSession()->addressEquals(Url::fromRoute('entity.configurable_language.collection'));

    // Get the weight of the last language and check that the weight is one unit
    // heavier than the last configurable language.
    $this->rebuildContainer();
    $languages = \Drupal::service('language_manager')->getLanguages();
    $last_language = end($languages);
    $this->assertEquals($last_language_weight + 1, $last_language->getWeight());
    $this->assertEquals($edit['predefined_langcode'], $last_language->getId());

    // Add custom language.
    $langcode = 'xx';
    $name = $this->randomMachineName(16);
    $edit = [
      'predefined_langcode' => 'custom',
      'langcode' => $langcode,
      'label' => $name,
      'direction' => Language::DIRECTION_LTR,
    ];
    $this->drupalGet('admin/config/regional/language/add');
    $this->submitForm($edit, 'Add custom language');
    $this->assertSession()->addressEquals(Url::fromRoute('entity.configurable_language.collection'));
    $this->assertRaw('"edit-languages-' . $langcode . '-weight"');
    $this->assertSession()->pageTextContains($name);

    $language = \Drupal::service('language_manager')->getLanguage($langcode);
    $english = \Drupal::service('language_manager')->getLanguage('en');

    // Check if we can change the default language.
    $path = 'admin/config/regional/language';
    $this->drupalGet($path);
    $this->assertSession()->checkboxChecked('edit-site-default-language-en');
    // Change the default language.
    $edit = [
      'site_default_language' => $langcode,
    ];
    $this->submitForm($edit, 'Save configuration');
    $this->rebuildContainer();
    $this->assertSession()->checkboxNotChecked('edit-site-default-language-en');
    $this->assertSession()->addressEquals(Url::fromRoute('entity.configurable_language.collection', [], ['language' => $language]));

    // Ensure we can't delete the default language.
    $this->drupalGet('admin/config/regional/language/delete/' . $langcode);
    $this->assertSession()->statusCodeEquals(403);

    // Ensure 'Edit' link works.
    $this->drupalGet('admin/config/regional/language');
    $this->clickLink('Edit');
    $this->assertSession()->titleEquals('Edit language | Drupal');
    // Edit a language.
    $name = $this->randomMachineName(16);
    $edit = [
      'label' => $name,
    ];
    $this->drupalGet('admin/config/regional/language/edit/' . $langcode);
    $this->submitForm($edit, 'Save language');
    $this->assertRaw($name);
    $this->assertSession()->addressEquals(Url::fromRoute('entity.configurable_language.collection', [], ['language' => $language]));

    // Change back the default language.
    $edit = [
      'site_default_language' => 'en',
    ];
    $this->drupalGet($path);
    $this->submitForm($edit, 'Save configuration');
    $this->rebuildContainer();
    // Ensure 'delete' link works.
    $this->drupalGet('admin/config/regional/language');
    $this->clickLink('Delete');
    $this->assertSession()->pageTextContains('Are you sure you want to delete the language');
    // Delete a language.
    $this->drupalGet('admin/config/regional/language/delete/' . $langcode);
    // First test the 'cancel' link.
    $this->clickLink('Cancel');
    $this->assertSession()->addressEquals(Url::fromRoute('entity.configurable_language.collection', [], ['language' => $english]));
    $this->assertRaw($name);
    // Delete the language for real. This a confirm form, we do not need any
    // fields changed.
    $this->drupalGet('admin/config/regional/language/delete/' . $langcode);
    $this->submitForm([], 'Delete');
    $this->assertSession()->pageTextContains("The {$name} ({$langcode}) language has been removed.");
    $this->assertSession()->addressEquals(Url::fromRoute('entity.configurable_language.collection', [], ['language' => $english]));
    // Verify that language is no longer found.
    $this->drupalGet('admin/config/regional/language/delete/' . $langcode);
    $this->assertSession()->statusCodeEquals(404);

    // Delete French.
    $this->drupalGet('admin/config/regional/language/delete/fr');
    $this->submitForm([], 'Delete');
    // Make sure the "language_count" state has been updated correctly.
    $this->rebuildContainer();
    $this->assertSession()->pageTextContains("The French (fr) language has been removed.");
    $this->assertSession()->addressEquals(Url::fromRoute('entity.configurable_language.collection'));
    // Verify that language is no longer found.
    $this->drupalGet('admin/config/regional/language/delete/fr');
    $this->assertSession()->statusCodeEquals(404);
    // Make sure the "language_count" state has not changed.

    // Ensure we can delete the English language. Right now English is the only
    // language so we must add a new language and make it the default before
    // deleting English.
    $langcode = 'xx';
    $name = $this->randomMachineName(16);
    $edit = [
      'predefined_langcode' => 'custom',
      'langcode' => $langcode,
      'label' => $name,
      'direction' => Language::DIRECTION_LTR,
    ];
    $this->drupalGet('admin/config/regional/language/add');
    $this->submitForm($edit, 'Add custom language');
    $this->assertSession()->addressEquals(Url::fromRoute('entity.configurable_language.collection'));
    $this->assertSession()->pageTextContains($name);

    // Check if we can change the default language.
    $path = 'admin/config/regional/language';
    $this->drupalGet($path);
    $this->assertSession()->checkboxChecked('edit-site-default-language-en');
    // Change the default language.
    $edit = [
      'site_default_language' => $langcode,
    ];
    $this->submitForm($edit, 'Save configuration');
    $this->rebuildContainer();
    $this->assertSession()->checkboxNotChecked('edit-site-default-language-en');
    $this->assertSession()->addressEquals(Url::fromRoute('entity.configurable_language.collection', [], ['language' => $language]));

    $this->drupalGet('admin/config/regional/language/delete/en');
    $this->submitForm([], 'Delete');
    $this->assertSession()->pageTextContains("The English (en) language has been removed.");
    $this->rebuildContainer();

    // Ensure we can't delete a locked language.
    $this->drupalGet('admin/config/regional/language/delete/und');
    $this->assertSession()->statusCodeEquals(403);

    // Ensure that NL cannot be set default when it's not available.
    // First create the NL language.
    $edit = [
      'predefined_langcode' => 'nl',
    ];
    $this->drupalGet('admin/config/regional/language/add');
    $this->submitForm($edit, 'Add language');

    // Load the form which has now the additional NL language option.
    $this->drupalGet('admin/config/regional/language');

    // Delete the NL language in the background.
    $language_storage = $this->container->get('entity_type.manager')->getStorage('configurable_language');
    $language_storage->load('nl')->delete();

    $this->submitForm(['site_default_language' => 'nl'], 'Save configuration');
    $this->assertSession()->pageTextContains('Selected default language no longer exists.');
    $this->assertSession()->checkboxNotChecked('edit-site-default-language-xx');
  }

  /**
   * Functional tests for the language states (locked or configurable).
   */
  public function testLanguageStates() {
    // Add some languages, and also lock some of them.
    ConfigurableLanguage::create(['label' => $this->randomMachineName(), 'id' => 'l1'])->save();
    ConfigurableLanguage::create(['label' => $this->randomMachineName(), 'id' => 'l2', 'locked' => TRUE])->save();
    ConfigurableLanguage::create(['label' => $this->randomMachineName(), 'id' => 'l3'])->save();
    ConfigurableLanguage::create(['label' => $this->randomMachineName(), 'id' => 'l4', 'locked' => TRUE])->save();
    $expected_locked_languages = ['l4' => 'l4', 'l2' => 'l2', 'und' => 'und', 'zxx' => 'zxx'];
    $expected_all_languages = ['l4' => 'l4', 'l3' => 'l3', 'l2' => 'l2', 'l1' => 'l1', 'en' => 'en', 'und' => 'und', 'zxx' => 'zxx'];
    $expected_conf_languages = ['l3' => 'l3', 'l1' => 'l1', 'en' => 'en'];

    $locked_languages = $this->container->get('language_manager')->getLanguages(LanguageInterface::STATE_LOCKED);
    $this->assertEquals([], array_diff_key($expected_locked_languages, $locked_languages), 'Locked languages loaded correctly.');

    $all_languages = $this->container->get('language_manager')->getLanguages(LanguageInterface::STATE_ALL);
    $this->assertEquals([], array_diff_key($expected_all_languages, $all_languages), 'All languages loaded correctly.');

    $conf_languages = $this->container->get('language_manager')->getLanguages();
    $this->assertEquals([], array_diff_key($expected_conf_languages, $conf_languages), 'Configurable languages loaded correctly.');
  }

}
