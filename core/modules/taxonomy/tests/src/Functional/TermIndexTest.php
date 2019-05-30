<?php

namespace Drupal\Tests\taxonomy\Functional;

use Drupal\Core\Database\Database;
use Drupal\Core\Field\FieldStorageDefinitionInterface;

/**
 * Tests the hook implementations that maintain the taxonomy index.
 *
 * @group taxonomy
 */
class TermIndexTest extends TaxonomyTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['views'];

  /**
   * Vocabulary for testing.
   *
   * @var \Drupal\taxonomy\VocabularyInterface
   */
  protected $vocabulary;

  /**
   * Name of the taxonomy term reference field.
   *
   * @var string
   */
  protected $fieldName1;

  /**
   * Name of the taxonomy term reference field.
   *
   * @var string
   */
  protected $fieldName2;

  protected function setUp() {
    parent::setUp();

    // Create an administrative user.
    $this->drupalLogin($this->drupalCreateUser(['administer taxonomy', 'bypass node access']));

    // Create a vocabulary and add two term reference fields to article nodes.
    $this->vocabulary = $this->createVocabulary();

    $this->fieldName1 = mb_strtolower($this->randomMachineName());
    $handler_settings = [
      'target_bundles' => [
        $this->vocabulary->id() => $this->vocabulary->id(),
       ],
      'auto_create' => TRUE,
    ];
    $this->createEntityReferenceField('node', 'article', $this->fieldName1, NULL, 'taxonomy_term', 'default', $handler_settings, FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED);

    /** @var \Drupal\Core\Entity\EntityDisplayRepositoryInterface $display_repository */
    $display_repository = \Drupal::service('entity_display.repository');
    $display_repository->getFormDisplay('node', 'article')
      ->setComponent($this->fieldName1, [
        'type' => 'options_select',
      ])
      ->save();
    $display_repository->getViewDisplay('node', 'article')
      ->setComponent($this->fieldName1, [
        'type' => 'entity_reference_label',
      ])
      ->save();

    $this->fieldName2 = mb_strtolower($this->randomMachineName());
    $this->createEntityReferenceField('node', 'article', $this->fieldName2, NULL, 'taxonomy_term', 'default', $handler_settings, FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED);

    /** @var \Drupal\Core\Entity\EntityDisplayRepositoryInterface $display_repository */
    $display_repository = \Drupal::service('entity_display.repository');
    $display_repository->getFormDisplay('node', 'article')
      ->setComponent($this->fieldName2, [
        'type' => 'options_select',
      ])
      ->save();
    $display_repository->getViewDisplay('node', 'article')
      ->setComponent($this->fieldName2, [
        'type' => 'entity_reference_label',
      ])
      ->save();
  }

  /**
   * Tests that the taxonomy index is maintained properly.
   */
  public function testTaxonomyIndex() {
    $node_storage = $this->container->get('entity_type.manager')->getStorage('node');
    // Create terms in the vocabulary.
    $term_1 = $this->createTerm($this->vocabulary);
    $term_2 = $this->createTerm($this->vocabulary);

    // Post an article.
    $edit = [];
    $edit['title[0][value]'] = $this->randomMachineName();
    $edit['body[0][value]'] = $this->randomMachineName();
    $edit["{$this->fieldName1}[]"] = $term_1->id();
    $edit["{$this->fieldName2}[]"] = $term_1->id();
    $this->drupalPostForm('node/add/article', $edit, t('Save'));

    // Check that the term is indexed, and only once.
    $node = $this->drupalGetNodeByTitle($edit['title[0][value]']);
    $connection = Database::getConnection();
    $index_count = $connection->query('SELECT COUNT(*) FROM {taxonomy_index} WHERE nid = :nid AND tid = :tid', [
      ':nid' => $node->id(),
      ':tid' => $term_1->id(),
    ])->fetchField();
    $this->assertEqual(1, $index_count, 'Term 1 is indexed once.');

    // Update the article to change one term.
    $edit["{$this->fieldName1}[]"] = $term_2->id();
    $this->drupalPostForm('node/' . $node->id() . '/edit', $edit, t('Save'));

    // Check that both terms are indexed.
    $index_count = $connection->query('SELECT COUNT(*) FROM {taxonomy_index} WHERE nid = :nid AND tid = :tid', [
      ':nid' => $node->id(),
      ':tid' => $term_1->id(),
    ])->fetchField();
    $this->assertEqual(1, $index_count, 'Term 1 is indexed.');
    $index_count = $connection->query('SELECT COUNT(*) FROM {taxonomy_index} WHERE nid = :nid AND tid = :tid', [
      ':nid' => $node->id(),
      ':tid' => $term_2->id(),
    ])->fetchField();
    $this->assertEqual(1, $index_count, 'Term 2 is indexed.');

    // Update the article to change another term.
    $edit["{$this->fieldName2}[]"] = $term_2->id();
    $this->drupalPostForm('node/' . $node->id() . '/edit', $edit, t('Save'));

    // Check that only one term is indexed.
    $index_count = $connection->query('SELECT COUNT(*) FROM {taxonomy_index} WHERE nid = :nid AND tid = :tid', [
      ':nid' => $node->id(),
      ':tid' => $term_1->id(),
    ])->fetchField();
    $this->assertEqual(0, $index_count, 'Term 1 is not indexed.');
    $index_count = $connection->query('SELECT COUNT(*) FROM {taxonomy_index} WHERE nid = :nid AND tid = :tid', [
      ':nid' => $node->id(),
      ':tid' => $term_2->id(),
    ])->fetchField();
    $this->assertEqual(1, $index_count, 'Term 2 is indexed once.');

    // Redo the above tests without interface.
    $node_storage->resetCache([$node->id()]);
    $node = $node_storage->load($node->id());
    $node->title = $this->randomMachineName();

    // Update the article with no term changed.
    $node->save();

    // Check that the index was not changed.
    $index_count = $connection->query('SELECT COUNT(*) FROM {taxonomy_index} WHERE nid = :nid AND tid = :tid', [
      ':nid' => $node->id(),
      ':tid' => $term_1->id(),
    ])->fetchField();
    $this->assertEqual(0, $index_count, 'Term 1 is not indexed.');
    $index_count = $connection->query('SELECT COUNT(*) FROM {taxonomy_index} WHERE nid = :nid AND tid = :tid', [
      ':nid' => $node->id(),
      ':tid' => $term_2->id(),
    ])->fetchField();
    $this->assertEqual(1, $index_count, 'Term 2 is indexed once.');

    // Update the article to change one term.
    $node->{$this->fieldName1} = [['target_id' => $term_1->id()]];
    $node->save();

    // Check that both terms are indexed.
    $index_count = $connection->query('SELECT COUNT(*) FROM {taxonomy_index} WHERE nid = :nid AND tid = :tid', [
      ':nid' => $node->id(),
      ':tid' => $term_1->id(),
    ])->fetchField();
    $this->assertEqual(1, $index_count, 'Term 1 is indexed.');
    $index_count = $connection->query('SELECT COUNT(*) FROM {taxonomy_index} WHERE nid = :nid AND tid = :tid', [
      ':nid' => $node->id(),
      ':tid' => $term_2->id(),
    ])->fetchField();
    $this->assertEqual(1, $index_count, 'Term 2 is indexed.');

    // Update the article to change another term.
    $node->{$this->fieldName2} = [['target_id' => $term_1->id()]];
    $node->save();

    // Check that only one term is indexed.
    $index_count = $connection->query('SELECT COUNT(*) FROM {taxonomy_index} WHERE nid = :nid AND tid = :tid', [
      ':nid' => $node->id(),
      ':tid' => $term_1->id(),
    ])->fetchField();
    $this->assertEqual(1, $index_count, 'Term 1 is indexed once.');
    $index_count = $connection->query('SELECT COUNT(*) FROM {taxonomy_index} WHERE nid = :nid AND tid = :tid', [
      ':nid' => $node->id(),
      ':tid' => $term_2->id(),
    ])->fetchField();
    $this->assertEqual(0, $index_count, 'Term 2 is not indexed.');
  }

  /**
   * Tests that there is a link to the parent term on the child term page.
   */
  public function testTaxonomyTermHierarchyBreadcrumbs() {
    // Create two taxonomy terms and set term2 as the parent of term1.
    $term1 = $this->createTerm($this->vocabulary);
    $term2 = $this->createTerm($this->vocabulary);
    $term1->parent = [$term2->id()];
    $term1->save();

    // Verify that the page breadcrumbs include a link to the parent term.
    $this->drupalGet('taxonomy/term/' . $term1->id());
    // Breadcrumbs are not rendered with a language, prevent the term
    // language from being added to the options.
    $this->assertRaw(\Drupal::l($term2->getName(), $term2->toUrl('canonical', ['language' => NULL])), 'Parent term link is displayed when viewing the node.');
  }

}
