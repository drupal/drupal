<?php
// $Id: install.mysql.inc,v 1.4 2006/12/27 13:02:34 drumm Exp $

// MySQL specific install functions

/**
 * Check if MySQL is available.
 *
 * @return
 *  TRUE/FALSE
 */
function mysql_is_available() {
  return function_exists('mysql_connect');
}

/**
 * Check if we can connect to MySQL.
 *
 * @return
 *  TRUE/FALSE
 */
function drupal_test_mysql($url, &$success) {
  if (!mysql_is_available()) {
    drupal_set_message(st('PHP MySQL support not enabled.'), 'error');
    return FALSE;
  }

  $url = parse_url($url);

  // Decode url-encoded information in the db connection string.
  $url['user'] = urldecode($url['user']);
  $url['pass'] = urldecode($url['pass']);
  $url['host'] = urldecode($url['host']);
  $url['path'] = urldecode($url['path']);

  // Allow for non-standard MySQL port.
  if (isset($url['port'])) {
     $url['host'] = $url['host'] .':'. $url['port'];
  }

  // Test connecting to the database.
  $connection = @mysql_connect($url['host'], $url['user'], $url['pass'], TRUE, 2);
  if (!$connection) {
    drupal_set_message(st('Failure to connect to your MySQL database server. MySQL reports the following message: %error.<ul><li>Are you sure you have the correct username and password?</li><li>Are you sure that you have typed the correct database hostname?</li><li>Are you sure that the database server is running?</li></ul>For more help, see the <a href="http://drupal.org/node/258">Installation and upgrading handbook</a>. If you are unsure what these terms mean you should probably contact your hosting provider.', array('%error' => mysql_error())), 'error');
    return FALSE;
  }

  // Test selecting the database.
  if (!mysql_select_db(substr($url['path'], 1))) {
    drupal_set_message(st('We were able to connect to the MySQL database server (which means your username and password are valid) but not able to select your database. MySQL reports the following message: %error.<ul><li>Are you sure you have the correct database name?</li><li>Are you sure the database exists?</li><li>Are you sure the username has permission to access the database?</li></ul>For more help, see the <a href="http://drupal.org/node/258">Installation and upgrading handbook</a>. If you are unsure what these terms mean you should probably contact your hosting provider.', array('%error' => mysql_error())), 'error');
    return FALSE;
  }

  $success = array('CONNECT');

  // Test CREATE.
  $query = 'CREATE TABLE drupal_install_test (id int NULL)';
  $result = mysql_query($query);
  if ($error = mysql_error()) {
    drupal_set_message(st('We were unable to create a test table on your MySQL database server with the command %query. MySQL reports the following message: %error.<ul><li>Are you sure the configured username has the necessary MySQL permissions to create tables in the database?</li></ul>For more help, see the <a href="http://drupal.org/node/258">Installation and upgrading handbook</a>. If you are unsure what these terms mean you should probably contact your hosting provider.', array('%query' => $query, '%error' => $error)), 'error');
    return FALSE;
  }
  $err = FALSE;
  $success[] = 'SELECT';
  $success[] = 'CREATE';

  // Test INSERT.
  $query = 'INSERT INTO drupal_install_test (id) VALUES (1)';
  $result = mysql_query($query);
  if ($error = mysql_error()) {
    drupal_set_message(st('We were unable to insert a value into a test table on your MySQL database server. We tried inserting a value with the command %query and MySQL reported the following error: %error.', array('%query' => $query, '%error' => $error)), 'error');
    $err = TRUE;
  }
  else {
    $success[] = 'INSERT';
  }

  // Test UPDATE.
  $query = 'UPDATE drupal_install_test SET id = 2';
  $result = mysql_query($query);
  if ($error = mysql_error()) {
    drupal_set_message(st('We were unable to update a value in a test table on your MySQL database server. We tried updating a value with the command %query and MySQL reported the following error: %error.', array('%query' => $query, '%error' => $error)), 'error');
    $err = TRUE;
  }
  else {
    $success[] = 'UPDATE';
  }

  // Test LOCK.
  $query = 'LOCK TABLES drupal_install_test WRITE';
  $result = mysql_query($query);
  if ($error = mysql_error()) {
    drupal_set_message(st('We were unable to lock a test table on your MySQL database server. We tried locking a table with the command %query and MySQL reported the following error: %error.', array('%query' => $query, '%error' => $error)), 'error');
    $err = TRUE;
  }
  else {
    $success[] = 'LOCK';
  }

  // Test UNLOCK.
  $query = 'UNLOCK TABLES';
  $result = mysql_query($query);
  if ($error = mysql_error()) {
    drupal_set_message(st('We were unable to unlock a test table on your MySQL database server. We tried unlocking a table with the command %query and MySQL reported the following error: %error.', array('%query' => $query, '%error' => $error)), 'error');
    $err = TRUE;
  }
  else {
    $success[] = 'UNLOCK';
  }

  // Test DELETE.
  $query = 'DELETE FROM drupal_install_test';
  $result = mysql_query($query);
  if ($error = mysql_error()) {
    drupal_set_message(st('We were unable to delete a value from a test table on your MySQL database server. We tried deleting a value with the command %query and MySQL reported the following error: %error.', array('%query' => $query, '%error' => $error)), 'error');
    $err = TRUE;
  }
  else {
    $success[] = 'DELETE';
  }

  // Test DROP.
  $query = 'DROP TABLE drupal_install_test';
  $result = mysql_query($query);
  if ($error = mysql_error()) {
    drupal_set_message(st('We were unable to drop a test table from your MySQL database server. We tried dropping a table with the command %query and MySQL reported the following error %error.', array('%query' => $query, '%error' => $error)), 'error');
    $err = TRUE;
  }
  else {
    $success[] = 'DROP';
  }

  if ($err) {
    return FALSE;
  }

  mysql_close($connection);
  return TRUE;
}

?>
