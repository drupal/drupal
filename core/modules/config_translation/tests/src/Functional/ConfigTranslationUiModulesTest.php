<?php

namespace Drupal\Tests\config_translation\Functional;

use Drupal\Component\Utility\Html;
use Drupal\Core\Language\Language;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\node\Entity\NodeType;

// cspell:ignore tuvan

/**
 * Translate settings and entities to various languages.
 *
 * @group config_translation
 * @group #slow
 */
class ConfigTranslationUiModulesTest extends ConfigTranslationUiTestBase {

  /**
   * Tests the contact form translation.
   */
  public function testContactConfigEntityTranslation() {
    $this->drupalLogin($this->adminUser);

    $this->drupalGet('admin/structure/contact');

    // Check for default contact form configuration entity from Contact module.
    $this->assertSession()->linkByHrefExists('admin/structure/contact/manage/feedback');

    // Save default language configuration.
    $label = 'Send your feedback';
    $edit = [
      'label' => $label,
      'recipients' => 'sales@example.com,support@example.com',
      'reply' => 'Thank you for your mail',
    ];
    $this->drupalGet('admin/structure/contact/manage/feedback');
    $this->submitForm($edit, 'Save');

    // Ensure translation link is present.
    $translation_base_url = 'admin/structure/contact/manage/feedback/translate';
    $this->assertSession()->linkByHrefExists($translation_base_url);

    // Make sure translate tab is present.
    $this->drupalGet('admin/structure/contact/manage/feedback');
    $this->assertSession()->linkExists('Translate contact form');

    // Visit the form to confirm the changes.
    $this->drupalGet('contact/feedback');
    $this->assertSession()->pageTextContains($label);

    foreach ($this->langcodes as $langcode) {
      $this->drupalGet($translation_base_url);
      $this->assertSession()->linkExists('Translate contact form');

      // 'Add' link should be present for $langcode translation.
      $translation_page_url = "$translation_base_url/$langcode/add";
      $this->assertSession()->linkByHrefExists($translation_page_url);

      // Make sure original text is present on this page.
      $this->drupalGet($translation_page_url);
      $this->assertSession()->pageTextContains($label);

      // Update translatable fields.
      $edit = [
        'translation[config_names][contact.form.feedback][label]' => 'Website feedback - ' . $langcode,
        'translation[config_names][contact.form.feedback][reply]' => 'Thank you for your mail - ' . $langcode,
      ];

      // Save language specific version of form.
      $this->drupalGet($translation_page_url);
      $this->submitForm($edit, 'Save translation');

      // Expect translated values in language specific file.
      $override = \Drupal::languageManager()->getLanguageConfigOverride($langcode, 'contact.form.feedback');
      $expected = [
        'label' => 'Website feedback - ' . $langcode,
        'reply' => 'Thank you for your mail - ' . $langcode,
      ];
      $this->assertEquals($expected, $override->get());

      // Check for edit, delete links (and no 'add' link) for $langcode.
      $this->assertSession()->linkByHrefNotExists("$translation_base_url/$langcode/add");
      $this->assertSession()->linkByHrefExists("$translation_base_url/$langcode/edit");
      $this->assertSession()->linkByHrefExists("$translation_base_url/$langcode/delete");

      // Visit language specific version of form to check label.
      $this->drupalGet($langcode . '/contact/feedback');
      $this->assertSession()->pageTextContains('Website feedback - ' . $langcode);

      // Submit feedback.
      $edit = [
        'subject[0][value]' => 'Test subject',
        'message[0][value]' => 'Test message',
      ];
      $this->submitForm($edit, 'Send message');
    }

    // Now that all language translations are present, check translation and
    // original text all appear in any translated page on the translation
    // forms.
    foreach ($this->langcodes as $langcode) {
      $langcode_prefixes = array_merge([''], $this->langcodes);
      foreach ($langcode_prefixes as $langcode_prefix) {
        $this->drupalGet(ltrim("$langcode_prefix/$translation_base_url/$langcode/edit", '/'));
        $this->assertSession()->fieldValueEquals('translation[config_names][contact.form.feedback][label]', 'Website feedback - ' . $langcode);
        $this->assertSession()->pageTextContains($label);
      }
    }

    // We get all emails so no need to check inside the loop.
    $captured_emails = $this->getMails();

    // Check language specific auto reply text in email body.
    foreach ($captured_emails as $email) {
      if ($email['id'] == 'contact_page_autoreply') {
        // Trim because we get an added newline for the body.
        $this->assertEquals('Thank you for your mail - ' . $email['langcode'], trim($email['body']));
      }
    }

    // Test that delete links work and operations perform properly.
    foreach ($this->langcodes as $langcode) {
      $language = \Drupal::languageManager()->getLanguage($langcode)->getName();

      $this->drupalGet("$translation_base_url/$langcode/delete");
      $this->assertSession()->pageTextContains("Are you sure you want to delete the $language translation of $label contact form?");
      // Assert link back to list page to cancel delete is present.
      $this->assertSession()->linkByHrefExists($translation_base_url);

      $this->submitForm([], 'Delete');
      $this->assertSession()->pageTextContains("$language translation of $label contact form was deleted");
      $this->assertSession()->linkByHrefExists("$translation_base_url/$langcode/add");
      $this->assertSession()->linkByHrefNotExists("translation_base_url/$langcode/edit");
      $this->assertSession()->linkByHrefNotExists("$translation_base_url/$langcode/delete");

      // Expect no language specific file present anymore.
      $override = \Drupal::languageManager()->getLanguageConfigOverride($langcode, 'contact.form.feedback');
      $this->assertTrue($override->isNew());
    }

    // Check configuration page with translator user. Should have no access.
    $this->drupalLogout();
    $this->drupalLogin($this->translatorUser);
    $this->drupalGet('admin/structure/contact/manage/feedback');
    $this->assertSession()->statusCodeEquals(403);

    // While translator can access the translation page, the edit link is not
    // present due to lack of permissions.
    $this->drupalGet($translation_base_url);
    $this->assertSession()->linkNotExists('Edit');

    // Check 'Add' link for French.
    $this->assertSession()->linkByHrefExists("$translation_base_url/fr/add");
  }

