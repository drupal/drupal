<?php

declare(strict_types=1);

namespace Drupal\Tests\layout_builder\Functional;

use Drupal\layout_builder\Entity\LayoutBuilderEntityViewDisplay;
use Drupal\Tests\BrowserTestBase;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests how Layout Builder handles changes to entity fields.
 */
#[Group('layout_builder')]
#[RunTestsInSeparateProcesses]
class FieldValuesTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'layout_builder',
    'block',
    'node',
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

    $this->createContentType(['type' => 'bundle_for_testing_fields']);
    $this->drupalLogin($this->drupalCreateUser([
      'edit any bundle_for_testing_fields content',
      'configure any layout',
    ]));
    LayoutBuilderEntityViewDisplay::load('node.bundle_for_testing_fields.default')
      ->createCopy('full')
      ->enableLayoutBuilder()
      ->setOverridable()
      ->save();
  }

  /**
   * Tests that changes to fields are visible in the Layout Builder UI.
   */
  public function testLayoutBuilderUiDoesNotShowStaleEntityFieldValues(): void {
    $assert_session = $this->assertSession();
    $page = $this->getSession()->getPage();

    $this->createNode([
      'type' => 'bundle_for_testing_fields',
      'body' => [
        [
          'value' => 'The initial value',
        ],
      ],
    ])->save();

    $this->drupalGet('node/1');
    $assert_session->pageTextContains('The initial value');
    $this->drupalGet('node/1/layout');

    // Change the Links block label in the override to confirm that these
    // changes aren't removed when entity field values are updated.
    $links_block = $page->findAll('css', ".layout__region--content > .layout-builder-block");
    // The second block is the body field so that is why $links_block[1].
    $links_block_uuid = $links_block[1]->getAttribute('data-layout-block-uuid');
    $this->drupalGet('layout_builder/update/block/overrides/node.1/0/content/' . $links_block_uuid);
    $page->checkField('settings[label_display]');
    $overridden_label = 'This is a label in the override';
    $page->fillField('settings[label]', $overridden_label);
    $page->pressButton('Update');

    $assert_session->pageTextContains('The initial value');
    $assert_session->pageTextContains($overridden_label);

    $changed_body_value = 'The changed value';

    $this->drupalGet('node/1/edit');
    $this->submitForm(['body[0][value]' => $changed_body_value], 'Save');

    // Confirm that changes to a field are seen in the Layout UI without
    // altering a layout's changes in the tempstore.
    $assert_session->pageTextContains($changed_body_value);
    $assert_session->pageTextNotContains($overridden_label);
    $this->drupalGet('node/1/layout');
    $assert_session->pageTextContains($changed_body_value);
    $assert_session->pageTextContains($overridden_label);

    // Confirm the fields appear correctly after the override is saved and
    // no tempstore is present.
    $page->pressButton('Save layout');
    $assert_session->pageTextContains($changed_body_value);
    $assert_session->pageTextContains($overridden_label);
    $this->drupalGet('node/1/layout');
    $assert_session->pageTextContains($changed_body_value);
    $assert_session->pageTextContains($overridden_label);
  }

}
