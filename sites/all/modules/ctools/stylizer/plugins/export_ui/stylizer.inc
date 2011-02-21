<?php
// $Id: stylizer.inc,v 1.2 2010/10/11 22:18:22 sdboyer Exp $

$plugin = array(
  'schema' => 'stylizer',
  'access' => 'administer stylizer',

  'menu' => array(
    'menu item' => 'stylizer',
    'menu title' => 'Stylizer',
    'menu description' => 'Add, edit or delete stylizer styles.',
  ),

  'title singular' => t('style'),
  'title singular proper' => t('Style'),
  'title plural' => t('styles'),
  'title plural proper' => t('Styles'),

  'handler' => array(
    'class' => 'stylizer_ui',
  ),

  'strings' => array(
    'message' => array(
      'missing base type' => t('There are currently no style types available to add. You should enable a module that utilizes them, such as Panels.'),
    ),
  ),

  'use wizard' => TRUE,
  'form info' => array(
    'add order' => array(
      'admin' => t('Administrative settings'),
      'type' => t('Select style type'),
      'choose' => t('Select base style'),
    ),
    'order' => array(
      'admin' => t('Administrative settings'),
    ),
    'forms' => array(
      'choose' => array(
        'form id' => 'ctools_stylizer_edit_style_form_choose',
      ),
    ),
  ),
);

