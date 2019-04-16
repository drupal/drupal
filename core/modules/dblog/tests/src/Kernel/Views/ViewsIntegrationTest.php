<?php

namespace Drupal\Tests\dblog\Kernel\Views;

use Drupal\Component\Render\FormattableMarkup;
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
   * {@inheritdoc}
   */
  public static $testViews = ['test_dblog', 'dblog_integration_test'];

  /**
   * {@inheritdoc}
   */
  public static $modules = ['dblog', 'dblog_test_views', 'user'];

  /**
   * {@inheritdoc}
   */
  protected $columnMap = ['watchdog_message' => 'message'];

  /**
   * {@inheritdoc}
   */
  protected function setUp($import_test_views = TRUE) {
    parent::setUp();

    $this->installEntitySchema('user');
    $this->installSchema('dblog', ['watchdog']);

    ViewTestData::createTestViews(get_class($this), ['dblog_test_views']);
  }

  /**
   * Tests the messages escaping functionality.
   */
  public function testMessages() {

    // Remove the watchdog entries added by the potential batch process.
    $this->container->get('database')->truncate('watchdog')->execute();

    $entries = $this->createLogEntries();

    $view = Views::getView('test_dblog');
    $this->executeView($view);
    $view->initStyle();

    foreach ($entries as $index => $entry) {
      if (!isset($entry['variables'])) {
        continue;
      }
      $this->assertEqual($view->style_plugin->getField($index, 'message'), new FormattableMarkup($entry['message'], $entry['variables']));
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

  /**
   * Tests the relationship with the users_field_data table.
   */
  public function testRelationship() {
    $view = Views::getView('dblog_integration_test');
    $view->setDisplay('page_1');
    // The uid relationship should now join to the {users_field_data} table.
    $tables = array_keys($view->getBaseTables());
    $this->assertTrue(in_array('users_field_data', $tables));
    $this->assertFalse(in_array('users', $tables));
    $this->assertTrue(in_array('watchdog', $tables));
  }

  /**
   * Test views can be filtered by severity and log type.
   */
  public function testFiltering() {
    // Remove the watchdog entries added by the potential batch process.
    $this->container->get('database')->truncate('watchdog')->execute();
    $this->createLogEntries();

    $view = Views::getView('dblog_integration_test');

    $filters = [
      'severity' => [
        'id' => 'severity',
        'table' => 'watchdog',
        'field' => 'severity',
        'relationship' => 'none',
        'group_type' => 'group',
        'admin_label' => '',
        'operator' => 'in',
        'value' => [
          RfcLogLevel::WARNING,
        ],
        'group' => 1,
        'exposed' => FALSE,
        'plugin_id' => 'in_operator',
      ],
    ];

    $view->setDisplay('page_1');
    $view->displayHandlers->get('page_1')->overrideOption('filters', $filters);
    $view->save();

    $this->executeView($view);

    $resultset = [['message' => 'Warning message']];
    $this->assertIdenticalResultset($view, $resultset, $this->columnMap);

    $view = Views::getView('dblog_integration_test');

    $filters = [
      'type' => [
        'id' => 'type',
        'table' => 'watchdog',
        'field' => 'type',
        'relationship' => 'none',
        'group_type' => 'group',
        'admin_label' => '',
        'operator' => 'in',
        'value' => [
          'my-module' => 'my-module',
        ],
        'group' => '1',
        'exposed' => FALSE,
        'is_grouped' => FALSE,
        'plugin_id' => 'dblog_types',
      ],
    ];

    $view->setDisplay('page_1');
    $view->displayHandlers->get('page_1')->overrideOption('filters', $filters);
    $view->save();

    $this->executeView($view);

    $resultset = [['message' => 'My module message']];
    $this->assertIdenticalResultset($view, $resultset, $this->columnMap);
  }

  /**
   * Create a set of log entries.
   *
   * @return array
   *   An array of data used to create the log entries.
   */
  protected function createLogEntries() {
    $entries = [];
    // Setup a watchdog entry without tokens.
    $entries[] = [
      'message' => $this->randomMachineName(),
      'variables' => ['link' => \Drupal::l('Link', new Url('<front>'))],
    ];
    // Setup a watchdog entry with one token.
    $entries[] = [
      'message' => '@token1',
      'variables' => ['@token1' => $this->randomMachineName(), 'link' => \Drupal::l('Link', new Url('<front>'))],
    ];
    // Setup a watchdog entry with two tokens.
    $entries[] = [
      'message' => '@token1 @token2',
      // Setup a link with a tag which is filtered by
      // \Drupal\Component\Utility\Xss::filterAdmin() in order to make sure
      // that strings which are not marked as safe get filtered.
      'variables' => [
        '@token1' => $this->randomMachineName(),
        '@token2' => $this->randomMachineName(),
        'link' => '<a href="' . Url::fromRoute('<front>')->toString() . '"><object>Link</object></a>',
      ],
    ];
    // Setup a watchdog entry with severity WARNING.
    $entries[] = [
      'message' => 'Warning message',
      'severity' => RfcLogLevel::WARNING,
    ];
    // Setup a watchdog entry with a different module.
    $entries[] = [
      'message' => 'My module message',
      'severity' => RfcLogLevel::INFO,
      'type' => 'my-module',
    ];

    $logger_factory = $this->container->get('logger.factory');
    foreach ($entries as $entry) {
      $entry += [
        'type' => 'test-views',
        'severity' => RfcLogLevel::NOTICE,
        'variables' => [],
      ];
      $logger_factory->get($entry['type'])->log($entry['severity'], $entry['message'], $entry['variables']);
    }
    return $entries;
  }

}
