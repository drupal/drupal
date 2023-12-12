<?php

declare(strict_types=1);

namespace Drupal\Tests\layout_builder\FunctionalJavascript;

use Drupal\FunctionalJavascriptTests\WebDriverTestBase;
use Drupal\layout_builder\Entity\LayoutBuilderEntityViewDisplay;
use Drupal\Tests\contextual\FunctionalJavascript\ContextualLinkClickTrait;

/**
 * Tests that messages appear in the off-canvas dialog with configuring blocks.
 *
 * @group layout_builder
 */
class BlockFormMessagesTest extends WebDriverTestBase {

  use ContextualLinkClickTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'layout_builder',
    'block',
    'node',
    'contextual',
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
    $this->createContentType(['type' => 'bundle_with_section_field']);
    LayoutBuilderEntityViewDisplay::load('node.bundle_with_section_field.default')
      ->enableLayoutBuilder()
      ->setOverridable()
      ->save();
    $this->createNode(['type' => 'bundle_with_section_field']);
  }

  /**
   * Tests that validation messages are shown on the block form.
   */
  public function testValidationMessage() {
    $assert_session = $this->assertSession();
    $page = $this->getSession()->getPage();

    $this->drupalLogin($this->drupalCreateUser([
      'access contextual links',
      'configure any layout',
    ]));
    $this->drupalGet('node/1/layout');
    $page->findLink('Add block')->click();
    $this->assertNotEmpty($assert_session->waitForElementVisible('css', '#drupal-off-canvas .block-categories'));
    $page->findLink('Powered by Drupal')->click();
    $this->assertNotEmpty($assert_session->waitForElementVisible('css', '#drupal-off-canvas [name="settings[label]"]'));
    $page->findField('Title')->setValue('');
    $page->findButton('Add block')->click();
    $this->assertMessagesDisplayed();
    $page->findField('Title')->setValue('New title');
    $page->pressButton('Add block');
    $block_css_locator = '#layout-builder .block-system-powered-by-block';
    $this->assertNotEmpty($assert_session->waitForElementVisible('css', $block_css_locator));

    $assert_session->assertNoElementAfterWait('css', '#drupal-off-canvas');
    $assert_session->assertWaitOnAjaxRequest();
    $this->drupalGet($this->getUrl());
    $page->findButton('Save layout')->click();
    $this->assertNotEmpty($assert_session->waitForElement('css', 'div:contains("The layout override has been saved")'));

    // Ensure that message are displayed when configuring an existing block.
    $this->drupalGet('node/1/layout');
    $this->clickContextualLink($block_css_locator, 'Configure', TRUE);
    $this->assertNotEmpty($assert_session->waitForElementVisible('css', '#drupal-off-canvas [name="settings[label]"]'));
    $page->findField('Title')->setValue('');
    $page->findButton('Update')->click();
    $this->assertMessagesDisplayed();
  }

  /**
   * Asserts that the validation messages are shown correctly.
   *
   * @internal
   */
  protected function assertMessagesDisplayed(): void {
    $assert_session = $this->assertSession();
    $page = $this->getSession()->getPage();
    $messages_locator = '#drupal-off-canvas .messages--error';
    $assert_session->assertWaitOnAjaxRequest();
    $this->assertNotEmpty($assert_session->waitForElement('css', $messages_locator));
    $assert_session->elementTextContains('css', $messages_locator, 'Title field is required.');
    /** @var \Behat\Mink\Element\NodeElement[] $top_form_elements */
    $top_form_elements = $page->findAll('css', '#drupal-off-canvas form > *');
    // Ensure the messages are the first top level element of the form.
    $this->assertStringContainsStringIgnoringCase('Title field is required.', $top_form_elements[0]->getText());
    $this->assertGreaterThan(4, count($top_form_elements));
  }

}
