<?php

namespace Drupal\Tests\system\Functional\Module;

use Drupal\node\Entity\NodeType;
use Drupal\Tests\BrowserTestBase;
use Drupal\Tests\taxonomy\Traits\TaxonomyTestTrait;

/**
 * Tests that modules which provide entity types can be uninstalled.
 *
 * @group Module
 */
class PrepareUninstallTest extends BrowserTestBase {

  use TaxonomyTestTrait;

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * An array of node objects.
   *
   * @var \Drupal\node\NodeInterface[]
   */
  protected $nodes;

  /**
   * An array of taxonomy term objects.
   *
   * @var \Drupal\taxonomy\TermInterface[]
   */
  protected $terms;

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = ['node', 'taxonomy', 'entity_test', 'node_access_test'];

  /**
   * {@inheritdoc}
   */
  public function setUp(): void {
    parent::setUp();

    $admin_user = $this->drupalCreateUser(['administer modules']);
    $this->drupalLogin($admin_user);

    $this->drupalCreateContentType(['type' => 'article', 'name' => 'Article']);
    node_access_rebuild();
    node_access_test_add_field(NodeType::load('article'));
    \Drupal::state()->set('node_access_test.private', TRUE);

    // Create 10 nodes.
    for ($i = 1; $i <= 5; $i++) {
      $this->nodes[] = $this->drupalCreateNode(['type' => 'page']);
      // These 5 articles are inaccessible to the admin user doing the uninstalling.
      $this->nodes[] = $this->drupalCreateNode(['type' => 'article', 'uid' => 0, 'private' => TRUE]);
    }

    // Create 3 top-level taxonomy terms, each with 11 children.
    $vocabulary = $this->createVocabulary();
    for ($i = 1; $i <= 3; $i++) {
      $term = $this->createTerm($vocabulary);
      $this->terms[] = $term;
      for ($j = 1; $j <= 11; $j++) {
        $this->terms[] = $this->createTerm($vocabulary, ['parent' => ['target_id' => $term->id()]]);
      }
    }
  }

  /**
   * Tests that Node and Taxonomy can be uninstalled.
   */
  public function testUninstall() {
    // Check that Taxonomy cannot be uninstalled yet.
    $this->drupalGet('admin/modules/uninstall');
    $this->assertText('Remove content items');
    $this->assertSession()->linkByHrefExists('admin/modules/uninstall/entity/taxonomy_term');

    // Delete Taxonomy term data.
    $this->drupalGet('admin/modules/uninstall/entity/taxonomy_term');
    $term_count = count($this->terms);
    for ($i = 1; $i < 11; $i++) {
      $this->assertText($this->terms[$term_count - $i]->label());
    }
    $term_count = $term_count - 10;
    $this->assertText("And $term_count more taxonomy terms.");
    $this->assertText('This action cannot be undone.');
    $this->assertText('Make a backup of your database if you want to be able to restore these items.');
    $this->submitForm([], 'Delete all taxonomy terms');

    // Check that we are redirected to the uninstall page and data has been
    // removed.
    $this->assertSession()->addressEquals('admin/modules/uninstall');
    $this->assertText('All taxonomy terms have been deleted.');

    // Check that there is no more data to be deleted, Taxonomy is ready to be
    // uninstalled.
    $this->assertText('Enables the categorization of content.');
    $this->assertSession()->linkByHrefNotExists('admin/modules/uninstall/entity/taxonomy_term');

    // Uninstall the Taxonomy module.
    $this->drupalPostForm('admin/modules/uninstall', ['uninstall[taxonomy]' => TRUE], 'Uninstall');
    $this->submitForm([], 'Uninstall');
    $this->assertText('The selected modules have been uninstalled.');
    $this->assertNoText('Enables the categorization of content.');

    // Check Node cannot be uninstalled yet, there is content to be removed.
    $this->drupalGet('admin/modules/uninstall');
    $this->assertText('Remove content items');
    $this->assertSession()->linkByHrefExists('admin/modules/uninstall/entity/node');

    // Delete Node data.
    $this->drupalGet('admin/modules/uninstall/entity/node');
    // Only the 5 pages should be listed as the 5 articles are initially inaccessible.
    foreach ($this->nodes as $node) {
      if ($node->bundle() === 'page') {
        $this->assertText($node->label());
      }
      else {
        $node->set('private', FALSE)->save();
      }
    }
    $this->assertText('And 5 more content items.');

    // All 10 nodes should now be listed as none are still inaccessible.
    $this->drupalGet('admin/modules/uninstall/entity/node');
    foreach ($this->nodes as $node) {
      $this->assertText($node->label());
    }

    // Ensures there is no more count when not necessary.
    $this->assertNoText('And 0 more content');
    $this->assertText('This action cannot be undone.');
    $this->assertText('Make a backup of your database if you want to be able to restore these items.');

    // Create another node so we have 11.
    $this->nodes[] = $this->drupalCreateNode(['type' => 'page']);
    $this->drupalGet('admin/modules/uninstall/entity/node');
    // Ensures singular case is used when a single entity is left after listing
    // the first 10's labels.
    $this->assertText('And 1 more content item.');

    // Create another node so we have 12, with one private.
    $this->nodes[] = $this->drupalCreateNode(['type' => 'article', 'private' => TRUE]);
    $this->drupalGet('admin/modules/uninstall/entity/node');
    $this->assertText('And 2 more content items.');

    $this->submitForm([], 'Delete all content items');

    // Check we are redirected to the uninstall page and data has been removed.
    $this->assertSession()->addressEquals('admin/modules/uninstall');
    $this->assertText('All content items have been deleted.');

    // Check there is no more data to be deleted, Node is ready to be
    // uninstalled.
    $this->assertText('Allows content to be submitted to the site and displayed on pages.');
    $this->assertSession()->linkByHrefNotExists('admin/modules/uninstall/entity/node');

    // Uninstall Node module.
    $this->drupalPostForm('admin/modules/uninstall', ['uninstall[node]' => TRUE], 'Uninstall');
    $this->submitForm([], 'Uninstall');
    $this->assertText('The selected modules have been uninstalled.');
    $this->assertNoText('Allows content to be submitted to the site and displayed on pages.');

    // Ensure a 404 is returned when accessing a non-existent entity type.
    $this->drupalGet('admin/modules/uninstall/entity/node');
    $this->assertSession()->statusCodeEquals(404);

    // Test an entity type which does not have any existing entities.
    $this->drupalGet('admin/modules/uninstall/entity/entity_test_no_label');
    $this->assertText('There are 0 entity test without label entities to delete.');
    $this->assertSession()->buttonNotExists("Delete all entity test without label entities");

    // Test an entity type without a label.
    /** @var \Drupal\Core\Entity\EntityStorageInterface $storage */
    $storage = $this->container->get('entity_type.manager')
      ->getStorage('entity_test_no_label');
    $storage->create([
      'id' => mb_strtolower($this->randomMachineName()),
      'name' => $this->randomMachineName(),
    ])->save();
    $this->drupalGet('admin/modules/uninstall/entity/entity_test_no_label');
    $this->assertText('This will delete 1 entity test without label.');
    $this->assertSession()->buttonExists("Delete all entity test without label entities");
    $storage->create([
      'id' => mb_strtolower($this->randomMachineName()),
      'name' => $this->randomMachineName(),
    ])->save();
    $this->drupalGet('admin/modules/uninstall/entity/entity_test_no_label');
    $this->assertText('This will delete 2 entity test without label entities.');
  }

}
