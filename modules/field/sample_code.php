<?php

$field = array(
  'field_name' => 'field_single',
  'type' => 'text',
);
field_create_field($field);

$instance = array(
  'field_name' => 'field_single',
  'bundle' => 'article',
  'label' => 'Single',
  'widget' => array(
    'type' => 'text_textfield',
  ),
  'display' => array(
    'full' => array(
      'label' => 'above',
      'type' => 'text_default',
      'exclude' => 0,
    ),
  ),
);
field_create_instance($instance);

$instance['bundle'] = 'user';
field_create_instance($instance);


$field = array(
  'field_name' => 'field_multiple',
  'cardinality' => FIELD_CARDINALITY_UNLIMITED,
  'type' => 'text',
);
field_create_field($field);

$instance = array(
  'field_name' => 'field_multiple',
  'bundle' => 'article',
  'label' => 'Multiple',
  'widget' => array(
    'type' => 'text_textfield',
  ),
  'display' => array(
    'full' => array(
      'label' => 'above',
      'type' => 'text_default',
      'exclude' => 0,
    ),
  ),
);
field_create_instance($instance);

$instance['bundle'] = 'user';
field_create_instance($instance);


// Number
$field = array(
  'field_name' => 'field_integer',
  'type' => 'number_integer',
);
field_create_field($field);
$instance = array(
  'field_name' => 'field_integer',
  'bundle' => 'article',
  'label' => 'Integer',
  'widget' => array(
    'type' => 'number',
  ),
  'display' => array(
    'full' => array(
      'label' => 'above',
      'type' => 'number_integer',
      'exclude' => 0,
    ),
  ),
);
field_create_instance($instance);

$field = array(
  'field_name' => 'field_decimal',
  'type' => 'number_decimal',
);
field_create_field($field);
$instance = array(
  'field_name' => 'field_decimal',
  'bundle' => 'article',
  'label' => 'Decimal',
  'widget' => array(
    'type' => 'number',
  ),
  'display' => array(
    'full' => array(
      'label' => 'above',
      'type' => 'number_decimal',
      'exclude' => 0,
    ),
  ),
);
field_create_instance($instance);


