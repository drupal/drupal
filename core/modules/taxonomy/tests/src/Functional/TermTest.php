<?php

namespace Drupal\Tests\taxonomy\Functional;

use Drupal\Component\Utility\Tags;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\field\Entity\FieldConfig;
use Drupal\taxonomy\Entity\Term;
use Drupal\taxonomy\TermInterface;
use Drupal\Tests\system\Functional\Menu\AssertBreadcrumbTrait;

/**
 * Tests load, save and delete for taxonomy terms.
 *
 * @group taxonomy
 */
class TermTest extends TaxonomyTestBase {

  use AssertBreadcrumbTrait;

  /**
   * Vocabulary for testing.
   *
   * @var \Drupal\taxonomy\VocabularyInterface
   */
  protected $vocabulary;

  /**
   * Taxonomy term reference field for testing.
   *
   * @var \Drupal\field\FieldConfigInterface
   */
  protected $field;

  /**
   * Modules to enable.
   *
   * @var string[]
   */
  protected static $modules = ['block'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->drupalPlaceBlock('local_actions_block');
    $this->drupalPlaceBlock('local_tasks_block');
    $this->drupalPlaceBlock('page_title_block');

    $this->drupalLogin($this->drupalCreateUser([
      'administer taxonomy',
      'access taxonomy overview',
      'bypass node access',
    ]));
    $this->vocabulary = $this->createVocabulary();

    $field_name = 'taxonomy_' . $this->vocabulary->id();

    $handler_settings = [
      'target_bundles' => [
        $this->vocabulary->id() => $this->vocabulary->id(),
      ],
      'auto_create' => TRUE,
    ];
    $this->createEntityReferenceField('node', 'article', $field_name, NULL, 'taxonomy_term', 'default', $handler_settings, FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED);
    $this->field = FieldConfig::loadByName('node', 'article', $field_name);

    /** @var \Drupal\Core\Entity\EntityDisplayRepositoryInterface $display_repository */
    $display_repository = \Drupal::service('entity_display.repository');
    $display_repository->getFormDisplay('node', 'article')
      ->setComponent($field_name, [
        'type' => 'options_select',
      ])
      ->save();
    $display_repository->getViewDisplay('node', 'article')
      ->setComponent($field_name, [
        'type' => 'entity_reference_label',
      ])
      ->save();
  }

  /**
   * The "parent" field must restrict references to the same vocabulary.
   */
  public function testParentHandlerSettings() {
    $vocabulary_fields = \Drupal::service('entity_field.manager')->getFieldDefinitions('taxonomy_term', $this->vocabulary->id());
    $parent_target_bundles = $vocabulary_fields['parent']->getSetting('handler_settings')['target_bundles'];
    $this->assertSame([$this->vocabulary->id() => $this->vocabulary->id()], $parent_target_bundles);
  }

  /**
   * Tests terms in a single and multiple hierarchy.
   */
  public function testTaxonomyTermHierarchy() {
    // Create two taxonomy terms.
    $term1 = $this->createTerm($this->vocabulary);
    $term2 = $this->createTerm($this->vocabulary);

    // Get the taxonomy storage.
    /** @var \Drupal\taxonomy\TermStorageInterface $taxonomy_storage */
    $taxonomy_storage = $this->container->get('entity_type.manager')->getStorage('taxonomy_term');

    // Check that hierarchy is flat.
    $this->assertEquals(0, $taxonomy_storage->getVocabularyHierarchyType($this->vocabulary->id()), 'Vocabulary is flat.');

    // Edit $term2, setting $term1 as parent.
    $edit = [];
    $edit['parent[]'] = [$term1->id()];
    $this->drupalGet('taxonomy/term/' . $term2->id() . '/edit');
    $this->submitForm($edit, 'Save');

    // Check the hierarchy.
    $children = $taxonomy_storage->loadChildren($term1->id());
    $parents = $taxonomy_storage->loadParents($term2->id());
    $this->assertTrue(isset($children[$term2->id()]), 'Child found correctly.');
    $this->assertTrue(isset($parents[$term1->id()]), 'Parent found correctly.');

    // Load and save a term, confirming that parents are still set.
    $term = Term::load($term2->id());
    $term->save();
    $parents = $taxonomy_storage->loadParents($term2->id());
    $this->assertTrue(isset($parents[$term1->id()]), 'Parent found correctly.');

    // Create a third term and save this as a parent of term2.
    $term3 = $this->createTerm($this->vocabulary);
    $term2->parent = [$term1->id(), $term3->id()];
    $term2->save();
    $parents = $taxonomy_storage->loadParents($term2->id());
    $this->assertArrayHasKey($term1->id(), $parents);
    $this->assertArrayHasKey($term3->id(), $parents);
  }

