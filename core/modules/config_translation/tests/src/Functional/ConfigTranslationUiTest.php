<?php

declare(strict_types=1);

namespace Drupal\Tests\config_translation\Functional;

use Drupal\Core\Language\Language;
use Drupal\Core\Language\LanguageInterface;
use Drupal\locale\Model\TranslationUpdateMode;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

// cspell:ignore anonyme viewsviewfiles
/**
 * Translate settings and entities to various languages.
 */
#[Group('config_translation')]
#[Group('#slow')]
#[RunTestsInSeparateProcesses]
class ConfigTranslationUiTest extends ConfigTranslationUiTestBase {

  /**
   * Tests the account settings translation interface.
   *
   * This is the only special case so far where we have multiple configuration
   * names involved building up one configuration translation form. Test that
   * the translations are saved for all configuration names properly.
   */
  public function testAccountSettingsConfigurationTranslation(): void {
    $this->drupalLogin($this->adminUser);

    $this->drupalGet('admin/config/people/accounts');
    $this->assertSession()->linkExists('Translate account settings');

    $this->drupalGet('admin/config/people/accounts/translate');
    $this->assertSession()->linkExists('Translate account settings');
    $this->assertSession()->linkByHrefExists('admin/config/people/accounts/translate/fr/add');

    // Update account settings fields for French.
    $edit = [
      'translation[config_names][user.settings][anonymous]' => 'Anonyme',
      'translation[config_names][user.mail][status_blocked][subject]' => 'Testing, your account is blocked.',
      'translation[config_names][user.mail][status_blocked][body]' => 'Testing account blocked body.',
    ];

    $this->drupalGet('admin/config/people/accounts/translate/fr/add');
    $this->submitForm($edit, 'Save translation');

    // Make sure the changes are saved and loaded back properly.
    $this->drupalGet('admin/config/people/accounts/translate/fr/edit');
    foreach ($edit as $key => $value) {
      // Check the translations appear in the right field type as well.
      $this->assertSession()->fieldValueEquals($key, $value);
    }
    // Check that labels for email settings appear.
    $this->assertSession()->pageTextContains('Account cancellation confirmation');
    $this->assertSession()->pageTextContains('Password recovery');
  }

  /**
   * Tests source and target language edge cases.
   */
  public function testSourceAndTargetLanguage(): void {
    $this->drupalLogin($this->adminUser);

    // Loading translation page for not-specified language (und)
    // should return 403.
    $this->drupalGet('admin/config/system/site-information/translate/und/add');
    $this->assertSession()->statusCodeEquals(403);

    // Check the source language doesn't have 'Add' or 'Delete' link and
    // make sure source language edit goes to original configuration page
    // not the translation specific edit page.
    $this->drupalGet('admin/config/system/site-information/translate');
    $this->assertSession()->linkByHrefNotExists('admin/config/system/site-information/translate/en/edit');
    $this->assertSession()->linkByHrefNotExists('admin/config/system/site-information/translate/en/add');
    $this->assertSession()->linkByHrefNotExists('admin/config/system/site-information/translate/en/delete');
    $this->assertSession()->linkByHrefExists('admin/config/system/site-information');

    // Translation addition to source language should return 403.
    $this->drupalGet('admin/config/system/site-information/translate/en/add');
    $this->assertSession()->statusCodeEquals(403);

    // Translation editing in source language should return 403.
    $this->drupalGet('admin/config/system/site-information/translate/en/edit');
    $this->assertSession()->statusCodeEquals(403);

    // Translation deletion in source language should return 403.
    $this->drupalGet('admin/config/system/site-information/translate/en/delete');
    $this->assertSession()->statusCodeEquals(403);

    // Set default language of site information to not-specified language (und).
    $this->config('system.site')
      ->set('langcode', LanguageInterface::LANGCODE_NOT_SPECIFIED)
      ->save();

    // Make sure translation tab does not exist on the configuration page.
    $this->drupalGet('admin/config/system/site-information');
    $this->assertSession()->linkByHrefNotExists('admin/config/system/site-information/translate');

    // If source language is not specified, translation page should be 403.
    $this->drupalGet('admin/config/system/site-information/translate');
    $this->assertSession()->statusCodeEquals(403);
  }

