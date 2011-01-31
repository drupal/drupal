<?php
#
# SVN: $Id: db_clean.php 4783 2010-12-24 09:32:25Z svowl $
#
# Re-create Drupal database: drop all Drupal tables
#

if (!defined('DRUPAL_ROOT')) {
    define('DRUPAL_ROOT', dirname(realpath(__FILE__)) . '/../src');
}

function recreate_drupal_database()
{
	$errorMsg = '';

	$configFile = DRUPAL_ROOT. '/sites/default/settings.php';

	if (file_exists($configFile)) {

		include_once $configFile;

		if (isset($databases['default']['default'])) {

			$db = $databases['default']['default'];

			$host = $db['host'];

			if (!empty($db['unix_socket'])) {
				$host = $host . '/' . $db['unix_socket'];
			
			} elseif (!empty($db['port'])) {
				$host = $host . ':' . $db['port'];
			}

			if (is_resource($connection = @mysql_connect($host, $db['username'], $db['password']))) {

				if (@mysql_select_db($db['database'])) {

					$res = mysql_query('SHOW TABLES LIKE "drupal%"');

					if ($res) {

						$tables = array();

						while ($data = mysql_fetch_row($res)) {
							$tables[] = $data[0];
						}

						if (!empty($tables)) {

							foreach($tables as $tableName) {
								mysql_query('DROP TABLE IF EXISTS `' . $tableName . '`');
							}
						}
					}

				} else {
					mysql_query('CREATE DATABASE `' . $db['database'] . '`');
				}

			} else {
				$errorMsg = "Cannot connect to MySQL server: host={$host}, user={$db['username']}";
			}

		} else {
			$errorMsg = 'Error: $databases is not defined in the settings.php file';
		}

	} else {
		$errorMsg = 'Error: Config file no found: "' . $configFile . '"';
	}

	if (!empty($errorMsg)) {
		die($errorMsg);
	}

	return true;
}


recreate_drupal_database();


