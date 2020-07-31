<?php

namespace Drupal\Tests\node\Functional\Views;

use Drupal\language\Entity\ConfigurableLanguage;
use Drupal\views\Views;

/**
 * Tests a node bulk form.
 *
 * @group node
 * @see \Drupal\node\Plugin\views\field\BulkForm
 */
class BulkFormTest extends NodeTestBase {

  /**
   * Modules to be enabled.
   *
   * @var array
   */
  public static $modules = ['node_test_views', 'language'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Views used by this test.
   *
   * @var array
   */
  public static $testViews = ['test_node_bulk_form'];

  /**
   * The test nodes.
   *
   * @var \Drupal\node\NodeInterface[]
   */
  protected $nodes;

  /**
   * {@inheritdoc}
   */
  protected function setUp($import_test_views = TRUE) {
    parent::setUp($import_test_views);

    ConfigurableLanguage::createFromLangcode('en-gb')->save();
    ConfigurableLanguage::createFromLangcode('it')->save();

    // Create some test nodes.
    $this->nodes = [];
    $langcodes = ['en', 'en-gb', 'it'];
    for ($i = 1; $i <= 5; $i++) {
      $langcode = $langcodes[($i - 1) % 3];
      $values = [
        'title' => $this->randomMachineName() . ' [' . $i . ':' . $langcode . ']',
        'langcode' => $langcode,
        'promote' => FALSE,
      ];
      $node = $this->drupalCreateNode($values);
      $this->nodes[] = $node;
    }

    // Create translations for all languages for some nodes.
    for ($i = 0; $i < 2; $i++) {
      $node = $this->nodes[$i];
      foreach ($langcodes as $langcode) {
        if (!$node->hasTranslation($langcode)) {
          $title = $this->randomMachineName() . ' [' . $node->id() . ':' . $langcode . ']';
          $translation = $node->addTranslation($langcode, ['title' => $title, 'promote' => FALSE]);
        }
      }
      $node->save();
    }

    // Create a node with only one translation.
    $node = $this->nodes[2];
    $langcode = 'en';
    $title = $this->randomMachineName() . ' [' . $node->id() . ':' . $langcode . ']';
    $translation = $node->addTranslation($langcode, ['title' => $title]);
    $node->save();

    // Check that all created translations are selected by the test view.
    $view = Views::getView('test_node_bulk_form');
    $view->execute();
    $this->assertCount(10, $view->result, 'All created translations are selected.');

    // Check the operations are accessible to the logged in user.
    $this->drupalLogin($this->drupalCreateUser([
      'administer nodes',
      'access content overview',
      'bypass node access',
    ]));
    $this->drupalGet('test-node-bulk-form');
    $elements = $this->xpath('//select[@id="edit-action"]//option');
    $this->assertCount(8, $elements, 'All node operations are found.');
  }

  /**
   * Tests the node bulk form.
   */
  public function testBulkForm() {
    // Unpublish a node using the bulk form.
    $node = reset($this->nodes);
    $this->assertTrue($node->isPublished(), 'Node is initially published');
    $this->assertTrue($node->getTranslation('en-gb')->isPublished(), 'Node translation is published');
    $this->assertTrue($node->getTranslation('it')->isPublished(), 'Node translation is published');
    $edit = [
      'node_bulk_form[0]' => TRUE,
      'action' => 'node_unpublish_action',
    ];
    $this->drupalPostForm(NULL, $edit, t('Apply to selected items'));
    $node = $this->loadNode($node->id());
    $this->assertFalse($node->isPublished(), 'Node has been unpublished');
    $this->assertTrue($node->getTranslation('en-gb')->isPublished(), 'Node translation has not been unpublished');
    $this->assertTrue($node->getTranslation('it')->isPublished(), 'Node translation has not been unpublished');

    // Publish action.
    $edit = [
      'node_bulk_form[0]' => TRUE,
      'action' => 'node_publish_action',
    ];
    $this->drupalPostForm(NULL, $edit, t('Apply to selected items'));
    $node = $this->loadNode($node->id());
    $this->assertTrue($node->isPublished(), 'Node has been published again');

    // Make sticky action.
    $this->assertFalse($node->isSticky(), 'Node is not sticky');
    $this->assertFalse($node->getTranslation('en-gb')->isSticky(), 'Node translation is not sticky');
    $this->assertFalse($node->getTranslation('it')->isSticky(), 'Node translation is not sticky');
    $edit = [
      'node_bulk_form[0]' => TRUE,
      'action' => 'node_make_sticky_action',
    ];
    $this->drupalPostForm(NULL, $edit, t('Apply to selected items'));
    $node = $this->loadNode($node->id());
    $this->assertTrue($node->isSticky(), 'Node has been made sticky');
    $this->assertFalse($node->getTranslation('en-gb')->isSticky(), 'Node translation has not been made sticky');
    $this->assertFalse($node->getTranslation('it')->isSticky(), 'Node translation has not been made sticky');

    // Make unsticky action.
    $edit = [
      'node_bulk_form[0]' => TRUE,
      'action' => 'node_make_unsticky_action',
    ];
    $this->drupalPostForm(NULL, $edit, t('Apply to selected items'));
    $node = $this->loadNode($node->id());
    $this->assertFalse($node->isSticky(), 'Node is not sticky anymore');

    // Promote to front page.
    $this->assertFalse($node->isPromoted(), 'Node is not promoted to the front page');
    $this->assertFalse($node->getTranslation('en-gb')->isPromoted(), 'Node translation is not promoted to the front page');
    $this->assertFalse($node->getTranslation('it')->isPromoted(), 'Node translation is not promoted to the front page');
    $edit = [
      'node_bulk_form[0]' => TRUE,
      'action' => 'node_promote_action',
    ];
    $this->drupalPostForm(NULL, $edit, t('Apply to selected items'));
    $node = $this->loadNode($node->id());
    $this->assertTrue($node->isPromoted(), 'Node has been promoted to the front page');
    $this->assertFalse($node->getTranslation('en-gb')->isPromoted(), 'Node translation has not been promoted to the front page');
    $this->assertFalse($node->getTranslation('it')->isPromoted(), 'Node translation has not been promoted to the front page');

    // Demote from front page.
    $edit = [
      'node_bulk_form[0]' => TRUE,
      'action' => 'node_unpromote_action',
    ];
    $this->drupalPostForm(NULL, $edit, t('Apply to selected items'));
    $node = $this->loadNode($node->id());
    $this->assertFalse($node->isPromoted(), 'Node has been demoted');

    // Select a bunch of translated and untranslated nodes and check that
    // operations are always applied to individual translations.
    $edit = [
      // Original and all translations.
      // Node 1, English, original.
      'node_bulk_form[0]' => TRUE,
      // Node 1, British English.
      'node_bulk_form[1]' => TRUE,
      // Node 1, Italian.
      'node_bulk_form[2]' => TRUE,
      // Original and only one translation.
      // Node 2, English.
      'node_bulk_form[3]' => TRUE,
      // Node 2, British English, original.
      'node_bulk_form[4]' => TRUE,
      // Node 2, Italian.
      'node_bulk_form[5]' => FALSE,
      // Only a single translation.
      // Node 3, English.
      'node_bulk_form[6]' => TRUE,
      // Node 3, Italian, original.
      'node_bulk_form[7]' => FALSE,
      // Only a single untranslated node.
      // Node 4, English, untranslated.
      'node_bulk_form[8]' => TRUE,
      // Node 5, British English, untranslated.
      'node_bulk_form[9]' => FALSE,
      'action' => 'node_unpublish_action',
    ];
    $this->drupalPostForm(NULL, $edit, t('Apply to selected items'));
    $node = $this->loadNode(1);
    $this->assertFalse($node->getTranslation('en')->isPublished(), '1: English translation has been unpublished');
    $this->assertFalse($node->getTranslation('en-gb')->isPublished(), '1: British English translation has been unpublished');
    $this->assertFalse($node->getTranslation('it')->isPublished(), '1: Italian translation has been unpublished');
    $node = $this->loadNode(2);
    $this->assertFalse($node->getTranslation('en')->isPublished(), '2: English translation has been unpublished');
    $this->assertFalse($node->getTranslation('en-gb')->isPublished(), '2: British English translation has been unpublished');
    $this->assertTrue($node->getTranslation('it')->isPublished(), '2: Italian translation has not been unpublished');
    $node = $this->loadNode(3);
    $this->assertFalse($node->getTranslation('en')->isPublished(), '3: English translation has been unpublished');
    $this->assertTrue($node->getTranslation('it')->isPublished(), '3: Italian translation has not been unpublished');
    $node = $this->loadNode(4);
    $this->assertFalse($node->isPublished(), '4: Node has been unpublished');
    $node = $this->loadNode(5);
    $this->assertTrue($node->isPublished(), '5: Node has not been unpublished');
  }

  /**
   * Test multiple deletion.
   */
  public function testBulkDeletion() {
    // Select a bunch of translated and untranslated nodes and check that
    // nodes and individual translations are properly deleted.
    $edit = [
      // Original and all translations.
      // Node 1, English, original.
      'node_bulk_form[0]' => TRUE,
      // Node 1, British English.
      'node_bulk_form[1]' => TRUE,
      // Node 1, Italian.
      'node_bulk_form[2]' => TRUE,
      // Original and only one translation.
      // Node 2, English.
      'node_bulk_form[3]' => TRUE,
      // Node 2, British English, original.
      'node_bulk_form[4]' => TRUE,
      // Node 2, Italian.
      'node_bulk_form[5]' => FALSE,
      // Only a single translation.
      // Node 3, English.
      'node_bulk_form[6]' => TRUE,
      // Node 3, Italian, original.
      'node_bulk_form[7]' => FALSE,
      // Only a single untranslated node.
      // Node 4, English, untranslated.
      'node_bulk_form[8]' => TRUE,
      // Node 5, British English, untranslated.
      'node_bulk_form[9]' => FALSE,
      'action' => 'node_delete_action',
    ];
    $this->drupalPostForm(NULL, $edit, t('Apply to selected items'));

    $label = $this->loadNode(1)->label();
    $this->assertText("$label (Original translation) - The following content item translations will be deleted:");
    $label = $this->loadNode(2)->label();
    $this->assertText("$label (Original translation) - The following content item translations will be deleted:");
    $label = $this->loadNode(3)->getTranslation('en')->label();
    $this->assertText($label);
    $this->assertNoText("$label (Original translation) - The following content item translations will be deleted:");
    $label = $this->loadNode(4)->label();
    $this->assertText($label);
    $this->assertNoText("$label (Original translation) - The following content item translations will be deleted:");

    $this->drupalPostForm(NULL, [], t('Delete'));

    $node = $this->loadNode(1);
    $this->assertNull($node, '1: Node has been deleted');
    $node = $this->loadNode(2);
    $this->assertNull($node, '2: Node has been deleted');
    $node = $this->loadNode(3);
    $result = count($node->getTranslationLanguages()) && $node->language()->getId() == 'it';
    $this->assertTrue($result, '3: English translation has been deleted');
    $node = $this->loadNode(4);
    $this->assertNull($node, '4: Node has been deleted');
    $node = $this->loadNode(5);
    $this->assertNotEmpty($node, '5: Node has not been deleted');

    $this->assertText('Deleted 8 content items.');
  }

  /**
   * Load the specified node from the storage.
   *
   * @param int $id
   *   The node identifier.
   *
   * @return \Drupal\node\NodeInterface
   *   The loaded node.
   */
  protected function loadNode($id) {
    /** @var \Drupal\node\NodeStorage $storage */
    $storage = $this->container->get('entity_type.manager')->getStorage('node');
    $storage->resetCache([$id]);
    return $storage->load($id);
  }

}
