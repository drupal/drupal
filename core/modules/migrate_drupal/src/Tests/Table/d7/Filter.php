<?php

/**
 * @file
 * Contains \Drupal\migrate_drupal\Tests\Table\d7\Filter.
 *
 * THIS IS A GENERATED FILE. DO NOT EDIT.
 *
 * @see core/scripts/migrate-db.sh
 * @see https://www.drupal.org/sandbox/benjy/2405029
 */

namespace Drupal\migrate_drupal\Tests\Table\d7;

use Drupal\migrate_drupal\Tests\Dump\DrupalDumpBase;

/**
 * Generated file to represent the filter table.
 */
class Filter extends DrupalDumpBase {

  public function load() {
    $this->createTable("filter", array(
      'primary key' => array(
        'format',
        'name',
      ),
      'fields' => array(
        'format' => array(
          'type' => 'varchar',
          'not null' => TRUE,
          'length' => '255',
        ),
        'module' => array(
          'type' => 'varchar',
          'not null' => TRUE,
          'length' => '64',
          'default' => '',
        ),
        'name' => array(
          'type' => 'varchar',
          'not null' => TRUE,
          'length' => '32',
          'default' => '',
        ),
        'weight' => array(
          'type' => 'int',
          'not null' => TRUE,
          'length' => '11',
          'default' => '0',
        ),
        'status' => array(
          'type' => 'int',
          'not null' => TRUE,
          'length' => '11',
          'default' => '0',
        ),
        'settings' => array(
          'type' => 'blob',
          'not null' => FALSE,
          'length' => 100,
        ),
      ),
    ));
    $this->database->insert("filter")->fields(array(
      'format',
      'module',
      'name',
      'weight',
      'status',
      'settings',
    ))
    ->values(array(
      'format' => 'custom_text_format',
      'module' => 'filter',
      'name' => 'filter_autop',
      'weight' => '0',
      'status' => '1',
      'settings' => 'a:0:{}',
    ))->values(array(
      'format' => 'custom_text_format',
      'module' => 'filter',
      'name' => 'filter_html',
      'weight' => '-10',
      'status' => '1',
      'settings' => 'a:3:{s:12:"allowed_html";s:82:"<a> <em> <strong> <cite> <blockquote> <code> <ul> <ol> <li> <dl> <dt> <dd> <table>";s:16:"filter_html_help";i:1;s:20:"filter_html_nofollow";i:1;}',
    ))->values(array(
      'format' => 'custom_text_format',
      'module' => 'filter',
      'name' => 'filter_htmlcorrector',
      'weight' => '10',
      'status' => '0',
      'settings' => 'a:0:{}',
    ))->values(array(
      'format' => 'custom_text_format',
      'module' => 'filter',
      'name' => 'filter_html_escape',
      'weight' => '-10',
      'status' => '0',
      'settings' => 'a:0:{}',
    ))->values(array(
      'format' => 'custom_text_format',
      'module' => 'filter',
      'name' => 'filter_url',
      'weight' => '0',
      'status' => '0',
      'settings' => 'a:1:{s:17:"filter_url_length";s:2:"72";}',
    ))->values(array(
      'format' => 'filtered_html',
      'module' => 'filter',
      'name' => 'filter_autop',
      'weight' => '2',
      'status' => '1',
      'settings' => 'a:0:{}',
    ))->values(array(
      'format' => 'filtered_html',
      'module' => 'filter',
      'name' => 'filter_html',
      'weight' => '1',
      'status' => '1',
      'settings' => 'a:3:{s:12:"allowed_html";s:74:"<a> <em> <strong> <cite> <blockquote> <code> <ul> <ol> <li> <dl> <dt> <dd>";s:16:"filter_html_help";i:1;s:20:"filter_html_nofollow";i:0;}',
    ))->values(array(
      'format' => 'filtered_html',
      'module' => 'filter',
      'name' => 'filter_htmlcorrector',
      'weight' => '10',
      'status' => '1',
      'settings' => 'a:0:{}',
    ))->values(array(
      'format' => 'filtered_html',
      'module' => 'filter',
      'name' => 'filter_html_escape',
      'weight' => '-10',
      'status' => '0',
      'settings' => 'a:0:{}',
    ))->values(array(
      'format' => 'filtered_html',
      'module' => 'filter',
      'name' => 'filter_url',
      'weight' => '0',
      'status' => '1',
      'settings' => 'a:1:{s:17:"filter_url_length";i:72;}',
    ))->values(array(
      'format' => 'full_html',
      'module' => 'filter',
      'name' => 'filter_autop',
      'weight' => '1',
      'status' => '1',
      'settings' => 'a:0:{}',
    ))->values(array(
      'format' => 'full_html',
      'module' => 'filter',
      'name' => 'filter_html',
      'weight' => '-10',
      'status' => '0',
      'settings' => 'a:3:{s:12:"allowed_html";s:74:"<a> <em> <strong> <cite> <blockquote> <code> <ul> <ol> <li> <dl> <dt> <dd>";s:16:"filter_html_help";i:1;s:20:"filter_html_nofollow";i:0;}',
    ))->values(array(
      'format' => 'full_html',
      'module' => 'filter',
      'name' => 'filter_htmlcorrector',
      'weight' => '10',
      'status' => '1',
      'settings' => 'a:0:{}',
    ))->values(array(
      'format' => 'full_html',
      'module' => 'filter',
      'name' => 'filter_html_escape',
      'weight' => '-10',
      'status' => '0',
      'settings' => 'a:0:{}',
    ))->values(array(
      'format' => 'full_html',
      'module' => 'filter',
      'name' => 'filter_url',
      'weight' => '0',
      'status' => '1',
      'settings' => 'a:1:{s:17:"filter_url_length";i:72;}',
    ))->values(array(
      'format' => 'plain_text',
      'module' => 'filter',
      'name' => 'filter_autop',
      'weight' => '2',
      'status' => '1',
      'settings' => 'a:0:{}',
    ))->values(array(
      'format' => 'plain_text',
      'module' => 'filter',
      'name' => 'filter_html',
      'weight' => '-10',
      'status' => '0',
      'settings' => 'a:3:{s:12:"allowed_html";s:74:"<a> <em> <strong> <cite> <blockquote> <code> <ul> <ol> <li> <dl> <dt> <dd>";s:16:"filter_html_help";i:1;s:20:"filter_html_nofollow";i:0;}',
    ))->values(array(
      'format' => 'plain_text',
      'module' => 'filter',
      'name' => 'filter_htmlcorrector',
      'weight' => '10',
      'status' => '0',
      'settings' => 'a:0:{}',
    ))->values(array(
      'format' => 'plain_text',
      'module' => 'filter',
      'name' => 'filter_html_escape',
      'weight' => '0',
      'status' => '1',
      'settings' => 'a:0:{}',
    ))->values(array(
      'format' => 'plain_text',
      'module' => 'filter',
      'name' => 'filter_url',
      'weight' => '1',
      'status' => '1',
      'settings' => 'a:1:{s:17:"filter_url_length";i:72;}',
    ))->execute();
  }

}
#e6349349e505a04f97f17e2080d68135
