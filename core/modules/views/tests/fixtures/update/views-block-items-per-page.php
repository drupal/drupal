<?php

/**
 * @file
 * Creates a Views block with an `items_per_page` setting of `none`.
 */

declare(strict_types=1);

use Drupal\Core\Database\Database;
use Drupal\Core\Serialization\Yaml;

$block_data = Yaml::decode(<<<END
uuid: ecdad54d-8165-4ed3-a678-8ad20b388282
langcode: en
status: true
dependencies:
  config:
    - views.view.who_s_online
  module:
    - views
  theme:
    - olivero
id: olivero_who_s_online
theme: olivero
region: header
weight: 0
provider: null
plugin: 'views_block:who_s_online-who_s_online_block'
settings:
  id: 'views_block:who_s_online-who_s_online_block'
  label: ''
  label_display: visible
  provider: views
  views_label: ''
  items_per_page: none
visibility: {  }
END
);

Database::getConnection()
  ->insert('config')
  ->fields([
    'collection' => '',
    'name' => 'block.block.olivero_who_s_online',
    'data' => serialize($block_data),
  ])
  ->execute();
