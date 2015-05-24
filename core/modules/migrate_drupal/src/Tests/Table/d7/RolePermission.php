<?php

/**
 * @file
 * Contains \Drupal\migrate_drupal\Tests\Table\d7\RolePermission.
 *
 * THIS IS A GENERATED FILE. DO NOT EDIT.
 *
 * @see core/scripts/migrate-db.sh
 * @see https://www.drupal.org/sandbox/benjy/2405029
 */

namespace Drupal\migrate_drupal\Tests\Table\d7;

use Drupal\migrate_drupal\Tests\Dump\DrupalDumpBase;

/**
 * Generated file to represent the role_permission table.
 */
class RolePermission extends DrupalDumpBase {

  public function load() {
    $this->createTable("role_permission", array(
      'primary key' => array(
        'rid',
        'permission',
      ),
      'fields' => array(
        'rid' => array(
          'type' => 'int',
          'not null' => TRUE,
          'length' => '10',
          'unsigned' => TRUE,
        ),
        'permission' => array(
          'type' => 'varchar',
          'not null' => TRUE,
          'length' => '128',
          'default' => '',
        ),
        'module' => array(
          'type' => 'varchar',
          'not null' => TRUE,
          'length' => '255',
          'default' => '',
        ),
      ),
    ));
    $this->database->insert("role_permission")->fields(array(
      'rid',
      'permission',
      'module',
    ))
    ->values(array(
      'rid' => '3',
      'permission' => 'access administration pages',
      'module' => 'system',
    ))->values(array(
      'rid' => '3',
      'permission' => 'access all views',
      'module' => 'views',
    ))->values(array(
      'rid' => '1',
      'permission' => 'access comments',
      'module' => 'comment',
    ))->values(array(
      'rid' => '2',
      'permission' => 'access comments',
      'module' => 'comment',
    ))->values(array(
      'rid' => '3',
      'permission' => 'access comments',
      'module' => 'comment',
    ))->values(array(
      'rid' => '1',
      'permission' => 'access content',
      'module' => 'node',
    ))->values(array(
      'rid' => '2',
      'permission' => 'access content',
      'module' => 'node',
    ))->values(array(
      'rid' => '3',
      'permission' => 'access content',
      'module' => 'node',
    ))->values(array(
      'rid' => '3',
      'permission' => 'access content overview',
      'module' => 'node',
    ))->values(array(
      'rid' => '3',
      'permission' => 'access contextual links',
      'module' => 'contextual',
    ))->values(array(
      'rid' => '3',
      'permission' => 'access dashboard',
      'module' => 'dashboard',
    ))->values(array(
      'rid' => '3',
      'permission' => 'access news feeds',
      'module' => 'aggregator',
    ))->values(array(
      'rid' => '3',
      'permission' => 'access overlay',
      'module' => 'overlay',
    ))->values(array(
      'rid' => '3',
      'permission' => 'access printer-friendly version',
      'module' => 'book',
    ))->values(array(
      'rid' => '3',
      'permission' => 'access site in maintenance mode',
      'module' => 'system',
    ))->values(array(
      'rid' => '3',
      'permission' => 'access site reports',
      'module' => 'system',
    ))->values(array(
      'rid' => '3',
      'permission' => 'access site-wide contact form',
      'module' => 'contact',
    ))->values(array(
      'rid' => '3',
      'permission' => 'access statistics',
      'module' => 'statistics',
    ))->values(array(
      'rid' => '3',
      'permission' => 'access toolbar',
      'module' => 'toolbar',
    ))->values(array(
      'rid' => '3',
      'permission' => 'access user contact forms',
      'module' => 'contact',
    ))->values(array(
      'rid' => '3',
      'permission' => 'access user profiles',
      'module' => 'user',
    ))->values(array(
      'rid' => '3',
      'permission' => 'add content to books',
      'module' => 'book',
    ))->values(array(
      'rid' => '3',
      'permission' => 'administer actions',
      'module' => 'system',
    ))->values(array(
      'rid' => '3',
      'permission' => 'administer blocks',
      'module' => 'block',
    ))->values(array(
      'rid' => '3',
      'permission' => 'administer book outlines',
      'module' => 'book',
    ))->values(array(
      'rid' => '3',
      'permission' => 'administer comments',
      'module' => 'comment',
    ))->values(array(
      'rid' => '3',
      'permission' => 'administer contact forms',
      'module' => 'contact',
    ))->values(array(
      'rid' => '3',
      'permission' => 'administer content types',
      'module' => 'node',
    ))->values(array(
      'rid' => '3',
      'permission' => 'administer filters',
      'module' => 'filter',
    ))->values(array(
      'rid' => '3',
      'permission' => 'administer forums',
      'module' => 'forum',
    ))->values(array(
      'rid' => '3',
      'permission' => 'administer image styles',
      'module' => 'image',
    ))->values(array(
      'rid' => '3',
      'permission' => 'administer languages',
      'module' => 'locale',
    ))->values(array(
      'rid' => '3',
      'permission' => 'administer menu',
      'module' => 'menu',
    ))->values(array(
      'rid' => '3',
      'permission' => 'administer modules',
      'module' => 'system',
    ))->values(array(
      'rid' => '3',
      'permission' => 'administer news feeds',
      'module' => 'aggregator',
    ))->values(array(
      'rid' => '3',
      'permission' => 'administer nodes',
      'module' => 'node',
    ))->values(array(
      'rid' => '3',
      'permission' => 'administer permissions',
      'module' => 'user',
    ))->values(array(
      'rid' => '3',
      'permission' => 'administer search',
      'module' => 'search',
    ))->values(array(
      'rid' => '3',
      'permission' => 'administer shortcuts',
      'module' => 'shortcut',
    ))->values(array(
      'rid' => '3',
      'permission' => 'administer site configuration',
      'module' => 'system',
    ))->values(array(
      'rid' => '3',
      'permission' => 'administer software updates',
      'module' => 'system',
    ))->values(array(
      'rid' => '3',
      'permission' => 'administer statistics',
      'module' => 'statistics',
    ))->values(array(
      'rid' => '3',
      'permission' => 'administer taxonomy',
      'module' => 'taxonomy',
    ))->values(array(
      'rid' => '3',
      'permission' => 'administer themes',
      'module' => 'system',
    ))->values(array(
      'rid' => '3',
      'permission' => 'administer unit tests',
      'module' => 'simpletest',
    ))->values(array(
      'rid' => '3',
      'permission' => 'administer url aliases',
      'module' => 'path',
    ))->values(array(
      'rid' => '3',
      'permission' => 'administer users',
      'module' => 'user',
    ))->values(array(
      'rid' => '3',
      'permission' => 'administer views',
      'module' => 'views',
    ))->values(array(
      'rid' => '3',
      'permission' => 'block IP addresses',
      'module' => 'system',
    ))->values(array(
      'rid' => '3',
      'permission' => 'bypass node access',
      'module' => 'node',
    ))->values(array(
      'rid' => '3',
      'permission' => 'cancel account',
      'module' => 'user',
    ))->values(array(
      'rid' => '3',
      'permission' => 'change own username',
      'module' => 'user',
    ))->values(array(
      'rid' => '3',
      'permission' => 'create article content',
      'module' => 'node',
    ))->values(array(
      'rid' => '3',
      'permission' => 'create new books',
      'module' => 'book',
    ))->values(array(
      'rid' => '3',
      'permission' => 'create page content',
      'module' => 'node',
    ))->values(array(
      'rid' => '3',
      'permission' => 'create url aliases',
      'module' => 'path',
    ))->values(array(
      'rid' => '3',
      'permission' => 'customize shortcut links',
      'module' => 'shortcut',
    ))->values(array(
      'rid' => '3',
      'permission' => 'delete any article content',
      'module' => 'node',
    ))->values(array(
      'rid' => '3',
      'permission' => 'delete any page content',
      'module' => 'node',
    ))->values(array(
      'rid' => '3',
      'permission' => 'delete own article content',
      'module' => 'node',
    ))->values(array(
      'rid' => '3',
      'permission' => 'delete own page content',
      'module' => 'node',
    ))->values(array(
      'rid' => '3',
      'permission' => 'delete revisions',
      'module' => 'node',
    ))->values(array(
      'rid' => '3',
      'permission' => 'delete terms in 1',
      'module' => 'taxonomy',
    ))->values(array(
      'rid' => '3',
      'permission' => 'edit any article content',
      'module' => 'node',
    ))->values(array(
      'rid' => '3',
      'permission' => 'edit any page content',
      'module' => 'node',
    ))->values(array(
      'rid' => '3',
      'permission' => 'edit own article content',
      'module' => 'node',
    ))->values(array(
      'rid' => '3',
      'permission' => 'edit own comments',
      'module' => 'comment',
    ))->values(array(
      'rid' => '3',
      'permission' => 'edit own page content',
      'module' => 'node',
    ))->values(array(
      'rid' => '3',
      'permission' => 'edit terms in 1',
      'module' => 'taxonomy',
    ))->values(array(
      'rid' => '2',
      'permission' => 'post comments',
      'module' => 'comment',
    ))->values(array(
      'rid' => '3',
      'permission' => 'post comments',
      'module' => 'comment',
    ))->values(array(
      'rid' => '3',
      'permission' => 'revert revisions',
      'module' => 'node',
    ))->values(array(
      'rid' => '3',
      'permission' => 'search content',
      'module' => 'search',
    ))->values(array(
      'rid' => '3',
      'permission' => 'select account cancellation method',
      'module' => 'user',
    ))->values(array(
      'rid' => '2',
      'permission' => 'skip comment approval',
      'module' => 'comment',
    ))->values(array(
      'rid' => '3',
      'permission' => 'skip comment approval',
      'module' => 'comment',
    ))->values(array(
      'rid' => '3',
      'permission' => 'switch shortcut sets',
      'module' => 'shortcut',
    ))->values(array(
      'rid' => '3',
      'permission' => 'translate content',
      'module' => 'translation',
    ))->values(array(
      'rid' => '3',
      'permission' => 'translate interface',
      'module' => 'locale',
    ))->values(array(
      'rid' => '3',
      'permission' => 'use advanced search',
      'module' => 'search',
    ))->values(array(
      'rid' => '2',
      'permission' => 'use text format custom_text_format',
      'module' => 'filter',
    ))->values(array(
      'rid' => '3',
      'permission' => 'use text format custom_text_format',
      'module' => 'filter',
    ))->values(array(
      'rid' => '1',
      'permission' => 'use text format filtered_html',
      'module' => 'filter',
    ))->values(array(
      'rid' => '2',
      'permission' => 'use text format filtered_html',
      'module' => 'filter',
    ))->values(array(
      'rid' => '3',
      'permission' => 'use text format filtered_html',
      'module' => 'filter',
    ))->values(array(
      'rid' => '3',
      'permission' => 'use text format full_html',
      'module' => 'filter',
    ))->values(array(
      'rid' => '3',
      'permission' => 'view own unpublished content',
      'module' => 'node',
    ))->values(array(
      'rid' => '3',
      'permission' => 'view post access counter',
      'module' => 'statistics',
    ))->values(array(
      'rid' => '3',
      'permission' => 'view revisions',
      'module' => 'node',
    ))->values(array(
      'rid' => '3',
      'permission' => 'view the administration theme',
      'module' => 'system',
    ))->execute();
  }

}
#250f49f0121123b59282926cbe8b7d00