  /**
   * Tests translation storage in locale storage.
   */
  public function testLocaleDBStorage(): void {
    // Enable import of translations. By default this is disabled for automated
    // tests.
    $this->config('locale.settings')
      ->set('translation.import_enabled', TRUE)
      ->set('translation.use_source', TranslationUpdateMode::Local->value)
      ->save();

    $this->drupalLogin($this->adminUser);

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

    // Make sure there is no translation stored in locale storage before edit.
    $translation = $this->getTranslation('user.settings', 'anonymous', 'fr');
    $this->assertEmpty($translation);

    // Add custom translation.
    $edit = [
      'translation[config_names][user.settings][anonymous]' => 'Anonyme',
    ];
    $this->drupalGet('admin/config/people/accounts/translate/fr/add');
    $this->submitForm($edit, 'Save translation');

    // Make sure translation stored in locale storage after saved language
    // specific configuration translation.
    $translation = $this->getTranslation('user.settings', 'anonymous', 'fr');
    $this->assertEquals('Anonyme', $translation->getString());

    // Revert custom translations to base translation.
    $edit = [
      'translation[config_names][user.settings][anonymous]' => 'Anonymous',
    ];
    $this->drupalGet('admin/config/people/accounts/translate/fr/edit');
    $this->submitForm($edit, 'Save translation');

    // Make sure there is no translation stored in locale storage after revert.
    $translation = $this->getTranslation('user.settings', 'anonymous', 'fr');
    $this->assertEquals('Anonymous', $translation->getString());
  }

  /**
   * Tests the single language existing.
   */
  public function testSingleLanguageUI(): void {
    $this->drupalLogin($this->adminUser);

    // Delete French language.
    $this->drupalGet('admin/config/regional/language/delete/fr');
    $this->submitForm([], 'Delete');
    $this->assertSession()->pageTextContains('The French (fr) language has been removed.');

    // Change default language to Tamil.
    $edit = [
      'site_default_language' => 'ta',
    ];
    $this->drupalGet('admin/config/regional/language');
    $this->submitForm($edit, 'Save configuration');
    $this->assertSession()->pageTextContains('Configuration saved.');

    // Delete English language.
    $this->drupalGet('admin/config/regional/language/delete/en');
    $this->submitForm([], 'Delete');
    $this->assertSession()->pageTextContains('The English (en) language has been removed.');

    // Visit account setting translation page, this should not
    // throw any notices.
    $this->drupalGet('admin/config/people/accounts/translate');
    $this->assertSession()->statusCodeEquals(200);
  }

  /**
   * Tests the config_translation_info_alter() hook.
   */
  public function testAlterInfo(): void {
    $this->drupalLogin($this->adminUser);

    $this->container->get('state')->set('config_translation_test_config_translation_info_alter', TRUE);
    $this->container->get('plugin.manager.config_translation.mapper')->clearCachedDefinitions();

    // Check if the translation page does not have the altered out settings.
    $this->drupalGet('admin/config/people/accounts/translate/fr/add');
    $this->assertSession()->pageTextContains('Name');
    $this->assertSession()->pageTextNotContains('Account cancellation confirmation');
    $this->assertSession()->pageTextNotContains('Password recovery');
  }

  /**
   * Tests the sequence data type translation.
   */
  public function testSequenceTranslation(): void {
    $this->drupalLogin($this->adminUser);
    /** @var \Drupal\Core\Config\ConfigFactoryInterface $config_factory */
    $config_factory = $this->container->get('config.factory');

    $expected = [
      'kitten',
      'llama',
      'elephant',
    ];
    $actual = $config_factory
      ->getEditable('config_translation_test.content')
      ->get('animals');
    $this->assertEquals($expected, $actual);

    $edit = [
      'translation[config_names][config_translation_test.content][content][value]' => '<p><strong>Hello World</strong> - FR</p>',
      'translation[config_names][config_translation_test.content][animals][0]' => 'kitten - FR',
      'translation[config_names][config_translation_test.content][animals][1]' => 'llama - FR',
      'translation[config_names][config_translation_test.content][animals][2]' => 'elephant - FR',
    ];
    $this->drupalGet('admin/config/media/file-system/translate/fr/add');
    $this->submitForm($edit, 'Save translation');

    $this->container->get('language.config_factory_override')
      ->setLanguage(new Language(['id' => 'fr']));

    $expected = [
      'kitten - FR',
      'llama - FR',
      'elephant - FR',
    ];
    $actual = $config_factory
      ->get('config_translation_test.content')
      ->get('animals');
    $this->assertEquals($expected, $actual);
  }

  /**
   * Tests escaping of source configuration label.
   */
  public function testLabelEscaping(): void {
    $this->drupalLogin($this->adminUser);

    // Testing via translating a role configuration.
    $role_id = $this->randomMachineName(16);
    $malicious_role_name = '">\'><img src="http://127.0.0.1/evil">';
    $this->drupalCreateRole([], $role_id, $malicious_role_name);

    // Visit the form that adds the translation of this label.
    $translate_link = 'admin/people/roles/manage/' . $role_id . '/translate/fr/add';
    $this->drupalGet($translate_link);

    // Ensure that the displayed label is escaped.
    $this->assertSession()->responseNotContains('<img src="http://127.0.0.1/evil">');
    $this->assertSession()->responseContains('<span lang="en">&quot;&gt;&#039;&gt;&lt;img src=&quot;http://127.0.0.1/evil&quot;&gt;</span>');
  }

}
