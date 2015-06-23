<?php

/**
 * @file
 * Contains \Drupal\migrate_drupal\Tests\Table\d7\LocalesSource.
 *
 * THIS IS A GENERATED FILE. DO NOT EDIT.
 *
 * @see core/scripts/migrate-db.sh
 * @see https://www.drupal.org/sandbox/benjy/2405029
 */

namespace Drupal\migrate_drupal\Tests\Table\d7;

use Drupal\migrate_drupal\Tests\Dump\DrupalDumpBase;

/**
 * Generated file to represent the locales_source table.
 */
class LocalesSource extends DrupalDumpBase {

  public function load() {
    $this->createTable("locales_source", array(
      'primary key' => array(
        'lid',
      ),
      'fields' => array(
        'lid' => array(
          'type' => 'serial',
          'not null' => TRUE,
          'length' => '11',
        ),
        'location' => array(
          'type' => 'text',
          'not null' => FALSE,
          'length' => 100,
        ),
        'textgroup' => array(
          'type' => 'varchar',
          'not null' => TRUE,
          'length' => '255',
          'default' => 'default',
        ),
        'source' => array(
          'type' => 'blob',
          'not null' => TRUE,
          'length' => 100,
        ),
        'context' => array(
          'type' => 'varchar',
          'not null' => TRUE,
          'length' => '255',
          'default' => '',
        ),
        'version' => array(
          'type' => 'varchar',
          'not null' => TRUE,
          'length' => '20',
          'default' => 'none',
        ),
      ),
      'mysql_character_set' => 'utf8',
    ));
    $this->database->insert("locales_source")->fields(array(
      'lid',
      'location',
      'textgroup',
      'source',
      'context',
      'version',
    ))
    ->values(array(
      'lid' => '1',
      'location' => 'misc/drupal.js',
      'textgroup' => 'default',
      'source' => 'An AJAX HTTP error occurred.',
      'context' => '',
      'version' => 'none',
    ))->values(array(
      'lid' => '2',
      'location' => 'misc/drupal.js',
      'textgroup' => 'default',
      'source' => 'HTTP Result Code: !status',
      'context' => '',
      'version' => 'none',
    ))->values(array(
      'lid' => '3',
      'location' => 'misc/drupal.js',
      'textgroup' => 'default',
      'source' => 'An AJAX HTTP request terminated abnormally.',
      'context' => '',
      'version' => 'none',
    ))->values(array(
      'lid' => '4',
      'location' => 'misc/drupal.js',
      'textgroup' => 'default',
      'source' => 'Debugging information follows.',
      'context' => '',
      'version' => 'none',
    ))->values(array(
      'lid' => '5',
      'location' => 'misc/drupal.js',
      'textgroup' => 'default',
      'source' => 'Path: !uri',
      'context' => '',
      'version' => 'none',
    ))->values(array(
      'lid' => '6',
      'location' => 'misc/drupal.js',
      'textgroup' => 'default',
      'source' => 'StatusText: !statusText',
      'context' => '',
      'version' => 'none',
    ))->values(array(
      'lid' => '7',
      'location' => 'misc/drupal.js',
      'textgroup' => 'default',
      'source' => 'ResponseText: !responseText',
      'context' => '',
      'version' => 'none',
    ))->values(array(
      'lid' => '8',
      'location' => 'misc/drupal.js',
      'textgroup' => 'default',
      'source' => 'ReadyState: !readyState',
      'context' => '',
      'version' => 'none',
    ))->values(array(
      'lid' => '9',
      'location' => 'misc/collapse.js',
      'textgroup' => 'default',
      'source' => 'Hide',
      'context' => '',
      'version' => 'none',
    ))->values(array(
      'lid' => '10',
      'location' => 'misc/collapse.js',
      'textgroup' => 'default',
      'source' => 'Show',
      'context' => '',
      'version' => 'none',
    ))->values(array(
      'lid' => '11',
      'location' => 'modules/toolbar/toolbar.js',
      'textgroup' => 'default',
      'source' => 'Show shortcuts',
      'context' => '',
      'version' => 'none',
    ))->values(array(
      'lid' => '12',
      'location' => 'modules/toolbar/toolbar.js',
      'textgroup' => 'default',
      'source' => 'Hide shortcuts',
      'context' => '',
      'version' => 'none',
    ))->values(array(
      'lid' => '13',
      'location' => 'misc/machine-name.js',
      'textgroup' => 'default',
      'source' => 'Edit',
      'context' => '',
      'version' => 'none',
    ))->values(array(
      'lid' => '14',
      'location' => 'modules/comment/comment-node-form.js',
      'textgroup' => 'default',
      'source' => '@number comments per page',
      'context' => '',
      'version' => 'none',
    ))->values(array(
      'lid' => '15',
      'location' => 'misc/vertical-tabs.js',
      'textgroup' => 'default',
      'source' => '(active tab)',
      'context' => '',
      'version' => 'none',
    ))->values(array(
      'lid' => '16',
      'location' => 'modules/node/content_types.js',
      'textgroup' => 'default',
      'source' => 'Requires a title',
      'context' => '',
      'version' => 'none',
    ))->values(array(
      'lid' => '17',
      'location' => 'modules/node/content_types.js; modules/node/node.js',
      'textgroup' => 'default',
      'source' => 'Not published',
      'context' => '',
      'version' => 'none',
    ))->values(array(
      'lid' => '18',
      'location' => 'modules/node/content_types.js',
      'textgroup' => 'default',
      'source' => "Don't display post information",
      'context' => '',
      'version' => 'none',
    ))->values(array(
      'lid' => '19',
      'location' => 'misc/tabledrag.js',
      'textgroup' => 'default',
      'source' => 'Re-order rows by numerical weight instead of dragging.',
      'context' => '',
      'version' => 'none',
    ))->values(array(
      'lid' => '20',
      'location' => 'misc/tabledrag.js',
      'textgroup' => 'default',
      'source' => 'Show row weights',
      'context' => '',
      'version' => 'none',
    ))->values(array(
      'lid' => '21',
      'location' => 'misc/tabledrag.js',
      'textgroup' => 'default',
      'source' => 'Hide row weights',
      'context' => '',
      'version' => 'none',
    ))->values(array(
      'lid' => '22',
      'location' => 'misc/tabledrag.js',
      'textgroup' => 'default',
      'source' => 'Drag to re-order',
      'context' => '',
      'version' => 'none',
    ))->values(array(
      'lid' => '23',
      'location' => 'misc/tabledrag.js',
      'textgroup' => 'default',
      'source' => 'Changes made in this table will not be saved until the form is submitted.',
      'context' => '',
      'version' => 'none',
    ))->values(array(
      'lid' => '24',
      'location' => 'sites/all/modules/date/date_api/date_year_range.js',
      'textgroup' => 'default',
      'source' => 'Other',
      'context' => '',
      'version' => 'none',
    ))->values(array(
      'lid' => '25',
      'location' => 'sites/all/modules/date/date_api/date_year_range.js',
      'textgroup' => 'default',
      'source' => '@count year from now',
      'context' => '',
      'version' => 'none',
    ))->values(array(
      'lid' => '26',
      'location' => 'sites/all/modules/date/date_api/date_year_range.js',
      'textgroup' => 'default',
      'source' => '@count years from now',
      'context' => '',
      'version' => 'none',
    ))->values(array(
      'lid' => '27',
      'location' => 'modules/file/file.js',
      'textgroup' => 'default',
      'source' => 'The selected file %filename cannot be uploaded. Only files with the following extensions are allowed: %extensions.',
      'context' => '',
      'version' => 'none',
    ))->values(array(
      'lid' => '28',
      'location' => 'misc/ajax.js',
      'textgroup' => 'default',
      'source' => 'Please wait...',
      'context' => '',
      'version' => 'none',
    ))->values(array(
      'lid' => '29',
      'location' => 'modules/field/modules/text/text.js',
      'textgroup' => 'default',
      'source' => 'Hide summary',
      'context' => '',
      'version' => 'none',
    ))->values(array(
      'lid' => '30',
      'location' => 'modules/field/modules/text/text.js',
      'textgroup' => 'default',
      'source' => 'Edit summary',
      'context' => '',
      'version' => 'none',
    ))->values(array(
      'lid' => '31',
      'location' => 'misc/autocomplete.js',
      'textgroup' => 'default',
      'source' => 'Autocomplete popup',
      'context' => '',
      'version' => 'none',
    ))->values(array(
      'lid' => '32',
      'location' => 'misc/autocomplete.js',
      'textgroup' => 'default',
      'source' => 'Searching for matches...',
      'context' => '',
      'version' => 'none',
    ))->values(array(
      'lid' => '33',
      'location' => 'modules/contextual/contextual.js',
      'textgroup' => 'default',
      'source' => 'Configure',
      'context' => '',
      'version' => 'none',
    ))->values(array(
      'lid' => '34',
      'location' => 'misc/tableselect.js',
      'textgroup' => 'default',
      'source' => 'Select all rows in this table',
      'context' => '',
      'version' => 'none',
    ))->values(array(
      'lid' => '35',
      'location' => 'misc/tableselect.js',
      'textgroup' => 'default',
      'source' => 'Deselect all rows in this table',
      'context' => '',
      'version' => 'none',
    ))->values(array(
      'lid' => '36',
      'location' => 'modules/user/user.permissions.js',
      'textgroup' => 'default',
      'source' => 'This permission is inherited from the authenticated user role.',
      'context' => '',
      'version' => 'none',
    ))->values(array(
      'lid' => '37',
      'location' => 'modules/filter/filter.admin.js',
      'textgroup' => 'default',
      'source' => 'Enabled',
      'context' => '',
      'version' => 'none',
    ))->values(array(
      'lid' => '38',
      'location' => 'modules/filter/filter.admin.js',
      'textgroup' => 'default',
      'source' => 'Disabled',
      'context' => '',
      'version' => 'none',
    ))->values(array(
      'lid' => '39',
      'location' => 'modules/menu/menu.js',
      'textgroup' => 'default',
      'source' => 'Not in menu',
      'context' => '',
      'version' => 'none',
    ))->values(array(
      'lid' => '40',
      'location' => 'modules/book/book.js',
      'textgroup' => 'default',
      'source' => 'Not in book',
      'context' => '',
      'version' => 'none',
    ))->values(array(
      'lid' => '41',
      'location' => 'modules/book/book.js',
      'textgroup' => 'default',
      'source' => 'New book',
      'context' => '',
      'version' => 'none',
    ))->values(array(
      'lid' => '42',
      'location' => 'modules/node/node.js',
      'textgroup' => 'default',
      'source' => 'New revision',
      'context' => '',
      'version' => 'none',
    ))->values(array(
      'lid' => '43',
      'location' => 'modules/node/node.js',
      'textgroup' => 'default',
      'source' => 'No revision',
      'context' => '',
      'version' => 'none',
    ))->values(array(
      'lid' => '44',
      'location' => 'modules/node/node.js',
      'textgroup' => 'default',
      'source' => 'By @name on @date',
      'context' => '',
      'version' => 'none',
    ))->values(array(
      'lid' => '45',
      'location' => 'modules/node/node.js',
      'textgroup' => 'default',
      'source' => 'By @name',
      'context' => '',
      'version' => 'none',
    ))->values(array(
      'lid' => '46',
      'location' => 'modules/path/path.js',
      'textgroup' => 'default',
      'source' => 'Alias: @alias',
      'context' => '',
      'version' => 'none',
    ))->values(array(
      'lid' => '47',
      'location' => 'modules/path/path.js',
      'textgroup' => 'default',
      'source' => 'No alias',
      'context' => '',
      'version' => 'none',
    ))->execute();
  }

}
#b7f4cb34968312ad989a50d27f42ccbf
