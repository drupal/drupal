<?php

namespace Drupal\Tests\language\Functional;

use Drupal\Core\Url;
use Drupal\Core\Language\LanguageInterface;
use Drupal\language\Entity\ConfigurableLanguage;
use Drupal\Tests\BrowserTestBase;

/**
 * Adds and configures languages to check negotiation changes.
 *
 * @group language
 */
class LanguageConfigurationTest extends BrowserTestBase {

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
  public function testLanguageConfiguration() {
    // Ensure the after installing the language module the weight of the English
    // language is still 0.
    $this->assertEqual(0, ConfigurableLanguage::load('en')->getWeight(), 'The English language has a weight of 0.');

    // User to add and remove language.
    $admin_user = $this->drupalCreateUser([
      'administer languages',
      'access administration pages',
    ]);
    $this->drupalLogin($admin_user);

    // Check if the Default English language has no path prefix.
    $this->drupalGet('admin/config/regional/language/detection/url');
    $this->assertSession()->fieldValueEquals("prefix[en]", '');

    // Check that Add language is a primary button.
    $this->drupalGet('admin/config/regional/language/add');
    $button = $this->assertSession()->buttonExists('Add language');
    $this->assertTrue($button->hasClass("button--primary"));

    // Add predefined language.
    $edit = [
      'predefined_langcode' => 'fr',
    ];
    $this->submitForm($edit, 'Add language');
    $this->assertText('French');
    $this->assertSession()->addressEquals(Url::fromRoute('entity.configurable_language.collection'));
    // Langcode for Languages is always 'en'.
    $language = $this->config('language.entity.fr')->get();
    $this->assertEqual('en', $language['langcode']);

    // Check if the Default English language has no path prefix.
    $this->drupalGet('admin/config/regional/language/detection/url');
    $this->assertSession()->fieldValueEquals("prefix[en]", '');
    // Check if French has a path prefix.
    $this->drupalGet('admin/config/regional/language/detection/url');
    $this->assertSession()->fieldValueEquals("prefix[fr]", 'fr');

    // Check if we can change the default language.
    $this->drupalGet('admin/config/regional/language');
    $this->assertSession()->checkboxChecked('edit-site-default-language-en');

    // Change the default language.
    $edit = [
      'site_default_language' => 'fr',
    ];
    $this->submitForm($edit, 'Save configuration');
    $this->rebuildContainer();
    $this->assertSession()->checkboxChecked('edit-site-default-language-fr');
    $this->assertSession()->addressEquals(Url::fromRoute('entity.configurable_language.collection', [], ['langcode' => 'fr']));

    // Check if a valid language prefix is added after changing the default
    // language.
    $this->drupalGet('admin/config/regional/language/detection/url');
    $this->assertSession()->fieldValueEquals("prefix[en]", 'en');
    // Check if French still has a path prefix.
    $this->drupalGet('admin/config/regional/language/detection/url');
    $this->assertSession()->fieldValueEquals("prefix[fr]", 'fr');

    // Check that prefix can be changed.
    $edit = [
      'prefix[fr]' => 'french',
    ];
    $this->submitForm($edit, 'Save configuration');
    $this->assertSession()->fieldValueEquals("prefix[fr]", 'french');

    // Check that the prefix can be removed.
    $edit = [
      'prefix[fr]' => '',
    ];
    $this->submitForm($edit, 'Save configuration');
    $this->assertNoText('The prefix may only be left blank for the selected detection fallback language.');

    // Change default negotiation language.
    $this->config('language.negotiation')->set('selected_langcode', 'fr')->save();
    // Check that the prefix of a language that is not the negotiation one
    // cannot be changed to empty string.
    $edit = [
      'prefix[en]' => '',
    ];
    $this->submitForm($edit, 'Save configuration');
    $this->assertText('The prefix may only be left blank for the selected detection fallback language.');

    //  Check that prefix cannot be changed to contain a slash.
    $edit = [
      'prefix[en]' => 'foo/bar',
    ];
    $this->submitForm($edit, 'Save configuration');
    $this->assertText('The prefix may not contain a slash.');

    // Remove English language and add a new Language to check if langcode of
    // Language entity is 'en'.
    $this->drupalPostForm('admin/config/regional/language/delete/en', [], 'Delete');
    $this->rebuildContainer();
    $this->assertRaw(t('The %language (%langcode) language has been removed.', ['%language' => 'English', '%langcode' => 'en']));

    // Ensure that French language has a weight of 1 after being created through
    // the UI.
    $french = ConfigurableLanguage::load('fr');
    $this->assertEqual(1, $french->getWeight(), 'The French language has a weight of 1.');
    // Ensure that French language can now have a weight of 0.
    $french->setWeight(0)->save();
    $this->assertEqual(0, $french->getWeight(), 'The French language has a weight of 0.');
    // Ensure that new languages created through the API get a weight of 0.
    $afrikaans = ConfigurableLanguage::createFromLangcode('af');
    $afrikaans->save();
    $this->assertEqual(0, $afrikaans->getWeight(), 'The Afrikaans language has a weight of 0.');
    // Ensure that a new language can be created with any weight.
    $arabic = ConfigurableLanguage::createFromLangcode('ar');
    $arabic->setWeight(4)->save();
    $this->assertEqual(4, $arabic->getWeight(), 'The Arabic language has a weight of 0.');

    $edit = [
      'predefined_langcode' => 'de',
    ];
    $this->drupalPostForm('admin/config/regional/language/add', $edit, 'Add language');
    $language = $this->config('language.entity.de')->get();
    $this->assertEqual('fr', $language['langcode']);

    // Ensure that German language has a weight of 5 after being created through
    // the UI.
    $french = ConfigurableLanguage::load('de');
    $this->assertEqual(5, $french->getWeight(), 'The German language has a weight of 5.');
  }

