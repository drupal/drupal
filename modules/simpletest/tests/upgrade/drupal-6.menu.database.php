<?php
db_insert('variable')->fields(array(
  'name',
  'value',
))
->values(array(
  'name' => 'menu_default_node_menu',
  'value' => 's:15:"secondary-links";',
))
->execute();