  /**
   * Tests that many terms with parents show on each page.
   */
  public function testTaxonomyTermChildTerms() {
    // Set limit to 10 terms per page. Set variable to 9 so 10 terms appear.
    $this->config('taxonomy.settings')->set('terms_per_page_admin', '9')->save();
    $term1 = $this->createTerm($this->vocabulary);
    $terms_array = [];

    $taxonomy_storage = $this->container->get('entity_type.manager')->getStorage('taxonomy_term');

    // Create 40 terms. Terms 1-12 get parent of $term1. All others are
    // individual terms.
    for ($x = 1; $x <= 40; $x++) {
      $edit = [];
      // Set terms in order so we know which terms will be on which pages.
      $edit['weight'] = $x;

      // Set terms 1-20 to be children of first term created.
      if ($x <= 12) {
        $edit['parent'] = $term1->id();
      }
      $term = $this->createTerm($this->vocabulary, $edit);
      $children = $taxonomy_storage->loadChildren($term1->id());
      $parents = $taxonomy_storage->loadParents($term->id());
      $terms_array[$x] = Term::load($term->id());
    }

    // Get Page 1. Parent term and terms 1-13 are displayed.
    $this->drupalGet('admin/structure/taxonomy/manage/' . $this->vocabulary->id() . '/overview');
    $this->assertSession()->pageTextContains($term1->getName());
    for ($x = 1; $x <= 13; $x++) {
      $this->assertSession()->pageTextContains($terms_array[$x]->getName());
    }

    // Get Page 2. Parent term and terms 1-18 are displayed.
    $this->drupalGet('admin/structure/taxonomy/manage/' . $this->vocabulary->id() . '/overview', ['query' => ['page' => 1]]);
    $this->assertSession()->pageTextContains($term1->getName());
    for ($x = 1; $x <= 18; $x++) {
      $this->assertSession()->pageTextContains($terms_array[$x]->getName());
    }

    // Get Page 3. No parent term and no terms <18 are displayed. Terms 18-25
    // are displayed.
    $this->drupalGet('admin/structure/taxonomy/manage/' . $this->vocabulary->id() . '/overview', ['query' => ['page' => 2]]);
    $this->assertSession()->pageTextNotContains($term1->getName());
    for ($x = 1; $x <= 17; $x++) {
      $this->assertSession()->pageTextNotContains($terms_array[$x]->getName());
    }
    for ($x = 18; $x <= 25; $x++) {
      $this->assertSession()->pageTextContains($terms_array[$x]->getName());
    }
  }

  /**
   * Tests that hook_node_$op implementations work correctly.
   *
   * Save & edit a node and assert that taxonomy terms are saved/loaded properly.
   */
  public function testTaxonomyNode() {
    // Create two taxonomy terms.
    $term1 = $this->createTerm($this->vocabulary);
    $term2 = $this->createTerm($this->vocabulary);

    // Post an article.
    $edit = [];
    $edit['title[0][value]'] = $this->randomMachineName();
    $edit['body[0][value]'] = $this->randomMachineName();
    $edit[$this->field->getName() . '[]'] = $term1->id();
    $this->drupalGet('node/add/article');
    $this->submitForm($edit, 'Save');

    // Check that the term is displayed when the node is viewed.
    $node = $this->drupalGetNodeByTitle($edit['title[0][value]']);
    $this->drupalGet('node/' . $node->id());
    $this->assertSession()->pageTextContains($term1->getName());

    $this->clickLink('Edit');
    $this->assertSession()->pageTextContains($term1->getName());
    $this->submitForm([], 'Save');
    $this->assertSession()->pageTextContains($term1->getName());

    // Edit the node with a different term.
    $edit[$this->field->getName() . '[]'] = $term2->id();
    $this->drupalGet('node/' . $node->id() . '/edit');
    $this->submitForm($edit, 'Save');

    $this->drupalGet('node/' . $node->id());
    $this->assertSession()->pageTextContains($term2->getName());

    // Preview the node.
    $this->drupalGet('node/' . $node->id() . '/edit');
    $this->submitForm($edit, 'Preview');
    // Ensure that term is displayed when previewing the node.
    $this->assertSession()->pageTextContainsOnce($term2->getName());
    $this->drupalGet('node/' . $node->id() . '/edit');
    $this->submitForm([], 'Preview');
    // Ensure that term is displayed when previewing the node again.
    $this->assertSession()->pageTextContainsOnce($term2->getName());
  }

