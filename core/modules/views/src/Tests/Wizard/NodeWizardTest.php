<?php

namespace Drupal\views\Tests\Wizard;


/**
 * Tests node wizard and generic entity integration.
 *
 * @group Views
 * @group node
 */
class NodeWizardTest extends WizardTestBase {

  /**
   * Tests creating a view with node titles.
   */
  public function testViewAddWithNodeTitles() {
    $this->drupalCreateContentType(['type' => 'article']);

    $view = [];
    $view['label'] = $this->randomMachineName(16);
    $view['id'] = strtolower($this->randomMachineName(16));
    $view['description'] = $this->randomMachineName(16);
    $view['page[create]'] = FALSE;
    $view['show[wizard_key]'] = 'node';
    $view['page[style][row_plugin]'] = 'titles';
    $this->drupalPostForm('admin/structure/views/add', $view, t('Save and edit'));

    $view_storage_controller = \Drupal::entityManager()->getStorage('view');
    /** @var \Drupal\views\Entity\View $view */
    $view = $view_storage_controller->load($view['id']);

    $display_options = $view->getDisplay('default')['display_options'];
    // Ensure that the 'entity_table' and 'entity_field' properties are set
    // property.
    $this->assertEqual('node', $display_options['fields']['title']['entity_type']);
    $this->assertEqual('title', $display_options['fields']['title']['entity_field']);

    $this->assertEqual('node', $display_options['filters']['status']['entity_type']);
    $this->assertEqual('status', $display_options['filters']['status']['entity_field']);

    $this->assertEqual('node', $display_options['sorts']['created']['entity_type']);
    $this->assertEqual('created', $display_options['sorts']['created']['entity_field']);
  }

}
