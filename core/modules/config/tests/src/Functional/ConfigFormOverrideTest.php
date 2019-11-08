<?php

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
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Tests that overrides do not affect forms.
   */
  public function testFormsWithOverrides() {
    $this->drupalLogin($this->drupalCreateUser(['access administration pages', 'administer site configuration']));

    $overridden_name = 'Site name global conf override';

    // Set up an override.
    $settings['config']['system.site']['name'] = (object) [
      'value' => $overridden_name,
      'required' => TRUE,
    ];
    $this->writeSettings($settings);

    // Test that everything on the form is the same, but that the override
    // worked for the actual site name.
    $this->drupalGet('admin/config/system/site-information');
    $this->assertTitle('Basic site settings | ' . $overridden_name);
    $elements = $this->xpath('//input[@name="site_name"]');
    $this->assertIdentical($elements[0]->getValue(), 'Drupal');

    // Submit the form and ensure the site name is not changed.
    $edit = [
      'site_name' => 'Custom site name',
    ];
    $this->drupalPostForm('admin/config/system/site-information', $edit, t('Save configuration'));
    $this->assertTitle('Basic site settings | ' . $overridden_name);
    $elements = $this->xpath('//input[@name="site_name"]');
    $this->assertIdentical($elements[0]->getValue(), $edit['site_name']);
  }

}