  /**
   * Tests the views translation interface.
   */
  public function testViewsTranslationUI() {
    $this->drupalLogin($this->adminUser);

    $description = 'All content promoted to the front page.';
    $human_readable_name = 'Frontpage';
    $display_settings_default = 'Default';
    $display_options_default = '(Empty)';
    $translation_base_url = 'admin/structure/views/view/frontpage/translate';

    $this->drupalGet($translation_base_url);

    // Check 'Add' link of French to visit add page.
    $this->assertSession()->linkByHrefExists("$translation_base_url/fr/add");
    $this->clickLink('Add');

    // Make sure original text is present on this page.
    $this->assertSession()->pageTextContains($description);
    $this->assertSession()->pageTextContains($human_readable_name);

    // Update Views Fields for French.
    $edit = [
      'translation[config_names][views.view.frontpage][description]' => $description . " FR",
      'translation[config_names][views.view.frontpage][label]' => $human_readable_name . " FR",
      'translation[config_names][views.view.frontpage][display][default][display_title]' => $display_settings_default . " FR",
      'translation[config_names][views.view.frontpage][display][default][display_options][title]' => $display_options_default . " FR",
    ];
    $this->drupalGet("{$translation_base_url}/fr/add");
    $this->submitForm($edit, 'Save translation');
    $this->assertSession()->pageTextContains('Successfully saved French translation.');

    // Check for edit, delete links (and no 'add' link) for French language.
    $this->assertSession()->linkByHrefNotExists("$translation_base_url/fr/add");
    $this->assertSession()->linkByHrefExists("$translation_base_url/fr/edit");
    $this->assertSession()->linkByHrefExists("$translation_base_url/fr/delete");

    // Check translation saved proper.
    $this->drupalGet("$translation_base_url/fr/edit");
    $this->assertSession()->fieldValueEquals('translation[config_names][views.view.frontpage][description]', $description . " FR");
    $this->assertSession()->fieldValueEquals('translation[config_names][views.view.frontpage][label]', $human_readable_name . " FR");
    $this->assertSession()->fieldValueEquals('translation[config_names][views.view.frontpage][display][default][display_title]', $display_settings_default . " FR");
    $this->assertSession()->fieldValueEquals('translation[config_names][views.view.frontpage][display][default][display_options][title]', $display_options_default . " FR");
  }

