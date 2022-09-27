<?php

namespace Drupal\Tests\taxonomy\Functional;

use Drupal\Component\Utility\Unicode;

/**
 * Tests the taxonomy vocabulary permissions.
 *
 * @group taxonomy
 */
class VocabularyPermissionsTest extends TaxonomyTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = ['help'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->drupalPlaceBlock('page_title_block');
    $this->drupalPlaceBlock('local_actions_block');
    $this->drupalPlaceBlock('help_block');
  }

  /**
   * Create, edit and delete a vocabulary via the user interface.
   */
  public function testVocabularyPermissionsVocabulary() {
    // VocabularyTest.php already tests for user with "administer taxonomy"
    // permission.

    // Test as user without proper permissions.
    $authenticated_user = $this->drupalCreateUser([]);
    $this->drupalLogin($authenticated_user);

    $assert_session = $this->assertSession();

    // Visit the main taxonomy administration page.
    $this->drupalGet('admin/structure/taxonomy');
    $assert_session->statusCodeEquals(403);

    // Test as user with "access taxonomy overview" permissions.
    $proper_user = $this->drupalCreateUser(['access taxonomy overview']);
    $this->drupalLogin($proper_user);

    // Visit the main taxonomy administration page.
    $this->drupalGet('admin/structure/taxonomy');
    $assert_session->statusCodeEquals(200);
    $assert_session->pageTextContains('Vocabulary name');
    $assert_session->linkNotExists('Add vocabulary');
  }

  /**
   * Tests the vocabulary overview permission.
   */
  public function testTaxonomyVocabularyOverviewPermissions() {
    // Create two vocabularies, one with two terms, the other without any term.
    /** @var \Drupal\taxonomy\Entity\Vocabulary $vocabulary1 , $vocabulary2 */
    $vocabulary1 = $this->createVocabulary();
    $vocabulary2 = $this->createVocabulary();
    $vocabulary1_id = $vocabulary1->id();
    $vocabulary2_id = $vocabulary2->id();
    $this->createTerm($vocabulary1);
    $this->createTerm($vocabulary1);

    // Assert expected help texts on first vocabulary.
    $vocabulary1_label = Unicode::ucfirst($vocabulary1->label());
    $edit_help_text = "You can reorganize the terms in $vocabulary1_label using their drag-and-drop handles, and group terms under a parent term by sliding them under and to the right of the parent.";
    $no_edit_help_text = "$vocabulary1_label contains the following terms.";

    $assert_session = $this->assertSession();

    // Logged in as admin user with 'administer taxonomy' permission.
    $admin_user = $this->drupalCreateUser(['administer taxonomy']);
    $this->drupalLogin($admin_user);
    $this->drupalGet('admin/structure/taxonomy/manage/' . $vocabulary1_id . '/overview');
    $assert_session->statusCodeEquals(200);
    $assert_session->linkExists('Edit');
    $assert_session->linkExists('Delete');
    $assert_session->linkExists('Add term');
    $assert_session->buttonExists('Save');
    $assert_session->pageTextContains('Weight');
    $assert_session->fieldExists('Weight');
    $assert_session->pageTextContains($edit_help_text);

    // Visit vocabulary overview without terms. 'Add term' should be shown.
    $this->drupalGet('admin/structure/taxonomy/manage/' . $vocabulary2_id . '/overview');
    $assert_session->statusCodeEquals(200);
    $assert_session->pageTextContains('No terms available');
    $assert_session->linkExists('Add term');

    // Login as a user without any of the required permissions.
    $no_permission_user = $this->drupalCreateUser();
    $this->drupalLogin($no_permission_user);
    $this->drupalGet('admin/structure/taxonomy/manage/' . $vocabulary1_id . '/overview');
    $assert_session->statusCodeEquals(403);
    $this->drupalGet('admin/structure/taxonomy/manage/' . $vocabulary2_id . '/overview');
    $assert_session->statusCodeEquals(403);

    // Log in as a user with only the overview permission, neither edit nor
    // delete operations must be available and no Save button.
    $overview_only_user = $this->drupalCreateUser(['access taxonomy overview']);
    $this->drupalLogin($overview_only_user);
    $this->drupalGet('admin/structure/taxonomy/manage/' . $vocabulary1_id . '/overview');
    $assert_session->statusCodeEquals(200);
    $assert_session->linkNotExists('Edit');
    $assert_session->linkNotExists('Delete');
    $assert_session->buttonNotExists('Save');
    $assert_session->pageTextContains('Weight');
    $assert_session->fieldNotExists('Weight');
    $assert_session->linkNotExists('Add term');
    $assert_session->pageTextContains($no_edit_help_text);

    // Visit vocabulary overview without terms. 'Add term' should not be shown.
    $this->drupalGet('admin/structure/taxonomy/manage/' . $vocabulary2_id . '/overview');
    $assert_session->statusCodeEquals(200);
    $assert_session->pageTextContains('No terms available');
    $assert_session->linkNotExists('Add term');

    // Login as a user with permission to edit terms, only edit link should be
    // visible.
    $edit_user = $this->createUser([
      'access taxonomy overview',
      'edit terms in ' . $vocabulary1_id,
      'edit terms in ' . $vocabulary2_id,
    ]);
    $this->drupalLogin($edit_user);
    $this->drupalGet('admin/structure/taxonomy/manage/' . $vocabulary1_id . '/overview');
    $assert_session->statusCodeEquals(200);
    $assert_session->linkExists('Edit');
    $assert_session->linkNotExists('Delete');
    $assert_session->buttonExists('Save');
    $assert_session->pageTextContains('Weight');
    $assert_session->fieldExists('Weight');
    $assert_session->linkNotExists('Add term');
    $assert_session->pageTextContains($edit_help_text);

    // Visit vocabulary overview without terms. 'Add term' should not be shown.
    $this->drupalGet('admin/structure/taxonomy/manage/' . $vocabulary2_id . '/overview');
    $assert_session->statusCodeEquals(200);
    $assert_session->pageTextContains('No terms available');
    $assert_session->linkNotExists('Add term');

    // Login as a user with permission only to delete terms.
    $edit_delete_user = $this->createUser([
      'access taxonomy overview',
      'delete terms in ' . $vocabulary1_id,
      'delete terms in ' . $vocabulary2_id,
    ]);
    $this->drupalLogin($edit_delete_user);
    $this->drupalGet('admin/structure/taxonomy/manage/' . $vocabulary1_id . '/overview');
    $assert_session->statusCodeEquals(200);
    $assert_session->linkNotExists('Edit');
    $assert_session->linkExists('Delete');
    $assert_session->linkNotExists('Add term');
    $assert_session->buttonNotExists('Save');
    $assert_session->pageTextContains('Weight');
    $assert_session->fieldNotExists('Weight');
    $assert_session->pageTextContains($no_edit_help_text);

    // Visit vocabulary overview without terms. 'Add term' should not be shown.
    $this->drupalGet('admin/structure/taxonomy/manage/' . $vocabulary2_id . '/overview');
    $assert_session->statusCodeEquals(200);
    $assert_session->pageTextContains('No terms available');
    $assert_session->linkNotExists('Add term');

    // Login as a user with permission to edit and delete terms.
    $edit_delete_user = $this->createUser([
      'access taxonomy overview',
      'edit terms in ' . $vocabulary1_id,
      'delete terms in ' . $vocabulary1_id,
      'edit terms in ' . $vocabulary2_id,
      'delete terms in ' . $vocabulary2_id,
    ]);
    $this->drupalLogin($edit_delete_user);
    $this->drupalGet('admin/structure/taxonomy/manage/' . $vocabulary1_id . '/overview');
    $assert_session->statusCodeEquals(200);
    $assert_session->linkExists('Edit');
    $assert_session->linkExists('Delete');
    $assert_session->linkNotExists('Add term');
    $assert_session->buttonExists('Save');
    $assert_session->pageTextContains('Weight');
    $assert_session->fieldExists('Weight');
    $assert_session->pageTextContains($edit_help_text);

    // Visit vocabulary overview without terms. 'Add term' should not be shown.
    $this->drupalGet('admin/structure/taxonomy/manage/' . $vocabulary2_id . '/overview');
    $assert_session->statusCodeEquals(200);
    $assert_session->pageTextContains('No terms available');
    $assert_session->linkNotExists('Add term');

    // Login as a user with permission to create new terms, only add new term
    // link should be visible.
    $edit_user = $this->createUser([
      'access taxonomy overview',
      'create terms in ' . $vocabulary1_id,
      'create terms in ' . $vocabulary2_id,
    ]);
    $this->drupalLogin($edit_user);
    $this->drupalGet('admin/structure/taxonomy/manage/' . $vocabulary1_id . '/overview');
    $assert_session->statusCodeEquals(200);
    $assert_session->linkNotExists('Edit');
    $assert_session->linkNotExists('Delete');
    $assert_session->linkExists('Add term');
    $assert_session->buttonNotExists('Save');
    $assert_session->pageTextContains('Weight');
    $assert_session->fieldNotExists('Weight');
    $assert_session->pageTextContains($no_edit_help_text);

    // Visit vocabulary overview without terms. 'Add term' should not be shown.
    $this->drupalGet('admin/structure/taxonomy/manage/' . $vocabulary2_id . '/overview');
    $assert_session->statusCodeEquals(200);
    $assert_session->pageTextContains('No terms available');
    $assert_session->linkExists('Add term');

    // Ensure the dynamic vocabulary permissions have the correct dependencies.
    $permissions = \Drupal::service('user.permissions')->getPermissions();
    $this->assertTrue(isset($permissions['create terms in ' . $vocabulary1_id]));
    $this->assertEquals(['config' => [$vocabulary1->getConfigDependencyName()]], $permissions['create terms in ' . $vocabulary1_id]['dependencies']);
  }

  /**
   * Create, edit and delete a taxonomy term via the user interface.
   */
  public function testVocabularyPermissionsTaxonomyTerm() {
    // Vocabulary used for creating, removing and editing terms.
    $vocabulary = $this->createVocabulary();

    // Test as admin user.
    $user = $this->drupalCreateUser(['administer taxonomy']);
    $this->drupalLogin($user);

    // Visit the main taxonomy administration page.
    $this->drupalGet('admin/structure/taxonomy/manage/' . $vocabulary->id() . '/add');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->fieldExists('edit-name-0-value');

    // Submit the term.
    $edit = [];
    $edit['name[0][value]'] = $this->randomMachineName();

    $this->submitForm($edit, 'Save');
    $this->assertSession()->pageTextContains('Created new term ' . $edit['name[0][value]'] . '.');

    // Verify that the creation message contains a link to a term.
    $this->assertSession()->elementExists('xpath', '//div[@data-drupal-messages]//a[contains(@href, "term/")]');

    $terms = \Drupal::entityTypeManager()
      ->getStorage('taxonomy_term')
      ->loadByProperties(['name' => $edit['name[0][value]']]);
    $term = reset($terms);

    // Edit the term.
    $this->drupalGet('taxonomy/term/' . $term->id() . '/edit');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->pageTextContains($edit['name[0][value]']);

    $edit['name[0][value]'] = $this->randomMachineName();
    $this->submitForm($edit, 'Save');
    $this->assertSession()->pageTextContains('Updated term ' . $edit['name[0][value]'] . '.');

    // Delete the vocabulary.
    $this->drupalGet('taxonomy/term/' . $term->id() . '/delete');
    $this->assertSession()->pageTextContains("Are you sure you want to delete the taxonomy term {$edit['name[0][value]']}?");

    // Confirm deletion.
    $this->submitForm([], 'Delete');
    $this->assertSession()->pageTextContains("Deleted term {$edit['name[0][value]']}.");

    // Test as user with "create" permissions.
    $user = $this->drupalCreateUser(["create terms in {$vocabulary->id()}"]);
    $this->drupalLogin($user);

    $assert_session = $this->assertSession();

    // Create a new term.
    $this->drupalGet('admin/structure/taxonomy/manage/' . $vocabulary->id() . '/add');
    $assert_session->statusCodeEquals(200);
    $assert_session->fieldExists('name[0][value]');

    // Submit the term.
    $edit = [];
    $edit['name[0][value]'] = $this->randomMachineName();

    $this->submitForm($edit, 'Save');
    $assert_session->pageTextContains("Created new term {$edit['name[0][value]']}.");

    $terms = \Drupal::entityTypeManager()
      ->getStorage('taxonomy_term')
      ->loadByProperties(['name' => $edit['name[0][value]']]);
    $term = reset($terms);

    // Ensure that edit and delete access is denied.
    $this->drupalGet('taxonomy/term/' . $term->id() . '/edit');
    $assert_session->statusCodeEquals(403);
    $this->drupalGet('taxonomy/term/' . $term->id() . '/delete');
    $assert_session->statusCodeEquals(403);

    // Test as user with "edit" permissions.
    $user = $this->drupalCreateUser(["edit terms in {$vocabulary->id()}"]);
    $this->drupalLogin($user);

    // Ensure the taxonomy term add form is denied.
    $this->drupalGet('admin/structure/taxonomy/manage/' . $vocabulary->id() . '/add');
    $this->assertSession()->statusCodeEquals(403);

    // Create a test term.
    $term = $this->createTerm($vocabulary);

    // Edit the term.
    $this->drupalGet('taxonomy/term/' . $term->id() . '/edit');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->pageTextContains($term->getName());

    $edit['name[0][value]'] = $this->randomMachineName();
    $this->submitForm($edit, 'Save');
    $this->assertSession()->pageTextContains('Updated term ' . $edit['name[0][value]'] . '.');

    // Verify that the update message contains a link to a term.
    $this->assertSession()->elementExists('xpath', '//div[@data-drupal-messages]//a[contains(@href, "term/")]');

    // Ensure the term cannot be deleted.
    $this->drupalGet('taxonomy/term/' . $term->id() . '/delete');
    $this->assertSession()->statusCodeEquals(403);

    // Test as user with "delete" permissions.
    $user = $this->drupalCreateUser(["delete terms in {$vocabulary->id()}"]);
    $this->drupalLogin($user);

    // Ensure the taxonomy term add form is denied.
    $this->drupalGet('admin/structure/taxonomy/manage/' . $vocabulary->id() . '/add');
    $this->assertSession()->statusCodeEquals(403);

    // Create a test term.
    $term = $this->createTerm($vocabulary);

    // Ensure that the term cannot be edited.
    $this->drupalGet('taxonomy/term/' . $term->id() . '/edit');
    $this->assertSession()->statusCodeEquals(403);

    // Delete the vocabulary.
    $this->drupalGet('taxonomy/term/' . $term->id() . '/delete');
    $this->assertSession()->pageTextContains("Are you sure you want to delete the taxonomy term {$term->getName()}?");

    // Confirm deletion.
    $this->submitForm([], 'Delete');
    $this->assertSession()->pageTextContains("Deleted term {$term->getName()}.");

    // Test as user without proper permissions.
    $user = $this->drupalCreateUser();
    $this->drupalLogin($user);

    // Ensure the taxonomy term add form is denied.
    $this->drupalGet('admin/structure/taxonomy/manage/' . $vocabulary->id() . '/add');
    $this->assertSession()->statusCodeEquals(403);

    // Create a test term.
    $term = $this->createTerm($vocabulary);

    // Ensure that the term cannot be edited.
    $this->drupalGet('taxonomy/term/' . $term->id() . '/edit');
    $this->assertSession()->statusCodeEquals(403);

    // Ensure the term cannot be deleted.
    $this->drupalGet('taxonomy/term/' . $term->id() . '/delete');
    $this->assertSession()->statusCodeEquals(403);
  }

}
