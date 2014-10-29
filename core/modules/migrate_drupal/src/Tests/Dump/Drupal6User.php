<?php

/**
 * @file
 * Contains \Drupal\migrate_drupal\Tests\Dump\Drupal6User.
 */

namespace Drupal\migrate_drupal\Tests\Dump;

/**
 * Database dump for testing the users migration.
 */
class Drupal6User extends Drupal6DumpBase {

  /**
   * {@inheritdoc}
   */
  public function load() {

    foreach (static::getSchema() as $table => $schema) {
      // Create tables.
      $this->createTable($table, $schema);

      // Insert data.
      $data = static::getData($table);
      if ($data) {
        $query = $this->database->insert($table)->fields(array_keys($data[0]));
        foreach ($data as $record) {
          $query->values($record);
        }
        $query->execute();
      }
    }
  }

  /**
   * Defines schema for this database dump.
   *
   * @return array
   *   Associative array having the structure as is returned by hook_schema().
   */
  protected static function getSchema() {
    return array(
      'users' => array(
        'description' => 'Stores user data.',
        'fields' => array(
          'uid' => array(
            'type' => 'serial',
            'unsigned' => TRUE,
            'not null' => TRUE,
            'description' => 'Primary Key: Unique user ID.',
          ),
          'name' => array(
            'type' => 'varchar',
            'length' => 60,
            'not null' => TRUE,
            'default' => '',
            'description' => 'Unique username.',
          ),
          'pass' => array(
            'type' => 'varchar',
            'length' => 32,
            'not null' => TRUE,
            'default' => '',
            'description' => "User's password (md5 hash).",
          ),
          'mail' => array(
            'type' => 'varchar',
            'length' => 64,
            'not null' => FALSE,
            'default' => '',
            'description' => "User's email address.",
          ),
          'mode' => array(
            'type' => 'int',
            'not null' => TRUE,
            'default' => 0,
            'size' => 'tiny',
            'description' => 'Per-user comment display mode (threaded vs. flat), used by the {comment} module.',
          ),
          'sort' => array(
            'type' => 'int',
            'not null' => FALSE,
            'default' => 0,
            'size' => 'tiny',
            'description' => 'Per-user comment sort order (newest vs. oldest first), used by the {comment} module.',
          ),
          'threshold' => array(
            'type' => 'int',
            'not null' => FALSE,
            'default' => 0,
            'size' => 'tiny',
            'description' => 'Previously used by the {comment} module for per-user preferences; no longer used.',
          ),
          'theme' => array(
            'type' => 'varchar',
            'length' => 255,
            'not null' => TRUE,
            'default' => '',
            'description' => "User's default theme.",
          ),
          'signature' => array(
            'type' => 'varchar',
            'length' => 255,
            'not null' => TRUE,
            'default' => '',
            'description' => "User's signature.",
          ),
          'signature_format' => array(
            'type' => 'int',
            'size' => 'small',
            'not null' => TRUE,
            'default' => 0,
            'description' => 'The {filter_formats}.format of the signature.',
          ),
          'created' => array(
            'type' => 'int',
            'not null' => TRUE,
            'default' => 0,
            'description' => 'Timestamp for when user was created.',
          ),
          'access' => array(
            'type' => 'int',
            'not null' => TRUE,
            'default' => 0,
            'description' => 'Timestamp for previous time user accessed the site.',
          ),
          'login' => array(
            'type' => 'int',
            'not null' => TRUE,
            'default' => 0,
            'description' => "Timestamp for user's last login.",
          ),
          'status' => array(
            'type' => 'int',
            'not null' => TRUE,
            'default' => 0,
            'size' => 'tiny',
            'description' => 'Whether the user is active(1) or blocked(0).',
          ),
          'timezone' => array(
            'type' => 'varchar',
            'length' => 8,
            'not null' => FALSE,
            'description' => "User's timezone.",
          ),
          'language' => array(
            'type' => 'varchar',
            'length' => 12,
            'not null' => TRUE,
            'default' => '',
            'description' => "User's default language.",
          ),
          'picture' => array(
            'type' => 'varchar',
            'length' => 255,
            'not null' => TRUE,
            'default' => '',
            'description' => "Path to the user's uploaded picture.",
          ),
          'init' => array(
            'type' => 'varchar',
            'length' => 64,
            'not null' => FALSE,
            'default' => '',
            'description' => 'Email address used for initial account creation.',
          ),
          'data' => array(
            'type' => 'text',
            'not null' => FALSE,
            'size' => 'big',
            'description' => 'A serialized array of name value pairs that are related to the user. Any form values posted during user edit are stored and are loaded into the $user object during user_load(). Use of this field is discouraged and it will likely disappear in a future version of Drupal.',
          ),
          // Field not part of Drupal 6 schema. Added by Date contributed module.
          'timezone_name' => array(
            'type' => 'varchar',
            'length' => 50,
            'not null' => FALSE,
            'default' => '',
            'description' => 'Field added by Date contributed module.',
          ),
          // Field not part of Drupal 6 schema. Added by Event contributed module.
          'timezone_id' => array(
            'type' => 'int',
            'not null' => TRUE,
            'default' => 0,
            'description' => 'Field added by Event contributed module.',
          ),
          // Field not part of Drupal 6 schema. Needed to test password rehashing.
          'pass_plain' => array(
            'type' => 'varchar',
            'length' => 255,
            'not null' => TRUE,
            'default' => '',
            'description' => "User's password (plain).",
          ),
          // Field not part of Drupal 6 schema. Needed to test user_update_7002.
          'expected_timezone' => array(
            'type' => 'varchar',
            'length' => 50,
            'not null' => FALSE,
          ),
        ),
        'indexes' => array(
          'access' => array('access'),
          'created' => array('created'),
          'mail' => array('mail'),
        ),
        'unique keys' => array(
          'name' => array('name'),
        ),
        'primary key' => array('uid'),
      ),
      'users_roles' => array(
        'description' => 'Maps users to roles.',
        'fields' => array(
          'uid' => array(
            'type' => 'int',
            'unsigned' => TRUE,
            'not null' => TRUE,
            'default' => 0,
            'description' => 'Primary Key: {users}.uid for user.',
          ),
          'rid' => array(
            'type' => 'int',
            'unsigned' => TRUE,
            'not null' => TRUE,
            'default' => 0,
            'description' => 'Primary Key: {role}.rid for role.',
          ),
        ),
        'primary key' => array('uid', 'rid'),
        'indexes' => array(
          'rid' => array('rid'),
        ),
      ),
      'event_timezones' => array(
        'fields' => array(
          'timezone' => array(
            'type' => 'int',
            'not null' => TRUE,
            'default' => 0,
          ),
          'name' => array(
            'type' => 'varchar',
            'length' => 255,
            'not null' => TRUE,
            'default' => '',
          ),
        )
      ),
      'profile_values' => array(
        'description' => 'Stores values for profile fields.',
        'fields' => array(
          'fid' => array(
            'type' => 'int',
            'unsigned' => TRUE,
            'not null' => TRUE,
            'default' => 0,
            'description' => 'The {profile_field}.fid of the field.',
          ),
          'uid' => array(
            'type' => 'int',
            'unsigned' => TRUE,
            'not null' => TRUE,
            'default' => 0,
            'description' => 'The {users}.uid of the profile user.',
          ),
          'value' => array(
            'type' => 'text',
            'not null' => FALSE,
            'description' => 'The value for the field.',
          ),
        ),
        'primary key' => array('uid', 'fid'),
        'indexes' => array(
          'fid' => array('fid'),
        ),
        'foreign keys' => array(
          'profile_field' => array(
            'table' => 'profile_field',
            'columns' => array('fid' => 'fid'),
          ),
          'profile_user' => array(
            'table' => 'users',
            'columns' => array('uid' => 'uid'),
          ),
        ),
      ),
    );
  }

