<?php
// $Id: install.mysqli.inc,v 1.6 2006/12/27 13:02:34 drumm Exp $

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
  $url['pass'] = urldecode($url['pass']);
  $url['host'] = urldecode($url['host']);
  $url['path'] = urldecode($url['path']);

  $connection = mysqli_init();
  @mysqli_real_connect($connection, $url['host'], $url['user'], $url['pass'], substr($url['path'], 1), $url['port'], NULL, MYSQLI_CLIENT_FOUND_ROWS);
  if (mysqli_connect_errno() >= 2000 || mysqli_connect_errno() == 1045) {
    drupal_set_message(st('Failure to connect to your MySQL database server. MySQL reports the following message: %error.<ul><li>Are you sure you have the correct username and password?</li><li>Are you sure that you have typed the correct database hostname?</li><li>Are you sure that the database server is running?</li></ul>For more help, see the <a href="http://drupal.org/node/258">Installation and upgrading handbook</a>. If you are unsure what these terms mean you should probably contact your hosting provider.', array('%error' => mysqli_error($connection))), 'error');
    return FALSE;
  }

  // Test selecting the database.
  if (mysqli_connect_errno() > 0) {
    drupal_set_message(st('We were able to connect to the MySQL database server (which means your username and password are valid) but not able to select your database. MySQL reports the following message: %error.<ul><li>Are you sure you have the correct database name?</li><li>Are you sure the database exists?</li><li>Are you sure the username has permission to access the database?</li></ul>For more help, see the <a href="http://drupal.org/node/258">Installation and upgrading handbook</a>. If you are unsure what these terms mean you should probably contact your hosting provider.', array('%error' => mysqli_error($connection))), 'error');
    return FALSE;
  }

  $success = array('CONNECT');

  // Test CREATE.
  $query = 'CREATE TABLE drupal_install_test (id int NULL)';
  $result = mysqli_query($connection, $query);
  if ($error = mysqli_error($connection)) {
    drupal_set_message(st('We were unable to create a test table on your MySQL database server with the command %query. MySQL reports the following message: %error.<ul><li>Are you sure the configured username has the necessary MySQL permissions to create tables in the database?</li></ul>For more help, see the <a href="http://drupal.org/node/258">Installation and upgrading handbook</a>. If you are unsure what these terms mean you should probably contact your hosting provider.', array('%query' => $query, '%error' => $error)), 'error');
    return FALSE;
  }
  $err = FALSE;
  $success[] = 'SELECT';
  $success[] = 'CREATE';

  // Test INSERT.
  $query = 'INSERT INTO drupal_install_test (id) VALUES (1)';
  $result = mysqli_query($connection, $query);
  if ($error = mysqli_error($connection)) {
    drupal_set_message(st('We were unable to insert a value into a test table on your MySQL database server. We tried inserting a value with the command %query and MySQL reported the following error: %error.', array('%query' => $query, '%error' => $error)), 'error');
    $err = TRUE;
  }
  else {
    $success[] = 'INSERT';
  }

  // Test UPDATE.
  $query = 'UPDATE drupal_install_test SET id = 2';
  $result = mysqli_query($connection, $query);
  if ($error = mysqli_error($connection)) {
    drupal_set_message(st('We were unable to update a value in a test table on your MySQL database server. We tried updating a value with the command %query and MySQL reported the following error: %error.', array('%query' => $query, '%error' => $error)), 'error');
    $err = TRUE;
  }
  else {
    $success[] = 'UPDATE';
  }

  // Test LOCK.
  $query = 'LOCK TABLES drupal_install_test WRITE';
  $result = mysqli_query($connection, $query);
  if ($error = mysqli_error($connection)) {
    drupal_set_message(st('We were unable to lock a test table on your MySQL database server. We tried locking a table with the command %query and MySQL reported the following error: %error.', array('%query' => $query, '%error' => $error)), 'error');
    $err = TRUE;
  }
  else {
    $success[] = 'LOCK';
  }

  // Test UNLOCK.
  $query = 'UNLOCK TABLES';
  $result = mysqli_query($connection, $query);
  if ($error = mysqli_error($connection)) {
    drupal_set_message(st('We were unable to unlock a test table on your MySQL database server. We tried unlocking a table with the command %query and MySQL reported the following error: %error.', array('%query' => $query, '%error' => $error)), 'error');
    $err = TRUE;
  }
  else {
    $success[] = 'UNLOCK';
  }

  // Test DELETE.
  $query = 'DELETE FROM drupal_install_test';
  $result = mysqli_query($connection, $query);
  if ($error = mysqli_error($connection)) {
    drupal_set_message(st('We were unable to delete a value from a test table on your MySQL database server. We tried deleting a value with the command %query and MySQL reported the following error: %error.', array('%query' => $query, '%error' => $error)), 'error');
    $err = TRUE;
  }
  else {
    $success[] = 'DELETE';
  }

  // Test DROP.
  $query = 'DROP TABLE drupal_install_test';
  $result = mysqli_query($connection, $query);
  if ($error = mysqli_error($connection)) {
    drupal_set_message(st('We were unable to drop a test table from your MySQL database server. We tried dropping a table with the command %query and MySQL reported the following error %error.', array('%query' => $query, '%error' => $error)), 'error');
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

?>
