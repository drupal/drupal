<?php
db_insert('comments')->fields(array(
  'cid',
  'pid',
  'nid',
  'uid',
  'subject',
  'comment',
  'hostname',
  'timestamp',
  'status',
  'format',
  'thread',
  'name',
  'mail',
  'homepage',
))
->values(array(
  'cid' => 1,
  'pid' => 0,
  'nid' => 37,
  'uid' => 3,
  'subject' => 'Comment title 1',
  'comment' => 'Comment body 1 - Comment body 1 - Comment body 1 - Comment body 1 - Comment body 1 - Comment body 1 - Comment body 1 - Comment body 1',
  'hostname' => '127.0.0.1',
  'timestamp' => 1008617630,
  'status' => 0,
  'format' => 1,
  'thread' => '01/',
  'name' => NULL,
  'mail' => NULL,
  'homepage' => '',
))
->execute();