  /**
   * Returns dump data from a specific table.
   *
   * @param string $table
   *   The table name.
   *
   * @return array
   *   Array of associative arrays each one having fields as keys.
   */
  public static function getData($table) {
    $data = array(
      'users' => array(
        array(
          'uid' => 2,
          'name' => 'john.doe',
          'pass' => md5('john.doe_pass'),
          'mail' => 'john.doe@example.com',
          'picture' => 'core/modules/simpletest/files/image-test.jpg',
          'mode' => 0,
          'sort' => 0,
          'threshold' => 0,
          'theme' => '',
          'signature' => 'John Doe | john.doe@example.com',
          'signature_format' => 1,
          'created' => 1391150052,
          'access' => 1391259672,
          'login' => 1391152253,
          'status' => 1,
          'timezone' => '3600',
          'language' => 'fr',
          'init' => 'doe@example.com',
          'data' => serialize(array('contact' => 1)),
          'timezone_name' => NULL,
          'timezone_id' => 1,
          'pass_plain' => 'john.doe_pass',
          'expected_timezone' => 'Europe/Berlin',
        ),
        array(
          'uid' => 8,
          'name' => 'joe.roe',
          'pass' => md5('joe.roe_pass'),
          'mail' => 'joe.roe@example.com',
          'picture' => 'core/modules/simpletest/files/image-test.png',
          'mode' => 0,
          'sort' => 0,
          'threshold' => 0,
          'theme' => '',
          'signature' => 'JR',
          'signature_format' => 2,
          'created' => 1391150053,
          'access' => 1391259673,
          'login' => 1391152254,
          'status' => 1,
          'timezone' => '7200',
          'language' => 'ro',
          'init' => 'roe@example.com',
          'data' => serialize(array('contact' => 0)),
          'timezone_name' => 'Europe/Helsinki',
          'timezone_id' => 0,
          'pass_plain' => 'joe.roe_pass',
          'expected_timezone' => 'Europe/Helsinki',
        ),
        array(
          'uid' => 15,
          'name' => 'joe.bloggs',
          'pass' => md5('joe.bloggs_pass'),
          'mail' => 'joe.bloggs@example.com',
          'picture' => '',
          'mode' => 0,
          'sort' => 0,
          'threshold' => 0,
          'theme' => '',
          'signature' => 'bloggs',
          'signature_format' => 1,
          'created' => 1391150054,
          'access' => 1391259674,
          'login' => 1391152255,
          'status' => 1,
          'timezone' => '3600',
          'language' => 'en',
          'init' => 'bloggs@example.com',
          'data' => serialize(array()),
          'timezone_name' => NULL,
          'timezone_id' => 0,
          'pass_plain' => 'joe.bloggs_pass',
          'expected_timezone' => NULL,
        ),
        array(
          'uid' => 16,
          'name' => 'sal.saraniti',
          'pass' => md5('sal.saraniti'),
          'mail' => 'sal.saraniti@example.com',
          'picture' => '',
          'mode' => 0,
          'sort' => 0,
          'threshold' => 0,
          'theme' => '',
          'signature' => '',
          'signature_format' => 0,
          'created' => 1391151054,
          'access' => 1391259574,
          'login' => 1391162255,
          'status' => 1,
          'timezone' => '3600',
          'language' => 'en',
          'init' => 'sal.saraniti@example.com',
          'data' => serialize(array()),
          'timezone_name' => NULL,
          'timezone_id' => 0,
          'pass_plain' => 'sal.saraniti',
          'expected_timezone' => NULL,
        ),
        array(
          'uid' => 17,
          'name' => 'terry.saraniti',
          'pass' => md5('terry.saraniti'),
          'mail' => 'terry.saraniti@example.com',
          'picture' => '',
          'mode' => 0,
          'sort' => 0,
          'threshold' => 0,
          'theme' => '',
          'signature' => '',
          'signature_format' => 0,
          'created' => 1390151054,
          'access' => 1390259574,
          'login' => 1390162255,
          'status' => 1,
          'timezone' => '3600',
          'language' => 'en',
          'init' => 'terry.saraniti@example.com',
          'data' => serialize(array()),
          'timezone_name' => NULL,
          'timezone_id' => 0,
          'pass_plain' => 'terry.saraniti',
          'expected_timezone' => NULL,
        ),
      ),
      'users_roles' => array(
        array('uid' => 2, 'rid' => 3),
        array('uid' => 8, 'rid' => 4),
        array('uid' => 8, 'rid' => 5),
        array('uid' => 15, 'rid' => 3),
        array('uid' => 15, 'rid' => 4),
        array('uid' => 15, 'rid' => 5),
        array('uid' => 16, 'rid' => 3),
        array('uid' => 16, 'rid' => 5),
        array('uid' => 17, 'rid' => 4),
      ),
      'event_timezones' => array(
        array(
          'timezone' => 1,
          'name' => 'Europe/Berlin',
        )
      ),
      'profile_values' => array(
        array('fid' => 8, 'uid' => 2, 'value' => 'red'),
        array('fid' => 9, 'uid' => 2, 'value' => 'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Nam nulla sapien, congue nec risus ut, adipiscing aliquet felis. Maecenas quis justo vel nulla varius euismod. Quisque metus metus, cursus sit amet sem non, bibendum vehicula elit. Cras dui nisl, eleifend at iaculis vitae, lacinia ut felis. Nullam aliquam ligula volutpat nulla consectetur accumsan. Maecenas tincidunt molestie diam, a accumsan enim fringilla sit amet. Morbi a tincidunt tellus. Donec imperdiet scelerisque porta. Sed quis sem bibendum eros congue sodales. Vivamus vel fermentum est, at rutrum orci. Nunc consectetur purus ut dolor pulvinar, ut volutpat felis congue. Cras tincidunt odio sed neque sollicitudin, vehicula tempor metus scelerisque.'),
        array('fid' => 10, 'uid' => 2, 'value' => '1'),
        array('fid' => 11, 'uid' => 2, 'value' => 'Back\slash'),
        array('fid' => 12, 'uid' => 2, 'value' => "AC/DC\n,,Eagles\r\nElton John,Lemonheads\r\n\r\nRolling Stones\rQueen\nThe White Stripes"),
        array('fid' => 13, 'uid' => 2, 'value' => "http://example.com/blog"),
        array('fid' => 14, 'uid' => 2, 'value' => 'a:3:{s:5:"month";s:1:"6";s:3:"day";s:1:"2";s:4:"year";s:4:"1974";}'),
        array('fid' => 8, 'uid' => 8, 'value' => 'brown'),
        array('fid' => 9, 'uid' => 8, 'value' => 'Nunc condimentum ligula felis, eget lacinia purus accumsan at. Pellentesque eu lobortis felis. Duis at accumsan nisl, vel pulvinar risus. Nullam venenatis, tellus non eleifend hendrerit, augue nulla rhoncus leo, eget convallis enim sem ut velit. Mauris tincidunt enim ut eros volutpat dapibus. Curabitur augue libero, imperdiet eget orci sed, malesuada dapibus tellus. Nam lacus sapien, convallis vitae quam vel, bibendum commodo odio.'),
        array('fid' => 10, 'uid' => 8, 'value' => '0'),
        array('fid' => 11, 'uid' => 8, 'value' => 'Forward/slash'),
        array('fid' => 12, 'uid' => 8, 'value' => "Deep Purple\nWho\nThe Beatles"),
        array('fid' => 13, 'uid' => 8, 'value' => "http://blog.example.com"),
        array('fid' => 14, 'uid' => 8, 'value' => 'a:3:{s:5:"month";s:1:"9";s:3:"day";s:1:"9";s:4:"year";s:4:"1980";}'),
        array('fid' => 8, 'uid' => 15, 'value' => 'orange'),
        array('fid' => 9, 'uid' => 15, 'value' => 'Donec a diam volutpat augue fringilla fringilla. Mauris ultricies turpis ut lacus tempus, vitae pharetra lacus mattis. Nulla semper dui euismod sem bibendum, in eleifend nisi malesuada. Vivamus orci mauris, volutpat vitae enim ac, aliquam tempus lectus.'),
        array('fid' => 10, 'uid' => 15, 'value' => '1'),
        array('fid' => 11, 'uid' => 15, 'value' => 'Dot.in.the.middle'),
        array('fid' => 12, 'uid' => 15, 'value' => "ABBA\nBoney M"),
        array('fid' => 13, 'uid' => 15, 'value' => "http://example.com/journal"),
        array('fid' => 14, 'uid' => 15, 'value' => 'a:3:{s:5:"month";s:2:"11";s:3:"day";s:2:"25";s:4:"year";s:4:"1982";}'),
        array('fid' => 8, 'uid' => 16, 'value' => 'blue'),
        array('fid' => 9, 'uid' => 16, 'value' => 'Pellentesque sit amet sem et purus pretium consectetuer.'),
        array('fid' => 10, 'uid' => 16, 'value' => '0'),
        array('fid' => 11, 'uid' => 16, 'value' => 'Faithful servant'),
        array('fid' => 12, 'uid' => 16, 'value' => "Van Halen\nDave M"),
        array('fid' => 13, 'uid' => 16, 'value' => "http://example.com/monkeys"),
        array('fid' => 14, 'uid' => 16, 'value' => 'a:3:{s:5:"month";s:1:"9";s:3:"day";s:2:"23";s:4:"year";s:4:"1939";}'),
        array('fid' => 8, 'uid' => 17, 'value' => 'yellow'),
        array('fid' => 9, 'uid' => 17, 'value' => 'The quick brown fox jumped over the lazy dog.'),
        array('fid' => 10, 'uid' => 17, 'value' => '0'),
        array('fid' => 11, 'uid' => 17, 'value' => 'Anonymous donor'),
        array('fid' => 12, 'uid' => 17, 'value' => "Toto\nJohn Denver"),
        array('fid' => 13, 'uid' => 17, 'value' => "http://example.com/penguins"),
        array('fid' => 14, 'uid' => 17, 'value' => 'a:3:{s:5:"month";s:2:"12";s:3:"day";s:2:"18";s:4:"year";s:4:"1942";}'),
      ),
    );

    return isset($data[$table]) ? $data[$table] : FALSE;
  }

}
