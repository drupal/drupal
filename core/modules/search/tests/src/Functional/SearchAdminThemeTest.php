<?php

declare(strict_types=1);

namespace Drupal\Tests\search\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Verify the search results using administration theme for specific plugins.
 *
 * @see \Drupal\search\Annotation\SearchPlugin::$use_admin_theme
 * @see \Drupal\search\Routing\SearchPageRoutes::routes()
 * @see \Drupal\Tests\system\Functional\System\ThemeTest::testAdministrationTheme()
 *
 * @group search
 */
class SearchAdminThemeTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'help',
    'node',
    'search',
    'search_extra_type',
    'user',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * The administration theme name.
   *
   * @var string
   */
  protected $adminTheme = 'claro';

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Install an administration theme to make sure it used for search results.
    \Drupal::service('theme_installer')->install([$this->adminTheme]);
    \Drupal::configFactory()
      ->getEditable('system.theme')
      ->set('admin', $this->adminTheme)
      ->save();
    // Create searching user.
    $user = $this->drupalCreateUser([
      'access content',
      'search content',
      'access help pages',
      'access user profiles',
      'view the administration theme',
    ]);
    // Log in with sufficient privileges.
    $this->drupalLogin($user);
  }

  /**
   * Tests that search results could be displayed in administration theme.
   *
   * @see \Drupal\node\Plugin\Search\NodeSearch
   * @see \Drupal\search_extra_type\Plugin\Search\SearchExtraTypeSearch
   * @see \Drupal\user\Plugin\Search\UserSearch
   */
  public function testSearchUsingAdminTheme(): void {
    /** @var \Drupal\search\SearchPageRepositoryInterface $repository */
    $repository = \Drupal::service('search.search_page_repository');
    $pages = $repository->getActiveSearchPages();
    // Test default configured pages.
    $page_ids = [
      'node_search' => FALSE,
      'dummy_search_type' => TRUE,
      'help_search' => TRUE,
      'user_search' => FALSE,
    ];
    foreach ($page_ids as $page_id => $use_admin_theme) {
      $plugin = $pages[$page_id]->getPlugin();
      $path = 'search/' . $pages[$page_id]->getPath();
      $this->drupalGet($path);
      $session = $this->assertSession();
      // Make sure help plugin rendered help link.
      $path_help = $path . '/help';
      $session->linkByHrefExists($path_help);
      $this->assertSame($use_admin_theme, $plugin->usesAdminTheme());
      $this->assertAdminTheme($use_admin_theme);
      // Make sure that search help also rendered in admin theme.
      $this->drupalGet($path_help);
      $this->assertAdminTheme($use_admin_theme);
    }
  }

  /**
   * Asserts whether an administrative theme's used for the loaded page.
   *
   * @param bool $is_admin
   *   TRUE to test for administrative theme, FALSE otherwise.
   *
   * @internal
   */
  protected function assertAdminTheme(bool $is_admin): void {
    if ($is_admin) {
      $this->assertSession()->responseContains('core/themes/' . $this->adminTheme);
    }
    else {
      $this->assertSession()->responseNotContains('core/themes/' . $this->adminTheme);
    }
  }

}