  /**
   * Tests the translation of field and field storage configuration.
   */
  public function testFieldConfigTranslation() {
    // Add a test field which has a translatable field setting and a
    // translatable field storage setting.
    $field_name = $this->randomMachineName();
    $field_storage = FieldStorageConfig::create([
      'field_name' => $field_name,
      'entity_type' => 'entity_test',
      'type' => 'test_field',
    ]);

    $translatable_storage_setting = $this->randomString();
    $field_storage->setSetting('translatable_storage_setting', $translatable_storage_setting);
    $field_storage->save();

    $bundle = $this->randomMachineName();
    entity_test_create_bundle($bundle);
    $field = FieldConfig::create([
      'field_name' => $field_name,
      'entity_type' => 'entity_test',
      'bundle' => $bundle,
    ]);

    $translatable_field_setting = $this->randomString();
    $field->setSetting('translatable_field_setting', $translatable_field_setting);
    $field->save();

    $this->drupalLogin($this->translatorUser);

    $this->drupalGet("/entity_test/structure/$bundle/fields/entity_test.$bundle.$field_name/translate");
    $this->clickLink('Add');

    $this->assertSession()->pageTextContains('Translatable field setting');
    $this->assertSession()->assertEscaped($translatable_field_setting);
    $this->assertSession()->pageTextContains('Translatable storage setting');
    $this->assertSession()->assertEscaped($translatable_storage_setting);
  }

  /**
   * Tests the translation of a boolean field settings.
   */
  public function testBooleanFieldConfigTranslation() {
    // Add a test boolean field.
    $field_name = $this->randomMachineName();
    FieldStorageConfig::create([
      'field_name' => $field_name,
      'entity_type' => 'entity_test',
      'type' => 'boolean',
    ])->save();

    $bundle = $this->randomMachineName();
    entity_test_create_bundle($bundle);
    $field = FieldConfig::create([
      'field_name' => $field_name,
      'entity_type' => 'entity_test',
      'bundle' => $bundle,
    ]);

    $on_label = 'On label (with <em>HTML</em> & things)';
    $field->setSetting('on_label', $on_label);
    $off_label = 'Off label (with <em>HTML</em> & things)';
    $field->setSetting('off_label', $off_label);
    $field->save();

    $this->drupalLogin($this->translatorUser);

    $this->drupalGet("/entity_test/structure/$bundle/fields/entity_test.$bundle.$field_name/translate");
    $this->clickLink('Add');

    // Checks the text of details summary element that surrounds the translation
    // options.
    $this->assertSession()->responseContains(Html::escape(strip_tags($on_label)) . ' Boolean settings');

    // Checks that the correct on and off labels appear on the form.
    $this->assertSession()->assertEscaped($on_label);
    $this->assertSession()->assertEscaped($off_label);
  }

