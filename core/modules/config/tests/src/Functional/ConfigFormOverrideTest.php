<?php

declare(strict_types=1);

namespace Drupal\Tests\config\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Tests config overrides do not appear on forms that extend ConfigFormBase.
 *
 * @group config
 * @see \Drupal\Core\Form\ConfigFormBase
 */
class ConfigFormOverrideTest extends BrowserTestBase {

  /**
   * Message text that appears when forms have values for overridden config.
   */
  private const OVERRIDE_TEXT = 'These values are overridden. Changes on this form will be saved, but overrides will take precedence. See configuration overrides documentation for more information.';

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['update', 'config_override_message_test'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Tests that overrides do not affect forms.
   */
  public function testFormsWithOverrides(): void {
    $this->drupalLogin($this->drupalCreateUser([
      'access administration pages',
      'administer site configuration',
      'link to any page',
    ]));

    // Set up an overrides for configuration that is present in the form.
    $settings['config']['system.site']['weight_select_max'] = (object) [
      'value' => 200,
      'required' => TRUE,
    ];
    $this->writeSettings($settings);

    // Test that although system.site has an overridden key no override
    // information is displayed because there is no corresponding form field.
    $this->drupalGet('admin/config/system/site-information');
    $this->assertSession()->fieldValueEquals("site_name", 'Drupal');
    $this->assertSession()->pageTextNotContains(self::OVERRIDE_TEXT);

    // Set up an overrides for configuration that is present in the form.
    $overridden_name = 'Site name global conf override';
    $settings['config']['system.site']['name'] = (object) [
      'value' => $overridden_name,
      'required' => TRUE,
    ];
    $settings['config']['update.settings']['notification']['emails'] = (object) [
      'value' => [
        0 => 'a@abc.com',
        1 => 'admin@example.com',
      ],
      'required' => TRUE,
    ];
    $this->writeSettings($settings);
    $this->drupalGet('admin/config/system/site-information');
    $this->assertSession()->titleEquals('Basic site settings | ' . $overridden_name);
    $this->assertSession()->elementTextContains('css', 'div[data-drupal-messages]', self::OVERRIDE_TEXT);
    // Ensure the configuration overrides message is at the top of the form.
    $this->assertSession()->elementExists('css', 'div[data-drupal-messages] + details#edit-site-information');
    $this->assertSession()->elementContains('css', 'div[data-drupal-messages]', '<a href="#edit-site-name" title="\'Site name\' form element">Site name</a>');
    $this->assertSession()->fieldValueEquals("site_name", 'Drupal');
    $this->submitForm([
      'site_name' => 'Custom site name',
    ], 'Save configuration');
    $this->assertSession()->titleEquals('Basic site settings | ' . $overridden_name);
    $this->assertSession()->fieldValueEquals("site_name", 'Custom site name');

    // Ensure it works for sequence.
    $this->drupalGet('admin/reports/updates/settings');
    $this->submitForm([], 'Save configuration');
    $this->assertSession()->pageTextContainsOnce(self::OVERRIDE_TEXT);
    // There are two status messages on the page due to the save.
    $messages = $this->getSession()->getPage()->findAll('css', 'div[data-drupal-messages]');
    $this->assertCount(2, $messages);
    $this->assertStringContainsString('The configuration options have been saved.', $messages[0]->getText());
    $this->assertTrue(
      $messages[1]->hasLink('Email addresses to notify when updates are available'),
      "Link to 'Email addresses to notify when updates are available' exists"
    );
  }

}
