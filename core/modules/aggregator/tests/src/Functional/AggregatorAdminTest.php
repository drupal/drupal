<?php

namespace Drupal\Tests\aggregator\Functional;

/**
 * Tests aggregator admin pages.
 *
 * @group aggregator
 * @group legacy
 */
class AggregatorAdminTest extends AggregatorTestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Tests the settings form to ensure the correct default values are used.
   */
  public function testSettingsPage() {
    $this->drupalGet('admin/config');
    $this->clickLink('Aggregator');
    $this->clickLink('Settings');
    // Make sure that test plugins are present.
    $this->assertSession()->pageTextContains('Test fetcher');
    $this->assertSession()->pageTextContains('Test parser');
    $this->assertSession()->pageTextContains('Test processor');

    // Set new values and enable test plugins.
    $edit = [
      'aggregator_allowed_html_tags' => '<a>',
      'aggregator_summary_items' => 10,
      'aggregator_clear' => 3600,
      'aggregator_teaser_length' => 200,
      'aggregator_fetcher' => 'aggregator_test_fetcher',
      'aggregator_parser' => 'aggregator_test_parser',
      'aggregator_processors[aggregator_test_processor]' => 'aggregator_test_processor',
    ];
    $this->drupalGet('admin/config/services/aggregator/settings');
    $this->submitForm($edit, 'Save configuration');
    $this->assertSession()->pageTextContains('The configuration options have been saved.');

    // Check that settings have the correct default value.
    foreach ($edit as $name => $value) {
      $this->assertSession()->fieldValueEquals($name, $value);
    }

    // Check for our test processor settings form.
    $this->assertSession()->pageTextContains('Dummy length setting');
    // Change its value to ensure that settingsSubmit is called.
    $edit = [
      'dummy_length' => 100,
    ];
    $this->drupalGet('admin/config/services/aggregator/settings');
    $this->submitForm($edit, 'Save configuration');
    $this->assertSession()->pageTextContains('The configuration options have been saved.');
    $this->assertSession()->fieldValueEquals('dummy_length', 100);

    // Make sure settings form is still accessible even after uninstalling a module
    // that provides the selected plugins.
    $this->container->get('module_installer')->uninstall(['aggregator_test']);
    $this->resetAll();
    $this->drupalGet('admin/config/services/aggregator/settings');
    $this->assertSession()->statusCodeEquals(200);
  }

  /**
   * Tests the overview page.
   */
  public function testOverviewPage() {
    $feed = $this->createFeed($this->getRSS091Sample());
    $this->drupalGet('admin/config/services/aggregator');

    // Check if the amount of feeds in the overview matches the amount created.
    $this->assertSession()->elementsCount('xpath', '//table/tbody/tr', 1);

    // Check if the fields in the table match with what's expected.
    $this->assertSession()->elementTextContains('xpath', '//table/tbody/tr//td[1]/a', $feed->label());
    $count = $this->container->get('entity_type.manager')->getStorage('aggregator_item')->getItemCount($feed);
    $this->assertSession()->elementTextContains('xpath', '//table/tbody/tr//td[2]', \Drupal::translation()->formatPlural($count, '1 item', '@count items'));

    // Update the items of the first feed.
    $feed->refreshItems();
    $this->drupalGet('admin/config/services/aggregator');
    $this->assertSession()->elementsCount('xpath', '//table/tbody/tr', 1);

    // Check if the fields in the table match with what's expected.
    $this->assertSession()->elementTextContains('xpath', '//table/tbody/tr//td[1]/a', $feed->label());
    $count = $this->container->get('entity_type.manager')->getStorage('aggregator_item')->getItemCount($feed);
    $this->assertSession()->elementTextContains('xpath', '//table/tbody/tr//td[2]', \Drupal::translation()->formatPlural($count, '1 item', '@count items'));
  }

}
