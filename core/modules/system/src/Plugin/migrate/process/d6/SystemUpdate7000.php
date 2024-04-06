<?php

namespace Drupal\system\Plugin\migrate\process\d6;

use Drupal\migrate\Attribute\MigrateProcess;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;

/**
 * Rename blog and forum permissions to be consistent with other content types.
 */
#[MigrateProcess('system_update_7000')]
class SystemUpdate7000 extends ProcessPluginBase {

  /**
   * {@inheritdoc}
   *
   * Rename blog and forum permissions to be consistent with other content types.
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
