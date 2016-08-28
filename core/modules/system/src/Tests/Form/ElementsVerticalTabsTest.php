<?php

namespace Drupal\system\Tests\Form;

use Drupal\Component\Utility\SafeMarkup;
use Drupal\simpletest\WebTestBase;
use Drupal\Component\Serialization\Json;

/**
 * Tests the vertical_tabs form element for expected behavior.
 *
 * @group Form
 */
class ElementsVerticalTabsTest extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('form_test');

  /**
   * A user with permission to access vertical_tab_test_tabs.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $adminUser;

  /**
   * A normal user.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $webUser;

  protected function setUp() {
    parent::setUp();

    $this->adminUser = $this->drupalCreateUser(array('access vertical_tab_test tabs'));
    $this->webUser = $this->drupalCreateUser();
    $this->drupalLogin($this->adminUser);
  }

  /**
   * Ensures that vertical-tabs.js is included before collapse.js.
   *
   * Otherwise, collapse.js adds "SHOW" or "HIDE" labels to the tabs.
   */
  function testJavaScriptOrdering() {
    $this->drupalGet('form_test/vertical-tabs');
    $position1 = strpos($this->content, 'core/misc/vertical-tabs.js');
    $position2 = strpos($this->content, 'core/misc/collapse.js');
    $this->assertTrue($position1 !== FALSE && $position2 !== FALSE && $position1 < $position2, 'vertical-tabs.js is included before collapse.js');
  }

  /**
   * Ensures that vertical tab markup is not shown if user has no tab access.
   */
  function testWrapperNotShownWhenEmpty() {
    // Test admin user can see vertical tabs and wrapper.
    $this->drupalGet('form_test/vertical-tabs');
    $wrapper = $this->xpath("//div[@data-vertical-tabs-panes]");
    $this->assertTrue(isset($wrapper[0]), 'Vertical tab panes found.');

    // Test wrapper markup not present for non-privileged web user.
    $this->drupalLogin($this->webUser);
    $this->drupalGet('form_test/vertical-tabs');
    $wrapper = $this->xpath("//div[@data-vertical-tabs-panes]");
    $this->assertFalse(isset($wrapper[0]), 'Vertical tab wrappers are not displayed to unprivileged users.');
  }

  /**
   * Ensures that default vertical tab is correctly selected.
   */
  function testDefaultTab() {
    $this->drupalGet('form_test/vertical-tabs');
    $this->assertFieldByName('vertical_tabs__active_tab', 'edit-tab3', t('The default vertical tab is correctly selected.'));
  }

  /**
   * Ensures that vertical tab form values are cleaned.
   */
  function testDefaultTabCleaned() {
    $values = Json::decode($this->drupalPostForm('form_test/form-state-values-clean', [], t('Submit')));
    $this->assertFalse(isset($values['vertical_tabs__active_tab']), SafeMarkup::format('%element was removed.', ['%element' => 'vertical_tabs__active_tab']));
  }

}
