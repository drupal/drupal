<?php
// $Id$

// MySQLi specific install functions

/**
 * Check if MySQLi is available.
 *
 * @return
 *  TRUE/FALSE
 */
function mysqli_is_available() {
  return function_exists('mysqli_connect');
}

/**
 * Check if we can connect to MySQL.
 *
 * @return
 *  TRUE/FALSE
 */
function drupal_test_mysqli($url, &$success) {
  if (!mysqli_is_available()) {
    drupal_set_message(st('PHP MySQLi support not enabled.'), 'error');
    return FALSE;
  }

  $url = parse_url($url);

  // Decode url-encoded information in the db connection string.
  $url['user'] = urldecode($url['user']);
  $url['pass'] = isset($url['pass']) ? urldecode($url['pass']) : '';
  $url['host'] = urldecode($url['host']);
  $url['path'] = urldecode($url['path']);

  $connection = mysqli_init();
  @mysqli_real_connect($connection, $url['host'], $url['user'], $url['pass'], substr($url['path'], 1), $url['port'], NULL, MYSQLI_CLIENT_FOUND_ROWS);
  if (mysqli_connect_errno() >= 2000 || mysqli_connect_errno() == 1045) {
    drupal_set_message(st('Failed to connect to your MySQL database server. MySQL reports the following message: %error.<ul><li>Are you sure you have the correct username and password?</li><li>Are you sure that you have typed the correct database hostname?</li><li>Are you sure that the database server is running?</li></ul>For more help, see the <a href="http://drupal.org/node/258">Installation and upgrading handbook</a>. If you are unsure what these terms mean you should probably contact your hosting provider.', array('%error' => mysqli_connect_error())), 'error');
    return FALSE;
  }

  // Test selecting the database.
  if (mysqli_connect_errno() > 0) {
    drupal_set_message(st('Failed to select your database on your MySQL database server, which means the connection username and password are valid, but there is a problem accessing your data. MySQL reports the following message: %error.<ul><li>Are you sure you have the correct database name?</li><li>Are you sure the database exists?</li><li>Are you sure the username has permission to access the database?</li></ul>For more help, see the <a href="http://drupal.org/node/258">Installation and upgrading handbook</a>. If you are unsure what these terms mean you should probably contact your hosting provider.', array('%error' => mysqli_connect_error())), 'error');
    return FALSE;
  }

  $success = array('CONNECT');

  // Test CREATE.
  $query = 'CREATE TABLE drupal_install_test (id int NULL)';
  $result = mysqli_query($connection, $query);
  if ($error = mysqli_error($connection)) {
    drupal_set_message(st('Failed to create a test table on your MySQL database server with the command %query. MySQL reports the following message: %error.<ul><li>Are you sure the configured username has the necessary MySQL permissions to create tables in the database?</li></ul>For more help, see the <a href="http://drupal.org/node/258">Installation and upgrading handbook</a>. If you are unsure what these terms mean you should probably contact your hosting provider.', array('%query' => $query, '%error' => $error)), 'error');
    return FALSE;
  }
  $err = FALSE;
  $success[] = 'SELECT';
  $success[] = 'CREATE';

  // Test INSERT.
  $query = 'INSERT INTO drupal_install_test (id) VALUES (1)';
  $result = mysqli_query($connection, $query);
  if ($error = mysqli_error($connection)) {
    drupal_set_message(st('Failed to insert a value into a test table on your MySQL database server. We tried inserting a value with the command %query and MySQL reported the following error: %error.', array('%query' => $query, '%error' => $error)), 'error');
    $err = TRUE;
  }
  else {
    $success[] = 'INSERT';
  }

  // Test UPDATE.
  $query = 'UPDATE drupal_install_test SET id = 2';
  $result = mysqli_query($connection, $query);
  if ($error = mysqli_error($connection)) {
    drupal_set_message(st('Failed to update a value in a test table on your MySQL database server. We tried updating a value with the command %query and MySQL reported the following error: %error.', array('%query' => $query, '%error' => $error)), 'error');
    $err = TRUE;
  }
  else {
    $success[] = 'UPDATE';
  }

  // Test DELETE.
  $query = 'DELETE FROM drupal_install_test';
  $result = mysqli_query($connection, $query);
  if ($error = mysqli_error($connection)) {
    drupal_set_message(st('Failed to delete a value from a test table on your MySQL database server. We tried deleting a value with the command %query and MySQL reported the following error: %error.', array('%query' => $query, '%error' => $error)), 'error');
    $err = TRUE;
  }
  else {
    $success[] = 'DELETE';
  }

  // Test DROP.
  $query = 'DROP TABLE drupal_install_test';
  $result = mysqli_query($connection, $query);
  if ($error = mysqli_error($connection)) {
    drupal_set_message(st('Failed to drop a test table from your MySQL database server. We tried dropping a table with the command %query and MySQL reported the following error %error.', array('%query' => $query, '%error' => $error)), 'error');
    $err = TRUE;
  }
  else {
    $success[] = 'DROP';
  }

  if ($err) {
    return FALSE;
  }

  mysqli_close($connection);
  return TRUE;
}
