<?php

namespace Drupal\Tests\views\Functional\Wizard;

/**
 * Tests wizard for generic revisionable entities.
 *
 * @group Views
 */
class EntityTestRevisionTest extends WizardTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['entity_test'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Tests creating a view of revisions where the type is not on the base table.
   */
  public function testRevisionsViewWithNoTypeOnBaseTable() {
    $type = [
      'show[wizard_key]' => 'standard:entity_test_rev_revision',
    ];
    $this->drupalGet('admin/structure/views/add');
    $this->submitForm($type, 'Update "Show" choice');
    $view = [];
    $view['label'] = $this->randomMachineName(16);
    $view['id'] = strtolower($this->randomMachineName(16));
    $view['description'] = $this->randomMachineName(16);
    $view['page[create]'] = FALSE;
    $view['show[type]'] = 'entity_test_rev';
    $this->submitForm($view, 'Save and edit');

    $view_storage_controller = \Drupal::entityTypeManager()->getStorage('view');
    /** @var \Drupal\views\Entity\View $view */
    $view = $view_storage_controller->load($view['id']);

    $display_options = $view->getDisplay('default')['display_options'];
    // Ensure that no filters exist on 'type' since that data is not available
    // on the base table.
    $this->assertEmpty($display_options['filters']);
  }

}
