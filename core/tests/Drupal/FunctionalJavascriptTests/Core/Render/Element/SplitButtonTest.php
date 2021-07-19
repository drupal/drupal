<?php

namespace Drupal\FunctionalJavascriptTests\Core\Render\Element;

use Drupal\FunctionalJavascriptTests\WebDriverTestBase;

/**
 * Tests for the splitbutton render element.
 *
 * @group Render
 */
class SplitButtonTest extends WebDriverTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['splitbutton_test', 'system'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Helper array for testing keyboard navigation.
   *
   * @var array
   *  Key is the key name, value is the ascii value.
   */
  protected $keys = [
    'tab' => 9,
    'esc' => 27,
    'pageUp' => 33,
    'pageDown' => 34,
    'up' => 38,
    'down' => 40,
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $user = $this->createUser(['access content']);
    $this->drupalLogin($user);
    $this->drupalGet('/splitbuttons');
  }

  /**
   * General splitbutton tests.
   *
   * @dataProvider providerTestSplitbuttons
   */
  public function testSplitbuttons($theme_name) {
    if (!empty($theme_name)) {
      $this->container->get('theme_installer')->install([$theme_name]);
      $this->config('system.theme')->set('default', $theme_name)->save();
      $this->drupalGet('/splitbuttons');
    }

    $assert_session = $this->assertSession();

    $button_types = [
      'default',
      'primary',
      'danger',
      'small',
      'extrasmall',
    ];

    $scenarios = [
      'splitbutton_link_first' => [
        'primary_selector' => 'a.splitbutton__main-button',
        'number_links' => 3,
        'number_submit' => 2,
        'number_items' => 5,
      ],
      'splitbutton_submit_first' => [
        'primary_selector' => 'input.splitbutton__main-button',
        'number_links' => 4,
        'number_submit' => 2,
        'number_items' => 6,
      ],
      'splitbutton_with_title' => [
        'primary_selector' => '[data-drupal-splitbutton-trigger]',
        'number_links' => 4,
        'number_submit' => 2,
        'number_items' => 6,
      ],
      'dropbutton_converted' => [
        'primary_selector' => 'a.splitbutton__main-button--link',
        'number_links' => 3,
        'number_submit' => 0,
        'number_items' => 3,
      ],
    ];

    foreach ($button_types as $button_type) {
      foreach ($scenarios as $scenario => $scenario_data) {
        $test_key = "$scenario-$button_type";
        $splitbutton_selector = "[data-splitbutton-test-id=\"$test_key\"]";
        $splitbutton = $assert_session->waitForElement('css', $splitbutton_selector);
        $this->assertNotNull($splitbutton, "Element $splitbutton_selector");
        $toggle = $splitbutton->find('css', '[data-drupal-splitbutton-trigger]');
        $this->assertNotNull($toggle);
        $this->assertFalse($splitbutton->hasAttribute('data-drupal-splitbutton-open'));
        $toggle->press();
        $open_splitbutton = $assert_session->waitForElement('css', $splitbutton_selector . '[data-drupal-splitbutton-open]');
        $this->assertNotNull($open_splitbutton);
        $operation_list = $assert_session->waitForElementVisible('css', "$splitbutton_selector [data-drupal-splitbutton-item-list]");
        $this->assertNotNull($operation_list, "$splitbutton_selector [data-drupal-splitbutton-item-list]");
        $operation_list_links = $operation_list->findAll('css', 'a');
        $operation_list_submits = $operation_list->findAll('css', 'input');
        $this->assertCount($scenario_data['number_links'], $operation_list_links);
        $this->assertCount($scenario_data['number_submit'], $operation_list_submits);

        $toggle->press();
        $closed_splitbutton = $assert_session->waitForElement('css', "$splitbutton_selector:not([data-drupal-splitbutton-open])");
        $this->assertNotNull($closed_splitbutton);

        if (!empty($theme_name)) {
          // Confirm expected classes are added to the primary button.
          $primary_button_selector = $scenario_data['primary_selector'];
          $assert_session->elementExists('css', "$splitbutton_selector $primary_button_selector");

          // Confirm splitbutton type classes are added to toggle.
          if ($button_type !== 'default') {
            $this->assertTrue($toggle->hasClass("button--$button_type"));
            if ($scenario !== 'splitbutton_with_title') {
              $assert_session->elementExists('css', "$splitbutton_selector .splitbutton__main-button.button--$button_type");
            }
          }
        }
      }
    }

    // Confirm classes are correctly added to splitbuttons with multiple types.
    // The conditional is present because these classes are not added in Stark.
    if (!empty($theme_name)) {
      // Confirm classes were properly added to splitbuttons that have multiple
      // types.
      $assert_session->elementExists('css', '[data-splitbutton-test-id="splitbutton-primary-small"].splitbutton--primary.splitbutton--small');
      $assert_session->elementExists('css', '[data-splitbutton-test-id="splitbutton-primary-small"] .splitbutton__main-button.button.button--primary.button--small');
      $assert_session->elementExists('css', '[data-splitbutton-test-id="splitbutton-primary-small"] [data-drupal-splitbutton-trigger].button.button--primary.button--small');

      $assert_session->elementExists('css', '[data-splitbutton-test-id="splitbutton-danger-extrasmall"].splitbutton--danger.splitbutton--extrasmall');
      $assert_session->elementExists('css', '[data-splitbutton-test-id="splitbutton-danger-extrasmall"] .splitbutton__main-button.button.button--danger.button--extrasmall');
      $assert_session->elementExists('css', '[data-splitbutton-test-id="splitbutton-danger-extrasmall"] [data-drupal-splitbutton-trigger].button.button--danger.button--extrasmall');
    }

    // Test single-item splitbuttons.
    $test_ids = [
      'splitbutton-single-default',
      'splitbutton-single-danger',
    ];
    foreach ($test_ids as $test_id) {
      $splitbutton = $assert_session->waitForElement('css', "[data-splitbutton-test-id=\"$test_id\"]");
      $this->assertFalse($splitbutton->hasAttribute('data-drupal-splitbutton-multiple'));
      $this->assertFalse($splitbutton->hasAttribute('data-drupal-splitbutton-enabled'));
      $this->assertTrue($splitbutton->hasAttribute('data-drupal-splitbutton-single'));
      $this->assertNull($splitbutton->find('css', '[data-drupal-splitbutton-item-list]'));
    }
  }

  /**
   * Data provider for testSplitbuttons().
   *
   * @return string[][]
   *   An array of themes to install for the test.
   */
  public function providerTestSplitbuttons() {
    return [
      'stark' => [''],
      'claro' => ['claro'],
      'seven' => ['seven'],
      'bartik' => ['bartik'],
    ];
  }

  /**
   * Tests keyboard navigation of splitbutton.
   *
   * Many keyboard events can't be simulated in FunctionalJavascript tests, this
   * covers those that can: Navigating to new items via arrow keys and page up/
   * page down, and closing open menus with escape and tab.
   */
  public function testSplitbuttonKeyboard() {
    $assert_session = $this->assertSession();

    // Expected link or input test of each menu item.
    $menu_item_values = [
      'Link Two',
      'Link Three',
      'Link Four',
      'Added Button',
      'Another Added Button',
    ];

    // Find splitbutton and toggle, confirm splitbutton is closed.
    $splitbutton = $assert_session->elementExists('css', '[data-splitbutton-test-id="splitbutton_link_first-default"]');
    $this->assertFalse($splitbutton->hasAttribute('data-drupal-splitbutton-open'));
    $toggle = $splitbutton->find('css', '[data-drupal-splitbutton-trigger]');
    $this->assertNotNull($toggle);

    // Open splitbutton and add newly visible menu items to a variable.
    $toggle->press();
    $this->assertNotNull($assert_session->waitForElementVisible('css', '[data-splitbutton-test-id="splitbutton_link_first-default"] [data-drupal-splitbutton-item-list]'));
    $this->assertTrue($splitbutton->hasAttribute('data-drupal-splitbutton-open'));
    $menu_items = $splitbutton->findAll('css', '[data-drupal-splitbutton-item]');
    $this->assertCount(5, $menu_items);

    // Use down key to select first item in the menu.
    $toggle->keyDown($this->keys['down']);
    $toggle->keyUp($this->keys['down']);

    // Each item in the array is a keyboard key to be pressed, and the index of
    // the menu item that should be focused after that keypress.
    $key_steps = [
      [
        'key' => $this->keys['down'],
        'expected_destination' => 1,
      ],
      [
        'key' => $this->keys['down'],
        'expected_destination' => 2,
      ],
      [
        'key' => $this->keys['down'],
        'expected_destination' => 3,
      ],
      [
        'key' => $this->keys['up'],
        'expected_destination' => 2,
      ],
      [
        'key' => $this->keys['up'],
        'expected_destination' => 1,
      ],
    ];

    // Script that finds the focused element and returns the inner text or its
    // value, depending on the type of element it is.
    $script = <<<EndOfScript
(document.activeElement.tagName === 'INPUT') ? document.activeElement.getAttribute("value") : document.activeElement.innerText
EndOfScript;

    $focused_element_text = $this->getSession()->evaluateScript($script);

    // Navigate through the menu via various keypresses and confirm it moves
    // focus to the expected element.
    foreach ($key_steps as $step) {
      $menu_items[array_search($focused_element_text, $menu_item_values)]->keyDown($step['key']);
      $menu_items[array_search($focused_element_text, $menu_item_values)]->keyUp($step['key']);
      $focused_element_text = $this->getSession()->evaluateScript($script);
      $this->assertEquals($step['expected_destination'], array_search($focused_element_text, $menu_item_values));
    }

    // Pressing the escape key should close the menu and return focus to the
    // toggle.
    $menu_items[array_search($focused_element_text, $menu_item_values)]->keyDown($this->keys['esc']);
    $menu_items[array_search($focused_element_text, $menu_item_values)]->keyUp($this->keys['esc']);
    $this->assertNotNull($assert_session->waitForElement('css', '[data-splitbutton-test-id="splitbutton_link_first-default"]:not([data-drupal-splitbutton-open])'));
    $this->assertJsCondition('document.querySelector("[data-drupal-splitbutton-trigger=\'splitbutton\']") === document.activeElement');

    // Reopen the menu.
    $toggle->press();
    $this->assertNotNull($assert_session->waitForElementVisible('css', '[data-splitbutton-test-id="splitbutton_link_first-default"] [data-drupal-splitbutton-item-list]'));
    $this->assertTrue($splitbutton->hasAttribute('data-drupal-splitbutton-open'));

    // Navigate into the menu with the up key, this should focus the last item.
    $toggle->keyDown($this->keys['up']);
    $toggle->keyUp($this->keys['up']);
    $focused_element_text = $this->getSession()->evaluateScript($script);
    $this->assertEquals(4, array_search($focused_element_text, $menu_item_values));
  }

  /**
   * Test a custom element extending splitbutton.
   *
   * The custom element creates a dropdown. It's essentially a splitbutton
   * without a primary action. It's also an element that may be a good addition
   * to Drupal core once Splitbutton is added.
   */
  public function testElementExtendingSplitbutton() {
    $assert_session = $this->assertSession();
    $custom_splitbutton = $assert_session->elementExists('css', '[data-drupal-selector="edit-dropbutton-that-extends-splitbutton"]');
    $toggle = $assert_session->elementExists('css', '[data-drupal-selector="edit-dropbutton-that-extends-splitbutton"] button');

    // Confirm that the existing key listener will work on a text input. In this
    // case, the down arrow should open the item list.
    $toggle->keyDown($this->keys['down']);
    $focused_element_text = $this->getSession()->evaluateScript('document.activeElement.innerText');
    $this->assertEquals('First Dropdown Item', $focused_element_text);

    $splitbutton_items = $custom_splitbutton->findAll('css', 'li');
    $this->assertCount(3, $splitbutton_items);
  }

}
