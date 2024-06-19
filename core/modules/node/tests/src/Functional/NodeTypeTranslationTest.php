<?php

declare(strict_types=1);

namespace Drupal\Tests\node\Functional;

use Drupal\language\Entity\ConfigurableLanguage;
use Drupal\Tests\BrowserTestBase;

/**
 * Ensures that node types translation work correctly.
 *
 * Note that the child site is installed in French; therefore, when making
 * assertions on translated text it is important to provide a langcode. This
 * ensures the asserts pass regardless of the Drupal version.
 *
 * @group node
 */
class NodeTypeTranslationTest extends BrowserTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = [
    'block',
    'config_translation',
    'field_ui',
    'node',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * The default language code to use in this test.
   *
   * @var array
   */
  protected $defaultLangcode = 'fr';

  /**
   * Languages to enable.
   *
   * @var array
   */
  protected $additionalLangcodes = ['es'];

  /**
   * Administrator user for tests.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $adminUser;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $admin_permissions = [
      'administer content types',
      'bypass node access',
      'administer node fields',
      'administer languages',
      'administer site configuration',
      'administer themes',
      'translate configuration',
    ];

    // Create and log in user.
    $this->adminUser = $this->drupalCreateUser($admin_permissions);

    // Add languages.
    foreach ($this->additionalLangcodes as $langcode) {
      ConfigurableLanguage::createFromLangcode($langcode)->save();
    }
  }

  /**
   * {@inheritdoc}
   *
   * Install Drupal in a language other than English for this test. This is not
   * needed to test the node type translation itself but acts as a regression
   * test.
   *
   * @see https://www.drupal.org/node/2584603
   */
  protected function installParameters() {
    $parameters = parent::installParameters();
    $parameters['parameters']['langcode'] = $this->defaultLangcode;
    // Create an empty po file so we don't attempt to download one from
    // localize.drupal.org. It does not need to match the version exactly as the
    // multi-lingual system will fallback.
    \Drupal::service('file_system')->mkdir($this->publicFilesDirectory . '/translations', NULL, TRUE);
    file_put_contents($this->publicFilesDirectory . "/translations/drupal-8.0.0.{$this->defaultLangcode}.po", '');
    return $parameters;
  }

  /**
   * Tests the node type translation.
   */
  public function testNodeTypeTranslation(): void {
    $type = $this->randomMachineName(16);
    $name = $this->randomString();
    $this->drupalLogin($this->adminUser);
    $this->drupalCreateContentType(['type' => $type, 'name' => $name]);

    // Translate the node type name.
    $langcode = $this->additionalLangcodes[0];
    $translated_name = $langcode . '-' . $name;
    $edit = [
      "translation[config_names][node.type.$type][name]" => $translated_name,
    ];

    // Edit the title label to avoid having an exception when we save the translation.
    $this->drupalGet("admin/structure/types/manage/{$type}/translate/{$langcode}/add");
    $this->submitForm($edit, 'Save translation');

    // Check the name is translated without admin theme for editing.
    $this->drupalGet('admin/appearance');
    $this->submitForm(['use_admin_theme' => '0'], 'Save configuration');
    $this->drupalGet("$langcode/node/add/$type");
    // This is a Spanish page, so ensure the text asserted is translated in
    // Spanish and not French by adding the langcode option.
    $this->assertSession()->responseContains(t('Create @name', ['@name' => $translated_name], ['langcode' => $langcode]));

    // Check the name is translated with admin theme for editing.
    $this->drupalGet('admin/appearance');
    $this->submitForm(['use_admin_theme' => '1'], 'Save configuration');
    $this->drupalGet("$langcode/node/add/$type");
    // This is a Spanish page, so ensure the text asserted is translated in
    // Spanish and not French by adding the langcode option.
    $this->assertSession()->responseContains(t('Create @name', ['@name' => $translated_name], ['langcode' => $langcode]));
  }

  /**
   * Tests the node type title label translation.
   */
  public function testNodeTypeTitleLabelTranslation(): void {
    $type = $this->randomMachineName(16);
    $name = $this->randomString();
    $this->drupalLogin($this->adminUser);
    $this->drupalCreateContentType(['type' => $type, 'name' => $name]);
    $langcode = $this->additionalLangcodes[0];

    // Edit the title label for it to be displayed on the translation form.
    $this->drupalGet("admin/structure/types/manage/{$type}");
    $this->submitForm(['title_label' => 'Edited title'], 'Save');

    // Assert that the title label is displayed on the translation form with the right value.
    $this->drupalGet("admin/structure/types/manage/$type/translate/$langcode/add");
    $this->assertSession()->pageTextContains('Edited title');

    // Translate the title label.
    $this->submitForm(["translation[config_names][core.base_field_override.node.$type.title][label]" => 'Translated title'], 'Save translation');

    // Assert that the right title label is displayed on the node add form. The
    // translations are created in this test; therefore, the assertions do not
    // use t(). If t() were used then the correct langcodes would need to be
    // provided.
    $this->drupalGet("node/add/$type");
    $this->assertSession()->pageTextContains('Edited title');
    $this->drupalGet("$langcode/node/add/$type");
    $this->assertSession()->pageTextContains('Translated title');

    // Add an email field.
    $this->drupalGet("admin/structure/types/manage/{$type}/fields/add-field");
    $this->submitForm([
      'new_storage_type' => 'email',
    ], 'Continue');
    $this->submitForm([
      'label' => 'Email',
      'field_name' => 'email',
    ], 'Continue');
    $this->submitForm([], 'Update settings');
    $this->submitForm([], 'Save settings');

    $type = $this->randomMachineName(16);
    $name = $this->randomString();
    $this->drupalCreateContentType(['type' => $type, 'name' => $name]);

    // Set tabs.
    $this->drupalPlaceBlock('local_tasks_block', ['primary' => TRUE]);

    // Change default language.
    $this->drupalGet('admin/config/regional/language');
    $this->submitForm(['site_default_language' => 'es'], 'Save configuration');

    // Try re-using the email field.
    $this->drupalGet("es/admin/structure/types/manage/$type/fields/reuse");
    $this->submitForm([], 'Re-use');
    $this->assertSession()->statusCodeEquals(200);
    $this->drupalGet("es/admin/structure/types/manage/$type/fields/node.$type.field_email/translate");
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->pageTextContains("The configuration objects have different language codes so they cannot be translated");
  }

}
