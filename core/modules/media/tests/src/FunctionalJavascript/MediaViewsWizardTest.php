<?php

declare(strict_types=1);

namespace Drupal\Tests\media\FunctionalJavascript;

use Drupal\views\Views;

/**
 * Tests the media entity type integration into the wizard.
 *
 * @group media
 *
 * @see \Drupal\media\Plugin\views\wizard\Media
 * @see \Drupal\media\Plugin\views\wizard\MediaRevision
 */
class MediaViewsWizardTest extends MediaJavascriptTestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Tests adding a view of media.
   */
  public function testMediaWizard(): void {
    $session = $this->getSession();
    $page = $session->getPage();
    $assert_session = $this->assertSession();

    $this->createMediaType('test');

    $view_id = $this->randomMachineName(16);
    $this->drupalGet('admin/structure/views/add');
    $page->fillField('label', $view_id);
    $this->waitUntilVisible('.machine-name-value');
    $page->selectFieldOption('show[wizard_key]', 'media');
    $result = $assert_session->waitForElementVisible('css', 'select[data-drupal-selector="edit-show-type"]');
    $this->assertNotEmpty($result);
    $page->checkField('page[create]');
    $page->fillField('page[path]', $this->randomMachineName(16));
    $page->pressButton('Save and edit');
    $this->assertSame($session->getCurrentUrl(), $this->baseUrl . '/admin/structure/views/view/' . $view_id);

    $view = Views::getView($view_id);
    $view->initHandlers();
    $row = $view->display_handler->getOption('row');
    $this->assertSame($row['type'], 'fields');
    // Check for the default filters.
    $this->assertSame($view->filter['status']->table, 'media_field_data');
    $this->assertSame($view->filter['status']->field, 'status');
    $this->assertSame($view->filter['status']->value, '1');
    // Check for the default fields.
    $this->assertSame($view->field['name']->table, 'media_field_data');
    $this->assertSame($view->field['name']->field, 'name');

  }

  /**
   * Tests adding a view of media revisions.
   */
  public function testMediaRevisionWizard(): void {
    $session = $this->getSession();
    $page = $session->getPage();
    $assert_session = $this->assertSession();

    $view_id = $this->randomMachineName(16);
    $this->drupalGet('admin/structure/views/add');
    $page->fillField('label', $view_id);
    $this->waitUntilVisible('.machine-name-value');
    $page->selectFieldOption('show[wizard_key]', 'media_revision');
    $assert_session->assertWaitOnAjaxRequest();
    $page->checkField('page[create]');
    $page->fillField('page[path]', $this->randomMachineName(16));
    $page->pressButton('Save and edit');
    $this->assertSame($session->getCurrentUrl(), $this->baseUrl . '/admin/structure/views/view/' . $view_id);

    $view = Views::getView($view_id);
    $view->initHandlers();
    $row = $view->display_handler->getOption('row');
    $this->assertSame($row['type'], 'fields');

    // Check for the default filters.
    $this->assertSame($view->filter['status']->table, 'media_field_revision');
    $this->assertSame($view->filter['status']->field, 'status');
    $this->assertSame($view->filter['status']->value, '1');

    // Check for the default fields.
    $this->assertSame($view->field['name']->table, 'media_field_revision');
    $this->assertSame($view->field['name']->field, 'name');
    $this->assertSame($view->field['changed']->table, 'media_field_revision');
    $this->assertSame($view->field['changed']->field, 'changed');
  }

}
