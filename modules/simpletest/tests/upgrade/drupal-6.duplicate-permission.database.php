<?php

// Simulate duplicated permission condition.
db_update('permission')->fields(array(
  'perm' => 'access content, access content',
))
->condition('pid', 1)
->execute();