  /**
   * Tests term creation with a free-tagging vocabulary from the node form.
   */
  public function testNodeTermCreationAndDeletion() {
    // Enable tags in the vocabulary.
    $field = $this->field;
    \Drupal::service('entity_display.repository')
      ->getFormDisplay($field->getTargetEntityTypeId(), $field->getTargetBundle())
      ->setComponent($field->getName(), [
        'type' => 'entity_reference_autocomplete_tags',
        'settings' => [
          'placeholder' => 'Start typing here.',
        ],
      ])
      ->save();
    // Prefix the terms with a letter to ensure there is no clash in the first
    // three letters.
    // @see https://www.drupal.org/node/2397691
    $terms = [
      'term1' => 'a' . $this->randomMachineName(),
      'term2' => 'b' . $this->randomMachineName(),
      'term3' => 'c' . $this->randomMachineName() . ', ' . $this->randomMachineName(),
      'term4' => 'd' . $this->randomMachineName(),
    ];

    $edit = [];
    $edit['title[0][value]'] = $this->randomMachineName();
    $edit['body[0][value]'] = $this->randomMachineName();
    // Insert the terms in a comma separated list. Vocabulary 1 is a
    // free-tagging field created by the default profile.
    $edit[$field->getName() . '[target_id]'] = Tags::implode($terms);

    // Verify the placeholder is there.
    $this->drupalGet('node/add/article');
    $this->assertSession()->responseContains('placeholder="Start typing here."');

    // Preview and verify the terms appear but are not created.
    $this->submitForm($edit, 'Preview');
    foreach ($terms as $term) {
      $this->assertSession()->pageTextContains($term);
    }
    $tree = $this->container->get('entity_type.manager')->getStorage('taxonomy_term')->loadTree($this->vocabulary->id());
    $this->assertEmpty($tree, 'The terms are not created on preview.');

    // Save, creating the terms.
    $this->drupalGet('node/add/article');
    $this->submitForm($edit, 'Save');
    $this->assertSession()->pageTextContains('Article ' . $edit['title[0][value]'] . ' has been created.');

    // Verify that the creation message contains a link to a node.
    $this->assertSession()->elementExists('xpath', '//div[@data-drupal-messages]//a[contains(@href, "node/")]');

    foreach ($terms as $term) {
      $this->assertSession()->pageTextContains($term);
    }

    // Get the created terms.
    $term_objects = [];
    foreach ($terms as $key => $term) {
      $term_objects[$key] = \Drupal::entityTypeManager()->getStorage('taxonomy_term')->loadByProperties([
        'name' => $term,
      ]);
      $term_objects[$key] = reset($term_objects[$key]);
    }

    // Get the node.
    $node = $this->drupalGetNodeByTitle($edit['title[0][value]']);

    // Test editing the node.
    $this->drupalGet('node/' . $node->id() . '/edit');
    $this->submitForm($edit, 'Save');
    foreach ($terms as $term) {
      $this->assertSession()->pageTextContains($term);
    }

    // Delete term 1 from the term edit page.
    $this->drupalGet('taxonomy/term/' . $term_objects['term1']->id() . '/edit');
    $this->clickLink('Delete');
    $this->submitForm([], 'Delete');

    // Delete term 2 from the term delete page.
    $this->drupalGet('taxonomy/term/' . $term_objects['term2']->id() . '/delete');
    $this->submitForm([], 'Delete');

    // Verify that the terms appear on the node page after the two terms were
    // deleted.
    $term_names = [$term_objects['term3']->getName(), $term_objects['term4']->getName()];
    $this->drupalGet('node/' . $node->id());
    foreach ($term_names as $term_name) {
      $this->assertSession()->pageTextContains($term_name);
    }
    $this->assertSession()->pageTextNotContains($term_objects['term1']->getName());
    $this->assertSession()->pageTextNotContains($term_objects['term2']->getName());
  }

