<?php
// $Id: drupal-6.upload.database.php,v 1.1 2010/10/03 23:19:52 webchick Exp $

db_insert('files')->fields(array(
  'fid',
  'uid',
  'filename',
  'filepath',
  'filemime',
  'filesize',
  'status',
  'timestamp',
))
->values(array(
  'fid' => '1',
  'uid' => '1',
  'filename' => 'powered-blue-80x15.png',
  'filepath' => 'sites/default/files/powered-blue-80x15.png',
  'filemime' => 'image/png',
  'filesize' => '1011',
  'status' => '1',
  'timestamp' => '1285700240',
))
->values(array(
  'fid' => '2',
  'uid' => '1',
  'filename' => 'powered-blue-80x15.png',
  'filepath' => 'sites/default/files/powered-blue-80x15_0.png',
  'filemime' => 'image/png',
  'filesize' => '1011',
  'status' => '1',
  'timestamp' => '1285700317',
))
->values(array(
  'fid' => '3',
  'uid' => '1',
  'filename' => 'powered-blue-88x31.png',
  'filepath' => 'sites/default/files/powered-blue-88x31.png',
  'filemime' => 'image/png',
  'filesize' => '2113',
  'status' => '1',
  'timestamp' => '1285700343',
))
->values(array(
  'fid' => '4',
  'uid' => '1',
  'filename' => 'powered-blue-135x42.png',
  'filepath' => 'sites/default/files/powered-blue-135x42.png',
  'filemime' => 'image/png',
  'filesize' => '3027',
  'status' => '1',
  'timestamp' => '1285700366',
))
->values(array(
  'fid' => '5',
  'uid' => '1',
  'filename' => 'powered-black-80x15.png',
  'filepath' => 'sites/default/files/powered-black-80x15.png',
  'filemime' => 'image/png',
  'filesize' => '1467',
  'status' => '1',
  'timestamp' => '1285700529',
))
->values(array(
  'fid' => '6',
  'uid' => '1',
  'filename' => 'powered-black-135x42.png',
  'filepath' => 'sites/default/files/powered-black-135x42.png',
  'filemime' => 'image/png',
  'filesize' => '2817',
  'status' => '1',
  'timestamp' => '1285700552',
))
->values(array(
  'fid' => '7',
  'uid' => '1',
  'filename' => 'forum-hot-new.png',
  'filepath' => 'sites/default/files/forum-hot-new.png',
  'filemime' => 'image/png',
  'filesize' => '237',
  'status' => '1',
  'timestamp' => '1285708937',
))
->values(array(
  'fid' => '8',
  'uid' => '1',
  'filename' => 'forum-hot.png',
  'filepath' => 'sites/default/files/forum-hot.png',
  'filemime' => 'image/png',
  'filesize' => '229',
  'status' => '1',
  'timestamp' => '1285708944',
))
->values(array(
  'fid' => '9',
  'uid' => '1',
  'filename' => 'forum-new.png',
  'filepath' => 'sites/default/files/forum-new.png',
  'filemime' => 'image/png',
  'filesize' => '175',
  'status' => '1',
  'timestamp' => '1285708950',
))
->values(array(
  'fid' => '10',
  'uid' => '1',
  'filename' => 'forum-sticky.png',
  'filepath' => 'sites/default/files/forum-sticky.png',
  'filemime' => 'image/png',
  'filesize' => '329',
  'status' => '1',
  'timestamp' => '1285708957',
))
->execute();

db_insert('node')->fields(array(
  'nid',
  'vid',
  'type',
  'language',
  'title',
  'uid',
  'status',
  'created',
  'changed',
  'comment',
  'promote',
  'moderate',
  'sticky',
  'tnid',
  'translate',
))
->values(array(
  'nid' => '38',
  'vid' => '51',
  'type' => 'page',
  'language' => '',
  'title' => 'node title 38 revision 51',
  'uid' => '1',
  'status' => '1',
  'created' => '1285700317',
  'changed' => '1285700600',
  'comment' => '0',
  'promote' => '0',
  'moderate' => '0',
  'sticky' => '0',
  'tnid' => '0',
  'translate' => '0',
))
->values(array(
  'nid' => '39',
  'vid' => '52',
  'type' => 'page',
  'language' => '',
  'title' => 'node title 39 revision 53',
  'uid' => '1',
  'status' => '1',
  'created' => '1285709012',
  'changed' => '1285709012',
  'comment' => '0',
  'promote' => '0',
  'moderate' => '0',
  'sticky' => '0',
  'tnid' => '0',
  'translate' => '0',
))
 ->execute();

