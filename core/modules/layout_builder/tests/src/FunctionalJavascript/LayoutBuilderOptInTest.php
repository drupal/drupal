<?php

declare(strict_types=1);

namespace Drupal\Tests\layout_builder\FunctionalJavascript;

use Drupal\FunctionalJavascriptTests\WebDriverTestBase;
use Drupal\Tests\layout_builder\Traits\EnableLayoutBuilderTrait;

/**
 * Tests the ability for opting in and out of Layout Builder.
 *
 * @group layout_builder
 */
class LayoutBuilderOptInTest extends WebDriverTestBase {

  use EnableLayoutBuilderTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'node',
    'field_ui',
    'block',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'starterkit_theme';

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Create one content type before installing Layout Builder and one after.
    $this->createContentType(['type' => 'before']);
    $this->container->get('module_installer')->install(['layout_builder']);
    $this->rebuildAll();
    $this->createContentType(['type' => 'after']);

    $this->drupalLogin($this->drupalCreateUser([
      'configure any layout',
      'administer node display',
    ]));
  }

  /**
   * Tests the interaction between the two layout checkboxes.
   */
  public function testCheckboxLogic(): void {
    $assert_session = $this->assertSession();
    $page = $this->getSession()->getPage();

    $this->drupalGet('admin/structure/types/manage/before/display/default');
    // Both fields are unchecked and allow_custom is disabled and hidden.
    $assert_session->checkboxNotChecked('layout[enabled]');
    $assert_session->checkboxNotChecked('layout[allow_custom]');
    $assert_session->fieldDisabled('layout[allow_custom]');
    $this->assertFalse($page->findField('layout[allow_custom]')->isVisible());

    // Checking is_enable will show allow_custom.
    $page->checkField('layout[enabled]');
    $assert_session->checkboxNotChecked('layout[allow_custom]');
    $this->assertTrue($page->findField('layout[allow_custom]')->isVisible());
    $page->pressButton('Save');
    $assert_session->checkboxChecked('layout[enabled]');
    $assert_session->checkboxNotChecked('layout[allow_custom]');

    // Check and submit allow_custom.
    $page->checkField('layout[allow_custom]');
    $page->pressButton('Save');
    $assert_session->checkboxChecked('layout[enabled]');
    $assert_session->checkboxChecked('layout[allow_custom]');

    // Reset the checkboxes.
    $this->disableLayoutBuilderFromUi('before', 'default');
    $assert_session->checkboxNotChecked('layout[enabled]');
    $assert_session->checkboxNotChecked('layout[allow_custom]');

    // Check both at the same time.
    $page->checkField('layout[enabled]');
    $page->checkField('layout[allow_custom]');
    $page->pressButton('Save');
    $assert_session->checkboxChecked('layout[enabled]');
    $assert_session->checkboxChecked('layout[allow_custom]');
  }

  /**
   * Tests the expected default values for enabling Layout Builder.
   */
  public function testDefaultValues(): void {
    $assert_session = $this->assertSession();
    $page = $this->getSession()->getPage();

    // Both the content type created before and after Layout Builder was
    // installed is still using the Field UI.
    $this->drupalGet('admin/structure/types/manage/before/display/default');
    $assert_session->checkboxNotChecked('layout[enabled]');

    $field_ui_prefix = 'admin/structure/types/manage/after/display/default';
    $this->drupalGet($field_ui_prefix);
    $assert_session->checkboxNotChecked('layout[enabled]');
    $page->checkField('layout[enabled]');
    $page->pressButton('Save');

    $layout_builder_ui = $this->getPathForFieldBlock('node', 'after', 'default', 'body');

    $assert_session->linkExists('Manage layout');
    $this->clickLink('Manage layout');
    // Ensure the body appears once and only once.
    $assert_session->elementsCount('css', '.field--name-body', 1);

    // Change the body formatter to Trimmed.
    $this->drupalGet($layout_builder_ui);
    $assert_session->fieldValueEquals('settings[formatter][type]', 'text_default');
    $page->selectFieldOption('settings[formatter][type]', 'text_trimmed');
    $assert_session->assertWaitOnAjaxRequest();
    $page->pressButton('Update');
    $page->pressButton('Save layout');

    $this->drupalGet($layout_builder_ui);
    $assert_session->fieldValueEquals('settings[formatter][type]', 'text_trimmed');

    // Disable Layout Builder.
    $this->drupalGet($field_ui_prefix);
    $this->submitForm(['layout[enabled]' => FALSE], 'Save');
    $page->pressButton('Confirm');

    // The Layout Builder UI is no longer accessible.
    $this->drupalGet($layout_builder_ui);
    $assert_session->pageTextContains('You are not authorized to access this page.');

    // The original body formatter is reflected in Field UI.
    $this->drupalGet($field_ui_prefix);
    $assert_session->fieldValueEquals('fields[body][type]', 'text_default');

    // Change the body formatter to Summary.
    $page->selectFieldOption('fields[body][type]', 'text_summary_or_trimmed');
    $assert_session->assertWaitOnAjaxRequest();
    $page->pressButton('Save');
    $assert_session->fieldValueEquals('fields[body][type]', 'text_summary_or_trimmed');

    // Reactivate Layout Builder.
    $this->drupalGet($field_ui_prefix);
    $this->submitForm(['layout[enabled]' => TRUE], 'Save');
    $assert_session->linkExists('Manage layout');
    $this->clickLink('Manage layout');
    // Ensure the body appears once and only once.
    $assert_session->elementsCount('css', '.field--name-body', 1);

    // The changed body formatter is reflected in Layout Builder UI.
    $this->drupalGet($this->getPathForFieldBlock('node', 'after', 'default', 'body'));
    $assert_session->fieldValueEquals('settings[formatter][type]', 'text_summary_or_trimmed');
  }

  /**
   * Returns the path to update a field block in the UI.
   *
   * @param string $entity_type_id
   *   The entity type ID.
   * @param string $bundle
   *   The bundle.
   * @param string $view_mode
   *   The view mode.
   * @param string $field_name
   *   The field name.
   *
   * @return string
   *   The path.
   */
  protected function getPathForFieldBlock($entity_type_id, $bundle, $view_mode, $field_name) {
    $delta = 0;
    /** @var \Drupal\layout_builder\Entity\LayoutEntityDisplayInterface $display */
    $display = $this->container->get('entity_type.manager')->getStorage('entity_view_display')->load("$entity_type_id.$bundle.$view_mode");
    $body_component = NULL;
    foreach ($display->getSection($delta)->getComponents() as $component) {
      if ($component->getPluginId() === "field_block:$entity_type_id:$bundle:$field_name") {
        $body_component = $component;
      }
    }
    $this->assertNotNull($body_component);
    return 'layout_builder/update/block/defaults/node.after.default/0/content/' . $body_component->getUuid();
  }

}
