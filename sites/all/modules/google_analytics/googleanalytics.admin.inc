<?php
// $Id: googleanalytics.admin.inc,v 1.24.2.28 2011/02/07 14:41:11 hass Exp $

/**
 * @file
 * Administrative page callbacks for the googleanalytics module.
 */

/**
 * Implements hook_admin_settings() for module settings configuration.
 */
function googleanalytics_admin_settings_form($form_state) {
  $form['account'] = array(
    '#type' => 'fieldset',
    '#title' => t('General settings'),
  );

  $form['account']['googleanalytics_account'] = array(
    '#title' => t('Web Property ID'),
    '#type' => 'textfield',
    '#default_value' => variable_get('googleanalytics_account', 'UA-'),
    '#size' => 15,
    '#maxlength' => 20,
    '#required' => TRUE,
    '#description' => t('This ID is unique to each site you want to track separately, and is in the form of UA-xxxxxxx-yy. To get a Web Property ID, <a href="@analytics">register your site with Google Analytics</a>, or if you already have registered your site, go to your Google Analytics Settings page to see the ID next to every site profile. <a href="@webpropertyid">Find more information in the documentation</a>.', array('@analytics' => 'http://www.google.com/analytics/', '@webpropertyid' => 'http://code.google.com/apis/analytics/docs/concepts/gaConceptsAccounts.html#webProperty')),
  );

  // Visibility settings.
  $form['tracking_title'] = array(
    '#type' => 'item',
    '#title' => t('Tracking scope'),
  );
  $form['tracking'] = array(
    '#type' => 'vertical_tabs',
    '#attached' => array(
      'js' => array(drupal_get_path('module', 'googleanalytics') . '/googleanalytics.admin.js'),
    ),
  );

  $form['tracking']['domain_tracking'] = array(
    '#type' => 'fieldset',
    '#title' => t('Domains'),
  );

  global $cookie_domain;
  $multiple_sub_domains = array();
  foreach (array('www', 'app', 'shop') as $subdomain) {
    if (count(explode('.', $cookie_domain)) > 2 && !is_numeric(str_replace('.', '', $cookie_domain))) {
      $multiple_sub_domains[] = $subdomain . $cookie_domain;
    }
    // IP addresses or localhost.
    else {
      $multiple_sub_domains[] = $subdomain . '.example.com';
    }
  }

  $multiple_toplevel_domains = array();
  foreach (array('.com', '.net', '.org') as $tldomain) {
    $host = $_SERVER['HTTP_HOST'];
    $domain = substr($host, 0, strrpos($host, '.'));
    if (count(explode('.', $host)) > 2 && !is_numeric(str_replace('.', '', $host))) {
      $multiple_toplevel_domains[] = $domain . $tldomain;
    }
    // IP addresses or localhost
    else {
      $multiple_toplevel_domains[] = 'www.example' . $tldomain;
    }
  }

  $form['tracking']['domain_tracking']['googleanalytics_domain_mode'] = array(
    '#type' => 'radios',
    '#title' => t('What are you tracking?'),
    '#options' => array(
      0 => t('A single domain (default)') . '<div class="description">' . t('Domain: @domain', array('@domain' => $_SERVER['HTTP_HOST'])) . '</div>',
      1 => t('One domain with multiple subdomains') . '<div class="description">' . t('Examples: @domains', array('@domains' => implode(', ', $multiple_sub_domains))) . '</div>',
      2 => t('Multiple top-level domains') . '<div class="description">' . t('Examples: @domains', array('@domains' => implode(', ', $multiple_toplevel_domains))) . '</div>',
    ),
    '#default_value' => variable_get('googleanalytics_domain_mode', 0),
  );
  $form['tracking']['domain_tracking']['googleanalytics_cross_domains'] = array(
    '#title' => t('List of top-level domains'),
    '#type' => 'textarea',
    '#default_value' => variable_get('googleanalytics_cross_domains', ''),
    '#description' => t('If you selected "Multiple top-level domains" above, enter all related top-level domains. Add one domain per line. By default, the data in your reports only includes the path and name of the page, and not the domain name. For more information see section <em>Show separate domain names</em> in <a href="http://www.google.com/support/analytics/bin/answer.py?answer=175538">Tracking multiple domains</a>.'),
  );

  // Page specific visibility configurations.
  $php_access = user_access('use PHP for tracking visibility');
  $visibility = variable_get('googleanalytics_visibility_pages', 0);
  $pages = variable_get('googleanalytics_pages', '');

  $form['tracking']['page_vis_settings'] = array(
    '#type' => 'fieldset',
    '#title' => t('Pages'),
    '#collapsible' => TRUE,
    '#collapsed' => TRUE,
  );

  if ($visibility == 2 && !$php_access) {
    $form['tracking']['page_vis_settings'] = array();
    $form['tracking']['page_vis_settings']['visibility'] = array('#type' => 'value', '#value' => 2);
    $form['tracking']['page_vis_settings']['pages'] = array('#type' => 'value', '#value' => $pages);
  }
  else {
    $options = array(
      t('Every page except the listed pages'),
      t('The listed pages only'),
    );
    $description = t("Specify pages by using their paths. Enter one path per line. The '*' character is a wildcard. Example paths are %blog for the blog page and %blog-wildcard for every personal blog. %front is the front page.", array('%blog' => 'blog', '%blog-wildcard' => 'blog/*', '%front' => '<front>'));

    if (module_exists('php') && $php_access) {
      $options[] = t('Pages on which this PHP code returns <code>TRUE</code> (experts only)');
      $title = t('Pages or PHP code');
      $description .= ' ' . t('If the PHP option is chosen, enter PHP code between %php. Note that executing incorrect PHP code can break your Drupal site.', array('%php' => '<?php ?>'));
    }
    else {
      $title = t('Pages');
    }
    $form['tracking']['page_vis_settings']['googleanalytics_visibility_pages'] = array(
      '#type' => 'radios',
      '#title' => t('Add tracking to specific pages'),
      '#options' => $options,
      '#default_value' => $visibility,
    );
    $form['tracking']['page_vis_settings']['googleanalytics_pages'] = array(
      '#type' => 'textarea',
      '#title' => $title,
      '#title_display' => 'invisible',
      '#default_value' => $pages,
      '#description' => $description,
      '#rows' => 10,
    );
  }

  // Render the role overview.
  $form['tracking']['role_vis_settings'] = array(
    '#type' => 'fieldset',
    '#title' => t('Roles'),
  );

  $form['tracking']['role_vis_settings']['googleanalytics_visibility_roles'] = array(
    '#type' => 'radios',
    '#title' => t('Add tracking for specific roles'),
    '#options' => array(
      t('Add to the selected roles only'),
      t('Add to every role except the selected ones'),
    ),
    '#default_value' => variable_get('googleanalytics_visibility_roles', 0),
  );

  $role_options = array_map('check_plain', user_roles());
  $form['tracking']['role_vis_settings']['googleanalytics_roles'] = array(
    '#type' => 'checkboxes',
    '#title' => t('Roles'),
    '#default_value' => variable_get('googleanalytics_roles', array()),
    '#options' => $role_options,
    '#description' => t('If none of the roles are selected, all users will be tracked. If a user has any of the roles checked, that user will be tracked (or excluded, depending on the setting above).'),
  );

  // Standard tracking configurations.
  $form['tracking']['user_vis_settings'] = array(
    '#type' => 'fieldset',
    '#title' => t('Users'),
  );
  $t_permission = array('%permission' => t('opt-in or out of tracking'));
  $form['tracking']['user_vis_settings']['googleanalytics_custom'] = array(
    '#type' => 'radios',
    '#title' => t('Allow users to customize tracking on their account page'),
    '#options' => array(
      t('No customization allowed'),
      t('Tracking on by default, users with %permission permission can opt out', $t_permission),
      t('Tracking off by default, users with %permission permission can opt in', $t_permission),
    ),
    '#default_value' => variable_get('googleanalytics_custom', 0),
  );

  // Link specific configurations.
  $form['tracking']['linktracking'] = array(
    '#type' => 'fieldset',
    '#title' => t('Links and downloads'),
  );
  $form['tracking']['linktracking']['googleanalytics_trackoutbound'] = array(
    '#type' => 'checkbox',
    '#title' => t('Track clicks on outbound links'),
    '#default_value' => variable_get('googleanalytics_trackoutbound', 1),
  );
  $form['tracking']['linktracking']['googleanalytics_trackmailto'] = array(
    '#type' => 'checkbox',
    '#title' => t('Track clicks on mailto links'),
    '#default_value' => variable_get('googleanalytics_trackmailto', 1),
  );
  $form['tracking']['linktracking']['googleanalytics_trackfiles'] = array(
    '#type' => 'checkbox',
    '#title' => t('Track downloads (clicks on file links) for the following extensions'),
    '#default_value' => variable_get('googleanalytics_trackfiles', 1),
  );
  $form['tracking']['linktracking']['googleanalytics_trackfiles_extensions'] = array(
    '#title' => t('List of download file extensions'),
    '#title_display' => 'invisible',
    '#type' => 'textfield',
    '#default_value' => variable_get('googleanalytics_trackfiles_extensions', GA_TRACKFILES_EXTENSIONS),
    '#description' => t('A file extension list separated by the | character that will be tracked as download when clicked. Regular expressions are supported. For example: !extensions', array('!extensions' => GA_TRACKFILES_EXTENSIONS)),
    '#maxlength' => 255,
  );
  $form['tracking']['linktracking']['googleanalytics_trackoutboundaspageview'] = array(
    '#type' => 'checkbox',
    '#title' => t('Track clicks on outbound links as page views'),
    '#default_value' => variable_get('googleanalytics_trackoutboundaspageview', 0),
    '#description' => t('By default outbound links are tracked as <em>Events</em>. In rare situations like tracking of <em>Goals</em> it may be required to track outbound clicks as page views.'),
  );

  // Google already have many translations, if not - they display a note to change the language.
  global $language;
  $form['tracking']['search_and_adsense'] = array(
    '#type' => 'fieldset',
    '#title' => t('Search and AdSense'),
  );

  $site_search_dependencies = '<div class="admin-dependencies">';
  $site_search_dependencies .= t('Depends on: !dependencies', array('!dependencies' => (module_exists('search') ? t('@module (<span class="admin-enabled">enabled</span>)', array('@module' => 'Search')) : t('@module (<span class="admin-disabled">disabled</span>)', array('@module' => 'Search')))));
  $site_search_dependencies .= '</div>';

  $form['tracking']['search_and_adsense']['googleanalytics_site_search'] = array(
    '#type' => 'checkbox',
    '#title' => t('Track internal search'),
    '#description' => t('If checked, internal search keywords are tracked. You must configure your Google account to use the internal query parameter <strong>search</strong>. For more information see <a href="@url">How do I set up Site Search for my profile</a>.', array('@url' => 'http://www.google.com/support/analytics/bin/answer.py?hl=' . $language->language . '&answer=75817')) . $site_search_dependencies,
    '#default_value' => variable_get('googleanalytics_site_search', FALSE),
    '#disabled' => (module_exists('search') ? FALSE : TRUE),
  );

  $form['tracking']['search_and_adsense']['googleanalytics_trackadsense'] = array(
    '#type' => 'checkbox',
    '#title' => t('Track AdSense ads'),
    '#description' => t('If checked, your AdSense ads will be tracked in your Google Analytics account.'),
    '#default_value' => variable_get('googleanalytics_trackadsense', FALSE),
  );

  // Backward compatibility only.
  // TODO: If currently not in use, hide the UI and later remove the code.
  $segmentation = variable_get('googleanalytics_segmentation', array());

  if (!empty($segmentation) || variable_get('googleanalytics_segmentation_DEPRECATED', FALSE)) {
    $profile_enabled = module_exists('profile');
    $form['segmentation'] = array(
      '#type' => 'fieldset',
      '#title' => t('Additional user information'),
      '#collapsible' => TRUE,
    );

    // Compile a list of fields to show.
    $fields = variable_get('googleanalytics_segmentation_default_fields', array('roles' => t('User roles')));
    if ($profile_enabled) {
      $result = db_query('SELECT name, title FROM {profile_field} ORDER BY weight');
      foreach ($result as $record) {
        $fields[$record->name] = $record->title;
      }
    }

    $form['segmentation']['googleanalytics_segmentation'] = array(
      '#type' => 'select',
      '#title' => t('Segment users based on these selected properties') . ' <span class="admin-disabled">***DEPRECATED***</span>',
      '#description' => t("You can supplement Google Analytics' basic IP address tracking of visitors by segmenting users based on these selected properties.") . ' <span class="admin-disabled">' . t('For most situations, Google recommend that you use Custom Variables to segment your visitors.') . '</span>' . (!$profile_enabled ? ' ' . t('<a href="@module_list">Enable the profile module</a> to be able to use profile fields for more granular tracking.', array('@module_list' => url('admin/build/modules'))) : '') . ' ' . t('Section 8.1 of the <a href="@ga_tos">Google Analytics terms of use</a> requires you to make sure you will not associate (or permit any third party to associate) any data gathered from your websites (or such third parties\' websites) with any personally identifying information from any source as part of your use (or such third parties\' use) of the Google Analytics\' service.', array('@ga_tos' => 'http://www.google.com/analytics/en-GB/tos.html')) . ' ' . t('You can select multiple values.'),
      '#default_value' => $segmentation,
      '#options' => $fields,
      '#size' => count($fields)>3 ? 10 : 3,
      '#multiple' => TRUE,
    );
  }

  $form['googleanalytics_custom_var'] = array(
    '#collapsible' => TRUE,
    '#description' => t('You can add Google Analytics <a href="!custom_var_documentation">Custom Variables</a> here. These will be added to every page that Google Analytics tracking code appears on. Google Analytics will only accept custom variables if the <em>name</em> and <em>value</em> combined are less than 64 bytes after URL encoding. Keep the names as short as possible and expect long values to get trimmed. You may use tokens in custom variable values. Global and user tokens are always available; on node pages, node tokens are also available.', array('!custom_var_documentation' => 'http://code.google.com/intl/en/apis/analytics/docs/tracking/gaTrackingCustomVariables.html')),
    '#theme' => 'googleanalytics_admin_custom_var_table',
    '#title' => t('Custom variables'),
    '#tree' => TRUE,
    '#type' => 'fieldset',
  );

  $googleanalytics_custom_vars = variable_get('googleanalytics_custom_var', array());

  // Google Analytics supports up to 5 custom variables.
  for ($i = 1; $i < 6; $i++) {
    $form['googleanalytics_custom_var']['slots'][$i]['slot'] = array(
      '#default_value' => $i,
      '#description' => t('Slot number'),
      '#disabled' => TRUE,
      '#size' => 1,
      '#title' => t('Custom variable slot #@slot', array('@slot' => $i)),
      '#title_display' => 'invisible',
      '#type' => 'textfield',
    );
    $form['googleanalytics_custom_var']['slots'][$i]['name'] = array(
      '#default_value' => !empty($googleanalytics_custom_vars['slots'][$i]['name']) ? $googleanalytics_custom_vars['slots'][$i]['name'] : '',
      '#description' => t('The custom variable name.'),
      '#size' => 20,
      '#title' => t('Custom variable name #@slot', array('@slot' => $i)),
      '#title_display' => 'invisible',
      '#type' => 'textfield',
    );
    $form['googleanalytics_custom_var']['slots'][$i]['value'] = array(
      '#default_value' => !empty($googleanalytics_custom_vars['slots'][$i]['value']) ? $googleanalytics_custom_vars['slots'][$i]['value'] : '',
      '#description' => t('The custom variable value. You may use tokens in this field.'),
      '#title' => t('Custom variable value #@slot', array('@slot' => $i)),
      '#title_display' => 'invisible',
      '#type' => 'textfield',
      '#element_validate' => array('googleanalytics_token_element_validate'),
      '#token_types' => array('node'),
    );
    if (module_exists('token')) {
      $form['googleanalytics_custom_var']['slots'][$i]['value']['#element_validate'][] = 'token_element_validate';
    }
    $form['googleanalytics_custom_var']['slots'][$i]['scope'] = array(
      '#default_value' => !empty($googleanalytics_custom_vars['slots'][$i]['scope']) ? $googleanalytics_custom_vars['slots'][$i]['scope'] : 3,
      '#description' => t('The scope for the custom variable.'),
      '#title' => t('Custom variable slot #@slot', array('@slot' => $i)),
      '#title_display' => 'invisible',
      '#type' => 'select',
      '#options' => array(
        1 => t('Visitor'),
        2 => t('Session'),
        3 => t('Page'),
      ),
    );
  }

  $form['googleanalytics_custom_var']['googleanalytics_custom_var_description'] = array(
    '#type' => 'item',
    '#description' => t('You can supplement Google Analytics\' basic IP address tracking of visitors by segmenting users based on custom variables. Section 8.1 of the <a href="@ga_tos">Google Analytics terms of use</a> requires you to make sure you will not associate (or permit any third party to associate) any data gathered from your websites (or such third parties\' websites) with any personally identifying information from any source as part of your use (or such third parties\' use) of the Google Analytics\' service.', array('@ga_tos' => 'http://www.google.com/analytics/en-GB/tos.html')),
  );
  $form['googleanalytics_custom_var']['googleanalytics_custom_var_token_tree'] = array(
    '#theme' => 'token_tree',
    '#token_types' => array('node'),
  );


  // Advanced feature configurations.
  $form['advanced'] = array(
    '#type' => 'fieldset',
    '#title' => t('Advanced settings'),
    '#collapsible' => TRUE,
    '#collapsed' => TRUE,
  );

  $form['advanced']['googleanalytics_tracker_anonymizeip'] = array(
    '#type' => 'checkbox',
    '#title' => t('Anonymize visitors IP address'),
    '#description' => t('Tell Google Analytics to anonymize the information sent by the tracker objects by removing the last octet of the IP address prior to its storage. Note that this will slightly reduce the accuracy of geographic reporting. In some countries it is not allowed to collect personally identifying information for privacy reasons and this setting may help you to comply with the local laws.'),
    '#default_value' => variable_get('googleanalytics_tracker_anonymizeip', 0),
  );

  $form['advanced']['googleanalytics_cache'] = array(
    '#type' => 'checkbox',
    '#title' => t('Locally cache tracking code file'),
    '#description' => t("If checked, the tracking code file is retrieved from Google Analytics and cached locally. It is updated daily from Google's servers to ensure updates to tracking code are reflected in the local copy. Do not activate this until after Google Analytics has confirmed that site tracking is working!"),
    '#default_value' => variable_get('googleanalytics_cache', 0),
  );

  // Allow for tracking of the originating node when viewing translation sets.
  if (module_exists('translation')) {
    $form['advanced']['googleanalytics_translation_set'] = array(
      '#type' => 'checkbox',
      '#title' => t('Track translation sets as one unit'),
      '#description' => t('When a node is part of a translation set, record statistics for the originating node instead. This allows for a translation set to be treated as a single unit.'),
      '#default_value' => variable_get('googleanalytics_translation_set', 0),
    );
  }

  $form['advanced']['codesnippet'] = array(
    '#type' => 'fieldset',
    '#title' => t('Custom JavaScript code'),
    '#collapsible' => TRUE,
    '#collapsed' => TRUE,
    '#description' => t('You can add custom Google Analytics <a href="@snippets">code snippets</a> here. These will be added every time tracking is in effect. Before you add your custom code, you should read the <a href="@ga_concepts_overview">Google Analytics Tracking Code - Functional Overview</a> and the <a href="@ga_js_api">Google Analytics Tracking API</a> documentation. <strong>Do not include the &lt;script&gt; tags</strong>, and always end your code with a semicolon (;).', array('@snippets' => 'http://drupal.org/node/248699', '@ga_concepts_overview' => 'http://code.google.com/apis/analytics/docs/gaConceptsOverview.html', '@ga_js_api' => 'http://code.google.com/apis/analytics/docs/gaJSApi.html')),
  );
  $form['advanced']['codesnippet']['googleanalytics_codesnippet_before'] = array(
    '#type' => 'textarea',
    '#title' => t('Code snippet (before)'),
    '#default_value' => variable_get('googleanalytics_codesnippet_before', ''),
    '#rows' => 5,
    '#description' => t("Code in this textarea will be added <strong>before</strong> _gaq.push(['_trackPageview'])."),
  );
  $form['advanced']['codesnippet']['googleanalytics_codesnippet_after'] = array(
    '#type' => 'textarea',
    '#title' => t('Code snippet (after)'),
    '#default_value' => variable_get('googleanalytics_codesnippet_after', ''),
    '#rows' => 5,
    '#description' => t("Code in this textarea will be added <strong>after</strong> _gaq.push(['_trackPageview']). This is useful if you'd like to track a site in two accounts."),
  );

  $form['advanced']['googleanalytics_js_scope'] = array(
    '#type' => 'select',
    '#title' => t('JavaScript scope'),
    '#description' => t('Google recommends adding the external JavaScript files to the header for performance reasons. If <em>Multiple top-level domains</em> has been selected, this setting will be forced to header.'),
    '#options' => array(
      'footer' => t('Footer'),
      'header' => t('Header'),
    ),
    '#default_value' => variable_get('googleanalytics_js_scope', 'header'),
    '#disabled' => (variable_get('googleanalytics_domain_mode', 0) == 2) ? TRUE : FALSE,
  );

  return system_settings_form($form);
}

