<?php

declare(strict_types=1);

namespace Drupal\Tests\dblog\Kernel\Views;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\Component\Utility\Xss;
use Drupal\Core\Logger\RfcLogLevel;
use Drupal\Core\Link;
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
  protected static $modules = ['dblog', 'dblog_test_views', 'user'];

  /**
   * {@inheritdoc}
   */
  protected $columnMap = ['watchdog_message' => 'message'];

  /**
   * {@inheritdoc}
   */
  protected function setUp($import_test_views = TRUE): void {
    parent::setUp();

    $this->installEntitySchema('user');
    $this->installSchema('dblog', ['watchdog']);

    ViewTestData::createTestViews(static::class, ['dblog_test_views']);
  }

  /**
   * Tests the messages escaping functionality.
   */
  public function testMessages(): void {

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
      $message_vars = $entry['variables'];
      unset($message_vars['link']);
      $this->assertEquals(new FormattableMarkup($entry['message'], $message_vars), $view->style_plugin->getField($index, 'message'));
      $link_field = $view->style_plugin->getField($index, 'link');
      // The 3rd entry contains some unsafe markup that needs to get filtered.
      if ($index == 2) {
        // Make sure that unsafe link differs from the rendered link, so we know
        // that some filtering actually happened. We use assertNotSame and cast
        // values to strings since HTML tags are significant.
        $this->assertNotSame((string) $entry['variables']['link'], (string) $link_field);
      }
      $this->assertSame(Xss::filterAdmin($entry['variables']['link']), (string) $link_field);
    }

    // Disable replacing variables and check that the tokens aren't replaced.
    $view->destroy();
    $view->storage->invalidateCaches();
    $view->initHandlers();
    $this->executeView($view);
    $view->initStyle();
    $view->field['message']->options['replace_variables'] = FALSE;
    foreach ($entries as $index => $entry) {
      $this->assertEquals($entry['message'], $view->style_plugin->getField($index, 'message'));
    }
  }

  /**
   * Tests the relationship with the users_field_data table.
   */
  public function testRelationship(): void {
    $view = Views::getView('dblog_integration_test');
    $view->setDisplay('page_1');
    // The uid relationship should now join to the {users_field_data} table.
    $base_tables = $view->getBaseTables();
    $this->assertArrayHasKey('users_field_data', $base_tables);
    $this->assertArrayNotHasKey('users', $base_tables);
    $this->assertArrayHasKey('watchdog', $base_tables);
  }

  /**
   * Tests views can be filtered by severity and log type.
   */
  public function testFiltering(): void {
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
  protected function createLogEntries(): array {
    $entries = [];
    // Setup a watchdog entry without tokens.
    $entries[] = [
      'message' => $this->randomMachineName(),
      'variables' => ['link' => Link::fromTextAndUrl('Link', Url::fromRoute('<front>'))->toString()],
    ];
    // Setup a watchdog entry with one token.
    $entries[] = [
      'message' => '@token1',
      'variables' => [
        '@token1' => $this->randomMachineName(),
        'link' => Link::fromTextAndUrl('Link', Url::fromRoute('<front>'))->toString(),
      ],
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
