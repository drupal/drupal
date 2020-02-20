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

    // Add views-form-views-form to the skipped templates array. It is
    // registered via views_theme() in views.module, but does not represent an
    // actual template.
    $templates_to_skip[] = 'views-form-views-form';

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
        'to-skip' => [],
      ],
      'seven' => [
        'theme-name' => 'seven',
        'to-skip' => [],
      ],
      'claro' => [
        'theme-name' => 'claro',
        'to-skip' => [],
      ],
      'bartik' => [
        'theme-name' => 'bartik',
        'to-skip' => [],
      ],
    ];
  }

}
