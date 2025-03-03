<?php

declare(strict_types=1);

namespace Drupal\Tests\taxonomy\Functional;

use Drupal\taxonomy\Entity\Vocabulary;
use Drupal\taxonomy\TermInterface;
use Drupal\Tests\BrowserTestBase;

/**
 * Tests managing taxonomy parents through the user interface.
 *
 * @group taxonomy
 */
class TermParentsTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['taxonomy_test'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * The term storage.
   *
   * @var \Drupal\taxonomy\TermStorageInterface
   */
  protected $termStorage;

  /**
   * The state service.
   *
   * @var \Drupal\Core\State\StateInterface
   */
  protected $state;

  /**
   * The ID of the vocabulary used in this test.
   *
   * @var string
   */
  protected $vocabularyId = 'test_vocabulary';

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    /** @var \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager */
    $entity_type_manager = $this->container->get('entity_type.manager');
    $this->termStorage = $entity_type_manager->getStorage('taxonomy_term');
    $this->state = $this->container->get('state');

    Vocabulary::create(['vid' => $this->vocabularyId, 'name' => 'Test'])->save();
    $this->drupalLogin($this->drupalCreateUser(['administer taxonomy']));
  }

  /**
   * Tests specifying parents when creating terms.
   */
  public function testAddWithParents(): void {
    $this->drupalGet("/admin/structure/taxonomy/manage/{$this->vocabularyId}/add");
    $page = $this->getSession()->getPage();

    // Create a term without any parents.
    $term_1 = $this->submitAddTermForm('Test term 1');
    $expected = [['target_id' => 0]];
    $this->assertEquals($expected, $term_1->get('parent')->getValue());

    // Explicitly selecting <root> should have the same effect as not selecting
    // anything.
    $page->selectFieldOption('Parent terms', '<root>');
    $term_2 = $this->submitAddTermForm('Test term 2');
    $this->assertEquals($expected, $term_2->get('parent')->getValue());

    // Create two terms with the previously created ones as parents,
    // respectively.
    $page->selectFieldOption('Parent terms', 'Test term 1');
    $term_3 = $this->submitAddTermForm('Test term 3');
    $expected = [['target_id' => $term_1->id()]];
    $this->assertEquals($expected, $term_3->get('parent')->getValue());
    $page->selectFieldOption('Parent terms', 'Test term 2');
    $term_4 = $this->submitAddTermForm('Test term 4');
    $expected = [['target_id' => $term_2->id()]];
    $this->assertEquals($expected, $term_4->get('parent')->getValue());

    // Create a term with term 3 as parent.
    $page->selectFieldOption('Parent terms', '-Test term 3');
    $term_5 = $this->submitAddTermForm('Test term 5');
    $expected = [['target_id' => $term_3->id()]];
    $this->assertEquals($expected, $term_5->get('parent')->getValue());

    // Create a term with multiple parents.
    $page->selectFieldOption('Parent terms', '--Test term 5');
    $page->selectFieldOption('Parent terms', '-Test term 4', TRUE);
    $term_6 = $this->submitAddTermForm('Test term 6');
    $expected = [
      ['target_id' => $term_5->id()],
      ['target_id' => $term_4->id()],
    ];
    $this->assertEquals($expected, $term_6->get('parent')->getValue());
  }

  /**
   * Creates a term through the user interface and returns it.
   *
   * @param string $name
   *   The name of the term to create.
   *
   * @return \Drupal\taxonomy\TermInterface
   *   The newly created taxonomy term.
   */
  protected function submitAddTermForm($name) {
    $this->getSession()->getPage()->fillField('Name', $name);

    $this->submitForm([], 'Save');

    $result = $this->termStorage
      ->getQuery()
      ->accessCheck(FALSE)
      ->condition('name', $name)
      ->execute();
    /** @var \Drupal\taxonomy\TermInterface $term_1 */
    $term_1 = $this->termStorage->load(reset($result));
    $this->assertInstanceOf(TermInterface::class, $term_1);
    return $term_1;
  }

  /**
   * Tests editing the parents of existing terms.
   */
  public function testEditingParents(): void {
    $terms = $this->doTestEditingSingleParent();
    $term_5 = array_pop($terms);
    $term_4 = array_pop($terms);

    // Create a term with multiple parents.
    $term_6 = $this->createTerm('Test term 6', [
      // Term 5 comes before term 4 in the user interface, so add the parents in
      // the matching order.
      $term_5->id(),
      $term_4->id(),
    ]);
    $this->drupalGet($term_6->toUrl('edit-form'));
    $this->assertParentOption('<root>');
    $this->assertParentOption('Test term 1');
    $this->assertParentOption('-Test term 3');
    $this->assertParentOption('--Test term 5', TRUE);
    $this->assertParentOption('Test term 2');
    $this->assertParentOption('-Test term 4', TRUE);
    $this->submitForm([], 'Save');
    $this->assertParentsUnchanged($term_6);
  }

  /**
   * Tests specifying parents when creating terms and a disabled parent form.
   */
  public function testEditingParentsWithDisabledFormElement(): void {
    // Disable the parent form element.
    $this->state->set('taxonomy_test.disable_parent_form_element', TRUE);
    $this->drupalGet("/admin/structure/taxonomy/manage/{$this->vocabularyId}/add");
    $this->assertSession()->fieldDisabled('Parent terms');

    $terms = $this->doTestEditingSingleParent();
    $term_5 = array_pop($terms);
    $term_4 = array_pop($terms);

    // Create a term with multiple parents.
    $term_6 = $this->createTerm('Test term 6', [
      // When the parent form element is disabled, its default value is used as
      // the value which gets populated in ascending order of term IDs.
      $term_4->id(),
      $term_5->id(),
    ]);
    $this->drupalGet($term_6->toUrl('edit-form'));
    $this->assertParentOption('<root>');
    $this->assertParentOption('Test term 1');
    $this->assertParentOption('-Test term 3');
    $this->assertParentOption('--Test term 5', TRUE);
    $this->assertParentOption('Test term 2');
    $this->assertParentOption('-Test term 4', TRUE);
    $this->submitForm([], 'Save');
    $this->assertParentsUnchanged($term_6);
  }

  /**
   * Performs tests that edit terms with a single parent.
   *
   * @return \Drupal\taxonomy\TermInterface[]
   *   A list of terms created for testing.
   */
  protected function doTestEditingSingleParent(): array {
    $terms = [];

    // Create two terms without any parents.
    $term_1 = $this->createTerm('Test term 1');
    $this->drupalGet($term_1->toUrl('edit-form'));
    $this->assertParentOption('<root>', TRUE);
    $this->submitForm([], 'Save');
    $this->assertParentsUnchanged($term_1);
    $terms[] = $term_1;

    $term_2 = $this->createTerm('Test term 2');
    $this->drupalGet($term_2->toUrl('edit-form'));
    $this->assertParentOption('<root>', TRUE);
    $this->assertParentOption('Test term 1');
    $this->submitForm([], 'Save');
    $this->assertParentsUnchanged($term_2);
    $terms[] = $term_2;

    // Create two terms with the previously created terms as parents,
    // respectively.
    $term_3 = $this->createTerm('Test term 3', [$term_1->id()]);
    $this->drupalGet($term_3->toUrl('edit-form'));
    $this->assertParentOption('<root>');
    $this->assertParentOption('Test term 1', TRUE);
    $this->assertParentOption('Test term 2');
    $this->submitForm([], 'Save');
    $this->assertParentsUnchanged($term_3);
    $terms[] = $term_3;

    $term_4 = $this->createTerm('Test term 4', [$term_2->id()]);
    $this->drupalGet($term_4->toUrl('edit-form'));
    $this->assertParentOption('<root>');
    $this->assertParentOption('Test term 1');
    $this->assertParentOption('-Test term 3');
    $this->assertParentOption('Test term 2', TRUE);
    $this->submitForm([], 'Save');
    $this->assertParentsUnchanged($term_4);
    $terms[] = $term_4;

    // Create a term with term 3 as parent.
    $term_5 = $this->createTerm('Test term 5', [$term_3->id()]);
    $this->drupalGet($term_5->toUrl('edit-form'));
    $this->assertParentOption('<root>');
    $this->assertParentOption('Test term 1');
    $this->assertParentOption('-Test term 3', TRUE);
    $this->assertParentOption('Test term 2');
    $this->assertParentOption('-Test term 4');
    $this->submitForm([], 'Save');
    $this->assertParentsUnchanged($term_5);
    $terms[] = $term_5;

    return $terms;
  }

  /**
   * Test the term add/edit form with parent query parameter.
   */
  public function testParentFromQuery(): void {
    // Create three terms without any parents.
    $term_1 = $this->createTerm('Test term 1');
    $term_2 = $this->createTerm('Test term 2');
    $term_3 = $this->createTerm('Test term 3');

    // Add term form with one parent.
    $this->drupalGet("/admin/structure/taxonomy/manage/{$this->vocabularyId}/add", ['query' => ['parent' => $term_1->id()]]);
    $this->assertParentOption('Test term 1', TRUE);
    $this->assertParentOption('Test term 2', FALSE);
    $this->assertParentOption('Test term 3', FALSE);
    // Add term form with two parents.
    $this->drupalGet("/admin/structure/taxonomy/manage/{$this->vocabularyId}/add", ['query' => ['parent[0]' => $term_1->id(), 'parent[1]' => $term_2->id()]]);
    $this->assertParentOption('Test term 1', TRUE);
    $this->assertParentOption('Test term 2', TRUE);
    $this->assertParentOption('Test term 3', FALSE);
    // Add term form with no parents.
    $this->drupalGet("/admin/structure/taxonomy/manage/{$this->vocabularyId}/add", ['query' => ['parent' => '']]);
    $this->assertParentOption('Test term 1', FALSE);
    $this->assertParentOption('Test term 2', FALSE);
    $this->assertParentOption('Test term 3', FALSE);
    // Add term form with invalid parent.
    $this->drupalGet("/admin/structure/taxonomy/manage/{$this->vocabularyId}/add", ['query' => ['parent' => -1]]);
    $this->assertParentOption('Test term 1', FALSE);
    $this->assertParentOption('Test term 2', FALSE);
    $this->assertParentOption('Test term 3', FALSE);

    // Edit term form with one parent.
    $this->drupalGet($term_1->toUrl('edit-form'), ['query' => ['parent' => $term_2->id()]]);
    $this->assertParentOption('Test term 2', TRUE);
    $this->assertParentOption('Test term 3', FALSE);
    // Edit term form with two parents.
    $this->drupalGet($term_1->toUrl('edit-form'), ['query' => ['parent[0]' => $term_2->id(), 'parent[1]' => $term_3->id()]]);
    $this->assertParentOption('Test term 2', TRUE);
    $this->assertParentOption('Test term 3', TRUE);
    // Edit term form with no parents.
    $this->drupalGet($term_1->toUrl('edit-form'), ['query' => ['parent' => '']]);
    $this->assertParentOption('Test term 2', FALSE);
    $this->assertParentOption('Test term 3', FALSE);
    // Edit term form with invalid parent.
    $this->drupalGet($term_1->toUrl('edit-form'), ['query' => ['parent' => -1]]);
    $this->assertParentOption('Test term 2', FALSE);
    $this->assertParentOption('Test term 3', FALSE);
  }

  /**
   * Creates a term, saves it and returns it.
   *
   * @param string $name
   *   The name of the term to create.
   * @param int[] $parent_ids
   *   (optional) A list of parent term IDs.
   *
   * @return \Drupal\taxonomy\TermInterface
   *   The created term.
   */
  protected function createTerm($name, array $parent_ids = []) {
    /** @var \Drupal\taxonomy\TermInterface $term */
    $term = $this->termStorage->create([
      'name' => $name,
      'vid' => $this->vocabularyId,
    ]);
    foreach ($parent_ids as $delta => $parent_id) {
      $term->get('parent')->set($delta, ['target_id' => $parent_id]);
    }

    $term->save();

    return $term;
  }

  /**
   * Asserts that an option in the parent form element of terms exists.
   *
   * @param string $option
   *   The label of the parent option.
   * @param bool $selected
   *   (optional) Whether or not the option should be selected. Defaults to
   *   FALSE.
   *
   * @internal
   */
  protected function assertParentOption(string $option, bool $selected = FALSE): void {
    $option = $this->assertSession()->optionExists('Parent terms', $option);
    if ($selected) {
      $this->assertTrue($option->hasAttribute('selected'));
    }
    else {
      $this->assertFalse($option->hasAttribute('selected'));
    }
  }

  /**
   * Asserts that the parents of the term have not changed after saving.
   *
   * @param \Drupal\taxonomy\TermInterface $term
   *   The term to check.
   *
   * @internal
   */
  protected function assertParentsUnchanged(TermInterface $term): void {
    $saved_term = $this->termStorage->load($term->id());

    $expected = $term->get('parent')->getValue();
    $this->assertEquals($expected, $saved_term->get('parent')->getValue());
  }

}
