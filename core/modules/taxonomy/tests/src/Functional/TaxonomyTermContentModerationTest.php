<?php

declare(strict_types=1);

namespace Drupal\Tests\taxonomy\Functional;

use Drupal\Core\Url;
use Drupal\Tests\content_moderation\Traits\ContentModerationTestTrait;
use Drupal\workflows\Entity\Workflow;

/**
 * Tests taxonomy terms with Content Moderation.
 *
 * @group content_moderation
 * @group taxonomy
 */
class TaxonomyTermContentModerationTest extends TaxonomyTestBase {

  use ContentModerationTestTrait;

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * The vocabulary used for creating terms.
   *
   * @var \Drupal\taxonomy\VocabularyInterface
   */
  protected $vocabulary;

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['content_moderation'];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->createEditorialWorkflow();

    $this->drupalLogin($this->drupalCreateUser([
      'administer taxonomy',
      'use editorial transition create_new_draft',
      'use editorial transition publish',
      'view any unpublished content',
      'view latest version',
    ]));

    $this->vocabulary = $this->createVocabulary();

    // Set the vocabulary as moderated.
    $workflow = Workflow::load('editorial');
    $workflow->getTypePlugin()->addEntityTypeAndBundle('taxonomy_term', $this->vocabulary->id());
    $workflow->save();
  }

  /**
   * Tests taxonomy term parents on a moderated vocabulary.
   */
  public function testTaxonomyTermParents(): void {
    $assert_session = $this->assertSession();
    // Create a simple hierarchy in the vocabulary, a root term and three parent
    // terms.
    $root = $this->createTerm($this->vocabulary, ['langcode' => 'en', 'moderation_state' => 'published']);
    $parent_1 = $this->createTerm($this->vocabulary, [
      'langcode' => 'en',
      'moderation_state' => 'published',
      'parent' => $root->id(),
    ]);
    $parent_2 = $this->createTerm($this->vocabulary, [
      'langcode' => 'en',
      'moderation_state' => 'published',
      'parent' => $root->id(),
    ]);
    $parent_3 = $this->createTerm($this->vocabulary, [
      'langcode' => 'en',
      'moderation_state' => 'published',
      'parent' => $root->id(),
    ]);

    // Create a child term and assign one of the parents above.
    $child = $this->createTerm($this->vocabulary, [
      'langcode' => 'en',
      'moderation_state' => 'published',
      'parent' => $parent_1->id(),
    ]);

    /** @var \Drupal\taxonomy\TermStorageInterface $taxonomy_storage */
    $taxonomy_storage = \Drupal::entityTypeManager()->getStorage('taxonomy_term');
    $validation_message = 'You can only change the hierarchy for the published version of this term.';

    // Add a pending revision without changing the term parent.
    $this->drupalGet($child->toUrl('edit-form'));
    $this->submitForm(['moderation_state[0][state]' => 'draft'], 'Save');

    $assert_session->pageTextNotContains($validation_message);

    // Add a pending revision and change the parent.
    $this->drupalGet($child->toUrl('edit-form'));
    $this->submitForm(['parent[]' => [$parent_2->id()], 'moderation_state[0][state]' => 'draft'], 'Save');

    // Check that parents were not changed.
    $assert_session->pageTextContains($validation_message);
    $taxonomy_storage->resetCache();
    $this->assertEquals([$parent_1->id()], array_keys($taxonomy_storage->loadParents($child->id())));

    // Add a pending revision and add a new parent.
    $this->drupalGet($child->toUrl('edit-form'));
    $this->submitForm(['parent[]' => [$parent_1->id(), $parent_3->id()], 'moderation_state[0][state]' => 'draft'], 'Save');

    // Check that parents were not changed.
    $assert_session->pageTextContains($validation_message);
    $taxonomy_storage->resetCache();
    $this->assertEquals([$parent_1->id()], array_keys($taxonomy_storage->loadParents($child->id())));

    // Add a pending revision and use the root term as a parent.
    $this->drupalGet($child->toUrl('edit-form'));
    $this->submitForm(['parent[]' => [$root->id()], 'moderation_state[0][state]' => 'draft'], 'Save');

    // Check that parents were not changed.
    $assert_session->pageTextContains($validation_message);
    $taxonomy_storage->resetCache();
    $this->assertEquals([$parent_1->id()], array_keys($taxonomy_storage->loadParents($child->id())));

    // Add a pending revision and remove the parent.
    $this->drupalGet($child->toUrl('edit-form'));
    $this->submitForm(['parent[]' => [], 'moderation_state[0][state]' => 'draft'], 'Save');

    // Check that parents were not changed.
    $assert_session->pageTextContains($validation_message);
    $taxonomy_storage->resetCache();
    $this->assertEquals([$parent_1->id()], array_keys($taxonomy_storage->loadParents($child->id())));

    // Add a published revision.
    $this->drupalGet($child->toUrl('edit-form'));
    $this->submitForm(['moderation_state[0][state]' => 'published'], 'Save');

    // Change the parents.
    $this->drupalGet($child->toUrl('edit-form'));
    $this->submitForm(['parent[]' => [$parent_2->id()]], 'Save');

    // Check that parents were changed.
    $assert_session->pageTextNotContains($validation_message);
    $taxonomy_storage->resetCache();
    $this->assertNotEquals([$parent_1->id()], array_keys($taxonomy_storage->loadParents($child->id())));

    // Add a pending revision and change the weight.
    $this->drupalGet($child->toUrl('edit-form'));
    $this->submitForm(['weight' => 10, 'moderation_state[0][state]' => 'draft'], 'Save');

    // Check that weight was not changed.
    $assert_session->pageTextContains($validation_message);

    // Add a new term without any parent and publish it.
    $edit = [
      'name[0][value]' => $this->randomMachineName(),
      'moderation_state[0][state]' => 'published',
    ];
    $this->drupalGet(Url::fromRoute('entity.taxonomy_term.add_form', ['taxonomy_vocabulary' => $this->vocabulary->id()]));
    $this->submitForm($edit, 'Save');
    // Add a pending revision without any changes.
    $terms = \Drupal::entityTypeManager()->getStorage('taxonomy_term')->loadByProperties(['name' => $edit['name[0][value]']]);
    $term = reset($terms);
    $this->drupalGet($term->toUrl('edit-form'));
    $this->submitForm(['moderation_state[0][state]' => 'draft'], 'Save');
    $assert_session->pageTextNotContains($validation_message);
  }

  /**
   * Tests changing field values in pending revisions of taxonomy terms.
   */
  public function testTaxonomyTermPendingRevisions(): void {
    $assert_session = $this->assertSession();
    $default_term_name = 'term - default revision';
    $default_term_description = 'The default revision of a term.';
    $term = $this->createTerm($this->vocabulary, [
      'name' => $default_term_name,
      'description' => $default_term_description,
      'langcode' => 'en',
      'moderation_state' => 'published',
    ]);

    // Add a pending revision without changing the term parent.
    $this->drupalGet($term->toUrl('edit-form'));
    $assert_session->pageTextContains($default_term_name);
    $assert_session->pageTextContains($default_term_description);

    // Check the revision log message field appears on the term edit page.
    $this->drupalGet($term->toUrl('edit-form'));
    $assert_session->fieldExists('revision_log_message[0][value]');

    $pending_term_name = 'term - pending revision';
    $pending_term_description = 'The pending revision of a term.';
    $edit = [
      'name[0][value]' => $pending_term_name,
      'description[0][value]' => $pending_term_description,
      'moderation_state[0][state]' => 'draft',
    ];
    $this->drupalGet($term->toUrl('edit-form'));
    $this->submitForm($edit, 'Save');

    $assert_session->pageTextContains($pending_term_name);
    $assert_session->pageTextContains($pending_term_description);
    $assert_session->pageTextNotContains($default_term_description);

    // Check that the default revision of the term contains the correct values.
    $this->drupalGet('taxonomy/term/' . $term->id());
    $assert_session->pageTextContains($default_term_name);
    $assert_session->pageTextContains($default_term_description);
  }

}
