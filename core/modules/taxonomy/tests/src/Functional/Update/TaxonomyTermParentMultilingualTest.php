<?php

namespace Drupal\Tests\taxonomy\Functional\Update;

use Drupal\FunctionalTests\Update\UpdatePathTestBase;
use Drupal\taxonomy\Entity\Term;
use Drupal\user\Entity\User;

/**
 * Tests the upgrade path for taxonomy parents with multilingual terms.
 *
 * @group taxonomy
 * @group Update
 * @group legacy
 */
class TaxonomyTermParentMultilingualTest extends UpdatePathTestBase {

  /**
   * {@inheritdoc}
   */
  protected function setDatabaseDumpFiles() {
    $this->databaseDumpFiles = [
      __DIR__ . '/../../../../../system/tests/fixtures/update/drupal-8.filled.standard.php.gz',
      __DIR__ . '/../../../../../system/tests/fixtures/update/drupal-8.taxonomy-parent-multilingual-3066439.php',
    ];
  }

  /**
   * Tests taxonomy multilingual term parents update.
   *
   * @see taxonomy_update_8501()
   * @see taxonomy_update_8502()
   * @see taxonomy_update_8503()
   * @see taxonomy_update_8702()
   */
  public function testMultilingualTermParentUpdate() {
    // There are 65 terms in the database. Process them in groups of 30 to test
    // batching.
    $settings['entity_update_batch_size'] = (object) [
      'value' => 30,
      'required' => TRUE,
    ];

    $this->writeSettings($settings);
    $this->runUpdates();

    $term = Term::load(65);
    $this->assertSame('64', $term->parent[0]->target_id);

    // Term 2 should have the root parent.
    $term = Term::load(2);
    $this->assertSame('0', $term->parent[0]->target_id);

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
  }

  /**
   * {@inheritdoc}
   */
  protected function replaceUser1() {
    // Do not replace the user from our dump.
  }

}
