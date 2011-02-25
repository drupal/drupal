<?php

/**
 * @file
 * Admin page callbacks for the throttle module.
 */

/**
 * Form builder; Configure the throttle system.
 *
 * @ingroup forms
 * @see system_settings_form()
 * @see throttle_admin_settings_validate()
 */
function throttle_admin_settings() {
  $probabilities = array(0 => '100%', 1 => '50%', 2 => '33.3%', 3 => '25%', 4 => '20%', 5 => '16.6%', 7 => '12.5%', 9 => '10%', 19 => '5%', 99 => '1%', 199 => '.5%', 399 => '.25%', 989 => '.1%');

  $form['throttle_anonymous'] = array(
    '#type' => 'textfield',
    '#title' => t('Auto-throttle on anonymous users'),
    '#default_value' => variable_get('throttle_anonymous', 0),
    '#size' => 5,
    '#maxlength' => 6,
    '#description' => t('The congestion control throttle can be automatically enabled when the number of anonymous users currently visiting your site exceeds the specified threshold. For example, to start the throttle when your site has 250 anonymous users online at once, enter \'250\' in this field. Leave this value blank or set to "0" if you do not wish to auto-throttle on anonymous users. You can inspect the current number of anonymous users using the "Who\'s online" block.')
  );
  $form['throttle_user'] = array(
    '#type' => 'textfield',
    '#title' => t('Auto-throttle on authenticated users'),
    '#default_value' => variable_get('throttle_user', 0),
    '#size' => 5,
    '#maxlength' => 6,
    '#description' => t('The congestion control throttle can be automatically enabled when the number of authenticated users currently visiting your site exceeds the specified threshold. For example, to start the throttle when your site has 50 registered users online at once, enter \'50\' in this field. Leave this value blank or set to "0" if you do not wish to auto-throttle on authenticated users. You can inspect the current number of authenticated users using the "Who\'s online" block.')
  );
  $form['throttle_probability_limiter'] = array(
    '#type' => 'select',
    '#title' => t('Auto-throttle probability limiter'),
    '#default_value' => variable_get('throttle_probability_limiter', 9),
    '#options' => $probabilities,
    '#description' => t('The auto-throttle probability limiter is an efficiency mechanism to statistically reduce the overhead of the auto-throttle. The limiter is expressed as a percentage of page views, so for example if set to the default of 10% we only perform the extra database queries to update the throttle status 1 out of every 10 page views. The busier your site, the lower you should set the limiter value.')
  );

  $form['#validate'] = array('throttle_admin_settings_validate');

  return system_settings_form($form);
}

function throttle_admin_settings_validate($form, &$form_state) {
  if (!is_numeric($form_state['values']['throttle_anonymous']) || $form_state['values']['throttle_anonymous'] < 0) {
    form_set_error('throttle_anonymous', t("%value is not a valid auto-throttle setting. Please enter a positive numeric value.", array('%value' => $form_state['values']['throttle_anonymous'])));
  }
  if (!is_numeric($form_state['values']['throttle_user']) || $form_state['values']['throttle_user'] < 0) {
    form_set_error('throttle_user', t("%value is not a valid auto-throttle setting. Please enter a positive numeric value.", array('%value' => $form_state['values']['throttle_user'])));
  }
}
