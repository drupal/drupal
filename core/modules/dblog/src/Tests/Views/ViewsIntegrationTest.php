<?php

/**
 * @file
 * Contains \Drupal\dblog\Tests\Views\ViewsIntegrationTest.
 */

namespace Drupal\dblog\Tests\Views;

use Drupal\Component\Utility\String;
use Drupal\Component\Utility\Xss;
use Drupal\views\Views;
use Drupal\views\Tests\ViewTestData;
use Drupal\views\Tests\ViewUnitTestBase;

/**
 * Tests the views integration of dblog module.
 *
 * @group dblog
 */
class ViewsIntegrationTest extends ViewUnitTestBase {

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
  public static $modules = array('dblog_test_views');

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->enableModules(array('system', 'dblog'));
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
      'variables' => array(),
      'link' => l('Link', 'node/1'),
    );
    // Setup a watchdog entry with one token.
    $entries[] = array(
      'message' => '@token1',
      'variables' => array('@token1' => $this->randomMachineName()),
      'link' => l('Link', 'node/2'),
    );
    // Setup a watchdog entry with two tokens.
    $entries[] = array(
      'message' => '@token1 !token2',
      'variables' => array('@token1' => $this->randomMachineName(), '!token2' => $this->randomMachineName()),
      // Setup a link with a tag which is filtered by
      // \Drupal\Component\Utility\Xss::filterAdmin().
      'link' => l('<object>Link</object>', 'node/2', array('html' => TRUE)),
    );
    foreach ($entries as $entry) {
      $entry += array(
        'type' => 'test-views',
        'severity' => WATCHDOG_NOTICE,
      );
      watchdog($entry['type'], $entry['message'], $entry['variables'], $entry['severity'], $entry['link']);
    }

    $view = Views::getView('test_dblog');
    $this->executeView($view);
    $view->initStyle();

    foreach ($entries as $index => $entry) {
      $this->assertEqual($view->style_plugin->getField($index, 'message'), String::format($entry['message'], $entry['variables']));
      $this->assertEqual($view->style_plugin->getField($index, 'link'), Xss::filterAdmin($entry['link']));
    }

    // Disable replacing variables and check that the tokens aren't replaced.
    $view->destroy();
    $view->initHandlers();
    $this->executeView($view);
    $view->initStyle();
    $view->field['message']->options['replace_variables'] = FALSE;
    foreach ($entries as $index => $entry) {
      $this->assertEqual($view->style_plugin->getField($index, 'message'), $entry['message']);
    }
  }

}
