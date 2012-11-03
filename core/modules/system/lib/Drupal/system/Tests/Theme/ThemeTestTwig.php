<?php

/**
 * @file
 * Definition of Drupal\system\Tests\Theme\ThemeTest.
 */

namespace Drupal\system\Tests\Theme;

use Drupal\simpletest\WebTestBase;

/**
 * Tests low-level theme functions.
 */
class ThemeTestTwig extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('theme_test');

  public static function getInfo() {
    return array(
      'name' => 'Twig Engine',
      'description' => 'Test theme functions with twig.',
      'group' => 'Theme',
    );
  }

  function setUp() {
    parent::setUp();
    theme_enable(array('test_theme_twig'));
  }

  /**
   * Ensures a themes template is overrideable based on the 'template' filename.
   */
  function testTemplateOverride() {
    variable_set('theme_default', 'test_theme_twig');
    $this->drupalGet('theme-test/template-test');
    $this->assertText('Success: Template overridden.', t('Template overridden by defined \'template\' filename.'));
  }

  /**
   * Tests drupal_find_theme_templates
   */
  function testFindThemeTemplates() {

    $cache = array();

    // Prime the theme cache
    foreach (module_implements('theme') as $module) {
      _theme_process_registry($cache, $module, 'module', $module, drupal_get_path('module', $module));
    }

    // Check for correct content
    // @todo Remove this tests once double engine code is removed

    $this->assertEqual($cache['node']['template_file'], 'core/modules/node/templates/node.twig', 'Node is using node.twig as template file');
    $this->assertEqual($cache['node']['engine'], 'twig', 'Node is using twig engine');

    $this->assertEqual($cache['theme_test_template_test']['template_file'], 'core/modules/system/tests/modules/theme_test/templates/theme_test.template_test.tpl.php', 'theme_test is using theme_test.template_test.tpl.php as template file');
    $this->assertEqual($cache['theme_test_template_test']['engine'], 'phptemplate', 'theme_test is using phptemplate as engine.');

    $templates = drupal_find_theme_templates($cache, '.twig', drupal_get_path('theme', 'test_theme_twig'));
    $this->assertEqual($templates['node__1']['template'], 'node--1', 'Template node--1.twig was found in test_theme_twig.');
  }
}