/**
 * Implements _form_validate().
 */
function googleanalytics_admin_settings_form_validate($form, &$form_state) {
  // Custom variables validation.
  foreach ($form_state['values']['googleanalytics_custom_var']['slots'] as $custom_var) {
    // Validate empty names/values.
    if (empty($custom_var['name']) && !empty($custom_var['value'])) {
      form_set_error("googleanalytics_custom_var][slots][" . $custom_var['slot'] . "][name", t('The custom variable @slot-number requires a <em>Value</em> if a <em>Name</em> has been provided.', array('@slot-number' =>  $custom_var['slot'])));
    }
    elseif (!empty($custom_var['name']) && empty($custom_var['value'])) {
      form_set_error("googleanalytics_custom_var][slots][" . $custom_var['slot'] . "][value", t('The custom variable @slot-number requires a <em>Name</em> if a <em>Value</em> has been provided.', array('@slot-number' =>  $custom_var['slot'])));
    }
  }

  // Trim some text values.
  $form_state['values']['googleanalytics_account'] = trim($form_state['values']['googleanalytics_account']);
  $form_state['values']['googleanalytics_pages'] = trim($form_state['values']['googleanalytics_pages']);
  $form_state['values']['googleanalytics_cross_domains'] = trim($form_state['values']['googleanalytics_cross_domains']);
  $form_state['values']['googleanalytics_codesnippet_before'] = trim($form_state['values']['googleanalytics_codesnippet_before']);
  $form_state['values']['googleanalytics_codesnippet_after'] = trim($form_state['values']['googleanalytics_codesnippet_after']);

  if (!preg_match('/^UA-\d{4,}-\d+$/', $form_state['values']['googleanalytics_account'])) {
    form_set_error('googleanalytics_account', t('A valid Google Analytics Web Property ID is case sensitive and formatted like UA-xxxxxxx-yy.'));
  }

  // If multiple top-level domains has been selected, a domain names list is required.
  if ($form_state['values']['googleanalytics_domain_mode'] == 2 && empty($form_state['values']['googleanalytics_cross_domains'])) {
    form_set_error('googleanalytics_cross_domains', t('A list of top-level domains is required if <em>Multiple top-level domains</em> has been selected.'));
  }
  // Clear obsolete local cache if cache has been disabled.
  if (empty($form_state['values']['googleanalytics_cache']) && $form['advanced']['googleanalytics_cache']['#default_value']) {
    googleanalytics_clear_js_cache();
  }

  // This is for the Newbie's who cannot read a text area description.
  if (stristr($form_state['values']['googleanalytics_codesnippet_before'], 'google-analytics.com/ga.js')) {
    form_set_error('googleanalytics_codesnippet_before', t('Do not add the tracker code provided by Google into the javascript code snippets! This module already builds the tracker code based on your Google Analytics account number and settings.'));
  }
  if (stristr($form_state['values']['googleanalytics_codesnippet_after'], 'google-analytics.com/ga.js')) {
    form_set_error('googleanalytics_codesnippet_after', t('Do not add the tracker code provided by Google into the javascript code snippets! This module already builds the tracker code based on your Google Analytics account number and settings.'));
  }
  if (preg_match('/(.*)<\/?script(.*)>(.*)/i', $form_state['values']['googleanalytics_codesnippet_before'])) {
    form_set_error('googleanalytics_codesnippet_before', t('Do not include the &lt;script&gt; tags in the javascript code snippets.'));
  }
  if (preg_match('/(.*)<\/?script(.*)>(.*)/i', $form_state['values']['googleanalytics_codesnippet_after'])) {
    form_set_error('googleanalytics_codesnippet_after', t('Do not include the &lt;script&gt; tags in the javascript code snippets.'));
  }

  // Header section must be forced for multiple top-level domains.
  if ($form_state['values']['googleanalytics_domain_mode'] == 2) {
    $form_state['values']['googleanalytics_js_scope'] = 'header';
  }
}

