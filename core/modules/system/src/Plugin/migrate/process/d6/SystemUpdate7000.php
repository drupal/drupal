<?php

namespace Drupal\system\Plugin\migrate\process\d6;

use Drupal\migrate\Attribute\MigrateProcess;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;

/**
 * Rename blog and forum permissions to be consistent with other content types.
 *
 * @deprecated in drupal:11.3.0 and is removed from drupal:12.0.0. There is no
 *   replacement.
 *
 * @see https://www.drupal.org/node/3533560
 */
#[MigrateProcess('system_update_7000')]
class SystemUpdate7000 extends ProcessPluginBase {

  public function __construct(array $configuration, $plugin_id, $plugin_definition) {
    @trigger_error(__CLASS__ . ' is deprecated in drupal:11.3.0 and is removed from drupal:12.0.0. There is no replacement. See https://www.drupal.org/node/3533560', E_USER_DEPRECATED);
    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }

  /**
   * {@inheritdoc}
   *
   * Makes blog and forum permissions to be consistent with other content types.
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    $value = preg_replace('/(?<=^|,\ )create\ blog\ entries(?=,|$)/', 'create blog content', $value);
    $value = preg_replace('/(?<=^|,\ )edit\ own\ blog\ entries(?=,|$)/', 'edit own blog content', $value);
    $value = preg_replace('/(?<=^|,\ )edit\ any\ blog\ entry(?=,|$)/', 'edit any blog content', $value);
    $value = preg_replace('/(?<=^|,\ )delete\ own\ blog\ entries(?=,|$)/', 'delete own blog content', $value);
    $value = preg_replace('/(?<=^|,\ )delete\ any\ blog\ entry(?=,|$)/', 'delete any blog content', $value);

    $value = preg_replace('/(?<=^|,\ )create\ forum\ topics(?=,|$)/', 'create forum content', $value);
    $value = preg_replace('/(?<=^|,\ )delete\ any\ forum\ topic(?=,|$)/', 'delete any forum content', $value);
    $value = preg_replace('/(?<=^|,\ )delete\ own\ forum\ topics(?=,|$)/', 'delete own forum content', $value);
    $value = preg_replace('/(?<=^|,\ )edit\ any\ forum\ topic(?=,|$)/', 'edit any forum content', $value);
    $value = preg_replace('/(?<=^|,\ )edit\ own\ forum\ topics(?=,|$)/', 'edit own forum content', $value);
    return $value;
  }

}
