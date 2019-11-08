<?php

namespace Drupal\Tests\language\Functional;

use Drupal\language\Entity\ConfigurableLanguage;
use Drupal\Tests\BrowserTestBase;

/**
 * Tests breadcrumbs functionality.
 *
 * @group Menu
 */
class LanguageBreadcrumbTest extends BrowserTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['language', 'block', 'filter'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'classy';

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->drupalPlaceBlock('system_breadcrumb_block');
    ConfigurableLanguage::createFromLangcode('de')->save();
    ConfigurableLanguage::createFromLangcode('gsw-berne')->save();
  }

  /**
   * Tests breadcrumbs with URL prefixes.
   */
  public function testBreadCrumbs() {
    // Prepare common base breadcrumb elements.
    $home = ['' => 'Home'];
    $admin = $home + ['admin' => t('Administration')];

    $page = $this->getSession()->getPage();

    // /user/login is the default frontpage which only works for an anonymous
    // user. Access the frontpage in different languages, ensure that no
    // breadcrumb is displayed.
    $this->drupalGet('user/login');
    $breadcrumbs = $page->find('css', '.block-system-breadcrumb-block');
    $this->assertNull($breadcrumbs);

    $this->drupalGet('de/user/login');
    $breadcrumbs = $page->find('css', '.block-system-breadcrumb-block');
    $this->assertNull($breadcrumbs);

    $this->drupalGet('gsw-berne/user/login');
    $breadcrumbs = $page->find('css', '.block-system-breadcrumb-block');
    $this->assertNull($breadcrumbs);

    $admin_user = $this->drupalCreateUser(['access administration pages']);
    $this->drupalLogin($admin_user);

    // Use administration routes to assert that breadcrumb is displayed
    // correctly on pages other than the frontpage.
    $this->drupalGet('admin');
    $breadcrumbs = $page->find('css', '.block-system-breadcrumb-block');
    $this->assertEquals(1, substr_count($breadcrumbs->getText(), 'Home'));
    $this->assertEquals(0, substr_count($breadcrumbs->getText(), 'Administration'));

    $this->drupalGet('de/admin');
    $breadcrumbs = $page->find('css', '.block-system-breadcrumb-block');
    $this->assertEquals(1, substr_count($breadcrumbs->getText(), 'Home'));
    $this->assertEquals(0, substr_count($breadcrumbs->getText(), 'Administration'));

    $this->drupalGet('admin/structure', $admin);
    $breadcrumbs = $page->find('css', '.block-system-breadcrumb-block');
    $this->assertEquals(1, substr_count($breadcrumbs->getText(), 'Home'));
    $this->assertEquals(1, substr_count($breadcrumbs->getText(), 'Administration'));

    $this->drupalGet('de/admin/structure', $admin);
    $breadcrumbs = $page->find('css', '.block-system-breadcrumb-block');
    $this->assertEquals(1, substr_count($breadcrumbs->getText(), 'Home'));
    $this->assertEquals(1, substr_count($breadcrumbs->getText(), 'Administration'));
  }

}