  /**
   * Functional tests for setting system language weight on adding, editing and deleting languages.
   */
  public function testLanguageConfigurationWeight() {
    // User to add and remove language.
    $admin_user = $this->drupalCreateUser([
      'administer languages',
      'access administration pages',
      ]);
    $this->drupalLogin($admin_user);
    $this->checkConfigurableLanguageWeight();

    // Add predefined language.
    $edit = [
      'predefined_langcode' => 'fr',
    ];
    $this->drupalPostForm('admin/config/regional/language/add', $edit, 'Add language');
    $this->checkConfigurableLanguageWeight('after adding new language');

    // Re-ordering languages.
    $edit = [
      'languages[en][weight]' => $this->getHighestConfigurableLanguageWeight() + 1,
    ];
    $this->drupalPostForm('admin/config/regional/language', $edit, 'Save configuration');
    $this->checkConfigurableLanguageWeight('after re-ordering');

    // Remove predefined language.
    $this->drupalPostForm('admin/config/regional/language/delete/fr', [], 'Delete');
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
    foreach (\Drupal::languageManager()->getLanguages(LanguageInterface::STATE_LOCKED) as $locked_language) {
      $this->assertGreaterThan($max_configurable_language_weight, $locked_language->getWeight(), sprintf('System language %s does not have higher weight than configurable languages %s', $locked_language->getName(), $state));
    }
  }

  /**
   * Helper to get maximum weight of configurable (unlocked) languages.
   *
   * @return int
   *   Maximum weight of configurable languages.
   */
  protected function getHighestConfigurableLanguageWeight() {
    $max_weight = 0;

    $storage = $this->container->get('entity_type.manager')
      ->getStorage('configurable_language');
    $storage->resetCache();
    /* @var $languages \Drupal\Core\Language\LanguageInterface[] */
    $languages = $storage->loadMultiple();
    foreach ($languages as $language) {
      if (!$language->isLocked()) {
        $max_weight = max($max_weight, $language->getWeight());
      }
    }

    return $max_weight;
  }

}
