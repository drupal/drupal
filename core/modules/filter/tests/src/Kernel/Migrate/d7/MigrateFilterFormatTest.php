<?php

declare(strict_types=1);

namespace Drupal\Tests\filter\Kernel\Migrate\d7;

use Drupal\Core\Database\Database;
use Drupal\filter\Entity\FilterFormat;
use Drupal\filter\FilterFormatInterface;
use Drupal\KernelTests\KernelTestBase;
use Drupal\Tests\migrate\Kernel\MigrateDumpAlterInterface;
use Drupal\Tests\migrate_drupal\Kernel\d7\MigrateDrupal7TestBase;

/**
 * Upgrade variables to filter.formats.*.yml.
 *
 * @group filter
 */
class MigrateFilterFormatTest extends MigrateDrupal7TestBase implements MigrateDumpAlterInterface {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['filter'];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->installConfig(static::$modules);
    $this->executeMigration('d7_filter_format');
  }

  /**
   * {@inheritdoc}
   */
  public static function migrateDumpAlter(KernelTestBase $test) {
    $db = Database::getConnection('default', 'migrate');
    $fields = [
      'format' => 'image_resize_filter',
      'name' => 'Image resize',
      'cache' => '1',
      'status' => '1',
      'weight' => '0',
    ];
    $db->insert('filter_format')->fields($fields)->execute();
    $fields = [
      'format' => 'image_resize_filter',
      'module' => 'filter',
      'name' => 'image_resize_filter',
      'weight' => '0',
      'status' => '1',
      'settings' => serialize([]),
    ];
    $db->insert('filter')->fields($fields)->execute();
  }

  /**
   * Asserts various aspects of a filter format entity.
   *
   * @param string $id
   *   The format ID.
   * @param string $label
   *   The expected label of the format.
   * @param array $enabled_filters
   *   The expected filters in the format, keyed by ID with weight as values.
   * @param int $weight
   *   The weight of the filter.
   * @param bool $status
   *   The status of the filter.
   *
   * @internal
   */
  protected function assertEntity(string $id, string $label, array $enabled_filters, int $weight, bool $status): void {
    /** @var \Drupal\filter\FilterFormatInterface $entity */
    $entity = FilterFormat::load($id);
    $this->assertInstanceOf(FilterFormatInterface::class, $entity);
    $this->assertSame($label, $entity->label());
    // get('filters') will return enabled filters only, not all of them.
    $this->assertSame(array_keys($enabled_filters), array_keys($entity->get('filters')));
    $this->assertSame($weight, $entity->get('weight'));
    $this->assertSame($status, $entity->status());
    foreach ($entity->get('filters') as $filter_id => $filter) {
      $this->assertSame($filter['weight'], $enabled_filters[$filter_id]);
    }
  }

  /**
   * Tests the Drupal 7 filter format to Drupal 8 migration.
   */
  public function testFilterFormat(): void {
    $this->assertEntity('custom_text_format', 'Custom Text format', ['filter_autop' => 0, 'filter_html' => -10], 0, TRUE);
    $this->assertEntity('filtered_html', 'Filtered HTML', ['filter_autop' => 2, 'filter_html' => 1, 'filter_htmlcorrector' => 10, 'filter_url' => 0], 0, TRUE);
    $this->assertEntity('full_html', 'Full HTML', ['filter_autop' => 1, 'filter_htmlcorrector' => 10, 'filter_url' => 0], 1, TRUE);
    $this->assertEntity('plain_text', 'Plain text', ['filter_autop' => 2, 'filter_html_escape' => 0, 'filter_url' => 1], 10, TRUE);
    // This assertion covers issue #2555089. Drupal 7 formats are identified
    // by machine names, so migrated formats should be merged into existing
    // ones.
    $this->assertNull(FilterFormat::load('plain_text1'));

    // Ensure that filter-specific settings were migrated.
    /** @var \Drupal\filter\FilterFormatInterface $format */
    $format = FilterFormat::load('filtered_html');
    $this->assertInstanceOf(FilterFormatInterface::class, $format);
    $config = $format->filters('filter_html')->getConfiguration();
    $this->assertSame('<div> <span> <ul type> <li> <ol start type> <a href hreflang> <img src alt height width>', $config['settings']['allowed_html']);
    $config = $format->filters('filter_url')->getConfiguration();
    $this->assertSame(128, $config['settings']['filter_url_length']);

    // The disabled php_code format gets migrated, but the php_code filter is
    // changed to filter_null.
    $this->assertEntity('php_code', 'PHP code', ['filter_null' => 0], 11, FALSE);

    // Test a non-existent format.
    $this->assertEntity('image_resize_filter', 'Image resize', [], 0, TRUE);

    // For each filter that does not exist on the destination, there should be
    // a log message.
    $migration = $this->getMigration('d7_filter_format');
    $errors = array_map(function ($message) {
      return $message->message;
    }, iterator_to_array($migration->getIdMap()->getMessages()));
    $this->assertCount(2, $errors);
    sort($errors);
    $message = 'Filter image_resize_filter could not be mapped to an existing filter plugin; omitted since it is a transformation-only filter. Install and configure a successor after the migration.';
    $this->assertEquals($errors[0], $message);
    $message = ('Filter php_code could not be mapped to an existing filter plugin; defaulting to filter_null and dropping all settings. Either redo the migration with the module installed that provides an equivalent filter, or modify the text format after the migration to remove this filter if it is no longer necessary.');
    $this->assertEquals($errors[1], $message);
  }

}
