<?php

namespace Drupal\Tests\layout_builder\FunctionalJavascript;

use Drupal\FunctionalJavascriptTests\WebDriverTestBase;

/**
 * Tests placing blocks containing forms in theLayout Builder UI.
 *
 * @group layout_builder
 */
class LayoutBuilderNestedFormUiTest extends WebDriverTestBase {

  /**
   * The form block labels used as text for links to add blocks.
   */
  const FORM_BLOCK_LABELS = [
    'Layout Builder form block test form api form block',
    'Layout Builder form block test inline template form block',
    'Test Block View: Exposed form block',
  ];

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'block',
    'field_ui',
    'node',
    'layout_builder',
    'layout_builder_form_block_test',
    'views',
    'layout_builder_views_test',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->drupalPlaceBlock('local_tasks_block');

    // Create a separate node to add a form block to, respectively.
    // - Block with form api form will be added to first node layout.
    // - Block with inline template with <form> tag added to second node layout.
    // - Views block exposed form added to third node layout.
    $this->createContentType([
      'type' => 'bundle_with_section_field',
      'name' => 'Bundle with section field',
    ]);
    for ($i = 1; $i <= count(static::FORM_BLOCK_LABELS); $i++) {
      $this->createNode([
        'type' => 'bundle_with_section_field',
        'title' => "Node $i title",
      ]);
    }
  }

  /**
   * Tests blocks containing forms can be successfully saved editing defaults.
   */
  public function testAddingFormBlocksToDefaults() {
    $this->markTestSkipped();
    $this->drupalLogin($this->drupalCreateUser([
      'configure any layout',
      'administer node display',
    ]));

    // From the manage display page, enable Layout Builder.
    $field_ui_prefix = 'admin/structure/types/manage/bundle_with_section_field';
    $this->drupalGet("$field_ui_prefix/display/default");
    $this->submitForm(['layout[enabled]' => TRUE], 'Save');
    $this->submitForm(['layout[allow_custom]' => TRUE], 'Save');

    // Save the entity view display so that it can be reverted to later.
    /** @var \Drupal\Core\Config\StorageInterface $active_config_storage */
    $active_config_storage = $this->container->get('config.storage');
    $original_display_config_data = $active_config_storage->read('core.entity_view_display.node.bundle_with_section_field.default');
    /** @var \Drupal\Core\Config\Entity\ConfigEntityStorageInterface $entity_view_display_storage */
    $entity_view_display_storage = $this->container->get('entity_type.manager')->getStorage('entity_view_display');
    $entity_view_display = $entity_view_display_storage->load('node.bundle_with_section_field.default');

    $expected_save_message = 'The layout has been saved.';
    foreach (static::FORM_BLOCK_LABELS as $label) {
      $this->addFormBlock($label, "$field_ui_prefix/display/default", $expected_save_message);
      // Revert the entity view display back to remove the previously added form
      // block.
      $entity_view_display = $entity_view_display_storage
        ->updateFromStorageRecord($entity_view_display, $original_display_config_data);
      $entity_view_display->save();
    }
  }

  /**
   * Tests blocks containing forms can be successfully saved editing overrides.
   */
  public function testAddingFormBlocksToOverrides() {
    $this->markTestSkipped();
    $this->drupalLogin($this->drupalCreateUser([
      'configure any layout',
      'administer node display',
    ]));

    // From the manage display page, enable Layout Builder.
    $field_ui_prefix = 'admin/structure/types/manage/bundle_with_section_field';
    $this->drupalGet("$field_ui_prefix/display/default");
    $this->submitForm(['layout[enabled]' => TRUE], 'Save');
    $this->submitForm(['layout[allow_custom]' => TRUE], 'Save');

    $expected_save_message = 'The layout override has been saved.';
    $nid = 1;
    foreach (static::FORM_BLOCK_LABELS as $label) {
      $this->addFormBlock($label, "node/$nid", $expected_save_message);
      $nid++;
    }
  }

  /**
   * Adds a form block specified by label layout and checks it can be saved.
   *
   * Need to test saving and resaving, because nested forms can cause issues
   * on the second save.
   *
   * @param string $label
   *   The form block label that will be used to identify link to add block.
   * @param string $path
   *   Root path of the entity (i.e. node/{NID) or the entity view display path.
   * @param string $expected_save_message
   *   The message that should be displayed after successful layout save.
   */
  protected function addFormBlock($label, $path, $expected_save_message) {
    $assert_session = $this->assertSession();
    $page = $this->getSession()->getPage();

    // Go to edit the layout.
    $this->drupalGet($path . '/layout');

    // Add the form block.
    $assert_session->linkExists('Add block');
    $this->clickLink('Add block');
    $assert_session->waitForElementVisible('named', ['link', $label]);
    $assert_session->linkExists($label);
    $this->clickLink($label);
    $assert_session->waitForElementVisible('named', ['button', 'Add block']);
    $page->pressButton('Add block');
    $assert_session->assertWaitOnAjaxRequest();
    $assert_session->pageTextContains($label);
    $assert_session->addressEquals($path . '/layout');

    // Save the defaults.
    $page->pressButton('Save layout');
    $assert_session->pageTextContains($expected_save_message);
    $assert_session->addressEquals($path);

    // Go back to edit layout and try to re-save.
    $this->drupalGet($path . '/layout');
    $page->pressButton('Save layout');
    $assert_session->pageTextContains($expected_save_message);
    $assert_session->addressEquals($path);
  }

}
