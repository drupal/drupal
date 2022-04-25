<?php

namespace Drupal\Tests\block\Functional;

/**
 * Tests branding block display.
 *
 * @group block
 */
class BlockSystemBrandingTest extends BlockTestBase {

  /**
   * Modules to install.
   *
   * @var array
   */
  protected static $modules = ['block', 'system'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    // Set a site slogan.
    $this->config('system.site')
      ->set('slogan', 'Community plumbing')
      ->save();
    // Add the system branding block to the page.
    $this->drupalPlaceBlock('system_branding_block', ['region' => 'header', 'id' => 'site-branding']);
  }

  /**
   * Tests system branding block configuration.
   */
  public function testSystemBrandingSettings() {
    $site_logo_xpath = '//div[@id="block-site-branding"]/a/img';
    $site_name_xpath = '//div[@id="block-site-branding"]/a[text() = "Drupal"]';
    $site_slogan_xpath = '//div[@id="block-site-branding"]/descendant::text()[last()]';

    // Set default block settings.
    $this->drupalGet('');
    $site_logo_element = $this->xpath($site_logo_xpath);
    $site_name_element = $this->xpath($site_name_xpath);

    // Test that all branding elements are displayed.
    $this->assertNotEmpty($site_logo_element, 'The branding block logo was found.');
    $this->assertNotEmpty($site_name_element, 'The branding block site name was found.');
    $this->assertSession()->elementTextContains('xpath', $site_slogan_xpath, 'Community plumbing');
    $this->assertSession()->responseHeaderContains('X-Drupal-Cache-Tags', 'config:system.site');
    // Just this once, assert that the img src of the logo is as expected.
    $theme_path = \Drupal::service('extension.list.theme')->getPath($this->defaultTheme);
    $this->assertSession()->elementAttributeContains('xpath', $site_logo_xpath, 'src', $theme_path . '/logo.svg');

    // Be sure the slogan is XSS-filtered.
    $this->config('system.site')
      ->set('slogan', '<script>alert("Community carpentry");</script>')
      ->save();
    $this->drupalGet('');
    $this->assertSession()->elementTextContains('xpath', $site_slogan_xpath, 'alert("Community carpentry");');
    $this->assertSession()->responseNotContains('<script>alert("Community carpentry");</script>');
    // Turn just the logo off.
    $this->config('block.block.site-branding')
      ->set('settings.use_site_logo', 0)
      ->save();
    $this->drupalGet('');
    $site_logo_element = $this->xpath($site_logo_xpath);
    $site_name_element = $this->xpath($site_name_xpath);
    // Re-test all branding elements.
    $this->assertEmpty($site_logo_element, 'The branding block logo was disabled.');
    $this->assertNotEmpty($site_name_element, 'The branding block site name was found.');
    $this->assertSession()->elementTextContains('xpath', $site_slogan_xpath, 'alert("Community carpentry");');
    $this->assertSession()->responseNotContains('<script>alert("Community carpentry");</script>');
    $this->assertSession()->responseHeaderContains('X-Drupal-Cache-Tags', 'config:system.site');

    // Turn just the site name off.
    $this->config('block.block.site-branding')
      ->set('settings.use_site_logo', 1)
      ->set('settings.use_site_name', 0)
      ->save();
    $this->drupalGet('');
    $site_logo_element = $this->xpath($site_logo_xpath);
    $site_name_element = $this->xpath($site_name_xpath);
    // Re-test all branding elements.
    $this->assertNotEmpty($site_logo_element, 'The branding block logo was found.');
    $this->assertEmpty($site_name_element, 'The branding block site name was disabled.');
    $this->assertSession()->elementTextContains('xpath', $site_slogan_xpath, 'alert("Community carpentry");');
    $this->assertSession()->responseNotContains('<script>alert("Community carpentry");</script>');
    $this->assertSession()->responseHeaderContains('X-Drupal-Cache-Tags', 'config:system.site');

    // Turn just the site slogan off.
    $this->config('block.block.site-branding')
      ->set('settings.use_site_name', 1)
      ->set('settings.use_site_slogan', 0)
      ->save();
    $this->drupalGet('');
    $site_logo_element = $this->xpath($site_logo_xpath);
    $site_name_element = $this->xpath($site_name_xpath);
    // Re-test all branding elements.
    $this->assertNotEmpty($site_logo_element, 'The branding block logo was found.');
    $this->assertNotEmpty($site_name_element, 'The branding block site name was found.');
    $this->assertSession()->elementTextNotContains('xpath', $site_slogan_xpath, 'Community carpentry');
    $this->assertSession()->responseHeaderContains('X-Drupal-Cache-Tags', 'config:system.site');

    // Turn the site name and the site slogan off.
    $this->config('block.block.site-branding')
      ->set('settings.use_site_name', 0)
      ->set('settings.use_site_slogan', 0)
      ->save();
    $this->drupalGet('');
    $site_logo_element = $this->xpath($site_logo_xpath);
    $site_name_element = $this->xpath($site_name_xpath);
    // Re-test all branding elements.
    $this->assertNotEmpty($site_logo_element, 'The branding block logo was found.');
    $this->assertEmpty($site_name_element, 'The branding block site name was disabled.');
    $this->assertSession()->elementTextNotContains('xpath', $site_slogan_xpath, 'Community carpentry');
    $this->assertSession()->responseHeaderContains('X-Drupal-Cache-Tags', 'config:system.site');
  }

}