  /**
   * Tests text_format translation.
   */
  public function testTextFormatTranslation() {
    $this->drupalLogin($this->adminUser);
    /** @var \Drupal\Core\Config\ConfigFactoryInterface $config_factory */
    $config_factory = $this->container->get('config.factory');

    $expected = [
      'value' => '<p><strong>Hello World</strong></p>',
      'format' => 'plain_text',
    ];
    $actual = $config_factory
      ->get('config_translation_test.content')
      ->getOriginal('content', FALSE);
    $this->assertEquals($expected, $actual);

    $translation_base_url = 'admin/config/media/file-system/translate';
    $this->drupalGet($translation_base_url);

    // 'Add' link should be present for French translation.
    $translation_page_url = "$translation_base_url/fr/add";
    $this->assertSession()->linkByHrefExists($translation_page_url);

    $this->drupalGet($translation_page_url);

    // Assert that changing the text format is not possible, even for an
    // administrator.
    $this->assertSession()->fieldNotExists('translation[config_names][config_translation_test.content][content][format]');

    // Update translatable fields.
    $edit = [
      'translation[config_names][config_translation_test.content][content][value]' => '<p><strong>Hello World</strong> - FR</p>',
    ];

    // Save language specific version of form.
    $this->drupalGet($translation_page_url);
    $this->submitForm($edit, 'Save translation');

    // Get translation and check we've got the right value.
    $expected = [
      'value' => '<p><strong>Hello World</strong> - FR</p>',
      'format' => 'plain_text',
    ];
    $this->container->get('language.config_factory_override')
      ->setLanguage(new Language(['id' => 'fr']));
    $actual = $config_factory
      ->get('config_translation_test.content')
      ->get('content');
    $this->assertEquals($expected, $actual);

    // Change the text format of the source configuration and verify that the
    // text format of the translation does not change because that could lead to
    // security vulnerabilities.
    $config_factory
      ->getEditable('config_translation_test.content')
      ->set('content.format', 'full_html')
      ->save();

    $actual = $config_factory
      ->get('config_translation_test.content')
      ->get('content');
    // The translation should not have changed, so re-use $expected.
    $this->assertEquals($expected, $actual);

    // Because the text is now in a text format that the translator does not
    // have access to, the translator should not be able to translate it.
    $translation_page_url = "$translation_base_url/fr/edit";
    $this->drupalLogin($this->translatorUser);
    $this->drupalGet($translation_page_url);
    $this->assertDisabledTextarea('edit-translation-config-names-config-translation-testcontent-content-value');
    $this->submitForm([], 'Save translation');
    // Check that submitting the form did not update the text format of the
    // translation.
    $actual = $config_factory
      ->get('config_translation_test.content')
      ->get('content');
    $this->assertEquals($expected, $actual);

    // The administrator must explicitly change the text format.
    $this->drupalLogin($this->adminUser);
    $edit = [
      'translation[config_names][config_translation_test.content][content][format]' => 'full_html',
    ];
    $this->drupalGet($translation_page_url);
    $this->submitForm($edit, 'Save translation');
    $expected = [
      'value' => '<p><strong>Hello World</strong> - FR</p>',
      'format' => 'full_html',
    ];
    $actual = $config_factory
      ->get('config_translation_test.content')
      ->get('content');
    $this->assertEquals($expected, $actual);
  }

  /**
   * Tests field translation for node fields.
   */
  public function testNodeFieldTranslation() {
    NodeType::create(['type' => 'article', 'name' => 'Article'])->save();

    $field_name = 'translatable_field';
    $field_storage = FieldStorageConfig::create([
      'field_name' => $field_name,
      'entity_type' => 'node',
      'type' => 'text',
    ]);

    $field_storage->setSetting('translatable_storage_setting', 'translatable_storage_setting');
    $field_storage->save();
    $field = FieldConfig::create([
      'field_name' => $field_name,
      'entity_type' => 'node',
      'bundle' => 'article',
    ]);
    $field->save();

    $this->drupalLogin($this->translatorUser);

    $this->drupalGet("/entity_test/structure/article/fields/node.article.$field_name/translate");
    $this->clickLink('Add');

    $form_values = [
      'translation[config_names][field.field.node.article.translatable_field][description]' => 'FR Help text.',
      'translation[config_names][field.field.node.article.translatable_field][label]' => 'FR label',
    ];
    $this->submitForm($form_values, 'Save translation');
    $this->assertSession()->pageTextContains('Successfully saved French translation.');

    // Check that the translations are saved.
    $this->clickLink('Add');
    $this->assertSession()->responseContains('FR label');
  }

  /**
   * Test translation save confirmation message.
   */
  public function testMenuTranslationWithoutChange() {
    $this->drupalLogin($this->adminUser);
    $this->drupalGet('admin/structure/menu/manage/main/translate/tyv/add');
    $this->submitForm([], 'Save translation');
    $this->assertSession()->pageTextContains('Tuvan translation was not added. To add a translation, you must modify the configuration.');

    $this->drupalGet('admin/structure/menu/manage/main/translate/tyv/add');
    $edit = [
      'translation[config_names][system.menu.main][label]' => 'Main navigation Translation',
      'translation[config_names][system.menu.main][description]' => 'Site section links Translation',
    ];
    $this->submitForm($edit, 'Save translation');
    $this->assertSession()->pageTextContains('Successfully saved Tuvan translation.');
  }

}
