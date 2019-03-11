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
    $node = $node_storage->create(['title' => $this->randomString(), 'type' => 'article', 'changed' => REQUEST_TIME + 40]);
    $node->save();

    $node = $node->createDuplicate();
    $node->setNewRevision();
    $node->changed->value = REQUEST_TIME + 20;
    $node->save();

    $node = $node_storage->create(['title' => $this->randomString(), 'type' => 'article', 'changed' => REQUEST_TIME + 30]);
    $node->save();

    $node = $node->createDuplicate();
    $node->setNewRevision();
    $node->changed->value = REQUEST_TIME + 10;
    $node->save();

    $this->drupalCreateContentType(['type' => 'not-article']);
    $node = $node_storage->create(['title' => $this->randomString(), 'type' => 'not-article', 'changed' => REQUEST_TIME + 80]);
    $node->save();

    $type = [
      'show[wizard_key]' => 'node_revision',
    ];
    $this->drupalPostForm('admin/structure/views/add', $type, t('Update "Show" choice'));

    $view = [];
    $view['label'] = $this->randomMachineName(16);
    $view['id'] = strtolower($this->randomMachineName(16));
    $view['description'] = $this->randomMachineName(16);
    $view['page[create]'] = FALSE;
    $view['show[type]'] = 'article';
    $view['show[sort]'] = 'changed:DESC';
    $this->drupalPostForm(NULL, $view, t('Save and edit'));

    $view = Views::getView($view['id']);
    $view->initHandlers();

    $this->assertEqual($view->getBaseTables(), [
        'node_field_revision' => TRUE,
        '#global' => TRUE,
        'node_field_data' => TRUE,
      ]
    );

    // Check for the default filters.
    $this->assertEqual($view->filter['status']->table, 'node_field_revision');
    $this->assertEqual($view->filter['status']->field, 'status');
    $this->assertTrue($view->filter['status']->value);
    $this->assertEquals('node_field_data', $view->filter['type']->table);

    $this->executeView($view);

    $this->assertIdenticalResultset($view, [['vid' => 1], ['vid' => 3], ['vid' => 2], ['vid' => 4]],
      ['vid' => 'vid']);

    // Create a new view with no filter on type.
    $type = [
      'show[wizard_key]' => 'node_revision',
    ];
    $this->drupalPostForm('admin/structure/views/add', $type, t('Update "Show" choice'));
    $view = [];
    $view['label'] = $this->randomMachineName(16);
    $view['id'] = strtolower($this->randomMachineName(16));
    $view['description'] = $this->randomMachineName(16);
    $view['page[create]'] = FALSE;
    $view['show[type]'] = 'all';
    $view['show[sort]'] = 'changed:DESC';
    $this->drupalPostForm(NULL, $view, t('Save and edit'));

    $view = Views::getView($view['id']);
    $view->initHandlers();

    $this->assertEqual($view->getBaseTables(), [
        'node_field_revision' => TRUE,
        '#global' => TRUE,
      ]
    );

    // Check for the default filters.
    $this->assertEqual($view->filter['status']->table, 'node_field_revision');
    $this->assertEqual($view->filter['status']->field, 'status');
    $this->assertTrue($view->filter['status']->value);
    $this->assertTrue(empty($view->filter['type']));

    $this->executeView($view);

    $this->assertIdenticalResultset($view, [['vid' => 5], ['vid' => 1], ['vid' => 3], ['vid' => 2], ['vid' => 4]],
      ['vid' => 'vid']);
  }

}