/**
 * Layout for the custom variables table in the admin settings form.
 */
function theme_googleanalytics_admin_custom_var_table($variables) {
  $form = $variables['form'];

  $header = array(
    array('data' => t('Slot')),
    array('data' => t('Name')),
    array('data' => t('Value')),
    array('data' => t('Scope')),
  );

  $rows = array();
  foreach (element_children($form['slots']) as $key => $id) {
    $rows[] = array(
      'data' => array(
        drupal_render($form['slots'][$id]['slot']),
        drupal_render($form['slots'][$id]['name']),
        drupal_render($form['slots'][$id]['value']),
        drupal_render($form['slots'][$id]['scope']),
      ),
    );
  }

  $output = theme('table', array('header' => $header, 'rows' => $rows));
  $output .= drupal_render($form['googleanalytics_custom_var_description']);
  $output .= drupal_render($form['googleanalytics_custom_var_token_tree']);

  return $output;
}

/**
 * Validate a form element that should have tokens in it.
 *
 * For example:
 * @code
 * $form['my_node_text_element'] = array(
 *   '#type' => 'textfield',
 *   '#title' => t('Some text to token-ize that has a node context.'),
 *   '#default_value' => 'The title of this node is [node:title].',
 *   '#element_validate' => array('googleanalytics_token_element_validate'),
 * );
 * @endcode
 */
