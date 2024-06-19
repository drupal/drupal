<?php

declare(strict_types=1);

namespace Drupal\Tests\node\Functional\Views\Wizard;

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
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Tests creating a node revision view.
   */
  public function testViewAdd(): void {
    $this->drupalCreateContentType(['type' => 'article']);
    // Create two nodes with two revision.
    $node_storage = \Drupal::entityTypeManager()->getStorage('node');
    /** @var \Drupal\node\NodeInterface $node */
    $node = $node_storage->create(['title' => $this->randomString(), 'type' => 'article', 'changed' => \Drupal::time()->getRequestTime() + 40]);
    $node->save();

    $node = $node->createDuplicate();
    $node->setNewRevision();
    $node->changed->value = \Drupal::time()->getRequestTime() + 20;
    $node->save();

    $node = $node_storage->create(['title' => $this->randomString(), 'type' => 'article', 'changed' => \Drupal::time()->getRequestTime() + 30]);
    $node->save();

    $node = $node->createDuplicate();
    $node->setNewRevision();
    $node->changed->value = \Drupal::time()->getRequestTime() + 10;
    $node->save();

    $this->drupalCreateContentType(['type' => 'not_article']);
    $node = $node_storage->create(['title' => $this->randomString(), 'type' => 'not_article', 'changed' => \Drupal::time()->getRequestTime() + 80]);
    $node->save();

    $type = [
      'show[wizard_key]' => 'node_revision',
    ];
    $this->drupalGet('admin/structure/views/add');
    $this->submitForm($type, 'Update "Show" choice');

    $view = [];
    $view['label'] = $this->randomMachineName(16);
    $view['id'] = $this->randomMachineName(16);
    $view['description'] = $this->randomMachineName(16);
    $view['page[create]'] = FALSE;
    $view['show[type]'] = 'article';
    $view['show[sort]'] = 'changed:DESC';
    $this->submitForm($view, 'Save and edit');

    $view = Views::getView($view['id']);
    $view->initHandlers();

    $this->assertEquals(['node_field_revision' => TRUE, '#global' => TRUE, 'node_field_data' => TRUE], $view->getBaseTables());

    // Check for the default filters.
    $this->assertEquals('node_field_revision', $view->filter['status']->table);
    $this->assertEquals('status', $view->filter['status']->field);
    $this->assertEquals('1', $view->filter['status']->value);
    $this->assertEquals('node_field_data', $view->filter['type']->table);

    $this->executeView($view);

    $this->assertIdenticalResultset($view, [['vid' => 1], ['vid' => 3], ['vid' => 2], ['vid' => 4]],
      ['vid' => 'vid']);

    // Create a new view with no filter on type.
    $type = [
      'show[wizard_key]' => 'node_revision',
    ];
    $this->drupalGet('admin/structure/views/add');
    $this->submitForm($type, 'Update "Show" choice');
    $view = [];
    $view['label'] = $this->randomMachineName(16);
    $view['id'] = $this->randomMachineName(16);
    $view['description'] = $this->randomMachineName(16);
    $view['page[create]'] = FALSE;
    $view['show[type]'] = 'all';
    $view['show[sort]'] = 'changed:DESC';
    $this->submitForm($view, 'Save and edit');

    $view = Views::getView($view['id']);
    $view->initHandlers();

    $this->assertEquals(['node_field_revision' => TRUE, '#global' => TRUE], $view->getBaseTables());

    // Check for the default filters.
    $this->assertEquals('node_field_revision', $view->filter['status']->table);
    $this->assertEquals('status', $view->filter['status']->field);
    $this->assertEquals('1', $view->filter['status']->value);
    $this->assertArrayNotHasKey('type', $view->filter);

    $this->executeView($view);

    $this->assertIdenticalResultset($view, [['vid' => 5], ['vid' => 1], ['vid' => 3], ['vid' => 2], ['vid' => 4]],
      ['vid' => 'vid']);
  }

}
