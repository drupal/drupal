<?php

declare(strict_types=1);

namespace Drupal\Tests\contact\Functional;

use Drupal\Tests\config_translation\Functional\ConfigTranslationUiTestBase;
use Drupal\user\Entity\Role;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Translate settings and entities to various languages.
 */
#[Group('contact')]
#[RunTestsInSeparateProcesses]
class ConfigTranslationUiTest extends ConfigTranslationUiTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'contact',
    'contact_test',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $admin_roles = $this->adminUser->getRoles(TRUE);
    assert(count($admin_roles) === 1);
    Role::load($admin_roles[0])
      ->grantPermission('administer contact forms')
      ->grantPermission('access site-wide contact form')
      ->save();
  }

  /**
   * Tests the contact form translation.
   */
  public function testContactConfigEntityTranslation(): void {
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

}
