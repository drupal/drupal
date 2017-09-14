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

    $view = Views::getView($view['id']);
    $view->initHandlers();

    $this->assertEqual($view->getBaseTables(), ['node_field_revision' => TRUE, '#global' => TRUE]);

    // Check for the default filters.
    $this->assertEqual($view->filter['status']->table, 'node_field_revision');
    $this->assertEqual($view->filter['status']->field, 'status');
    $this->assertTrue($view->filter['status']->value);

    $this->executeView($view);

    $this->assertIdenticalResultset($view, [['vid' => 1], ['vid' => 3], ['vid' => 2], ['vid' => 4]],
      ['vid' => 'vid']);
  }

}
