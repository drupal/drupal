<?php

namespace Drupal\Tests\aggregator\Functional;

use Drupal\aggregator\Entity\Feed;

/**
 * Tests OPML import.
 *
 * @group aggregator
 */
class ImportOpmlTest extends AggregatorTestBase {

  /**
   * Modules to install.
   *
   * @var array
   */
  protected static $modules = ['block', 'help'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $admin_user = $this->drupalCreateUser([
      'administer news feeds',
      'access news feeds',
      'create article content',
      'administer blocks',
    ]);
    $this->drupalLogin($admin_user);
  }

  /**
   * Opens OPML import form.
   */
  public function openImportForm() {
    // Enable the help block.
    $this->drupalPlaceBlock('help_block', ['region' => 'help']);

    $this->drupalGet('admin/config/services/aggregator/add/opml');
    $this->assertSession()->pageTextContains('A single OPML document may contain many feeds.');
    // Ensure that the file upload, remote URL, and refresh fields exist.
    $this->assertSession()->fieldExists('files[upload]');
    $this->assertSession()->fieldExists('remote');
    $this->assertSession()->fieldExists('refresh');
  }

  /**
   * Submits form filled with invalid fields.
   */
  public function validateImportFormFields() {
    $count_query = \Drupal::entityQuery('aggregator_feed')->accessCheck(FALSE)->count();
    $before = $count_query->execute();

    $edit = [];
    $this->drupalGet('admin/config/services/aggregator/add/opml');
    $this->submitForm($edit, 'Import');
    $this->assertSession()->pageTextContains('Either upload a file or enter a URL.');

    $path = $this->getEmptyOpml();
    $edit = [
      'files[upload]' => $path,
      'remote' => file_create_url($path),
    ];
    $this->drupalGet('admin/config/services/aggregator/add/opml');
    $this->submitForm($edit, 'Import');
    $this->assertSession()->pageTextContains('Either upload a file or enter a URL.');

    // Error if the URL is invalid.
    $edit = ['remote' => 'invalidUrl://empty'];
    $this->drupalGet('admin/config/services/aggregator/add/opml');
    $this->submitForm($edit, 'Import');
    $this->assertSession()->pageTextContains('The URL invalidUrl://empty is not valid.');

    $after = $count_query->execute();
    $this->assertEquals($before, $after, 'No feeds were added during the three last form submissions.');
  }

  /**
   * Submits form with invalid, empty, and valid OPML files.
   */
  protected function submitImportForm() {
    $count_query = \Drupal::entityQuery('aggregator_feed')->accessCheck(FALSE)->count();
    $before = $count_query->execute();

    // Attempting to upload invalid XML.
    $form['files[upload]'] = $this->getInvalidOpml();
    $this->drupalGet('admin/config/services/aggregator/add/opml');
    $this->submitForm($form, 'Import');
    $this->assertSession()->pageTextContains('No new feed has been added.');

    // Attempting to load empty OPML from remote URL
    $edit = ['remote' => file_create_url($this->getEmptyOpml())];
    $this->drupalGet('admin/config/services/aggregator/add/opml');
    $this->submitForm($edit, 'Import');
    $this->assertSession()->pageTextContains('No new feed has been added.');

    $after = $count_query->execute();
    $this->assertEquals($before, $after, 'No feeds were added during the two last form submissions.');

    foreach (Feed::loadMultiple() as $feed) {
      $feed->delete();
    }

    $feeds[0] = $this->getFeedEditArray();
    $feeds[1] = $this->getFeedEditArray();
    $feeds[2] = $this->getFeedEditArray();
    $edit = [
      'files[upload]' => $this->getValidOpml($feeds),
      'refresh'       => '900',
    ];
    $this->drupalGet('admin/config/services/aggregator/add/opml');
    $this->submitForm($edit, 'Import');
    // Verify that a duplicate URL was identified.
    $this->assertSession()->pageTextContains('A feed with the URL ' . $feeds[0]['url[0][value]'] . ' already exists.');
    // Verify that a duplicate title was identified.
    $this->assertSession()->pageTextContains('A feed named ' . $feeds[1]['title[0][value]'] . ' already exists.');

    $after = $count_query->execute();
    $this->assertEquals(2, $after, 'Verifying that two distinct feeds were added.');

    $feed_entities = Feed::loadMultiple();
    $refresh = TRUE;
    foreach ($feed_entities as $feed_entity) {
      $title[$feed_entity->getUrl()] = $feed_entity->label();
      $url[$feed_entity->label()] = $feed_entity->getUrl();
      $refresh = $refresh && $feed_entity->getRefreshRate() == 900;
    }

    $this->assertEquals($title[$feeds[0]['url[0][value]']], $feeds[0]['title[0][value]'], 'First feed was added correctly.');
    $this->assertEquals($url[$feeds[1]['title[0][value]']], $feeds[1]['url[0][value]'], 'Second feed was added correctly.');
    $this->assertTrue($refresh, 'Refresh times are correct.');
  }

  /**
   * Tests the import of an OPML file.
   */
  public function testOpmlImport() {
    $this->openImportForm();
    $this->validateImportFormFields();
    $this->submitImportForm();
  }

}
