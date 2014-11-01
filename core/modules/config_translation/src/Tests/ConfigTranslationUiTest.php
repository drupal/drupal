<?php

/**
 * @file
 * Contains \Drupal\config_translation\Tests\ConfigTranslationUiTest.
 */

namespace Drupal\config_translation\Tests;

use Drupal\Component\Serialization\Json;
use Drupal\Component\Utility\Unicode;
use Drupal\Core\Config\FileStorage;
use Drupal\Core\Language\Language;
use Drupal\Core\Language\LanguageInterface;
use Drupal\language\Entity\ConfigurableLanguage;
use Drupal\simpletest\WebTestBase;

/**
 * Translate settings and entities to various languages.
 *
 * @group config_translation
 */
class ConfigTranslationUiTest extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('node', 'contact', 'contact_test', 'config_translation', 'config_translation_test', 'views', 'views_ui', 'contextual');

  /**
   * Languages to enable.
   *
   * @var array
   */
  protected $langcodes = array('fr', 'ta');

  /**
   * Administrator user for tests.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $admin_user;

  /**
   * Translator user for tests.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $translator_user;

  /**
   * String translation storage object.
   *
   * @var \Drupal\locale\StringStorageInterface
   */
  protected $localeStorage;

  protected function setUp() {
    parent::setUp();
    $translator_permissions = array(
      'translate configuration',
    );
    $admin_permissions = array_merge(
      $translator_permissions,
      array(
        'administer languages',
        'administer site configuration',
        'administer contact forms',
        'access site-wide contact form',
        'access contextual links',
        'administer views',
        'administer account settings',
      )
    );
    // Create and login user.
    $this->translator_user = $this->drupalCreateUser($translator_permissions);
    $this->admin_user = $this->drupalCreateUser($admin_permissions);

    // Add languages.
    foreach ($this->langcodes as $langcode) {
      ConfigurableLanguage::createFromLangcode($langcode)->save();
    }
    $this->localeStorage = $this->container->get('locale.storage');
  }

  /**
   * Tests the site information translation interface.
   */
  public function testSiteInformationTranslationUi() {
    $this->drupalLogin($this->admin_user);

    $site_name = 'Site name for testing configuration translation';
    $site_slogan = 'Site slogan for testing configuration translation';
    $fr_site_name = 'Nom du site pour tester la configuration traduction';
    $fr_site_slogan = 'Slogan du site pour tester la traduction de configuration';
    $translation_base_url = 'admin/config/system/site-information/translate';

    // Set site name and slogan for default language.
    $this->setSiteInformation($site_name, $site_slogan);

    $this->drupalGet('admin/config/system/site-information');
    // Check translation tab exist.
    $this->assertLinkByHref($translation_base_url);

    $this->drupalGet($translation_base_url);

    // Check that the 'Edit' link in the source language links back to the
    // original form.
    $this->clickLink(t('Edit'));
    // Also check that saving the form leads back to the translation overview.
    $this->drupalPostForm(NULL, array(), t('Save configuration'));
    $this->assertUrl($translation_base_url);

    // Check 'Add' link of French to visit add page.
    $this->assertLinkByHref("$translation_base_url/fr/add");
    $this->clickLink(t('Add'));

    // Make sure original text is present on this page.
    $this->assertRaw($site_name);
    $this->assertRaw($site_slogan);

    // Update site name and slogan for French.
    $edit = array(
      'config_names[system.site][name][translation]' => $fr_site_name,
      'config_names[system.site][slogan][translation]' => $fr_site_slogan,
    );

    $this->drupalPostForm("$translation_base_url/fr/add", $edit, t('Save translation'));
    $this->assertRaw(t('Successfully saved @language translation.', array('@language' => 'French')));

    // Check for edit, delete links (and no 'add' link) for French language.
    $this->assertNoLinkByHref("$translation_base_url/fr/add");
    $this->assertLinkByHref("$translation_base_url/fr/edit");
    $this->assertLinkByHref("$translation_base_url/fr/delete");

    // Check translation saved proper.
    $this->drupalGet("$translation_base_url/fr/edit");
    $this->assertFieldByName('config_names[system.site][name][translation]', $fr_site_name);
    $this->assertFieldByName('config_names[system.site][slogan][translation]', $fr_site_slogan);

    // Check French translation of site name and slogan are in place.
    $this->drupalGet('fr');
    $this->assertRaw($fr_site_name);
    $this->assertRaw($fr_site_slogan);

    // Visit French site to ensure base language string present as source.
    $this->drupalGet("fr/$translation_base_url/fr/edit");
    $this->assertText($site_name);
    $this->assertText($site_slogan);
  }

  /**
   * Tests the site information translation interface.
   */
  public function testSourceValueDuplicateSave() {
    $this->drupalLogin($this->admin_user);

    $site_name = 'Site name for testing configuration translation';
    $site_slogan = 'Site slogan for testing configuration translation';
    $translation_base_url = 'admin/config/system/site-information/translate';
    $this->setSiteInformation($site_name, $site_slogan);

    $this->drupalGet($translation_base_url);

    // Case 1: Update new value for site slogan and site name.
    $edit = array(
      'config_names[system.site][name][translation]' => 'FR ' . $site_name,
      'config_names[system.site][slogan][translation]' => 'FR ' . $site_slogan,
    );
    // First time, no overrides, so just Add link.
    $this->drupalPostForm("$translation_base_url/fr/add", $edit, t('Save translation'));

    // Read overridden file from active config.
    $override = \Drupal::languageManager()->getLanguageConfigOverride('fr', 'system.site');

    // Expect both name and slogan in language specific file.
    $expected = array(
      'name' => 'FR ' . $site_name,
      'slogan' => 'FR ' . $site_slogan,
    );
    $this->assertEqual($expected, $override->get());

    // Case 2: Update new value for site slogan and default value for site name.
    $this->drupalGet("$translation_base_url/fr/edit");
    // Assert that the language configuration does not leak outside of the
    // translation form into the actual site name and slogan.
    $this->assertNoText('FR ' . $site_name);
    $this->assertNoText('FR ' . $site_slogan);
    $edit = array(
      'config_names[system.site][name][translation]' => $site_name,
      'config_names[system.site][slogan][translation]' => 'FR ' . $site_slogan,
    );
    $this->drupalPostForm(NULL, $edit, t('Save translation'));
    $this->assertRaw(t('Successfully updated @language translation.', array('@language' => 'French')));
    $override = \Drupal::languageManager()->getLanguageConfigOverride('fr', 'system.site');

    // Expect only slogan in language specific file.
    $expected = 'FR ' . $site_slogan;
    $this->assertEqual($expected, $override->get('slogan'));

    // Case 3: Keep default value for site name and slogan.
    $this->drupalGet("$translation_base_url/fr/edit");
    $this->assertNoText('FR ' . $site_slogan);
    $edit = array(
      'config_names[system.site][name][translation]' => $site_name,
      'config_names[system.site][slogan][translation]' => $site_slogan,
    );
    $this->drupalPostForm(NULL, $edit, t('Save translation'));
    $override = \Drupal::languageManager()->getLanguageConfigOverride('fr', 'system.site');

    // Expect no language specific file.
    $this->assertTrue($override->isNew());

    // Check configuration page with translator user. Should have no access.
    $this->drupalLogout();
    $this->drupalLogin($this->translator_user);
    $this->drupalGet('admin/config/system/site-information');
    $this->assertResponse(403);

    // While translator can access the translation page, the edit link is not
    // present due to lack of permissions.
    $this->drupalGet($translation_base_url);
    $this->assertNoLink(t('Edit'));

    // Check 'Add' link for French.
    $this->assertLinkByHref("$translation_base_url/fr/add");
  }

  /**
   * Tests the contact form translation.
   */
  public function testContactConfigEntityTranslation() {
    $this->drupalLogin($this->admin_user);

    $this->drupalGet('admin/structure/contact');

    // Check for default contact form configuration entity from Contact module.
    $this->assertLinkByHref('admin/structure/contact/manage/feedback');

    // Save default language configuration.
    $label = 'Send your feedback';
    $edit = array(
      'label' => $label,
      'recipients' => 'sales@example.com,support@example.com',
      'reply' => 'Thank you for your mail',
    );
    $this->drupalPostForm('admin/structure/contact/manage/feedback', $edit, t('Save'));

    // Ensure translation link is present.
    $translation_base_url = 'admin/structure/contact/manage/feedback/translate';
    $this->assertLinkByHref($translation_base_url);

    // Make sure translate tab is present.
    $this->drupalGet('admin/structure/contact/manage/feedback');
    $this->assertLink(t('Translate @type', array('@type' => 'contact form')));

    // Visit the form to confirm the changes.
    $this->drupalGet('contact/feedback');
    $this->assertText($label);

    foreach ($this->langcodes as $langcode) {
      $this->drupalGet($translation_base_url);
      $this->assertLink(t('Translate @type', array('@type' => 'contact form')));

      // 'Add' link should be present for $langcode translation.
      $translation_page_url = "$translation_base_url/$langcode/add";
      $this->assertLinkByHref($translation_page_url);

      // Make sure original text is present on this page.
      $this->drupalGet($translation_page_url);
      $this->assertText($label);

      // Update translatable fields.
      $edit = array(
        'config_names[contact.form.feedback][label][translation]' => 'Website feedback - ' . $langcode,
        'config_names[contact.form.feedback][reply][translation]' => 'Thank you for your mail - ' . $langcode,
      );

      // Save language specific version of form.
      $this->drupalPostForm($translation_page_url, $edit, t('Save translation'));

      // Expect translated values in language specific file.
      $override = \Drupal::languageManager()->getLanguageConfigOverride($langcode, 'contact.form.feedback');
      $expected = array(
        'label' => 'Website feedback - ' . $langcode,
        'reply' => 'Thank you for your mail - ' . $langcode,
      );
      $this->assertEqual($expected, $override->get());

      // Check for edit, delete links (and no 'add' link) for $langcode.
      $this->assertNoLinkByHref("$translation_base_url/$langcode/add");
      $this->assertLinkByHref("$translation_base_url/$langcode/edit");
      $this->assertLinkByHref("$translation_base_url/$langcode/delete");

      // Visit language specific version of form to check label.
      $this->drupalGet($langcode . '/contact/feedback');
      $this->assertText('Website feedback - ' . $langcode);

      // Submit feedback.
      $edit = array(
        'subject[0][value]' => 'Test subject',
        'message[0][value]' => 'Test message',
      );
      $this->drupalPostForm(NULL, $edit, t('Send message'));
    }

    // Now that all language translations are present, check translation and
    // original text all appear in any translated page on the translation
    // forms.
    foreach ($this->langcodes as $langcode) {
      $langcode_prefixes = array_merge(array(''), $this->langcodes);
      foreach ($langcode_prefixes as $langcode_prefix) {
        $this->drupalGet(ltrim("$langcode_prefix/$translation_base_url/$langcode/edit"));
        $this->assertFieldByName('config_names[contact.form.feedback][label][translation]', 'Website feedback - ' . $langcode);
        $this->assertText($label);
      }
    }

    // We get all emails so no need to check inside the loop.
    $captured_emails = $this->drupalGetMails();

    // Check language specific auto reply text in email body.
    foreach ($captured_emails as $email) {
      if ($email['id'] == 'contact_page_autoreply') {
        // Trim because we get an added newline for the body.
        $this->assertEqual(trim($email['body']), 'Thank you for your mail - ' . $email['langcode']);
      }
    }

    // Test that delete links work and operations perform properly.
    foreach ($this->langcodes as $langcode) {
      $replacements = array('%label' => t('!label !entity_type', array('!label' => $label, '!entity_type' => Unicode::strtolower(t('Contact form')))), '@language' => language_load($langcode)->getName());

      $this->drupalGet("$translation_base_url/$langcode/delete");
      $this->assertRaw(t('Are you sure you want to delete the @language translation of %label?', $replacements));
      // Assert link back to list page to cancel delete is present.
      $this->assertLinkByHref($translation_base_url);

      $this->drupalPostForm(NULL, array(), t('Delete'));
      $this->assertRaw(t('@language translation of %label was deleted', $replacements));
      $this->assertLinkByHref("$translation_base_url/$langcode/add");
      $this->assertNoLinkByHref("translation_base_url/$langcode/edit");
      $this->assertNoLinkByHref("$translation_base_url/$langcode/delete");

      // Expect no language specific file present anymore.
      $override = \Drupal::languageManager()->getLanguageConfigOverride($langcode, 'contact.form.feedback');
      $this->assertTrue($override->isNew());
    }

    // Check configuration page with translator user. Should have no access.
    $this->drupalLogout();
    $this->drupalLogin($this->translator_user);
    $this->drupalGet('admin/structure/contact/manage/feedback');
    $this->assertResponse(403);

    // While translator can access the translation page, the edit link is not
    // present due to lack of permissions.
    $this->drupalGet($translation_base_url);
    $this->assertNoLink(t('Edit'));

    // Check 'Add' link for French.
    $this->assertLinkByHref("$translation_base_url/fr/add");
  }

  /**
   * Tests date format translation.
   */
  public function testDateFormatTranslation() {
    $this->drupalLogin($this->admin_user);

    $this->drupalGet('admin/config/regional/date-time');

    // Check for medium format.
    $this->assertLinkByHref('admin/config/regional/date-time/formats/manage/medium');

    // Save default language configuration for a new format.
    $edit = array(
      'label' => 'Custom medium date',
      'id' => 'custom_medium',
      'date_format_pattern' => 'Y. m. d. H:i',
    );
    $this->drupalPostForm('admin/config/regional/date-time/formats/add', $edit, t('Add format'));

    // Test translating a default shipped format and our custom format.
    $formats = array(
      'medium' => 'Default medium date',
      'custom_medium' => 'Custom medium date',
    );
    foreach($formats as $id => $label) {
      $translation_base_url = 'admin/config/regional/date-time/formats/manage/' . $id . '/translate';

      $this->drupalGet($translation_base_url);

      // 'Add' link should be present for French translation.
      $translation_page_url = "$translation_base_url/fr/add";
      $this->assertLinkByHref($translation_page_url);

      // Make sure original text is present on this page.
      $this->drupalGet($translation_page_url);
      $this->assertText($label);

      // Update translatable fields.
      $edit = array(
        'config_names[core.date_format.' . $id . '][label][translation]' => $id . ' - FR',
        'config_names[core.date_format.' . $id . '][pattern][translation]' => 'D',
      );

      // Save language specific version of form.
      $this->drupalPostForm($translation_page_url, $edit, t('Save translation'));

      // Get translation and check we've got the right value.
      $override = \Drupal::languageManager()->getLanguageConfigOverride('fr', 'core.date_format.' . $id);
      $expected = array(
        'label' => $id . ' - FR',
        'pattern' => 'D',
      );
      $this->assertEqual($expected, $override->get());

      // Formatting the date 8 / 27 / 1985 @ 13:37 EST with pattern D should
      // display "Tue".
      $formatted_date = format_date(494015820, $id, NULL, NULL, 'fr');
      $this->assertEqual($formatted_date, 'Tue', 'Got the right formatted date using the date format translation pattern.');
    }
  }

  /**
   * Tests the account settings translation interface.
   *
   * This is the only special case so far where we have multiple configuration
   * names involved building up one configuration translation form. Test that
   * the translations are saved for all configuration names properly.
   */
  public function testAccountSettingsConfigurationTranslation() {
    $this->drupalLogin($this->admin_user);

    $this->drupalGet('admin/config/people/accounts');
    $this->assertLink(t('Translate @type', array('@type' => 'account settings')));

    $this->drupalGet('admin/config/people/accounts/translate');
    $this->assertLink(t('Translate @type', array('@type' => 'account settings')));
    $this->assertLinkByHref('admin/config/people/accounts/translate/fr/add');

    // Update account settings fields for French.
    $edit = array(
      'config_names[user.settings][anonymous][translation]' => 'Anonyme',
      'config_names[user.mail][status_blocked][status_blocked.subject][translation]' => 'Testing, your account is blocked.',
      'config_names[user.mail][status_blocked][status_blocked.body][translation]' => 'Testing account blocked body.',
    );

    $this->drupalPostForm('admin/config/people/accounts/translate/fr/add', $edit, t('Save translation'));

    // Make sure the changes are saved and loaded back properly.
    $this->drupalGet('admin/config/people/accounts/translate/fr/edit');
    foreach ($edit as $key => $value) {
      // Check the translations appear in the right field type as well.
      $xpath = '//' . (strpos($key, '.body') ? 'textarea' : 'input') . '[@name="'. $key . '"]';
      $this->assertFieldByXPath($xpath, $value);
    }
    // Check that labels for email settings appear.
    $this->assertText(t('Account cancellation confirmation'));
    $this->assertText(t('Password recovery'));
  }

  /**
   * Tests source and target language edge cases.
   */
  public function testSourceAndTargetLanguage() {
    $this->drupalLogin($this->admin_user);

    // Loading translation page for not-specified language (und)
    // should return 403.
    $this->drupalGet('admin/config/system/site-information/translate/und/add');
    $this->assertResponse(403);

    // Check the source language doesn't have 'Add' or 'Delete' link and
    // make sure source language edit goes to original configuration page
    // not the translation specific edit page.
    $this->drupalGet('admin/config/system/site-information/translate');
    $this->assertNoLinkByHref('admin/config/system/site-information/translate/en/edit');
    $this->assertNoLinkByHref('admin/config/system/site-information/translate/en/add');
    $this->assertNoLinkByHref('admin/config/system/site-information/translate/en/delete');
    $this->assertLinkByHref('admin/config/system/site-information');

    // Translation addition to source language should return 403.
    $this->drupalGet('admin/config/system/site-information/translate/en/add');
    $this->assertResponse(403);

    // Translation editing in source language should return 403.
    $this->drupalGet('admin/config/system/site-information/translate/en/edit');
    $this->assertResponse(403);

    // Translation deletion in source language should return 403.
    $this->drupalGet('admin/config/system/site-information/translate/en/delete');
    $this->assertResponse(403);

    // Set default language of site information to not-specified language (und).
    $this->container
      ->get('config.factory')
      ->get('system.site')
      ->set('langcode', LanguageInterface::LANGCODE_NOT_SPECIFIED)
      ->save();

    // Make sure translation tab does not exist on the configuration page.
    $this->drupalGet('admin/config/system/site-information');
    $this->assertNoLinkByHref('admin/config/system/site-information/translate');

    // If source language is not specified, translation page should be 403.
    $this->drupalGet('admin/config/system/site-information/translate');
    $this->assertResponse(403);
  }

  /**
   * Tests the views translation interface.
   */
  public function testViewsTranslationUI() {
    $this->drupalLogin($this->admin_user);

    // Assert contextual link related to views.
    $ids = array('entity.view.edit_form:view=frontpage:location=page&name=frontpage&display_id=page_1');
    $response = $this->renderContextualLinks($ids, 'node');
    $this->assertResponse(200);
    $json = Json::decode($response);
    $this->assertTrue(strpos($json[$ids[0]], t('Translate view')), 'Translate view contextual link added.');

    $description = 'All content promoted to the front page.';
    $human_readable_name = 'Frontpage';
    $display_settings_master = 'Master';
    $display_options_master = '(Empty)';
    $translation_base_url = 'admin/structure/views/view/frontpage/translate';

    $this->drupalGet($translation_base_url);

    // Check 'Add' link of French to visit add page.
    $this->assertLinkByHref("$translation_base_url/fr/add");
    $this->clickLink(t('Add'));

    // Make sure original text is present on this page.
    $this->assertRaw($description);
    $this->assertRaw($human_readable_name);

    // Update Views Fields for French.
    $edit = array(
      'config_names[views.view.frontpage][description][translation]' => $description . " FR",
      'config_names[views.view.frontpage][label][translation]' => $human_readable_name . " FR",
      'config_names[views.view.frontpage][display][default][display.default.display_title][translation]' => $display_settings_master . " FR",
      'config_names[views.view.frontpage][display][default][display_options][display.default.display_options.title][translation]' => $display_options_master . " FR",
    );
    $this->drupalPostForm("$translation_base_url/fr/add", $edit, t('Save translation'));
    $this->assertRaw(t('Successfully saved @language translation.', array('@language' => 'French')));

    // Check for edit, delete links (and no 'add' link) for French language.
    $this->assertNoLinkByHref("$translation_base_url/fr/add");
    $this->assertLinkByHref("$translation_base_url/fr/edit");
    $this->assertLinkByHref("$translation_base_url/fr/delete");

    // Check translation saved proper.
    $this->drupalGet("$translation_base_url/fr/edit");
    $this->assertFieldByName('config_names[views.view.frontpage][description][translation]', $description . " FR");
    $this->assertFieldByName('config_names[views.view.frontpage][label][translation]', $human_readable_name . " FR");
    $this->assertFieldByName('config_names[views.view.frontpage][display][default][display.default.display_title][translation]', $display_settings_master . " FR");
    $this->assertFieldByName('config_names[views.view.frontpage][display][default][display_options][display.default.display_options.title][translation]', $display_options_master . " FR");
  }

  /**
   * Test translation storage in locale storage.
   */
  public function testLocaleDBStorage() {
    // Enable import of translations. By default this is disabled for automated
    // tests.
    \Drupal::config('locale.settings')
      ->set('translation.import_enabled', TRUE)
      ->save();

    $this->drupalLogin($this->admin_user);

    $langcode = 'xx';
    $name = $this->randomMachineName(16);
    $edit = array(
      'predefined_langcode' => 'custom',
      'langcode' => $langcode,
      'label' => $name,
      'direction' => Language::DIRECTION_LTR,
    );
    $this->drupalPostForm('admin/config/regional/language/add', $edit, t('Add custom language'));

    // Make sure there is no translation stored in locale storage before edit.
    $translation = $this->getTranslation('user.settings', 'anonymous', 'fr');
    $this->assertTrue(empty($translation));

    // Add custom translation.
    $edit = array(
      'config_names[user.settings][anonymous][translation]' => 'Anonyme',
    );
    $this->drupalPostForm('admin/config/people/accounts/translate/fr/add', $edit, t('Save translation'));

    // Make sure translation stored in locale storage after saved language
    // specific configuration translation.
    $translation = $this->getTranslation('user.settings', 'anonymous', 'fr');
    $this->assertEqual('Anonyme', $translation->getString());

    // revert custom translations to base translation.
    $edit = array(
      'config_names[user.settings][anonymous][translation]' => 'Anonymous',
    );
    $this->drupalPostForm('admin/config/people/accounts/translate/fr/edit', $edit, t('Save translation'));

    // Make sure there is no translation stored in locale storage after revert.
    $translation = $this->getTranslation('user.settings', 'anonymous', 'fr');
    $this->assertEqual('Anonymous', $translation->getString());
  }

  /**
   * Tests the single language existing.
   */
  public function testSingleLanguageUI() {
    $this->drupalLogin($this->admin_user);

    // Delete French language
    $this->drupalPostForm('admin/config/regional/language/delete/fr', array(), t('Delete'));
    $this->assertRaw(t('The %language (%langcode) language has been removed.', array('%language' => 'French', '%langcode' => 'fr')));

    // Change default language to Tamil.
    $edit = array(
      'site_default_language' => 'ta',
    );
    $this->drupalPostForm('admin/config/regional/settings', $edit, t('Save configuration'));
    $this->assertRaw(t('The configuration options have been saved.'));

    // Delete English language
    $this->drupalPostForm('admin/config/regional/language/delete/en', array(), t('Delete'));
    $this->assertRaw(t('The %language (%langcode) language has been removed.', array('%language' => 'English', '%langcode' => 'en')));

    // Visit account setting translation page, this should not
    // throw any notices.
    $this->drupalGet('admin/config/people/accounts/translate');
    $this->assertResponse(200);
  }

  /**
   * Tests the config_translation_info_alter() hook.
   */
  public function testAlterInfo() {
    $this->drupalLogin($this->admin_user);

    $this->container->get('state')->set('config_translation_test_config_translation_info_alter', TRUE);
    $this->container->get('plugin.manager.config_translation.mapper')->clearCachedDefinitions();

    // Check out if the translation page has the altered in settings.
    $this->drupalGet('admin/config/system/site-information/translate/fr/add');
    $this->assertText(t('Feed channel'));
    $this->assertText(t('Feed description'));

    // Check if the translation page does not have the altered out settings.
    $this->drupalGet('admin/config/people/accounts/translate/fr/add');
    $this->assertText(t('Name'));
    $this->assertNoText(t('Account cancellation confirmation'));
    $this->assertNoText(t('Password recovery'));
  }

  /**
   * Gets translation from locale storage.
   *
   * @param $config_name
   *   Configuration object.
   * @param $key
   *   Translation configuration field key.
   * @param $langcode
   *   String language code to load translation.
   *
   * @return bool|mixed
   *   Returns translation if exists, FALSE otherwise.
   */
  protected function getTranslation($config_name, $key, $langcode) {
    $settings_locations = $this->localeStorage->getLocations(array('type' => 'configuration', 'name' => $config_name));
    $this->assertTrue(!empty($settings_locations), format_string('Configuration locations found for %config_name.', array('%config_name' => $config_name)));

    if (!empty($settings_locations)) {
      $source = $this->container->get('config.factory')->get($config_name)->get($key);
      $source_string = $this->localeStorage->findString(array('source' => $source, 'type' => 'configuration'));
      $this->assertTrue(!empty($source_string), format_string('Found string for %config_name.%key.', array('%config_name' => $config_name, '%key' => $key)));

      if (!empty($source_string)) {
        $conditions = array(
          'lid' => $source_string->lid,
          'language' => $langcode,
        );
        $translations = $this->localeStorage->getTranslations($conditions + array('translated' => TRUE));
        return reset($translations);
      }
    }
    return FALSE;
  }

  /**
   * Sets site name and slogan for default language, helps in tests.
   *
   * @param string $site_name
   * @param string $site_slogan
   */
  protected function setSiteInformation($site_name, $site_slogan) {
    $edit = array(
      'site_name' => $site_name,
      'site_slogan' => $site_slogan,
    );
    $this->drupalPostForm('admin/config/system/site-information', $edit, t('Save configuration'));
    $this->assertRaw(t('The configuration options have been saved.'));
  }

  /**
   * Get server-rendered contextual links for the given contextual link ids.
   *
   * @param array $ids
   *   An array of contextual link ids.
   * @param string $current_path
   *   The Drupal path for the page for which the contextual links are rendered.
   *
   * @return string
   *   The response body.
   */
  protected function renderContextualLinks($ids, $current_path) {
    $post = array();
    for ($i = 0; $i < count($ids); $i++) {
      $post['ids[' . $i . ']'] = $ids[$i];
    }
    return $this->drupalPost('contextual/render', 'application/json', $post, array('query' => array('destination' => $current_path)));
  }

}
