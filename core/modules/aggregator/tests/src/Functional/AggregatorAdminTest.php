<?php

namespace Drupal\Tests\aggregator\Functional;

/**
 * Tests aggregator admin pages.
 *
 * @group aggregator
 */
class AggregatorAdminTest extends AggregatorTestBase {

  /**
   * Tests the settings form to ensure the correct default values are used.
   */
  public function testSettingsPage() {
    $this->drupalGet('admin/config');
    $this->clickLink('Aggregator');
    $this->clickLink('Settings');
    // Make sure that test plugins are present.
    $this->assertText('Test fetcher');
    $this->assertText('Test parser');
    $this->assertText('Test processor');

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
    $this->drupalPostForm('admin/config/services/aggregator/settings', $edit, t('Save configuration'));
    $this->assertText(t('The configuration options have been saved.'));

    foreach ($edit as $name => $value) {
      $this->assertFieldByName($name, $value, format_string('"@name" has correct default value.', ['@name' => $name]));
    }

    // Check for our test processor settings form.
    $this->assertText(t('Dummy length setting'));
    // Change its value to ensure that settingsSubmit is called.
    $edit = [
      'dummy_length' => 100,
    ];
    $this->drupalPostForm('admin/config/services/aggregator/settings', $edit, t('Save configuration'));
    $this->assertText(t('The configuration options have been saved.'));
    $this->assertFieldByName('dummy_length', 100, '"dummy_length" has correct default value.');

    // Make sure settings form is still accessible even after uninstalling a module
    // that provides the selected plugins.
    $this->container->get('module_installer')->uninstall(['aggregator_test']);
    $this->resetAll();
    $this->drupalGet('admin/config/services/aggregator/settings');
    $this->assertResponse(200);
  }

  /**
   * Tests the overview page.
   */
  public function testOverviewPage() {
    $feed = $this->createFeed($this->getRSS091Sample());
    $this->drupalGet('admin/config/services/aggregator');

    $result = $this->xpath('//table/tbody/tr');
    // Check if the amount of feeds in the overview matches the amount created.
    $this->assertEqual(1, count($result), 'Created feed is found in the overview');
    // Check if the fields in the table match with what's expected.
    $link = $this->xpath('//table/tbody/tr//td[1]/a');
    $this->assertEquals($feed->label(), $link[0]->getText());
    $count = $this->container->get('entity.manager')->getStorage('aggregator_item')->getItemCount($feed);
    $td = $this->xpath('//table/tbody/tr//td[2]');
    $this->assertEquals(\Drupal::translation()->formatPlural($count, '1 item', '@count items'), $td[0]->getText());

    // Update the items of the first feed.
    $feed->refreshItems();
    $this->drupalGet('admin/config/services/aggregator');
    $result = $this->xpath('//table/tbody/tr');
    // Check if the fields in the table match with what's expected.
    $link = $this->xpath('//table/tbody/tr//td[1]/a');
    $this->assertEquals($feed->label(), $link[0]->getText());
    $count = $this->container->get('entity.manager')->getStorage('aggregator_item')->getItemCount($feed);
    $td = $this->xpath('//table/tbody/tr//td[2]');
    $this->assertEquals(\Drupal::translation()->formatPlural($count, '1 item', '@count items'), $td[0]->getText());
  }

}
