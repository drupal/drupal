<?php

/**
 * Implements hook_rules_action_info().
 */
function devel_rules_action_info() {
  return array(
    'devel_debug' => array(
      'base' => 'devel_rules_debug_action',
      'label' => t('Debug value'),
      'group' => t('Devel'),
      'parameter' => array(
        'value' => array('type' => 'unknown', 'label' => t('Value to debug')),
      ),
    ),
  );
}

/**
 * Rules action for debugging values.
 */
function devel_rules_debug_action($value) {
  dpm($value);
}
