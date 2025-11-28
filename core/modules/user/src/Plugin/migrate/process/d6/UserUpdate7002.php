<?php

namespace Drupal\user\Plugin\migrate\process\d6;

use Drupal\Core\Datetime\TimeZoneFormHelper;
use Drupal\migrate\Attribute\MigrateProcess;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;
use Drupal\Core\Config\Config;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Converts user time zones from time zone offsets to time zone names.
 *
 * @deprecated in drupal:11.3.0 and is removed from drupal:12.0.0. There is no
 *   replacement.
 *
 * @see https://www.drupal.org/node/3533560
 */
#[MigrateProcess('user_update_7002')]
class UserUpdate7002 extends ProcessPluginBase implements ContainerFactoryPluginInterface {

  /**
   * System timezones.
   *
   * @var array
   */
  protected static $timezones;

  /**
   * Contains the system.theme configuration object.
   *
   * @var \Drupal\Core\Config\Config
   */
  protected $dateConfig;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, array $plugin_definition, Config $date_config) {
    @trigger_error(__CLASS__ . ' is deprecated in drupal:11.3.0 and is removed from drupal:12.0.0. There is no replacement. See https://www.drupal.org/node/3533560', E_USER_DEPRECATED);
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->dateConfig = $date_config;
    if (!isset(static::$timezones)) {
      static::$timezones = TimeZoneFormHelper::getOptionsList();
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('config.factory')->get('system.date')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    $timezone = $row->getSourceProperty('timezone_name') ?? $row->getSourceProperty('event_timezone');

    if ($timezone === NULL || !isset(static::$timezones[$timezone])) {
      $timezone = $this->dateConfig->get('timezone.default');
    }
    return $timezone;
  }

}
