<?php

namespace Drupal\Tests\dblog\Kernel\Views;

use Drupal\Component\Utility\SafeMarkup;
use Drupal\Component\Utility\Xss;
use Drupal\Core\Logger\RfcLogLevel;
use Drupal\Core\Url;
use Drupal\Tests\views\Kernel\ViewsKernelTestBase;
use Drupal\views\Views;
use Drupal\views\Tests\ViewTestData;

/**
 * Tests the views integration of dblog module.
 *
 * @group dblog
 */
class ViewsIntegrationTest extends ViewsKernelTestBase {

  /**
   * Views used by this test.
   *
   * @var array
   */
  public static $testViews = array('test_dblog');

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('dblog', 'dblog_test_views', 'user');

  /**
   * {@inheritdoc}
   */
  protected function setUp($import_test_views = TRUE) {
    parent::setUp();

    // Rebuild the router, otherwise we can't generate links.
    $this->container->get('router.builder')->rebuild();

    $this->installSchema('dblog', array('watchdog'));

    ViewTestData::createTestViews(get_class($this), array('dblog_test_views'));
  }

  /**
   * Tests the integration.
   */
  public function testIntegration() {

    // Remove the watchdog entries added by the potential batch process.
    $this->container->get('database')->truncate('watchdog')->execute();

    $entries = array();
    // Setup a watchdog entry without tokens.
    $entries[] = array(
      'message' => $this->randomMachineName(),
      'variables' => array('link' => \Drupal::l('Link', new Url('<front>'))),
    );
    // Setup a watchdog entry with one token.
    $entries[] = array(
      'message' => '@token1',
      'variables' => array('@token1' => $this->randomMachineName(), 'link' => \Drupal::l('Link', new Url('<front>'))),
    );
    // Setup a watchdog entry with two tokens.
    $entries[] = array(
      'message' => '@token1 @token2',
      // Setup a link with a tag which is filtered by
      // \Drupal\Component\Utility\Xss::filterAdmin() in order to make sure
      // that strings which are not marked as safe get filtered.
      'variables' => array(
        '@token1' => $this->randomMachineName(),
        '@token2' => $this->randomMachineName(),
        'link' => '<a href="' . \Drupal::url('<front>') . '"><object>Link</object></a>',
      ),
    );
    $logger_factory = $this->container->get('logger.factory');
    foreach ($entries as $entry) {
      $entry += array(
        'type' => 'test-views',
        'severity' => RfcLogLevel::NOTICE,
      );
      $logger_factory->get($entry['type'])->log($entry['severity'], $entry['message'], $entry['variables']);
    }

    $view = Views::getView('test_dblog');
    $this->executeView($view);
    $view->initStyle();

    foreach ($entries as $index => $entry) {
      $this->assertEqual($view->style_plugin->getField($index, 'message'), SafeMarkup::format($entry['message'], $entry['variables']));
      $link_field = $view->style_plugin->getField($index, 'link');
      // The 3rd entry contains some unsafe markup that needs to get filtered.
      if ($index == 2) {
        // Make sure that unsafe link differs from the rendered link, so we know
        // that some filtering actually happened.
        $this->assertNotEqual($link_field, $entry['variables']['link']);
      }
      $this->assertEqual($link_field, Xss::filterAdmin($entry['variables']['link']));
    }

    // Disable replacing variables and check that the tokens aren't replaced.
    $view->destroy();
    $view->storage->invalidateCaches();
    $view->initHandlers();
    $this->executeView($view);
    $view->initStyle();
    $view->field['message']->options['replace_variables'] = FALSE;
    foreach ($entries as $index => $entry) {
      $this->assertEqual($view->style_plugin->getField($index, 'message'), $entry['message']);
    }
  }

}
