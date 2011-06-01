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
