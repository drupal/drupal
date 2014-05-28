<?php

/**
 * @file
 * Contains \Drupal\node\Tests\Views\NodeRevisionWizardTest.
 */

namespace Drupal\node\Tests\Views;

use Drupal\views\Tests\Wizard\WizardTestBase;
use Drupal\views\Views;

/**
 * Tests the wizard with node_revision as base table.
 *
 * @see \Drupal\node\Plugin\views\wizard\NodeRevision
 */
class NodeRevisionWizardTest extends WizardTestBase {

  /**
   * {@inheritdoc}
   */
  public static function getInfo() {
    return array(
      'name' => 'Node revision wizard',
      'description' => 'Tests the wizard with node_revision as base table.',
      'group' => 'Views Wizard',
    );
  }

  /**
   * Tests creating a node revision view.
   */
  public function testViewAdd() {
    $this->drupalCreateContentType(array('type' => 'article'));
    // Create two nodes with two revision.
    $node_storage = \Drupal::entityManager()->getStorage('node');
    /** @var \Drupal\node\NodeInterface $node */
    $node = $node_storage->create(array('type' => 'article', 'created' => REQUEST_TIME + 40));
    $node->save();

    $node = $node->createDuplicate();
    $node->setNewRevision();
    $node->created->value = REQUEST_TIME + 20;
    $node->save();

    $node = $node_storage->create(array('type' => 'article', 'created' => REQUEST_TIME + 30));
    $node->save();

    $node = $node->createDuplicate();
    $node->setNewRevision();
    $node->created->value = REQUEST_TIME + 10;
    $node->save();

    $view = array();
    $view['label'] = $this->randomName(16);
    $view['id'] = strtolower($this->randomName(16));
    $view['description'] = $this->randomName(16);
    $view['page[create]'] = FALSE;
    $view['show[wizard_key]'] = 'node_revision';
    $this->drupalPostForm('admin/structure/views/add', $view, t('Save and edit'));

    $view_storage_controller = \Drupal::entityManager()->getStorage('view');
    /** @var \Drupal\views\Entity\View $view */
    $view = $view_storage_controller->load($view['id']);

    $this->assertEqual($view->get('base_table'), 'node_revision');

    $executable = Views::executableFactory()->get($view);
    $this->executeView($executable);

    $this->assertIdenticalResultset($executable, array(array('vid' => 1), array('vid' => 3), array('vid' => 2), array('vid' => 4)),
      array('node_field_revision_vid' => 'vid'));
  }

}

