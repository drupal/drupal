<?php

namespace Drupal\Tests\migrate_drupal\Kernel\d6;

use Drupal\field\Plugin\migrate\source\d6\FieldInstance;
use Drupal\field_discovery_test\FieldDiscoveryTestClass;
use Drupal\migrate_drupal\FieldDiscoveryInterface;
use Drupal\Tests\migrate_drupal\Traits\FieldDiscoveryTestTrait;

// cspell:ignore filefield imagefield imagelink nodelink selectlist spamspan

/**
 * Tests FieldDiscovery service against Drupal 6.
 *
 * @group migrate_drupal
 * @coversDefaultClass \Drupal\migrate_drupal\FieldDiscovery
 */
class FieldDiscoveryTest extends MigrateDrupal6TestBase {

  use FieldDiscoveryTestTrait;
  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'menu_ui',
    'comment',
    'datetime',
    'file',
    'image',
    'link',
    'node',
    'system',
    'taxonomy',
    'telephone',
    'text',
  ];

  /**
   * The Field discovery service.
   *
   * @var \Drupal\migrate_drupal\FieldDiscoveryInterface
   */
  protected $fieldDiscovery;

  /**
   * The field plugin manager.
   *
   * @var \Drupal\migrate_drupal\Plugin\MigrateFieldPluginManagerInterface
   */
  protected $fieldPluginManager;

  /**
   * The migration plugin manager.
   *
   * @var \Drupal\migrate\Plugin\MigrationPluginManagerInterface
   */
  protected $migrationPluginManager;
  /**
   * The logger.
   *
   * @var \Drupal\Core\Logger\LoggerChannelInterface
   */
  protected $logger;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->installConfig(['node']);
    $this->executeMigration('d6_node_type');
    $this->executeMigration('d6_field');
    $this->executeMigration('d6_field_instance');
    $this->fieldDiscovery = $this->container->get('migrate_drupal.field_discovery');
    $this->migrationPluginManager = $this->container->get('plugin.manager.migration');
    $this->fieldPluginManager = $this->container->get('plugin.manager.migrate.field');
    $this->logger = $this->container->get('logger.channel.migrate_drupal');

  }

  /**
   * Tests the addAllFieldProcesses method.
   *
   * @covers ::addAllFieldProcesses
   */
  public function testAddAllFieldProcesses() {
    $expected_process_keys = [
      'field_commander',
      'field_company',
      'field_company_2',
      'field_company_3',
      'field_sync',
      'field_multivalue',
      'field_test_text_single_checkbox',
      'field_reference',
      'field_reference_2',
      'field_test',
      'field_test_date',
      'field_test_datestamp',
      'field_test_datetime',
      'field_test_decimal_radio_buttons',
      'field_test_email',
      'field_test_exclude_unset',
      'field_test_filefield',
      'field_test_float_single_checkbox',
      'field_test_four',
      'field_test_identical1',
      'field_test_identical2',
      'field_test_imagefield',
      'field_test_integer_selectlist',
      'field_test_link',
      'field_test_phone',
      'field_test_string_selectlist',
      'field_test_text_single_checkbox2',
      'field_test_three',
      'field_test_two',
    ];
    $this->assertFieldProcessKeys($this->fieldDiscovery, $this->migrationPluginManager, FieldDiscoveryInterface::DRUPAL_6, $expected_process_keys);
  }

  /**
   * Tests the addAllFieldProcesses method for field migrations.
   *
   * @covers ::addAllFieldProcesses
   * @dataProvider addAllFieldProcessesAltersData
   */
  public function testAddAllFieldProcessesAlters($field_plugin_method, $expected_process) {
    $this->assertFieldProcess($this->fieldDiscovery, $this->migrationPluginManager, FieldDiscoveryInterface::DRUPAL_6, $field_plugin_method, $expected_process);
  }

  /**
   * Provides data for testAddAllFieldProcessesAlters.
   *
   * @return array
   *   The data.
   */
  public function addAllFieldProcessesAltersData() {
    return [
      'Field Formatter' => [
        'field_plugin_method' => 'alterFieldFormatterMigration',
        'expected_process' => [
          'options/type' => [
            0 => [
              'map' => [
                'email' => [
                  'email_formatter_default' => 'email_mailto',
                  'email_formatter_contact' => 'basic_string',
                  'email_formatter_plain' => 'basic_string',
                  'email_formatter_spamspan' => 'basic_string',
                  'email_default' => 'email_mailto',
                  'email_contact' => 'basic_string',
                  'email_plain' => 'basic_string',
                  'email_spamspan' => 'basic_string',
                ],
                'text' => [
                  'default' => 'text_default',
                  'trimmed' => 'text_trimmed',
                  'plain' => 'basic_string',
                ],
                'datetime' => [
                  'date_default' => 'datetime_default',
                  'format_interval' => 'datetime_time_ago',
                  'date_plain' => 'datetime_plain',
                ],
                'filefield' => [
                  'default' => 'file_default',
                  'url_plain' => 'file_url_plain',
                  'path_plain' => 'file_url_plain',
                  'image_plain' => 'image',
                  'image_nodelink' => 'image',
                  'image_imagelink' => 'image',
                ],
                'link' => [
                  'default' => 'link',
                  'plain' => 'link',
                  'absolute' => 'link',
                  'title_plain' => 'link',
                  'url' => 'link',
                  'short' => 'link',
                  'label' => 'link',
                  'separate' => 'link_separate',
                ],
              ],
            ],
          ],
        ],
      ],
      'Field Widget' => [
        'field_plugin_method' => 'alterFieldWidgetMigration',
        'expected_process' => [
          'options/type' => [
            'type' => [
              'map' => [
                'userreference_select' => 'options_select',
                'userreference_buttons' => 'options_buttons',
                'userreference_autocomplete' => 'entity_reference_autocomplete_tags',
                'nodereference_select' => 'options_select',
                'nodereference_buttons' => 'options_buttons',
                'nodereference_autocomplete' => 'entity_reference_autocomplete_tags',
                'email_textfield' => 'email_default',
                'text_textfield' => 'text_textfield',
                'date' => 'datetime_default',
                'datetime' => 'datetime_default',
                'datestamp' => 'datetime_timestamp',
                'filefield_widget' => 'file_generic',
                'link' => 'link_default',
              ],
            ],
          ],
        ],
      ],
    ];
  }

  /**
   * Tests the addFields method.
   *
   * @covers ::addAllFieldProcesses
   */
  public function testAddFields() {
    $this->migrateFields();
    $field_discovery = $this->container->get('migrate_drupal.field_discovery');
    $migration_plugin_manager = $this->container->get('plugin.manager.migration');
    $definition = [
      'migration_tags' => ['Drupal 6'],
    ];
    $migration = $migration_plugin_manager->createStubMigration($definition);
    $field_discovery->addBundleFieldProcesses($migration, 'node', 'test_planet');
    $actual_process = $migration->getProcess();
    $expected_process = [
      'field_multivalue' => [
        0 => [
          'plugin' => 'get',
          'source' => 'field_multivalue',
        ],
      ],
      'field_test_text_single_checkbox' => [
        0 => [
          'plugin' => 'sub_process',
          'source' => 'field_test_text_single_checkbox',
          'process' => [
            'value' => 'value',
            'format' => [
              0 => [
                'plugin' => 'static_map',
                'bypass' => TRUE,
                'source' => 'format',
                'map' => [
                  0 => NULL,
                ],
              ],
              1 => [
                'plugin' => 'skip_on_empty',
                'method' => 'process',
              ],
              2 => [
                'plugin' => 'migration_lookup',
                'migration' => [
                  0 => 'd6_filter_format',
                  1 => 'd7_filter_format',
                ],
                'source' => 'format',
              ],
            ],
          ],
        ],
      ],
    ];
    $this->assertEquals($expected_process, $actual_process);
  }

  /**
   * Tests the getAllFields method.
   *
   * @covers ::getAllFields
   */
  public function testGetAllFields() {
    $field_discovery_test = new FieldDiscoveryTestClass($this->fieldPluginManager, $this->migrationPluginManager, $this->logger);
    $actual_fields = $field_discovery_test->getAllFields('6');
    $actual_node_types = array_keys($actual_fields['node']);
    sort($actual_node_types);
    $this->assertSame(['node'], array_keys($actual_fields));
    $this->assertSame(['employee', 'page', 'story', 'test_page', 'test_planet'], $actual_node_types);
    $this->assertCount(25, $actual_fields['node']['story']);
    foreach ($actual_fields['node'] as $bundle => $fields) {
      foreach ($fields as $field_name => $field_info) {
        $this->assertArrayHasKey('type', $field_info);
        $this->assertCount(22, $field_info);
        $this->assertEquals($bundle, $field_info['type_name']);
      }
    }
  }

  /**
   * Tests the getSourcePlugin method.
   *
   * @covers ::getSourcePlugin
   */
  public function testGetSourcePlugin() {
    $this->assertSourcePlugin('6', FieldInstance::class, [
      'requirements_met' => TRUE,
      'id' => 'd6_field_instance',
      'source_module' => 'content',
      'class' => 'Drupal\\field\\Plugin\\migrate\\source\\d6\\FieldInstance',
      'provider' => [
        0 => 'field',
        1 => 'migrate_drupal',
        2 => 'migrate',
        4 => 'core',
      ],
    ]);
  }

}
