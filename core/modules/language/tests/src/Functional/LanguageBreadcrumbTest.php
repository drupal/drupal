<?php

declare(strict_types=1);

namespace Drupal\Tests\language\Functional;

use Drupal\language\Entity\ConfigurableLanguage;
use Drupal\Tests\BrowserTestBase;
use Drupal\Tests\system\Functional\Menu\AssertBreadcrumbTrait;

/**
 * Tests breadcrumbs functionality.
 *
 * @group Menu
 */
class LanguageBreadcrumbTest extends BrowserTestBase {

  use AssertBreadcrumbTrait;

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = ['language', 'block', 'filter'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->drupalPlaceBlock('system_breadcrumb_block');
    ConfigurableLanguage::createFromLangcode('de')->save();
    ConfigurableLanguage::createFromLangcode('gsw-berne')->save();
  }

  /**
   * Tests breadcrumbs with URL prefixes.
   */
  public function testBreadCrumbs(): void {
    // /user/login is the default frontpage which only works for an anonymous
    // user. Access the frontpage in different languages, ensure that no
    // breadcrumb is displayed.
    $this->assertBreadcrumb('user/login', []);
    $this->assertBreadcrumb('de/user/login', []);
    $this->assertBreadcrumb('gsw-berne/user/login', []);

    $admin_user = $this->drupalCreateUser(['access administration pages', 'administer blocks']);
    $this->drupalLogin($admin_user);

    // Use administration routes to assert that breadcrumb is displayed
    // correctly on pages other than the frontpage.
    $this->assertBreadcrumb('admin', [
      '' => 'Home',
    ]);
    $this->assertBreadcrumb('de/admin', [
      'de' => 'Home',
    ]);

    $this->assertBreadcrumb('admin/structure', [
      '' => 'Home',
      'admin' => 'Administration',
    ]);
    $this->assertBreadcrumb('de/admin/structure', [
      'de' => 'Home',
      'de/admin' => 'Administration',
    ]);
  }

}
