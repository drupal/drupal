<?php

/**
 * @file
 * Contains \Drupal\system\Tests\Update\CompatibilityFixTest.
 */

namespace Drupal\system\Tests\Update;

use Drupal\simpletest\KernelTestBase;

/**
 * Tests that extensions that are incompatible with the current core version are disabled.
 *
 * @group Update
 */
class CompatibilityFixTest extends KernelTestBase {

  protected function setUp() {
    parent::setUp();
    require_once \Drupal::root() . '/core/includes/update.inc';
  }

  function testFixCompatibility() {
    $extension_config = \Drupal::configFactory()->getEditable('core.extension');

    // Add an incompatible/non-existent module to the config.
    $modules = $extension_config->get('module');
    $modules['incompatible_module'] = 0;
    $extension_config->set('module', $modules);
    $modules = $extension_config->get('module');
    $this->assertTrue(in_array('incompatible_module', array_keys($modules)), 'Added incompatible/non-existent module to the config.');

    // Add an incompatible/non-existent theme to the config.
    $themes = $extension_config->get('theme');
    $themes['incompatible_theme'] = 0;
    $extension_config->set('theme', $themes);
    $themes = $extension_config->get('theme');
    $this->assertTrue(in_array('incompatible_theme', array_keys($themes)), 'Added incompatible/non-existent theme to the config.');

    // Fix compatibility.
    update_fix_compatibility();
    $modules = $extension_config->get('module');
    $this->assertFalse(in_array('incompatible_module', array_keys($modules)), 'Fixed modules compatibility.');
    $themes = $extension_config->get('theme');
    $this->assertFalse(in_array('incompatible_theme', array_keys($themes)), 'Fixed themes compatibility.');
  }
}
