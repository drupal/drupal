<?php

namespace Drupal\Tests\system\Functional\Form;

use Drupal\Component\Serialization\Json;
use Drupal\Tests\BrowserTestBase;

/**
 * Tests the vertical_tabs form element for expected behavior.
 *
 * @group Form
 */
class ElementsVerticalTabsTest extends BrowserTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = ['form_test'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

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

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->adminUser = $this->drupalCreateUser([
      'access vertical_tab_test tabs',
    ]);
    $this->webUser = $this->drupalCreateUser();
    $this->drupalLogin($this->adminUser);
  }

  /**
   * Ensures that vertical tab markup is not shown if user has no tab access.
   */
  public function testWrapperNotShownWhenEmpty() {
    // Test admin user can see vertical tabs and wrapper.
    $this->drupalGet('form_test/vertical-tabs');
    $this->assertSession()->elementExists('xpath', "//div[@data-vertical-tabs-panes]");

    // Test wrapper markup not present for non-privileged web user.
    $this->drupalLogin($this->webUser);
    $this->drupalGet('form_test/vertical-tabs');
    $this->assertSession()->elementNotExists('xpath', "//div[@data-vertical-tabs-panes]");
  }

  /**
   * Ensures that default vertical tab is correctly selected.
   */
  public function testDefaultTab() {
    $this->drupalGet('form_test/vertical-tabs');
    $this->assertSession()->elementAttributeContains('css', 'input[name="vertical_tabs__active_tab"]', 'value', 'edit-tab3');
  }

  /**
   * Ensures that vertical tab form values are cleaned.
   */
  public function testDefaultTabCleaned() {
    $this->drupalGet('form_test/form-state-values-clean');
    $this->submitForm([], 'Submit');
    $values = Json::decode($this->getSession()->getPage()->getContent());
    $this->assertFalse(isset($values['vertical_tabs__active_tab']), 'vertical_tabs__active_tab was removed.');
  }

}
