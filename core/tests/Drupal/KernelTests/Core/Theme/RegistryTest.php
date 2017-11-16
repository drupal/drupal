<?php

namespace Drupal\KernelTests\Core\Theme;

use Drupal\Core\Path\CurrentPathStack;
use Drupal\Core\Path\PathMatcherInterface;
use Drupal\Core\Theme\Registry;
use Drupal\Core\Utility\ThemeRegistry;
use Drupal\KernelTests\KernelTestBase;

/**
 * Tests the behavior of the ThemeRegistry class.
 *
 * @group Theme
 */
class RegistryTest extends KernelTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['theme_test', 'system'];

  protected $profile = 'testing';

  /**
   * Tests the behavior of the theme registry class.
   */
  public function testRaceCondition() {
    // The theme registry is not marked as persistable in case we don't have a
    // proper request.
    \Drupal::request()->setMethod('GET');
    $cid = 'test_theme_registry';

    // Directly instantiate the theme registry, this will cause a base cache
    // entry to be written in __construct().
    $cache = \Drupal::cache();
    $lock_backend = \Drupal::lock();
    $registry = new ThemeRegistry($cid, $cache, $lock_backend, ['theme_registry'], $this->container->get('module_handler')->isLoaded());

    $this->assertTrue(\Drupal::cache()->get($cid), 'Cache entry was created.');

    // Trigger a cache miss for an offset.
    $this->assertTrue($registry->get('theme_test_template_test'), 'Offset was returned correctly from the theme registry.');
    // This will cause the ThemeRegistry class to write an updated version of
    // the cache entry when it is destroyed, usually at the end of the request.
    // Before that happens, manually delete the cache entry we created earlier
    // so that the new entry is written from scratch.
    \Drupal::cache()->delete($cid);

    // Destroy the class so that it triggers a cache write for the offset.
    $registry->destruct();

    $this->assertTrue(\Drupal::cache()->get($cid), 'Cache entry was created.');

    // Create a new instance of the class. Confirm that both the offset
    // requested previously, and one that has not yet been requested are both
    // available.
    $registry = new ThemeRegistry($cid, $cache, $lock_backend, ['theme_registry'], $this->container->get('module_handler')->isLoaded());
    $this->assertTrue($registry->get('theme_test_template_test'), 'Offset was returned correctly from the theme registry');
    $this->assertTrue($registry->get('theme_test_template_test_2'), 'Offset was returned correctly from the theme registry');
  }

  /**
   * Tests the theme registry with multiple subthemes.
   */
  public function testMultipleSubThemes() {
    $theme_handler = \Drupal::service('theme_handler');
    $theme_handler->install(['test_basetheme', 'test_subtheme', 'test_subsubtheme']);

    $registry_subsub_theme = new Registry(\Drupal::root(), \Drupal::cache(), \Drupal::lock(), \Drupal::moduleHandler(), $theme_handler, \Drupal::service('theme.initialization'), 'test_subsubtheme');
    $registry_subsub_theme->setThemeManager(\Drupal::theme());
    $registry_sub_theme = new Registry(\Drupal::root(), \Drupal::cache(), \Drupal::lock(), \Drupal::moduleHandler(), $theme_handler, \Drupal::service('theme.initialization'), 'test_subtheme');
    $registry_sub_theme->setThemeManager(\Drupal::theme());
    $registry_base_theme = new Registry(\Drupal::root(), \Drupal::cache(), \Drupal::lock(), \Drupal::moduleHandler(), $theme_handler, \Drupal::service('theme.initialization'), 'test_basetheme');
    $registry_base_theme->setThemeManager(\Drupal::theme());

    $preprocess_functions = $registry_subsub_theme->get()['theme_test_template_test']['preprocess functions'];
    $this->assertSame([
      'template_preprocess',
      'test_basetheme_preprocess_theme_test_template_test',
      'test_subtheme_preprocess_theme_test_template_test',
      'test_subsubtheme_preprocess_theme_test_template_test',
    ], $preprocess_functions);

    $preprocess_functions = $registry_sub_theme->get()['theme_test_template_test']['preprocess functions'];
    $this->assertSame([
      'template_preprocess',
      'test_basetheme_preprocess_theme_test_template_test',
      'test_subtheme_preprocess_theme_test_template_test',
    ], $preprocess_functions);

    $preprocess_functions = $registry_base_theme->get()['theme_test_template_test']['preprocess functions'];
    $this->assertSame([
      'template_preprocess',
      'test_basetheme_preprocess_theme_test_template_test',
    ], $preprocess_functions);

    $preprocess_functions = $registry_base_theme->get()['theme_test_function_suggestions']['preprocess functions'];
    $this->assertSame([
       'template_preprocess_theme_test_function_suggestions',
       'test_basetheme_preprocess_theme_test_function_suggestions',
    ], $preprocess_functions, "Theme functions don't have template_preprocess but do have template_preprocess_HOOK");
  }

  /**
   * Tests the theme registry with suggestions.
   */
  public function testSuggestionPreprocessFunctions() {
    $theme_handler = \Drupal::service('theme_handler');
    $theme_handler->install(['test_theme']);

    $registry_theme = new Registry(\Drupal::root(), \Drupal::cache(), \Drupal::lock(), \Drupal::moduleHandler(), $theme_handler, \Drupal::service('theme.initialization'), 'test_theme');
    $registry_theme->setThemeManager(\Drupal::theme());

    $suggestions = ['__kitten', '__flamingo'];
    $expected_preprocess_functions = [
      'template_preprocess',
      'theme_test_preprocess_theme_test_preprocess_suggestions',
    ];
    $suggestion = '';
    $hook = 'theme_test_preprocess_suggestions';
    do {
      $hook .= "$suggestion";
      $expected_preprocess_functions[] = "test_theme_preprocess_$hook";
      $preprocess_functions = $registry_theme->get()[$hook]['preprocess functions'];
      $this->assertSame($expected_preprocess_functions, $preprocess_functions, "$hook has correct preprocess functions.");
    } while ($suggestion = array_shift($suggestions));

    $expected_preprocess_functions = [
      'template_preprocess',
      'theme_test_preprocess_theme_test_preprocess_suggestions',
      'test_theme_preprocess_theme_test_preprocess_suggestions',
      'test_theme_preprocess_theme_test_preprocess_suggestions__kitten',
    ];

    $preprocess_functions = $registry_theme->get()['theme_test_preprocess_suggestions__kitten__meerkat']['preprocess functions'];
    $this->assertSame($expected_preprocess_functions, $preprocess_functions, 'Suggestion implemented as a function correctly inherits preprocess functions.');

    $preprocess_functions = $registry_theme->get()['theme_test_preprocess_suggestions__kitten__bearcat']['preprocess functions'];
    $this->assertSame($expected_preprocess_functions, $preprocess_functions, 'Suggestion implemented as a template correctly inherits preprocess functions.');

    $this->assertTrue(isset($registry_theme->get()['theme_test_preprocess_suggestions__kitten__meerkat__tarsier__moose']), 'Preprocess function with an unimplemented lower-level suggestion is added to the registry.');
  }

  /**
   * Tests that the theme registry can be altered by themes.
   */
  public function testThemeRegistryAlterByTheme() {

    /** @var \Drupal\Core\Extension\ThemeHandlerInterface $theme_handler */
    $theme_handler = \Drupal::service('theme_handler');
    $theme_handler->install(['test_theme']);
    $this->config('system.theme')->set('default', 'test_theme')->save();

    $registry = new Registry(\Drupal::root(), \Drupal::cache(), \Drupal::lock(), \Drupal::moduleHandler(), $theme_handler, \Drupal::service('theme.initialization'), 'test_theme');
    $registry->setThemeManager(\Drupal::theme());
    $this->assertEqual('value', $registry->get()['theme_test_template_test']['variables']['additional']);
  }

  /**
   * Tests front node theme suggestion generation.
   */
  public function testThemeSuggestions() {
    // Mock the current page as the front page.
    /** @var \Drupal\Core\Path\PathMatcherInterface $path_matcher */
    $path_matcher = $this->prophesize(PathMatcherInterface::class);
    $path_matcher->isFrontPage()->willReturn(TRUE);
    $this->container->set('path.matcher', $path_matcher->reveal());
    /** @var \Drupal\Core\Path\CurrentPathStack $path_matcher */
    $path_current = $this->prophesize(CurrentPathStack::class);
    $path_current->getPath()->willReturn('/node/1');
    $this->container->set('path.current', $path_current->reveal());

    // Check suggestions provided through hook_theme_suggestions_html().
    $suggestions = \Drupal::moduleHandler()->invokeAll('theme_suggestions_html', [[]]);
    $this->assertSame([
      'html__node',
      'html__node__%',
      'html__node__1',
      'html__front',
    ], $suggestions, 'Found expected html node suggestions.');

    // Check suggestions provided through hook_theme_suggestions_page().
    $suggestions = \Drupal::moduleHandler()->invokeAll('theme_suggestions_page', [[]]);
    $this->assertSame([
      'page__node',
      'page__node__%',
      'page__node__1',
      'page__front',
    ], $suggestions, 'Found expected page node suggestions.');
  }

}
