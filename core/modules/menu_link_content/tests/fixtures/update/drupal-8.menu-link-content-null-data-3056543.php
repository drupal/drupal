<?php

/**
 * @file
 * Contains database additions to drupal-8.filled.standard.php.gz for testing
 * the upgrade path of https://www.drupal.org/project/drupal/issues/3056543.
 */

use Drupal\Core\Database\Database;

$connection = Database::getConnection();

$connection->insert('menu_link_content')
  ->fields([
    'id' => 997,
    'bundle' => 'menu_link_content',
    'uuid' => 'ea32f399-b53b-416c-81a9-e66204236c97',
    'langcode' => 'en',
  ])
  ->execute();
$connection->insert('menu_link_content_data')
  ->fields([
    'id' => 997,
    'bundle' => 'menu_link_content',
    'langcode' => 'en',
    'enabled' => 1,
    'title' => 'menu_link_997',
    'menu_name' => 'test-menu',
    'link__uri' => 'https://drupal.org',
    'link__title' => '',
    'link__options' => 'a:0:{}',
    'external' => 0,
    'rediscover' => 0,
    'weight' => 0,
    'expanded' => 0,
    'changed' => 1579555997,
    'default_langcode' => 0,
  ])
  ->execute();

$connection->insert('menu_link_content')
  ->fields([
    'id' => 998,
    'bundle' => 'menu_link_content',
    'uuid' => 'ea32f399-b53b-416c-81a9-e66204236c98',
    'langcode' => 'en',
  ])
  ->execute();
$connection->insert('menu_link_content_data')
  ->fields([
    'id' => 998,
    'bundle' => 'menu_link_content',
    'langcode' => 'en',
    'enabled' => 1,
    'title' => 'menu_link_998',
    'menu_name' => 'test-menu',
    'link__uri' => 'https://drupal.org',
    'link__title' => '',
    'link__options' => 'a:0:{}',
    'external' => 0,
    'rediscover' => 0,
    'weight' => 0,
    'expanded' => 0,
    'changed' => 1579555997,
    'default_langcode' => 0,
  ])
  ->execute();

$connection->insert('menu_link_content')
  ->fields([
    'id' => 999,
    'bundle' => 'menu_link_content',
    'uuid' => 'ea32f399-b53b-416c-81a9-e66204236c99',
    'langcode' => 'en',
  ])
  ->execute();
$connection->insert('menu_link_content_data')
  ->fields([
    'id' => 999,
    'bundle' => 'menu_link_content',
    'langcode' => 'en',
    'enabled' => 1,
    'title' => 'menu_link_999',
    'menu_name' => 'test-menu',
    'link__uri' => 'https://drupal.org',
    'link__title' => '',
    'link__options' => 'a:0:{}',
    'external' => 0,
    'rediscover' => 0,
    'weight' => 0,
    'expanded' => 0,
    'changed' => 1579555997,
    'default_langcode' => 0,
  ])
  ->execute();
$connection->insert('menu_link_content_data')
  ->fields([
    'id' => 999,
    'bundle' => 'menu_link_content',
    'langcode' => 'es',
    'enabled' => 1,
    'title' => 'menu_link_999-es',
    'menu_name' => 'test-menu',
    'link__uri' => 'https://drupal.org',
    'link__title' => '',
    'link__options' => 'a:0:{}',
    'external' => 0,
    'rediscover' => 0,
    'weight' => 0,
    'expanded' => 0,
    'changed' => 1579555997,
    'default_langcode' => 1,
  ])
  ->execute();
