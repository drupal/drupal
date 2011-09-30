<?php
db_insert('variable')->fields(array(
  'name',
  'value',
))
->values(array(
  'name' => 'user_mail_register_no_approval_required_body',
  'value' => 's:97:"!password, !username, !site, !uri, !uri_brief, !mailto, !date, !login_uri, !edit_uri, !login_url.";',
))
->execute();

db_insert('users')->fields(array(
  'uid',
  'name',
  'pass',
  'mail',
  'mode',
  'sort',
  'threshold',
  'theme',
  'signature',
  'signature_format',
  'created',
  'access',
  'login',
  'status',
  'timezone',
  'language',
  'picture',
  'init',
  'data',
))
->values(array(
  'uid' => 3,
  'name' => 'hashtester',
  // This is not a valid D7 hash, but a truncated one.
  'pass' => '$S$DAK00p3Dkojkf4O/UizYxenguXnjv',
  'mail' => 'hashtester@example.com',
  'mode' => '0',
  'sort' => '0',
  'threshold' => '0',
  'theme' => '',
  'signature' => '',
  'signature_format' => '0',
  'created' => '1277671599',
  'access' => '1277671612',
  'login' => '1277671612',
  'status' => '1',
  'timezone' => '-21600',
  'language' => '',
  'picture' => '',
  'init' => 'hashtester@example.com',
  'data' => 'a:0:{}',
))
->execute();
