<?php

/**
 * @file
 * Contains \Drupal\user\Plugin\migrate\process\d6\UserUpdate7002.
 */

namespace Drupal\user\Plugin\migrate\process\d6;

use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;

/**
 * Converts user time zones from time zone offsets to time zone names.
 *
 * @MigrateProcessPlugin(
 *   id = "user_update_7002"
 * )
 */
class UserUpdate7002 extends ProcessPluginBase {

  /**
   * System timezones.
   *
   * @var array
   */
  protected static $timezones;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, array $plugin_definition) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    if (!isset(static::$timezones)) {
      static::$timezones = system_time_zones();
    }
  }

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    $timezone = NULL;

    if ($row->hasSourceProperty('timezone_name')) {
      if (isset(static::$timezones[$row->getSourceProperty('timezone_name')])) {
        $timezone = $row->getSourceProperty('timezone_name');
      }
    }
    if (!$timezone && $row->hasSourceProperty('event_timezone')) {
      if (isset(static::$timezones[$row->getSourceProperty('event_timezone')])) {
        $timezone = $row->getSourceProperty('event_timezone');
      }
    }

    return $timezone;
  }

}
