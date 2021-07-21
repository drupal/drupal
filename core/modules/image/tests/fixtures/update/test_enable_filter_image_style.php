<?php

/**
 * @file
 * Test fixture for ImageUpdateTest::testPostUpdateFilterImageStyle() test.
 */

use Drupal\Core\Database\Database;

$connection = Database::getConnection();

$data = unserialize($connection->select('config')
  ->fields('config', ['data'])
  ->condition('collection', '')
  ->condition('name', 'filter.format.full_html')
  ->execute()
  ->fetchField());

$data['filters']['filter_html'] = [
  'id' => 'filter_html',
  'provider' => 'filter',
  'status' => FALSE,
  'weight' => -10,
  'settings' => [
    'allowed_html' => '<a href hreflang> <em> <strong> <cite> <blockquote cite> <code> <ul type> <ol start type> <li> <dl> <dt> <dd> <h2 id> <h3 id> <h4 id> <h5 id> <h6 id> <s> <sup> <sub> <table> <caption> <tbody> <thead> <tfoot> <th> <td> <tr> <hr> <p> <h1> <pre> <drupal-media data-entity-type data-entity-uuid data-view-mode data-align data-caption alt title>',
    'filter_html_help' => TRUE,
    'filter_html_nofollow' => FALSE,
  ],
];

$connection->update('config')
  ->fields(['data' => serialize($data)])
  ->condition('collection', '')
  ->condition('name', 'filter.format.full_html')
  ->execute();
