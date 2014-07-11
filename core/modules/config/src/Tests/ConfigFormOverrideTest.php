<?php

/**
 * @file
 * Contains \Drupal\config\Tests\ConfigFormOverrideTest.
 */

namespace Drupal\config\Tests;

use Drupal\simpletest\WebTestBase;

/**
 * Tests config overrides do not appear on forms that extend ConfigFormBase.
 *
 * @group config
 * @see \Drupal\Core\Form\ConfigFormBase
 */
class ConfigFormOverrideTest extends WebTestBase {

  /**
   * Tests that overrides do not affect forms.
   */
  public function testFormsWithOverrides() {
    $this->drupalLogin($this->drupalCreateUser(array('access administration pages', 'administer site configuration')));

    $overridden_name = 'Site name global conf override';

    // Set up an override.
    $settings['config']['system.site']['name'] = (object) array(
      'value' => $overridden_name,
      'required' => TRUE,
    );
    $this->writeSettings($settings);
    \Drupal::configFactory()->setOverrideState(TRUE);

    // Test that everything on the form is the same, but that the override
    // worked for the actual site name.
    $this->drupalGet('admin/config/system/site-information');
    $this->assertTitle('Site information | ' . $overridden_name);
    $elements = $this->xpath('//input[@name="site_name"]');
    $this->assertIdentical((string) $elements[0]['value'], 'Drupal');

    // Submit the form and ensure the site name is not changed.
    $edit = array(
      'site_name' => 'Custom site name',
    );
    $this->drupalPostForm('admin/config/system/site-information', $edit, t('Save configuration'));
    $this->assertTitle('Site information | ' . $overridden_name);
    $elements = $this->xpath('//input[@name="site_name"]');
    $this->assertIdentical((string) $elements[0]['value'], $edit['site_name']);
  }

}
