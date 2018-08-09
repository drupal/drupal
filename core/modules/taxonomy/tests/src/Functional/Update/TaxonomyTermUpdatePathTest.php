<?php

namespace Drupal\Tests\taxonomy\Functional\Update;

use Drupal\FunctionalTests\Update\UpdatePathTestBase;
use Drupal\user\Entity\User;
use Drupal\views\Entity\View;

/**
 * Tests the upgrade path for taxonomy terms.
 *
 * @group taxonomy
 * @group Update
 * @group legacy
 */
class TaxonomyTermUpdatePathTest extends UpdatePathTestBase {

  /**
   * {@inheritdoc}
   */
  protected function setDatabaseDumpFiles() {
    $this->databaseDumpFiles = [
      __DIR__ . '/../../../../../system/tests/fixtures/update/drupal-8.filled.standard.php.gz',
      __DIR__ . '/../../../fixtures/update/drupal-8.views-taxonomy-term-publishing-status-2981887.php',
    ];
  }

  /**
   * Tests the conversion of taxonomy terms to be publishable.
   *
   * @see taxonomy_update_8601()
   */
  public function testPublishable() {
    $this->runUpdates();

    // Log in as user 1.
    $account = User::load(1);
    $account->passRaw = 'drupal';
    $this->drupalLogin($account);

    // Make sure our vocabulary exists.
    $this->drupalGet('admin/structure/taxonomy/manage/test_vocabulary/overview');

    // Make sure our terms exist.
    $assert_session = $this->assertSession();
    $assert_session->pageTextContains('Test root term');
    $assert_session->pageTextContains('Test child term');

    $this->drupalGet('taxonomy/term/3');
    $assert_session->statusCodeEquals('200');

    // Make sure the terms are still translated.
    $this->drupalGet('taxonomy/term/2/translations');
    $assert_session->linkExists('Test root term - Spanish');

    $storage = \Drupal::entityTypeManager()->getStorage('taxonomy_term');

    // Check that the 'content_translation_status' field has been updated
    // correctly.
    /** @var \Drupal\taxonomy\TermInterface $term */
    $term = $storage->load(2);
    $translation = $term->getTranslation('es');
    $this->assertTrue($translation->isPublished());

    // Check that taxonomy terms can be created, saved and then loaded.
    $term = $storage->create([
      'name' => 'Test term',
      'vid' => 'tags',
    ]);
    $term->save();

    $term = $storage->loadUnchanged($term->id());

    $this->assertEquals('Test term', $term->label());
    $this->assertEquals('tags', $term->bundle());
    $this->assertTrue($term->isPublished());

    // Check that the term can be unpublished.
    $term->setUnpublished();
    $term->save();
    $term = $storage->loadUnchanged($term->id());
    $this->assertFalse($term->isPublished());

    // Test the update does not run when a status field already exists.
    module_load_install('taxonomy');
    $this->assertEquals('The publishing status field has <strong>not</strong> been added to taxonomy terms. See <a href="https://www.drupal.org/node/2985366">this page</a> for more information on how to install it.', (string) taxonomy_update_8601());
    // Test the message can be overridden.
    \Drupal::state()->set('taxonomy_update_8601_skip_message', 'Another message');
    $this->assertEquals('Another message', (string) taxonomy_update_8601());
  }

  /**
   * Tests handling of the publishing status in taxonomy term views updates.
   *
   * @see taxonomy_post_update_handle_publishing_status_addition_in_views()
   */
  public function testPublishingStatusUpdateForTaxonomyTermViews() {
    // Check that the test view was previously using the
    // 'content_translation_status' field.
    $config = \Drupal::config('views.view.test_taxonomy_term_view_with_content_translation_status');
    $display_options = $config->get('display.default.display_options');
    $this->assertEquals('content_translation_status', $display_options['fields']['content_translation_status']['field']);
    $this->assertEquals('content_translation_status', $display_options['filters']['content_translation_status']['field']);
    $this->assertEquals('content_translation_status', $display_options['sorts']['content_translation_status']['field']);

    // Check a test view without any filter.
    $config = \Drupal::config('views.view.test_taxonomy_term_view_without_content_translation_status');
    $display_options = $config->get('display.default.display_options');
    $this->assertEmpty($display_options['filters']);

    $this->runUpdates();

    // Check that a view which had a field, filter and a sort on the
    // 'content_translation_status' field has been updated to use the new
    // 'status' field.
    $view = View::load('test_taxonomy_term_view_with_content_translation_status');
    foreach ($view->get('display') as $display) {
      $this->assertEquals('status', $display['display_options']['fields']['content_translation_status']['field']);
      $this->assertEquals('status', $display['display_options']['sorts']['content_translation_status']['field']);
      $this->assertEquals('status', $display['display_options']['filters']['content_translation_status']['field']);
    }

    // Check that a view without any filters has been updated to include a
    // filter for the 'status' field.
    $view = View::load('test_taxonomy_term_view_without_content_translation_status');
    foreach ($view->get('display') as $display) {
      $this->assertNotEmpty($display['display_options']['filters']);
      $this->assertEquals('status', $display['display_options']['filters']['status']['field']);
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function replaceUser1() {
    // Do not replace the user from our dump.
  }

}
