<?php
// $Id: install.pgsql.inc,v 1.2 2006/12/27 13:02:34 drumm Exp $

// PostgreSQL specific install functions

/**
 * Check if PostgreSQL is available.
 *
 * @return
 *  TRUE/FALSE
 */
function pgsql_is_available() {
  return function_exists('pg_connect');
}

/**
 * Check if we can connect to PostgreSQL.
 *
 * @return
 *  TRUE/FALSE
 */
function drupal_test_pgsql($url, &$success) {
  if (!pgsql_is_available()) {
    drupal_set_message(st('PHP PostgreSQL support not enabled.'), 'error');
    return FALSE;
  }

  $url = parse_url($url);

  // Decode url-encoded information in the db connection string.
  $url['user'] = urldecode($url['user']);
  $url['pass'] = urldecode($url['pass']);
  $url['host'] = urldecode($url['host']);
  $url['path'] = urldecode($url['path']);

  // Build pgsql connection string and allow for non-standard PostgreSQL port.
  $conn_string = ' user='. $url['user'] .' dbname='. substr($url['path'], 1) .' password='. $url['pass'] . ' host=' . $url['host'];
  $conn_string .= isset($url['port']) ? ' port=' . $url['port'] : '';

  // Test connecting to the database.
  $connection = @pg_connect($conn_string);
  if (!$connection) {
    drupal_set_message(st('Failure to connect to your PostgreSQL database server. PostgreSQL reports the following message: %error.<ul><li>Are you sure you have the correct username and password?</li><li>Are you sure that you have typed the correct database hostname?</li><li>Are you sure that the database server is running?</li><li>Are you sure you typed the correct database name?</li></ul>For more help, see the <a href="http://drupal.org/node/258">Installation and upgrading handbook</a>. If you are unsure what these terms mean you should probably contact your hosting provider.', array('%error' => 'Connection failed. See log file for failure reason')), 'error');
    return FALSE;
  }

  $success = array('CONNECT');

  // Test CREATE.
  $query = 'CREATE TABLE drupal_install_test (id integer NOT NULL)';
  $result = pg_query($connection, $query);
  if ($error = pg_result_error($result)) {
    drupal_set_message(st('We were unable to create a test table on your PostgreSQL database server with the command %query. PostgreSQL reports the following message: %error.<ul><li>Are you sure the configured username has the necessary PostgreSQL permissions to create tables in the database?</li></ul>For more help, see the <a href="http://drupal.org/node/258">Installation and upgrading handbook</a>. If you are unsure what these terms mean you should probably contact your hosting provider.', array('%query' => $query, '%error' => $error)), 'error');
    return FALSE;
  }
  $err = FALSE;
  $success[] = 'SELECT';
  $success[] = 'CREATE';

  // Test INSERT.
  $query = 'INSERT INTO drupal_install_test (id) VALUES (1)';
  $result = pg_query($connection, $query);
  if ($error = pg_result_error($result)) {
    drupal_set_message(st('We were unable to insert a value into a test table on your PostgreSQL database server. We tried inserting a value with the command %query and PostgreSQL reported the following error: %error.', array('%query' => $query, '%error' => $error)), 'error');
    $err = TRUE;
  }
  else {
    $success[] = 'INSERT';
  }

  // Test UPDATE.
  $query = 'UPDATE drupal_install_test SET id = 2';
  $result = pg_query($connection, $query);
  if ($error = pg_result_error($result)) {
    drupal_set_message(st('We were unable to update a value in a test table on your PostgreSQL database server. We tried updating a value with the command %query and PostgreSQL reported the following error: %error.', array('%query' => $query, '%error' => $error)), 'error');
    $err = TRUE;
  }
  else {
    $success[] = 'UPDATE';
  }

  // Test LOCK.
  $query = 'BEGIN; LOCK drupal_install_test IN SHARE ROW EXCLUSIVE MODE';
  $result = pg_query($connection, $query);
  if ($error = pg_result_error($result)) {
    drupal_set_message(st('We were unable to lock a test table on your PostgreSQL database server. We tried locking a table with the command %query and PostgreSQL reported the following error: %error.', array('%query' => $query, '%error' => $error)), 'error');
    $err = TRUE;
  }
  else {
    $success[] = 'LOCK';
  }

  // Test UNLOCK, which is done automatically upon transaction end in PostgreSQL
  $query = 'COMMIT';
  $result = pg_query($connection, $query);
  if ($error = pg_result_error()) {
    drupal_set_message(st('We were unable to unlock a test table on your PostgreSQL database server. We tried unlocking a table with the command %query and PostgreSQL reported the following error: %error.', array('%query' => $query, '%error' => $error)), 'error');
    $err = TRUE;
  }
  else {
    $success[] = 'UNLOCK';
  }

  // Test DELETE.
  $query = 'DELETE FROM drupal_install_test';
  $result = pg_query($connection, $query);
  if ($error = pg_result_error()) {
    drupal_set_message(st('We were unable to delete a value from a test table on your PostgreSQL database server. We tried deleting a value with the command %query and PostgreSQL reported the following error: %error.', array('%query' => $query, '%error' => $error)), 'error');
    $err = TRUE;
  }
  else {
    $success[] = 'DELETE';
  }

  // Test DROP.
  $query = 'DROP TABLE drupal_install_test';
  $result = pg_query($connection, $query);
  if ($error = pg_result_error()) {
    drupal_set_message(st('We were unable to drop a test table from your PostgreSQL database server. We tried dropping a table with the command %query and PostgreSQL reported the following error %error.', array('%query' => $query, '%error' => $error)), 'error');
    $err = TRUE;
  }
  else {
    $success[] = 'DROP';
  }

  if ($err) {
    return FALSE;
  }

  pg_close($connection);
  return TRUE;
}

?>
