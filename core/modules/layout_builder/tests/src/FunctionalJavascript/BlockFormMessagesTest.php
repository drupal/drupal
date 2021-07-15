<?php

namespace Drupal\Tests\layout_builder\FunctionalJavascript;

use Behat\Mink\Element\NodeElement;
use Drupal\FunctionalJavascriptTests\WebDriverTestBase;
use Drupal\Tests\contextual\FunctionalJavascript\ContextualLinkClickTrait;
use WebDriver\Exception\UnknownError;

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
  protected $defaultTheme = 'classy';

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->createContentType(['type' => 'bundle_with_section_field']);
  }

  /**
   * Tests that validation messages are shown on the block form.
   */
  public function testValidationMessage() {
    // @todo Work out why this fixes random fails in this test.
    //   https://www.drupal.org/project/drupal/issues/3055982
    $this->getSession()->resizeWindow(800, 1000);
    $assert_session = $this->assertSession();
    $page = $this->getSession()->getPage();

    $this->drupalLogin($this->drupalCreateUser([
      'access contextual links',
      'configure any layout',
      'administer node display',
      'administer node fields',
    ]));
    $field_ui_prefix = 'admin/structure/types/manage/bundle_with_section_field';
    // Enable layout builder.
    $this->drupalGet($field_ui_prefix . '/display/default');
    $this->submitForm(['layout[enabled]' => TRUE], 'Save');
    $this->clickElementWhenClickable($page->findLink('Manage layout'));
    $assert_session->addressEquals($field_ui_prefix . '/display/default/layout');
    $this->clickElementWhenClickable($page->findLink('Add block'));
    $this->assertNotEmpty($assert_session->waitForElementVisible('css', '#drupal-off-canvas .block-categories'));
    $this->clickElementWhenClickable($page->findLink('Powered by Drupal'));
    $this->assertNotEmpty($assert_session->waitForElementVisible('css', '#drupal-off-canvas [name="settings[label]"]'));
    $page->findField('Title')->setValue('');
    $this->clickElementWhenClickable($page->findButton('Add block'));
    $this->assertMessagesDisplayed();
    $page->findField('Title')->setValue('New title');
    $page->pressButton('Add block');
    $block_css_locator = '#layout-builder .block-system-powered-by-block';
    $this->assertNotEmpty($assert_session->waitForElementVisible('css', $block_css_locator));

    $assert_session->assertNoElementAfterWait('css', '#drupal-off-canvas');
    $assert_session->assertWaitOnAjaxRequest();
    $this->drupalGet($this->getUrl());
    $this->clickElementWhenClickable($page->findButton('Save layout'));
    $this->assertNotEmpty($assert_session->waitForElement('css', 'div:contains("The layout has been saved")'));

    // Ensure that message are displayed when configuring an existing block.
    $this->drupalGet($field_ui_prefix . '/display/default/layout');
    $assert_session->assertWaitOnAjaxRequest();
    $this->clickContextualLink($block_css_locator, 'Configure', TRUE);
    $this->assertNotEmpty($assert_session->waitForElementVisible('css', '#drupal-off-canvas [name="settings[label]"]'));
    $page->findField('Title')->setValue('');
    $this->clickElementWhenClickable($page->findButton('Update'));
    $this->assertMessagesDisplayed();
  }

  /**
   * Asserts that the validation messages are shown correctly.
   */
  protected function assertMessagesDisplayed() {
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

  /**
   * Attempts to click an element until it is in a clickable state.
   *
   * @param \Behat\Mink\Element\NodeElement $element
   *   The element to click.
   * @param int $timeout
   *   (Optional) Timeout in milliseconds, defaults to 10000.
   *
   * @todo Replace this method with general solution for random click() test
   *   failures in https://www.drupal.org/node/3032275.
   */
  protected function clickElementWhenClickable(NodeElement $element, $timeout = 10000) {
    $page = $this->getSession()->getPage();

    $result = $page->waitFor($timeout / 1000, function () use ($element) {
      try {
        $element->click();
        return TRUE;
      }
      catch (UnknownError $exception) {
        if (strstr($exception->getMessage(), 'not clickable') === FALSE) {
          // Rethrow any unexpected UnknownError exceptions.
          throw $exception;
        }
        return NULL;
      }
    });
    $this->assertTrue($result);
  }

}