  /**
   * Save, edit and delete a term using the user interface.
   */
  public function testTermInterface() {
    \Drupal::service('module_installer')->install(['views']);
    $edit = [
      'name[0][value]' => $this->randomMachineName(12),
      'description[0][value]' => $this->randomMachineName(100),
    ];
    // Explicitly set the parents field to 'root', to ensure that
    // TermForm::save() handles the invalid term ID correctly.
    $edit['parent[]'] = [0];

    // Create the term to edit.
    $this->drupalGet('admin/structure/taxonomy/manage/' . $this->vocabulary->id() . '/add');
    $this->submitForm($edit, 'Save');

    // Ensure form redirected back to term add page.
    $this->assertSession()->addressEquals('admin/structure/taxonomy/manage/' . $this->vocabulary->id() . '/add');

    $terms = \Drupal::entityTypeManager()->getStorage('taxonomy_term')->loadByProperties([
      'name' => $edit['name[0][value]'],
    ]);
    $term = reset($terms);
    $this->assertNotNull($term, 'Term found in database.');

    // Submitting a term takes us to the add page; we need the List page.
    $this->drupalGet('admin/structure/taxonomy/manage/' . $this->vocabulary->id() . '/overview');

    $this->clickLink('Edit', 1);

    // Verify that the randomly generated term is present.
    $this->assertSession()->pageTextContains($edit['name[0][value]']);
    $this->assertSession()->pageTextContains($edit['description[0][value]']);

    $edit = [
      'name[0][value]' => $this->randomMachineName(14),
      'description[0][value]' => $this->randomMachineName(102),
    ];

    // Edit the term.
    $this->drupalGet('taxonomy/term/' . $term->id() . '/edit');
    $this->submitForm($edit, 'Save');

    // Ensure form redirected back to term view.
    $this->assertSession()->addressEquals('taxonomy/term/' . $term->id());

    // Check that the term is still present at admin UI after edit.
    $this->drupalGet('admin/structure/taxonomy/manage/' . $this->vocabulary->id() . '/overview');
    $this->assertSession()->pageTextContains($edit['name[0][value]']);
    $this->assertSession()->linkExists('Edit');

    // Check the term link can be clicked through to the term page.
    $this->clickLink($edit['name[0][value]']);
    $this->assertSession()->statusCodeEquals(200);

    // View the term and check that it is correct.
    $this->drupalGet('taxonomy/term/' . $term->id());
    $this->assertSession()->pageTextContains($edit['name[0][value]']);
    $this->assertSession()->pageTextContains($edit['description[0][value]']);

    // Did this page request display a 'term-listing-heading'?
    $this->assertSession()->elementExists('xpath', '//div[@class="views-element-container"]/div/header/div/div/p');
    // Check that it does NOT show a description when description is blank.
    $term->setDescription(NULL);
    $term->save();
    $this->drupalGet('taxonomy/term/' . $term->id());
    $this->assertSession()->elementNotExists('xpath', '//div[@class="views-element-container"]/div/header/div/div/p');

    // Check that the description value is processed.
    $value = $this->randomMachineName();
    $term->setDescription($value);
    $term->save();
    $this->assertSame("<p>{$value}</p>\n", (string) $term->description->processed);

    // Check that the term feed page is working.
    $this->drupalGet('taxonomy/term/' . $term->id() . '/feed');

    // Delete the term.
    $this->drupalGet('taxonomy/term/' . $term->id() . '/edit');
    $this->clickLink('Delete');
    $this->submitForm([], 'Delete');

    // Assert that the term no longer exists.
    $this->drupalGet('taxonomy/term/' . $term->id());
    $this->assertSession()->statusCodeEquals(404);

    // Test "save and go to list" action while creating term.
    // Create the term to edit.
    $this->drupalGet('admin/structure/taxonomy/manage/' . $this->vocabulary->id() . '/add');
    $edit = [
      'name[0][value]' => $this->randomMachineName(12),
      'description[0][value]' => $this->randomMachineName(100),
    ];

    // Create the term to edit.
    $this->submitForm($edit, 'Save and go to list');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->addressEquals('admin/structure/taxonomy/manage/' . $this->vocabulary->id() . '/overview');
    $this->assertSession()->pageTextContains($edit['name[0][value]']);

    // Validate that "Save and go to list" doesn't exist when destination
    // parameter is present.
    $this->drupalGet('admin/structure/taxonomy/manage/' . $this->vocabulary->id() . '/add', ['query' => ['destination' => 'node/add']]);
    $this->assertSession()->pageTextNotContains('Save and go to list');

    // Validate that "Save and go to list" doesn't exist when missing permission
    // 'access taxonomy overview'.
    $this->drupalLogin($this->drupalCreateUser([
      'administer taxonomy',
      'bypass node access',
    ]));
    $this->drupalGet('admin/structure/taxonomy/manage/' . $this->vocabulary->id() . '/add');
    $this->assertSession()->pageTextNotContains('Save and go to list');
  }

