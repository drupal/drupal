<?php
// $Id: ctools_access_ruleset.inc,v 1.2 2010/10/11 22:18:23 sdboyer Exp $

$plugin = array(
  'schema' => 'ctools_access_ruleset',
  'access' => 'administer ctools access ruleset',

  'menu' => array(
    'menu item' => 'ctools-rulesets',
    'menu title' => 'Custom access rulesets',
    'menu description' => 'Add, edit or delete custom access rulesets for use with Panels and other systems that utilize CTools content plugins.',
  ),

  'title singular' => t('ruleset'),
  'title singular proper' => t('Ruleset'),
  'title plural' => t('rulesets'),
  'title plural proper' => t('Rulesets'),

  'handler' => 'ctools_access_ruleset_ui',

  'use wizard' => TRUE,
  'form info' => array(
    'order' => array(
      'basic' => t('Basic information'),
      'context' => t('Contexts'),
      'rules' => t('Rules'),
    ),
  ),
);

