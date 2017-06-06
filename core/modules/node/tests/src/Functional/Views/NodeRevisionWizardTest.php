<?php

namespace Drupal\Tests\node\Functional\Views;

use Drupal\Tests\views\Functional\Wizard\WizardTestBase;
use Drupal\views\Views;

/**
 * Tests the wizard with node_revision as base table.
 *
 * @group node
 * @see \Drupal\node\Plugin\views\wizard\NodeRevision
 */
class NodeRevisionWizardTest extends WizardTestBase {

  /**
   * Tests creating a node revision view.
   */
  public function testViewAdd() {
    $this->drupalCreateContentType(['type' => 'article']);
    // Create two nodes with two revision.
    $node_storage = \Drupal::entityManager()->getStorage('node');
    /** @var \Drupal\node\NodeInterface $node */
    $node = $node_storage->create(['title' => $this->randomString(), 'type' => 'article', 'created' => REQUEST_TIME + 40]);
    $node->save();

    $node = $node->createDuplicate();
    $node->setNewRevision();
    $node->created->value = REQUEST_TIME + 20;
    $node->save();

    $node = $node_storage->create(['title' => $this->randomString(), 'type' => 'article', 'created' => REQUEST_TIME + 30]);
    $node->save();

    $node = $node->createDuplicate();
    $node->setNewRevision();
    $node->created->value = REQUEST_TIME + 10;
    $node->save();

    $view = [];
    $view['label'] = $this->randomMachineName(16);
    $view['id'] = strtolower($this->randomMachineName(16));
    $view['description'] = $this->randomMachineName(16);
    $view['page[create]'] = FALSE;
    $view['show[wizard_key]'] = 'node_revision';
    $this->drupalPostForm('admin/structure/views/add', $view, t('Save and edit'));

    $view_storage_controller = \Drupal::entityManager()->getStorage('view');
    /** @var \Drupal\views\Entity\View $view */
    $view = $view_storage_controller->load($view['id']);

    $this->assertEqual($view->get('base_table'), 'node_field_revision');

    $executable = Views::executableFactory()->get($view);
    $this->executeView($executable);

    $this->assertIdenticalResultset($executable, [['vid' => 1], ['vid' => 3], ['vid' => 2], ['vid' => 4]],
      ['vid' => 'vid']);
  }

}