db_insert('node_revisions')->fields(array(
  'nid',
  'vid',
  'uid',
  'title',
  'body',
  'teaser',
  'log',
  'timestamp',
  'format',
))
->values(array(
  'nid' => '38',
  'vid' => '50',
  'uid' => '1',
  'title' => 'node title 38 revision 50',
  'body' => "Attachments:\r\npowered-blue-80x15.png\r\npowered-blue-88x31.png\r\npowered-blue-135x42.png",
  'teaser' => "Attachments:\r\npowered-blue-80x15.png\r\npowered-blue-88x31.png\r\npowered-blue-135x42.png",
  'log' => '',
  'timestamp' => '1285700487',
  'format' => '1',
))
->values(array(
  'nid' => '38',
  'vid' => '51',
  'uid' => '1',
  'title' => 'node title 38 revision 51',
  'body' => "Attachments:\r\npowered-blue-88x31.png\r\npowered-black-80x15.png\r\npowered-black-135x42.png",
  'teaser' => "Attachments:\r\npowered-blue-88x31.png\r\npowered-black-80x15.png\r\npowered-black-135x42.png",
  'log' => '',
  'timestamp' => '1285700600',
  'format' => '1',
))
->values(array(
  'nid' => '39',
  'vid' => '52',
  'uid' => '1',
  'title' => 'node title 39 revision 53',
  'body' => "Attachments:\r\nforum-hot-new.png\r\nforum-hot.png\r\nforum-sticky.png\r\nforum-new.png",
  'teaser' => "Attachments:\r\nforum-hot-new.png\r\nforum-hot.png\r\nforum-sticky.png\r\nforum-new.png",
  'log' => '',
  'timestamp' => '1285709012',
  'format' => '1',
))
 ->execute();

db_create_table('upload', array(
  'fields' => array(
    'fid' => array(
      'type' => 'int',
      'unsigned' => TRUE,
      'not null' => TRUE,
      'default' => 0,
    ),
    'nid' => array(
      'type' => 'int',
      'unsigned' => TRUE,
      'not null' => TRUE,
      'default' => 0,
    ),
    'vid' => array(
      'type' => 'int',
      'unsigned' => TRUE,
      'not null' => TRUE,
      'default' => 0,
    ),
    'description' => array(
      'type' => 'varchar',
      'length' => 255,
      'not null' => TRUE,
      'default' => '',
    ),
    'list' => array(
      'type' => 'int',
      'unsigned' => TRUE,
      'not null' => TRUE,
      'default' => 0,
      'size' => 'tiny',
    ),
    'weight' => array(
      'type' => 'int',
      'not null' => TRUE,
      'default' => 0,
      'size' => 'tiny',
    ),
  ),
  'primary key' => array(
    'vid',
    'fid',
  ),
  'indexes' => array(
    'fid' => array(
      'fid',
    ),
    'nid' => array(
      'nid',
    ),
  ),
  'module' => 'upload',
  'name' => 'upload',
));
db_insert('upload')->fields(array(
  'fid',
  'nid',
  'vid',
  'description',
  'list',
  'weight',
))
->values(array(
  'fid' => '2',
  'nid' => '38',
  'vid' => '50',
  'description' => 'powered-blue-80x15.png',
  'list' => '1',
  'weight' => '0',
))
->values(array(
  'fid' => '3',
  'nid' => '38',
  'vid' => '50',
  'description' => 'powered-blue-88x31.png',
  'list' => '1',
  'weight' => '0',
))
->values(array(
  'fid' => '4',
  'nid' => '38',
  'vid' => '50',
  'description' => 'powered-blue-135x42.png',
  'list' => '1',
  'weight' => '0',
))
->values(array(
  'fid' => '3',
  'nid' => '38',
  'vid' => '51',
  'description' => 'powered-blue-88x31.png',
  'list' => '1',
  'weight' => '0',
))
->values(array(
  'fid' => '5',
  'nid' => '38',
  'vid' => '51',
  'description' => 'powered-black-80x15.png',
  'list' => '1',
  'weight' => '0',
))
->values(array(
  'fid' => '6',
  'nid' => '38',
  'vid' => '51',
  'description' => 'powered-black-135x42.png',
  'list' => '1',
  'weight' => '0',
))
->values(array(
  'fid' => '7',
  'nid' => '39',
  'vid' => '52',
  'description' => 'forum-hot-new.png',
  'list' => '1',
  'weight' => '-4',
))
->values(array(
  'fid' => '8',
  'nid' => '39',
  'vid' => '52',
  'description' => 'forum-hot.png',
  'list' => '1',
  'weight' => '-3',
))
->values(array(
  'fid' => '10',
  'nid' => '39',
  'vid' => '52',
  'description' => 'forum-sticky.png',
  'list' => '1',
  'weight' => '-2',
))
->values(array(
  'fid' => '9',
  'nid' => '39',
  'vid' => '52',
  'description' => 'forum-new.png',
  'list' => '1',
  'weight' => '-1',
))
->execute();