  /**
   * Test UI with override_selector TRUE.
   */
  public function testTermSaveOverrideSelector() {
    $this->config('taxonomy.settings')->set('override_selector', TRUE)->save();

    // Create a Term.
    $edit = [
      'name[0][value]' => $this->randomMachineName(12),
      'description[0][value]' => $this->randomMachineName(100),
    ];
    // Create the term to edit.
    $this->drupalGet('admin/structure/taxonomy/manage/' . $this->vocabulary->id() . '/add');
    $this->submitForm($edit, 'Save');
    $terms = \Drupal::entityTypeManager()->getStorage('taxonomy_term')->loadByProperties([
      'name' => $edit['name[0][value]'],
    ]);
    $term = reset($terms);
    $this->assertNotNull($term, 'Term found in database.');

    // The term appears on the vocab list page.
    $this->drupalGet('admin/structure/taxonomy/manage/' . $this->vocabulary->id() . '/overview');
    $this->assertSession()->pageTextContains($term->getName());
  }

  /**
   * Save, edit and delete a term using the user interface.
   */
  public function testTermReorder() {
    $assert = $this->assertSession();
    $this->createTerm($this->vocabulary);
    $this->createTerm($this->vocabulary);
    $this->createTerm($this->vocabulary);

    $taxonomy_storage = $this->container->get('entity_type.manager')->getStorage('taxonomy_term');

    // Fetch the created terms in the default alphabetical order, i.e. term1
    // precedes term2 alphabetically, and term2 precedes term3.
    $taxonomy_storage->resetCache();
    [$term1, $term2, $term3] = $taxonomy_storage->loadTree($this->vocabulary->id(), 0, NULL, TRUE);

    $this->drupalGet('admin/structure/taxonomy/manage/' . $this->vocabulary->id() . '/overview');

    // Each term has four hidden fields, "tid:1:0[tid]", "tid:1:0[parent]",
    // "tid:1:0[depth]", and "tid:1:0[weight]". Change the order to term2,
    // term3, term1 by setting weight property, make term3 a child of term2 by
    // setting the parent and depth properties, and update all hidden fields.
    $hidden_edit = [
      'terms[tid:' . $term2->id() . ':0][term][tid]' => $term2->id(),
      'terms[tid:' . $term2->id() . ':0][term][parent]' => 0,
      'terms[tid:' . $term2->id() . ':0][term][depth]' => 0,
      'terms[tid:' . $term3->id() . ':0][term][tid]' => $term3->id(),
      'terms[tid:' . $term3->id() . ':0][term][parent]' => $term2->id(),
      'terms[tid:' . $term3->id() . ':0][term][depth]' => 1,
      'terms[tid:' . $term1->id() . ':0][term][tid]' => $term1->id(),
      'terms[tid:' . $term1->id() . ':0][term][parent]' => 0,
      'terms[tid:' . $term1->id() . ':0][term][depth]' => 0,
    ];
    // Because we can't post hidden form elements, we have to change them in
    // code here, and then submit.
    foreach ($hidden_edit as $field => $value) {
      $node = $assert->hiddenFieldExists($field);
      $node->setValue($value);
    }
    // Edit non-hidden elements within submitForm().
    $edit = [
      'terms[tid:' . $term2->id() . ':0][weight]' => 0,
      'terms[tid:' . $term3->id() . ':0][weight]' => 1,
      'terms[tid:' . $term1->id() . ':0][weight]' => 2,
    ];
    $this->submitForm($edit, 'Save');

    $taxonomy_storage->resetCache();
    $terms = $taxonomy_storage->loadTree($this->vocabulary->id());
    $this->assertEquals($term2->id(), $terms[0]->tid, 'Term 2 was moved above term 1.');
    $this->assertEquals([$term2->id()], $terms[1]->parents, 'Term 3 was made a child of term 2.');
    $this->assertEquals($term1->id(), $terms[2]->tid, 'Term 1 was moved below term 2.');

    $this->drupalGet('admin/structure/taxonomy/manage/' . $this->vocabulary->id() . '/overview');
    $this->submitForm([], 'Reset to alphabetical');
    // Submit confirmation form.
    $this->submitForm([], 'Reset to alphabetical');
    // Ensure form redirected back to overview.
    $this->assertSession()->addressEquals('admin/structure/taxonomy/manage/' . $this->vocabulary->id() . '/overview');

    $taxonomy_storage->resetCache();
    $terms = $taxonomy_storage->loadTree($this->vocabulary->id(), 0, NULL, TRUE);
    $this->assertEquals($term1->id(), $terms[0]->id(), 'Term 1 was moved to back above term 2.');
    $this->assertEquals($term2->id(), $terms[1]->id(), 'Term 2 was moved to back below term 1.');
    $this->assertEquals($term3->id(), $terms[2]->id(), 'Term 3 is still below term 2.');
    $this->assertEquals([$term2->id()], $terms[2]->parents, 'Term 3 is still a child of term 2.');
  }

