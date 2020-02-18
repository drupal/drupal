<?php

namespace Drupal\KernelTests\Core\Theme;

use Drupal\Core\Theme\Registry;
use Drupal\KernelTests\KernelTestBase;

/**
 * Tests that themes do not depend on Classy templates.
 *
 * These tests exist to facilitate the process of decoupling themes from
 * Classy. The decoupling process includes eliminating the use of Classy
 * templates by providing theme-specific versions of templates that would
 * otherwise be inherited from Classy.
 *
 * This test can be removed once the Classy decoupling is complete, and it will
 * fail if it is still present when Classy is removed from Drupal core.
 *
 * @group Theme
 */
class ThemesNotUsingClassyTemplatesTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['system', 'user'];

  /**
   * The theme handler.
   *
   * @var \Drupal\Core\Extension\ThemeHandlerInterface
   */
  protected $themeHandler;

  /**
   * Templates that are identical in Stable, which means they can be skipped.
   *
   * In several cases, the templates in Classy are identical to those in
   * Stable. This means that a theme would behave identically even if those
   * templates were removed from Classy. They are effectively decoupled from
   * Classy already as they rely on no functionality unique to Classy.
   *
   * @var string[]
   *
   * @see \Drupal\Tests\Core\Theme\ClassyTemplatesIdenticalToStableTest for a
   *   test that confirms that these templates are identical.
   */
  protected $templatesSkippableBecauseIdenticalToStable = [
    'file-upload-help',
    'file-widget-multiple',
    'image-formatter',
    'image-style',
    'checkboxes',
    'confirm-form',
    'container',
    'dropbutton-wrapper',
    'field-multiple-value-form',
    'form',
    'input',
    'select',
    'links',
    'menu-local-action',
    'pager',
    'vertical-tabs',
    'views-view-grid',
    'views-view-list',
    'views-view-mapping-test',
    'views-view-opml',
    'views-view-row-opml',
    'views-view-rss',
    'views-view-unformatted',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->themeHandler = $this->container->get('theme_handler');
    $this->container->get('theme_installer')->install([
      'umami',
      'bartik',
      'seven',
      'claro',
    ]);
    // Enable all modules so every template is present in the theme registry.
    // This makes it possible to check the source of every template and
    // determine if they come from Classy.
    $this->installAllModules();
  }

  /**
   * Installs all core modules.
   */
  protected function installAllModules() {
    // Enable all core modules.
    $all_modules = $this->container->get('extension.list.module')->getList();
    $all_modules = array_filter($all_modules, function ($module) {
      // Filter contrib, hidden, experimental, already enabled modules, and
      // modules in the Testing package.
      if ($module->origin !== 'core' || !empty($module->info['hidden']) || $module->status === TRUE || $module->info['package'] === 'Testing' || $module->info['package'] === 'Core (Experimental)') {
        return FALSE;
      }
      return TRUE;
    });
    $all_modules = array_keys($all_modules);
    $module_installer = $this->container->get('module_installer');
    $module_installer->install($all_modules);
  }

  /**
   * Ensures that themes are not inheriting templates from Classy.
   *
   * @param string $theme
   *   The theme to test.
   * @param string[] $templates_to_skip
   *   Templates that will not be tested.
   *
   * @dataProvider providerTestThemesTemplatesNotClassy
   */
  public function testThemesTemplatesNotClassy($theme, array $templates_to_skip) {
    // Get every template available to the theme being tested.
    $theme_registry = new Registry($this->root, \Drupal::cache(), \Drupal::lock(), \Drupal::moduleHandler(), $this->themeHandler, \Drupal::service('theme.initialization'), $theme);
    $theme_registry->setThemeManager(\Drupal::theme());
    $theme_registry_full = $theme_registry->get();

    // Loop through every template available to the current theme, confirm it
    // does not come from Classy, does not attach Classy libraries, and does not
    // extend or include Classy templates.
    foreach ($theme_registry_full as $info) {
      if (isset($info['template'])) {
        $template_name = $info['template'];

        if (in_array($template_name, $templates_to_skip) || in_array($template_name, $this->templatesSkippableBecauseIdenticalToStable)) {
          continue;
        }

        $template_contents = file_get_contents("{$this->root}/{$info['path']}/$template_name.html.twig");

        // Confirm template does not come from Classy.
        $this->assertFalse($info['theme path'] === 'core/themes/classy', "$theme is inheriting $template_name from Classy.");

        // Confirm template does not include or extend Classy templates.
        preg_match_all('/(extends|include)\s+(\'|")@classy/', $template_contents, $classy_extend_include_matches);
        $this->assertEmpty($classy_extend_include_matches[0], "The template: '$template_name' in the theme: '$theme' includes or extends a Classy template.");

        // Confirm template does not attach a Classy library.
        preg_match_all('/attach_library\((\'|")classy\/.+(\'|")\)/', $template_contents, $classy_extend_library_matches);
        $this->assertEmpty($classy_extend_library_matches[0], "The template: '$template_name' in the theme: '$theme' attaches a Classy library.");
      }
    }
  }

  /**
   * Data provider for testThemesTemplatesNotClassy().
   *
   * @return array
   *   Array of test cases using these keys:
   *    -'theme-name': The machine name of the theme being tested.
   *    -'to-skip': Templates that will skipped by the test.
   */
  public function providerTestThemesTemplatesNotClassy() {
    // Each item provides the theme name and an array of templates to skip. The
    // templates in the to-skip array are ones that have not yet been decoupled
    // from Classy. When a template is properly decoupled from Classy, it can be
    // removed from to-skip. If this test passes with an empty to-skip array,
    // this is confirmation that the templates are fully decoupled form Classy.
    return [
      'umami' => [
        'theme-name' => 'umami',
        'to-skip' => [
          'aggregator-feed',
          'aggregator-item',
          'block',
          'book-export-html',
          'book-all-books-block',
          'book-tree',
          'book-navigation',
          'book-node-export-html',
          'comment',
          'field--comment',
          'file-managed-file',
          'file-audio',
          'file-link',
          'file-video',
          'filter-guidelines',
          'filter-tips',
          'text-format-wrapper',
          'filter-caption',
          'form-element-label',
          'forum-list',
          'forum-icon',
          'forums',
          'forum-submitted',
          'help-section',
          'image-widget',
          'link-formatter-link-separate',
          'media-embed-error',
          'media',
          'media--media-library',
          'media-library-wrapper',
          'media-library-item',
          'views-view-unformatted--media-library',
          'item-list--search-results',
          'links--node',
          'field--text',
          'field--text-long',
          'field--text-with-summary',
          'links--media-library-menu',
          'container--media-library-content',
          'media-library-item--small',
          'container--media-library-widget-selection',
          'block--local-tasks-block',
          'block--search-form-block',
          'node-edit-form',
          'node-add-list',
          'field--node--uid',
          'field--node--title',
          'field--node--created',
          'rdf-metadata',
          'search-result',
          'progress-bar',
          'datetime-wrapper',
          'fieldset',
          'datetime-form',
          'textarea',
          'details',
          'form-element',
          'radios',
          'item-list',
          'table',
          'maintenance-page',
          'html',
          'region',
          'breadcrumb',
          'menu-local-tasks',
          'page-title',
          'mark',
          'time',
          'image',
          'field',
          'block--local-actions-block',
          'block--system-menu-block',
          'taxonomy-term',
          'toolbar',
          'username',
          'user',
          'views-form-views-form',
          'views-mini-pager',
          'views-exposed-form',
          'views-view-grouping',
          'views-view-summary',
          'views-view-table',
          'views-view-row-rss',
          'views-view-summary-unformatted',
          'views-view',
        ],
      ],
      'seven' => [
        'theme-name' => 'seven',
        'to-skip' => [
          'aggregator-feed',
          'aggregator-item',
          'block--system-branding-block',
          'block',
          'breadcrumb',
          'book-export-html',
          'book-all-books-block',
          'book-tree',
          'book-navigation',
          'book-node-export-html',
          'comment',
          'field--comment',
          'file-managed-file',
          'file-audio',
          'file-link',
          'file-video',
          'filter-guidelines',
          'filter-tips',
          'text-format-wrapper',
          'filter-caption',
          'form-element-label',
          'forum-list',
          'forum-icon',
          'forums',
          'forum-submitted',
          'help-section',
          'link-formatter-link-separate',
          'media-embed-error',
          'media',
          'media--media-library',
          'media-library-wrapper',
          'media-library-item',
          'views-view-unformatted--media-library',
          'item-list--search-results',
          'links--node',
          'field--text',
          'field--text-long',
          'field--text-with-summary',
          'links--media-library-menu',
          'container--media-library-content',
          'media-library-item--small',
          'container--media-library-widget-selection',
          'block--local-tasks-block',
          'block--search-form-block',
          'node',
          'field--node--uid',
          'field--node--title',
          'field--node--created',
          'rdf-metadata',
          'search-result',
          'progress-bar',
          'status-messages',
          'datetime-wrapper',
          'fieldset',
          'datetime-form',
          'textarea',
          'form-element',
          'radios',
          'item-list',
          'table',
          'html',
          'region',
          'menu',
          'menu-local-task',
          'page-title',
          'mark',
          'time',
          'image',
          'field',
          'block--system-menu-block',
          'taxonomy-term',
          'toolbar',
          'username',
          'user',
          'views-form-views-form',
          'views-mini-pager',
          'views-exposed-form',
          'views-view-grouping',
          'views-view-summary',
          'views-view-table',
          'views-view-row-rss',
          'views-view-summary-unformatted',
          'views-view',
        ],
      ],
      'claro' => [
        'theme-name' => 'claro',
        'to-skip' => [
          'aggregator-feed',
          'aggregator-item',
          'block--system-branding-block',
          'block',
          'book-export-html',
          'book-all-books-block',
          'book-tree',
          'book-navigation',
          'book-node-export-html',
          'comment',
          'field--comment',
          'file-audio',
          'file-video',
          'filter-caption',
          'forum-list',
          'forum-icon',
          'forums',
          'forum-submitted',
          'help-section',
          'link-formatter-link-separate',
          'media-embed-error',
          'media',
          'media--media-library',
          'media-library-wrapper',
          'media-library-item',
          'views-view-unformatted--media-library',
          'item-list--search-results',
          'links--node',
          'field--text',
          'field--text-long',
          'field--text-with-summary',
          'links--media-library-menu',
          'container--media-library-content',
          'media-library-item--small',
          'container--media-library-widget-selection',
          'block--local-tasks-block',
          'block--search-form-block',
          'node',
          'field--node--uid',
          'field--node--title',
          'field--node--created',
          'rdf-metadata',
          'search-result',
          'progress-bar',
          'textarea',
          'radios',
          'item-list',
          'table',
          'html',
          'region',
          'menu',
          'page-title',
          'mark',
          'time',
          'image',
          'field',
          'block--local-actions-block',
          'block--system-menu-block',
          'taxonomy-term',
          'toolbar',
          'username',
          'user',
          'views-form-views-form',
          'views-view-grouping',
          'views-view-summary',
          'views-view-table',
          'views-view-row-rss',
          'views-view-summary-unformatted',
          'views-view',
        ],
      ],
      'bartik' => [
        'theme-name' => 'bartik',
        'to-skip' => [
          'aggregator-feed',
          'aggregator-item',
          'block--system-branding-block',
          'block',
          'book-export-html',
          'book-all-books-block',
          'book-tree',
          'book-navigation',
          'book-node-export-html',
          'comment',
          'field--comment',
          'file-managed-file',
          'file-audio',
          'file-link',
          'file-video',
          'filter-guidelines',
          'filter-tips',
          'text-format-wrapper',
          'filter-caption',
          'forum-list',
          'forum-icon',
          'forums',
          'forum-submitted',
          'help-section',
          'image-widget',
          'link-formatter-link-separate',
          'media-embed-error',
          'media',
          'media--media-library',
          'media-library-wrapper',
          'media-library-item',
          'views-view-unformatted--media-library',
          'item-list--search-results',
          'links--node',
          'field--text',
          'field--text-long',
          'field--text-with-summary',
          'links--media-library-menu',
          'container--media-library-content',
          'media-library-item--small',
          'container--media-library-widget-selection',
          'block--local-tasks-block',
          'node-edit-form',
          'node-add-list',
          'field--node--uid',
          'field--node--title',
          'field--node--created',
          'rdf-metadata',
          'search-result',
          'progress-bar',
          'datetime-wrapper',
          'fieldset',
          'datetime-form',
          'textarea',
          'details',
          'form-element',
          'form-element-label',
          'radios',
          'item-list',
          'table',
          'page',
          'maintenance-page',
          'html',
          'region',
          'menu',
          'menu-local-task',
          'breadcrumb',
          'menu-local-tasks',
          'mark',
          'time',
          'image',
          'field',
          'block--local-actions-block',
          'taxonomy-term',
          'toolbar',
          'username',
          'user',
          'views-mini-pager',
          'views-exposed-form',
          'views-form-views-form',
          'views-view-grouping',
          'views-view-summary',
          'views-view-table',
          'views-view-row-rss',
          'views-view-summary-unformatted',
          'views-view',
        ],
      ],
    ];
  }

}
