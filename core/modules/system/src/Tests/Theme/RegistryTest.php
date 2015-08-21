<?php

/**
 * @file
 * Contains \Drupal\system\Tests\Theme\RegistryTest.
 */

namespace Drupal\system\Tests\Theme;

use Drupal\Core\Theme\Registry;
use Drupal\simpletest\KernelTestBase;
use Drupal\Core\Utility\ThemeRegistry;

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
  public static $modules = array('theme_test', 'system');

  protected $profile = 'testing';

  /**
   * Tests the behavior of the theme registry class.
   */
  function testRaceCondition() {
    // The theme registry is not marked as persistable in case we don't have a
    // proper request.
    \Drupal::request()->setMethod('GET');
    $cid = 'test_theme_registry';

    // Directly instantiate the theme registry, this will cause a base cache
    // entry to be written in __construct().
    $cache = \Drupal::cache();
    $lock_backend = \Drupal::lock();
    $registry = new ThemeRegistry($cid, $cache, $lock_backend, array('theme_registry'), $this->container->get('module_handler')->isLoaded());

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
    $registry = new ThemeRegistry($cid, $cache, $lock_backend, array('theme_registry'), $this->container->get('module_handler')->isLoaded());
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
    $this->assertIdentical([
      'template_preprocess',
      'test_basetheme_preprocess_theme_test_template_test',
      'test_subtheme_preprocess_theme_test_template_test',
      'test_subsubtheme_preprocess_theme_test_template_test',
    ], $preprocess_functions);

    $preprocess_functions = $registry_sub_theme->get()['theme_test_template_test']['preprocess functions'];
    $this->assertIdentical([
      'template_preprocess',
      'test_basetheme_preprocess_theme_test_template_test',
      'test_subtheme_preprocess_theme_test_template_test',
    ], $preprocess_functions);

    $preprocess_functions = $registry_base_theme->get()['theme_test_template_test']['preprocess functions'];
    $this->assertIdentical([
      'template_preprocess',
      'test_basetheme_preprocess_theme_test_template_test',
    ], $preprocess_functions);

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
      $this->assertIdentical($expected_preprocess_functions, $preprocess_functions, "$hook has correct preprocess functions.");
    } while ($suggestion = array_shift($suggestions));

    $expected_preprocess_functions = [
      'template_preprocess',
      'theme_test_preprocess_theme_test_preprocess_suggestions',
      'test_theme_preprocess_theme_test_preprocess_suggestions',
      'test_theme_preprocess_theme_test_preprocess_suggestions__kitten',
    ];

    $preprocess_functions = $registry_theme->get()['theme_test_preprocess_suggestions__kitten__meerkat']['preprocess functions'];
    $this->assertIdentical($expected_preprocess_functions, $preprocess_functions, 'Suggestion implemented as a function correctly inherits preprocess functions.');

    $preprocess_functions = $registry_theme->get()['theme_test_preprocess_suggestions__kitten__bearcat']['preprocess functions'];
    $this->assertIdentical($expected_preprocess_functions, $preprocess_functions, 'Suggestion implemented as a template correctly inherits preprocess functions.');

  }

  /**
   * Tests that the theme registry can be altered by themes.
   */
  public function testThemeRegistryAlterByTheme() {

    /** @var \Drupal\Core\Extension\ThemeHandlerInterface $theme_handler */
    $theme_handler = \Drupal::service('theme_handler');
    $theme_handler->install(['test_theme']);
    $theme_handler->setDefault('test_theme');

    $registry = new Registry(\Drupal::root(), \Drupal::cache(), \Drupal::lock(), \Drupal::moduleHandler(), $theme_handler, \Drupal::service('theme.initialization'), 'test_theme');
    $registry->setThemeManager(\Drupal::theme());
    $this->assertEqual('value', $registry->get()['theme_test_template_test']['variables']['additional']);
  }

}