  /**
   * Tests saving a term with multiple parents through the UI.
   */
  public function testTermMultipleParentsInterface() {
    // Add two new terms to the vocabulary so that we can have multiple parents.
    // These will be terms with tids of 1 and 2 respectively.
    $this->createTerm($this->vocabulary);
    $this->createTerm($this->vocabulary);

    // Add a new term with multiple parents.
    $edit = [
      'name[0][value]' => $this->randomMachineName(12),
      'description[0][value]' => $this->randomMachineName(100),
      'parent[]' => [0, 1],
    ];
    // Save the new term.
    $this->drupalGet('admin/structure/taxonomy/manage/' . $this->vocabulary->id() . '/add');
    $this->submitForm($edit, 'Save');

    // Check that the term was successfully created.
    $term = $this->reloadTermByName($edit['name[0][value]']);
    $this->assertNotNull($term, 'Term found in database.');
    $this->assertEquals($edit['name[0][value]'], $term->getName(), 'Term name was successfully saved.');
    $this->assertEquals($edit['description[0][value]'], $term->getDescription(), 'Term description was successfully saved.');

    // Check that we have the expected parents.
    $this->assertEquals([0, 1], $this->getParentTids($term), 'Term parents (root plus one) were successfully saved.');

    // Load the edit form and save again to ensure parent are preserved.
    // Generate a new name, so we know that the term really is saved.
    $this->drupalGet('taxonomy/term/' . $term->id() . '/edit');
    $edit = [
      'name[0][value]' => $this->randomMachineName(12),
    ];
    $this->submitForm($edit, 'Save');
    $this->assertSession()->pageTextContains('Updated term ' . $edit['name[0][value]']);
    $this->drupalGet('taxonomy/term/' . $term->id() . '/edit');
    $this->submitForm([], 'Save');
    $this->assertSession()->pageTextContains('Updated term ' . $edit['name[0][value]']);

    // Check that we still have the expected parents.
    $term = $this->reloadTermByName($edit['name[0][value]']);
    $this->assertEquals([0, 1], $this->getParentTids($term), 'Term parents (root plus one) were successfully saved again.');

    // Save with two real parents. i.e., not including <root>.
    $this->drupalGet('taxonomy/term/' . $term->id() . '/edit');
    $edit = [
      'name[0][value]' => $this->randomMachineName(12),
      'parent[]' => [1, 2],
    ];
    $this->submitForm($edit, 'Save');
    $this->assertSession()->pageTextContains('Updated term ' . $edit['name[0][value]']);
    $this->drupalGet('taxonomy/term/' . $term->id() . '/edit');
    $this->submitForm([], 'Save');
    $this->assertSession()->pageTextContains('Updated term ' . $edit['name[0][value]']);

    // Check that we have the expected parents.
    $term = $this->reloadTermByName($edit['name[0][value]']);
    $this->assertEquals([1, 2], $this->getParentTids($term), 'Term parents (two real) were successfully saved.');
  }