function googleanalytics_token_element_validate(&$element, &$form_state) {
  $value = isset($element['#value']) ? $element['#value'] : $element['#default_value'];

  if (!drupal_strlen($value)) {
    // Empty value needs no further validation since the element should depend
    // on using the '#required' FAPI property.
    return $element;
  }

  $tokens = token_scan($value);
  $invalid_tokens = _googleanalytics_get_forbidden_tokens($tokens);
  if ($invalid_tokens) {
    form_error($element, t('The %element-title is using the following forbidden tokens with personal identifying information: @invalid-tokens.', array('%element-title' => $element['#title'], '@invalid-tokens' => implode(', ', $invalid_tokens))));
  }

  return $element;
}

function _googleanalytics_get_forbidden_tokens($value) {
  $invalid_tokens = array();
  $value_tokens = is_string($value) ? token_scan($value) : $value;

  foreach ($value_tokens as $type => $tokens) {
    if (array_filter($tokens, '_googleanalytics_contains_forbidden_token')) {
      $invalid_tokens = array_merge($invalid_tokens, array_values($tokens));
    }
  }

  array_unique($invalid_tokens);
  return $invalid_tokens;
}

/**
 * Validate if a string contains forbidden tokens not allowed by privacy rules.
 *
 * @param $token_string
 *   A string with one or more tokens to be validated.
 * @return boolean
 *   TRUE if blacklisted token has been found, otherwise FALSE.
 */
function _googleanalytics_contains_forbidden_token($token_string) {
  // List of strings in tokens with personal identifying information not allowed
  // for privacy reasons. See section 8.1 of the Google Analytics terms of use
  // for more detailed information.
  //
  // This list can never ever be complete. For this reason it tries to use a
  // regex and may kill a few other valid tokens, but it's the only way to
  // protect users as much as possible from admins with illegal ideas.
  //
  // User tokens are not prefixed with colon to catch 'current-user' and 'user'.
  //
  // TODO: If someone have better ideas, share them, please!
  $token_blacklist = array(
    ':author]',
    ':author:edit-url]',
    ':author:url]',
    ':author:path]',
    ':fid]',
    ':mail]',
    ':name]',
    ':uid]',
    ':one-time-login-url]',
    ':owner]',
    ':owner:cancel-url]',
    ':owner:edit-url]',
    ':owner:url]',
    ':owner:path]',
    'user:cancel-url]',
    'user:edit-url]',
    'user:url]',
    'user:path]',
  );

  return preg_match('/' . implode('|', array_map('preg_quote', $token_blacklist)) . '/i', $token_string);
}
