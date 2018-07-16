<?php

namespace Drupal\Tests\search\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Tests that searching for a phrase gets the correct page count.
 *
 * @group search
 */
class SearchExactTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['node', 'search'];

  /**
   * Tests that the correct number of pager links are found for both keywords and phrases.
   */
  public function testExactQuery() {
    $this->drupalCreateContentType(['type' => 'page', 'name' => 'Basic page']);

    // Log in with sufficient privileges.
    $user = $this->drupalCreateUser(['create page content', 'search content']);
    $this->drupalLogin($user);

    $settings = [
      'type' => 'page',
      'title' => 'Simple Node',
    ];
    // Create nodes with exact phrase.
    for ($i = 0; $i <= 17; $i++) {
      $settings['body'] = [['value' => 'love pizza']];
      $this->drupalCreateNode($settings);
    }
    // Create nodes containing keywords.
    for ($i = 0; $i <= 17; $i++) {
      $settings['body'] = [['value' => 'love cheesy pizza']];
      $this->drupalCreateNode($settings);
    }
    // Create another node and save it for later.
    $settings['body'] = [['value' => 'Druplicon']];
    $node = $this->drupalCreateNode($settings);

    // Update the search index.
    $this->container->get('plugin.manager.search')->createInstance('node_search')->updateIndex();
    search_update_totals();

    // Refresh variables after the treatment.
    $this->refreshVariables();

    // Test that the correct number of pager links are found for keyword search.
    $edit = ['keys' => 'love pizza'];
    $this->drupalPostForm('search/node', $edit, t('Search'));
    $this->assertLinkByHref('page=1', 0, '2nd page link is found for keyword search.');
    $this->assertLinkByHref('page=2', 0, '3rd page link is found for keyword search.');
    $this->assertLinkByHref('page=3', 0, '4th page link is found for keyword search.');
    $this->assertNoLinkByHref('page=4', '5th page link is not found for keyword search.');

    // Test that the correct number of pager links are found for exact phrase search.
    $edit = ['keys' => '"love pizza"'];
    $this->drupalPostForm('search/node', $edit, t('Search'));
    $this->assertLinkByHref('page=1', 0, '2nd page link is found for exact phrase search.');
    $this->assertNoLinkByHref('page=2', '3rd page link is not found for exact phrase search.');

    // Check that with post settings turned on the post information is displayed.
    $node_type_config = \Drupal::configFactory()->getEditable('node.type.page');
    $node_type_config->set('display_submitted', TRUE);
    $node_type_config->save();

    $edit = ['keys' => 'Druplicon'];
    $this->drupalPostForm('search/node', $edit, t('Search'));
    $this->assertText($user->getUsername(), 'Basic page node displays author name when post settings are on.');
    $this->assertText(format_date($node->getChangedTime(), 'short'), 'Basic page node displays post date when post settings are on.');

    // Check that with post settings turned off the user and changed date
    // information is not displayed.
    $node_type_config->set('display_submitted', FALSE);
    $node_type_config->save();
    $edit = ['keys' => 'Druplicon'];
    $this->drupalPostForm('search/node', $edit, t('Search'));
    $this->assertNoText($user->getUsername(), 'Basic page node does not display author name when post settings are off.');
    $this->assertNoText(format_date($node->getChangedTime(), 'short'), 'Basic page node does not display post date when post settings are off.');

  }

}
