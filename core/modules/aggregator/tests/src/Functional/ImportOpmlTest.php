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
    $this->assertText('A single OPML document may contain many feeds.');
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
    $this->drupalPostForm('admin/config/services/aggregator/add/opml', $edit, 'Import');
    $this->assertRaw(t('<em>Either</em> upload a file or enter a URL.'));

    $path = $this->getEmptyOpml();
    $edit = [
      'files[upload]' => $path,
      'remote' => file_create_url($path),
    ];
    $this->drupalPostForm('admin/config/services/aggregator/add/opml', $edit, 'Import');
    $this->assertRaw(t('<em>Either</em> upload a file or enter a URL.'));

    // Error if the URL is invalid.
    $edit = ['remote' => 'invalidUrl://empty'];
    $this->drupalPostForm('admin/config/services/aggregator/add/opml', $edit, 'Import');
    $this->assertText('The URL invalidUrl://empty is not valid.');

    $after = $count_query->execute();
    $this->assertEqual($before, $after, 'No feeds were added during the three last form submissions.');
  }

  /**
   * Submits form with invalid, empty, and valid OPML files.
   */
  protected function submitImportForm() {
    $count_query = \Drupal::entityQuery('aggregator_feed')->accessCheck(FALSE)->count();
    $before = $count_query->execute();

    // Attempting to upload invalid XML.
    $form['files[upload]'] = $this->getInvalidOpml();
    $this->drupalPostForm('admin/config/services/aggregator/add/opml', $form, 'Import');
    $this->assertText('No new feed has been added.');

    // Attempting to load empty OPML from remote URL
    $edit = ['remote' => file_create_url($this->getEmptyOpml())];
    $this->drupalPostForm('admin/config/services/aggregator/add/opml', $edit, 'Import');
    $this->assertText('No new feed has been added.');

    $after = $count_query->execute();
    $this->assertEqual($before, $after, 'No feeds were added during the two last form submissions.');

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
    $this->drupalPostForm('admin/config/services/aggregator/add/opml', $edit, 'Import');
    // Verify that a duplicate URL was identified.
    $this->assertRaw(t('A feed with the URL %url already exists.', ['%url' => $feeds[0]['url[0][value]']]));
    // Verify that a duplicate title was identified.
    $this->assertRaw(t('A feed named %title already exists.', ['%title' => $feeds[1]['title[0][value]']]));

    $after = $count_query->execute();
    $this->assertEqual(2, $after, 'Verifying that two distinct feeds were added.');

    $feed_entities = Feed::loadMultiple();
    $refresh = TRUE;
    foreach ($feed_entities as $feed_entity) {
      $title[$feed_entity->getUrl()] = $feed_entity->label();
      $url[$feed_entity->label()] = $feed_entity->getUrl();
      $refresh = $refresh && $feed_entity->getRefreshRate() == 900;
    }

    $this->assertEqual($title[$feeds[0]['url[0][value]']], $feeds[0]['title[0][value]'], 'First feed was added correctly.');
    $this->assertEqual($url[$feeds[1]['title[0][value]']], $feeds[1]['url[0][value]'], 'Second feed was added correctly.');
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
