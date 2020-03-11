<?php

namespace Drupal\Tests\taxonomy\Functional\Update;

use Drupal\Core\Database\Database;
use Drupal\Core\Entity\Entity\EntityFormDisplay;
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
      __DIR__ . '/../../../fixtures/update/drupal-8.taxonomy-term-publishing-status-ui-2899923.php',
      __DIR__ . '/../../../fixtures/update/drupal-8.taxonomy-term-null-data-3056543.php',
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
   * Tests taxonomy term views updates succeed even if Views is not installed.
   */
  public function testPublishingStatusUpdateForTaxonomyTermViewsWithoutViews() {
    // Uninstalling Views will trigger some activity in the menu tree storage
    // system, which will cause errors until system_update_8001() is run. This
    // is because, in the drupal-8.filled.standard database fixture used for
    // this update test, the menu link titles are not serialized (this is what
    // gets done by system_update_8001()). Since this method is not testing
    // anything relating to menu links, it's OK to just truncate the menu_tree
    // table before uninstalling Views.
    $this->container->get('database')->truncate('menu_tree')->execute();
    $this->container->get('module_installer')->uninstall(['views']);
    $this->runUpdates();
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
   * Tests the conversion of taxonomy terms to be revisionable.
   *
   * @see taxonomy_post_update_make_taxonomy_term_revisionable()
   */
  public function testConversionToRevisionable() {
    // Set the batch size to 1 to test multiple steps.
    drupal_rewrite_settings([
      'settings' => [
        'update_sql_batch_size' => (object) [
          'value' => 1,
          'required' => TRUE,
        ],
      ],
    ]);

    // Check that there are broken terms in the taxonomy tables, initially.
    $this->assertTermName(997, '');
    $this->assertTermName(998, '');
    $this->assertTermName(999, 'tag999-es');

    $this->runUpdates();

    // Check that the update function returned the expected message.
    $this->assertSession()->pageTextContains('Taxonomy terms have been converted to be revisionable. 2 terms with data integrity issues were restored. More details have been logged.');

    // Check the database tables and the field storage definitions.
    $schema = \Drupal::database()->schema();
    $this->assertTrue($schema->tableExists('taxonomy_term_data'));
    $this->assertTrue($schema->tableExists('taxonomy_term_field_data'));
    $this->assertTrue($schema->tableExists('taxonomy_term_revision'));
    $this->assertTrue($schema->tableExists('taxonomy_term_field_revision'));

    $field_storage_definitions = \Drupal::service('entity.last_installed_schema.repository')->getLastInstalledFieldStorageDefinitions('taxonomy_term');
    $this->assertTrue($field_storage_definitions['langcode']->isRevisionable());
    $this->assertTrue($field_storage_definitions['name']->isRevisionable());
    $this->assertTrue($field_storage_definitions['description']->isRevisionable());
    $this->assertTrue($field_storage_definitions['changed']->isRevisionable());

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

    // Check that taxonomy terms can be created, saved and then loaded.
    /** @var \Drupal\taxonomy\TermInterface $term */
    $term = $storage->create([
      'name' => 'Test term',
      'vid' => 'article',
      'revision_log_message' => 'Initial revision.',
    ]);
    $term->save();

    $storage->resetCache();
    $term = $storage->loadRevision($term->getRevisionId());

    $this->assertEquals('Test term', $term->label());
    $this->assertEquals('article', $term->bundle());
    $this->assertEquals('Initial revision.', $term->getRevisionLogMessage());
    $this->assertTrue($term->isPublished());

    // Check that two terms were restored and one was ignored. The latter cannot
    // be manually restored, since we would end up with two data table records
    // having "default_langcode" equalling 1, which would not make sense.
    $this->assertTermName(997, 'tag997');
    $this->assertTermName(998, 'tag998');
    $this->assertTermName(999, 'tag999-es');
  }

  /**
   * Assert that a term name matches the expectation.
   *
   * @param string $id
   *   The term ID.
   * @param string $expected_name
   *   The expected term name.
   */
  protected function assertTermName($id, $expected_name) {
    $database = \Drupal::database();
    $query = $database->select('taxonomy_term_field_data', 'd');
    $query->join('taxonomy_term_data', 't', 't.tid = d.tid AND d.default_langcode = 1');
    $name = $query
      ->fields('d', ['name'])
      ->condition('d.tid', $id)
      ->execute()
      ->fetchField();

    $this->assertSame($expected_name, $name ?: '');
  }

  /**
   * Test the update hook requirements check for revisionable terms.
   *
   * @see taxonomy_post_update_make_taxonomy_term_revisionable()
   * @see taxonomy_requirements()
   */
  public function testMissingDataUpdateRequirementsCheck() {
    // Insert invalid data for a non-existent taxonomy term.
    Database::getConnection()->insert('taxonomy_term_data')
      ->fields([
        'tid' => '6',
        'vid' => 'tags',
        'uuid' => 'd5fd282b-df66-4d50-b0d1-76bf9eede9c5',
        'langcode' => 'en',
      ])
      ->execute();
    $this->writeSettings([
      'settings' => [
        'update_free_access' => (object) [
          'value' => TRUE,
          'required' => TRUE,
        ],
      ],
    ]);
    $this->drupalGet($this->updateUrl);

    $this->assertSession()->pageTextContains('Errors found');
    $this->assertSession()->elementTextContains('css', '.system-status-report__entry--error', 'The make_taxonomy_term_revisionable database update cannot be run until the data has been fixed.');
  }

  /**
   * {@inheritdoc}
   */
  protected function replaceUser1() {
    // Do not replace the user from our dump.
  }

  /**
   * Tests that the taxonomy_term entity form has the status checkbox.
   *
   * @see taxonomy_post_update_configure_status_field_widget()
   */
  public function testStatusCheckbox() {
    $ids = \Drupal::entityQuery('entity_form_display')
      ->condition('targetEntityType', 'taxonomy_term')
      ->execute();

    // Make sure we have the expected values before the update.
    $config_keys = [];
    foreach ($ids as $id) {
      $config_keys[] = 'core.entity_form_display.' . $id;
    }
    /* @var \Drupal\Core\Config\ImmutableConfig[] $form_display_configs */
    $form_display_configs = $this->container->get('config.factory')->loadMultiple($config_keys);
    foreach ($form_display_configs as $config) {
      $status_config = $config->get('content.status');
      if ($config->getName() == 'core.entity_form_display.taxonomy_term.tags.default') {
        $this->assertNotNull($status_config);
        $this->assertEquals(['display_label' => FALSE], $status_config['settings']);
      }
      else {
        $this->assertNull($status_config);
      }
    }

    // Run updates.
    $this->runUpdates();

    /* @var \Drupal\Core\Entity\Display\EntityDisplayInterface[] $form_displays */
    $form_displays = EntityFormDisplay::loadMultiple($ids);
    foreach ($form_displays as $form_display) {
      $component = $form_display->getComponent('status');
      if ($form_display->id() == 'taxonomy_term.tags.default') {
        // Display label should not have been set to TRUE by the upgrade path.
        $this->assertEquals(['display_label' => FALSE], $component['settings']);
      }
      else {
        $this->assertEquals('boolean_checkbox', $component['type']);
        $this->assertEquals(['display_label' => TRUE], $component['settings']);
      }
    }
  }

}
