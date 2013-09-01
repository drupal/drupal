<?php

/**
 * @file
 * Definition of Drupal\system\Tests\Upgrade\SystemUpgradePathTest.
 */

namespace Drupal\system\Tests\Upgrade;

/**
 * Tests upgrade of system variables.
 */
class SystemUpgradePathTest extends UpgradePathTestBase {
  public static function getInfo() {
    return array(
      'name' => 'System config upgrade test',
      'description' => 'Tests upgrade of system variables to the configuration system.',
      'group' => 'Upgrade path',
    );
  }

  public function setUp() {
    $this->databaseDumpFiles = array(
      drupal_get_path('module', 'system') . '/tests/upgrade/drupal-7.bare.standard_all.database.php.gz',
      drupal_get_path('module', 'system') . '/tests/upgrade/drupal-7.system.database.php',
    );
    parent::setUp();
  }

  /**
   * Tests upgrade of variables to config.
   */
  public function testVariableUpgrade() {
    $this->assertTrue($this->performUpgrade(), 'The upgrade was completed successfully.');

    // Verify that variables were properly upgraded.
    $expected_config['system.cron'] = array(
      'threshold.autorun' => '86400',
      'threshold.requirements_warning' => '86400',
      'threshold.requirements_error' => '172800',
    );

    $expected_config['system.logging'] = array(
      'error_level' => 'some',
    );

    $expected_config['system.maintenance'] = array(
      'enabled' => '1',
      'message' => 'Testing config upgrade',
    );

    $expected_config['system.performance'] = array(
      'cache.page.use_internal' => '1',
      'cache.page.max_age' => '1800',
      'response.gzip' => '1',
      'js.preprocess' => '1',
      'css.preprocess' => '1',
      'fast_404' => array(
        'enabled' => '1',
        'paths' => '/\.(?:txt|png|gif|jpe?g|css|js|ico|swf|flv|cgi|bat|pl|dll|exe|aspi|pdf)$/i',
        'exclude_paths' => '/\/(?:styles|imagecache)\//',
        'html' => '<!DOCTYPE html><html><head><title>Page Not Found</title></head><body><h1>Page Not Found</h1><p>The requested URL "@path" was not found on this server.</p></body></html>',
      ),
    );

    $expected_config['system.rss'] = array(
      'channel.description' => 'Testing config upgrade',
      'items.limit' => '20',
      'items.view_mode' => 'teaser',
    );

    $expected_config['system.site'] = array(
      'name' => 'Testing config upgrade',
      // The upgrade from site_mail to system.site:mail is not testable as
      // simpletest overrides this configuration with simpletest@example.com.
      // 'mail' => 'config@example.com',
      'slogan' => 'CMI makes Drupal 8 drush cex -y',
      'page.403' => '403',
      'page.404' => '404',
      'page.front' => 'node',
    );

    $expected_config['user.settings'] = array(
      'cancel_method' => 'user_cancel_reassign',
    );

    $expected_config['system.filter'] = array(
      'protocols.0' => 'http',
      'protocols.1' => 'https',
      'protocols.2' => 'ftp',
      'protocols.3' => 'mailto',
    );

    $expected_config['taxonomy.settings'] = array(
      'override_selector' => '1',
      'terms_per_page_admin' => '32',
      'maintain_index_table' => '0',
    );

    $expected_config['filter.settings'] = array(
      'fallback_format' => 'plain_text'
    );

    $expected_config['action.settings'] = array(
      'recursion_limit' => 42,
    );

    // Color module for theme bartik, optional screenshot.
    $expected_config['color.bartik'] = array(
      'palette' => array(
        'top' => '#8eccf2',
        'bottom' => '#48a9e4',
        'bg' => '#ffffff',
        'sidebar' => '#f6f6f2',
        'sidebarborders' => '#f9f9f9',
        'footer' => '#db2a2a',
        'titleslogan' => '#fffeff',
        'text' => '#fb8484',
        'link' => '#3587b7',
      ),
      'logo' => 'public://color/bartik-09696463/logo.png',
      'stylesheets' => 'public://color/bartik-09696463/colors.css',
      'files' => array(
        'public://color/bartik-09696463/logo.png', 'public://color/bartik-09696463/colors.css'
      ),
    );
    // Second try with faked seven upgrade, optional screenshot.
    $expected_config['color.seven'] = array(
      'palette' => array(
        'top' => '#8eccf2',
        'bottom' => '#48a9e4',
        'bg' => '#ffffff',
        'sidebar' => '#f6f6f2',
        'sidebarborders' => '#f9f9f9',
        'footer' => '#db2a2a',
        'titleslogan' => '#fffeff',
        'text' => '#fb8484',
        'link' => '#3587b7',
      ),
      'logo' => 'public://color/seven-09696463/logo.png',
      'stylesheets' => 'public://color/seven-09696463/colors.css',
      'files' => array(
        'public://color/seven-09696463/logo.png', 'public://color/seven-09696463/colors.css'
      ),
      'screenshot' => 'public://color/seven-09696463/dummy-screenshot.png',
    );

    $expected_config['book.settings'] = array(
      'allowed_types' => array(
        'book',
        // Content type does not have to exist.
        'test',
      ),
      'block' => array(
        'navigation' => array(
          'mode' => 'all pages'
        )
      ),
      'child_type' => 'book',
    );

    $expected_config['aggregator.settings'] = array(
      'fetcher' => 'test_fetcher',
      'parser' => 'test_parser',
      'processors' => array('test_processor'),
      'items.allowed_html' => '<a>',
      'items.teaser_length' => 6000,
      'items.expire' => 10,
      'source.list_max' => 5,
    );

    foreach ($expected_config as $file => $values) {
      $config = \Drupal::config($file);
      $this->verbose(print_r($config->get(), TRUE));
      foreach ($values as $name => $value) {
        $stored = $config->get($name);
        // Make sure we have a string representation to show.
        $stored_txt = !is_string($stored) ? json_encode($stored) : $stored;
        $value_txt = !is_string($value) ? json_encode($value) : $value;
        $this->assertEqual($value, $stored, format_string('Expected value for %name found: %stored (previously: %value).', array('%name' => $name, '%stored' => $stored_txt, '%value' => $value_txt)));
      }
    }
  }

  /**
   * Check whether views got enabled.
   */
  public function testFrontpageUpgrade() {
    $this->assertTrue($this->performUpgrade(), 'The upgrade was completed successfully.');

    $this->assertTrue($this->container->get('module_handler')->moduleExists('views'), 'Views is enabled after the upgrade.');
    $view = $this->container->get('entity.manager')->getStorageController('view')->load('frontpage');
    $this->assertTrue($view->status(), 'The frontpage view is enabled after the upgrade.');
  }

}