  /**
   * Reloads a term by name.
   *
   * @param string $name
   *   The name of the term.
   *
   * @return \Drupal\taxonomy\TermInterface
   *   The reloaded term.
   */
  private function reloadTermByName(string $name): TermInterface {
    \Drupal::entityTypeManager()->getStorage('taxonomy_term')->resetCache();
    /** @var \Drupal\taxonomy\TermInterface[] $terms */
    $terms = \Drupal::entityTypeManager()->getStorage('taxonomy_term')->loadByProperties(['name' => $name]);
    return reset($terms);
  }

  /**
   * Get the parent tids for a term including root.
   *
   * @param \Drupal\taxonomy\TermInterface $term
   *   The term.
   *
   * @return array
   *   A sorted array of tids and 0 if the root is a parent.
   */
  private function getParentTids($term) {
    $parent_tids = [];
    foreach ($term->get('parent') as $item) {
      $parent_tids[] = (int) $item->target_id;
    }
    sort($parent_tids);

    return $parent_tids;
  }

  /**
   * Tests that editing and saving a node with no changes works correctly.
   */
  public function testReSavingTags() {
    // Enable tags in the vocabulary.
    $field = $this->field;
    \Drupal::service('entity_display.repository')
      ->getFormDisplay($field->getTargetEntityTypeId(), $field->getTargetBundle())
      ->setComponent($field->getName(), [
        'type' => 'entity_reference_autocomplete_tags',
      ])
      ->save();

    // Create a term and a node using it.
    $term = $this->createTerm($this->vocabulary);
    $edit = [];
    $edit['title[0][value]'] = $this->randomMachineName(8);
    $edit['body[0][value]'] = $this->randomMachineName(16);
    $edit[$this->field->getName() . '[target_id]'] = $term->getName();
    $this->drupalGet('node/add/article');
    $this->submitForm($edit, 'Save');

    // Check that the term is displayed when editing and saving the node with no
    // changes.
    $this->clickLink('Edit');
    $this->assertSession()->responseContains($term->getName());
    $this->submitForm([], 'Save');
    $this->assertSession()->responseContains($term->getName());
  }

  /**
   * Check the breadcrumb on edit and delete a term page.
   */
  public function testTermBreadcrumbs() {
    $edit = [
      'name[0][value]' => $this->randomMachineName(14),
      'description[0][value]' => $this->randomMachineName(100),
      'parent[]' => [0],
    ];

    // Create the term.
    $this->drupalGet('admin/structure/taxonomy/manage/' . $this->vocabulary->id() . '/add');
    $this->submitForm($edit, 'Save');

    $terms = \Drupal::entityTypeManager()->getStorage('taxonomy_term')->loadByProperties([
      'name' => $edit['name[0][value]'],
    ]);
    $term = reset($terms);
    $this->assertNotNull($term, 'Term found in database.');

    // Check the breadcrumb on the term edit page.
    $trail = [
      '' => 'Home',
      'taxonomy/term/' . $term->id() => $term->label(),
    ];
    $this->assertBreadcrumb('taxonomy/term/' . $term->id() . '/edit', $trail);
    $this->assertSession()->assertEscaped($term->label());

    // Check the breadcrumb on the term delete page.
    $trail = [
      '' => 'Home',
      'taxonomy/term/' . $term->id() => $term->label(),
    ];
    $this->assertBreadcrumb('taxonomy/term/' . $term->id() . '/delete', $trail);
    $this->assertSession()->assertEscaped($term->label());
  }

}
