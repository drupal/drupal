<?php

declare(strict_types=1);

namespace Drupal\migrate_drupal_message_test\Plugin\migrate\source;

use Drupal\system\Plugin\migrate\source\Menu;

/**
 * Source plugin with a source id removed from the array returned by fields().
 *
 * @MigrateSource(
 *   id = "d7_menu_test",
 *   source_module = "menu"
 * )
 */
class MenuTest extends Menu {

  /**
   * {@inheritdoc}
   */
  public function fields() {
    $fields = parent::fields();
    unset($fields['menu_name']);
    return $fields;
  }

}
