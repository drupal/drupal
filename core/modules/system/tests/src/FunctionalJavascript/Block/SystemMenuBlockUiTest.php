<?php

declare(strict_types=1);

namespace Drupal\Tests\system\FunctionalJavascript\Block;

use Drupal\Core\Url;
use Drupal\FunctionalJavascriptTests\WebDriverTestBase;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Test the #states on the system menu block form.
 */
#[Group('Block')]
#[RunTestsInSeparateProcesses]
class SystemMenuBlockUiTest extends WebDriverTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['block', 'system'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    // Create and log in an administrative user.
    $user = $this->drupalCreateUser([
      'administer blocks',
      'access administration pages',
    ]);
    $this->drupalLogin($user);
  }

  /**
   * Tests that add_active_trail_class field states based on other form values.
   */
  public function testSystemMenuBlockForm(): void {
    $this->drupalGet(Url::fromRoute('block.admin_add', [
      'plugin_id' => 'system_menu_block:admin',
      'theme' => 'stark',
    ]));
    $page = $this->getSession()->getPage();
    $page->findById('edit-settings-menu-levels')->click();
    $levelField = $page->findField('settings[level]');
    $depthField = $page->findField('settings[depth]');
    // On first form when adding the menu block, the level should be set to 1,
    // "expand_all_items" unchecked, and "add_active_trail_class" checkbox is
    // required.
    $this->assertEquals('1', $levelField->getValue());
    $this->assertEquals('0', $depthField->getValue());
    $expandField = $page->findField('settings[expand_all_items]');
    $this->assertFalse($expandField->isChecked());
    $addActiveTrailField = $page->findField('settings[add_active_trail_class]');
    $this->assertTrue($addActiveTrailField->hasAttribute('required'));

    // Setting the depth value to '1' should mean the checkbox is no longer
    // required.
    $depthField->setValue('1');
    $this->assertFalse($addActiveTrailField->hasAttribute('required'));

    // Clicking on "expand_all_items" makes "add_active_trail_class" not required
    // when level is 1.
    $depthField->setValue('0');
    $this->assertTrue($addActiveTrailField->hasAttribute('required'));
    $expandField->click();
    $this->assertFalse($addActiveTrailField->hasAttribute('required'));

    // Setting level to a value greater than one makes "add_active_trail_class"
    // required.
    $levelField->setValue('2');
    $this->assertTrue($addActiveTrailField->hasAttribute('required'));
  }

}
