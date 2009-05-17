<?php

/**
 * @file
 * Definition of views_handler_field_user_picture.
 */

/**
 * Field handler to provide simple renderer that allows using a themed user link.
 *
 * @ingroup views_field_handlers
 */
class views_handler_field_user_picture extends views_handler_field {
  function construct() {
    parent::construct();
    $this->additional_fields['uid'] = 'uid';
    $this->additional_fields['name'] = 'name';
    $this->additional_fields['mail'] = 'mail';
  }

  function element_type($none_supported = FALSE, $default_empty = FALSE, $inline = FALSE) {
    if ($inline) {
      return 'span';
    }
    if ($none_supported) {
      if ($this->options['element_type'] === '0') {
        return '';
      }
    }
    if ($this->options['element_type']) {
      return check_plain($this->options['element_type']);
    }
    if ($default_empty) {
      return '';
    }
    if (isset($this->definition['element type'])) {
      return $this->definition['element type'];
    }

    return 'div';
  }

  function option_definition() {
    $options = parent::option_definition();
    $options['link_photo_to_profile'] = array('default' => TRUE, 'bool' => TRUE);
    $options['image_style'] = array('default' => '');
    return $options;
  }

  function options_form(&$form, &$form_state) {
    parent::options_form($form, $form_state);
    $form['link_photo_to_profile'] = array(
      '#title' => t("Link to user's profile"),
      '#description' => t("Link the user picture to the user's profile"),
      '#type' => 'checkbox',
      '#default_value' => $this->options['link_photo_to_profile'],
    );

    if (module_exists('image')) {
      $styles = image_styles();
      $style_options = array('' => t('Default'));
      foreach ($styles as $style) {
        $style_options[$style['name']] = $style['name'];
      }

      $form['image_style'] = array(
        '#title' => t('Image style'),
        '#description' => t('Using <em>Default</em> will use the site-wide image style for user pictures set in the <a href="!account-settings">Account settings</a>.', array('!account-settings' => url('admin/config/people/accounts', array('fragment' => 'edit-personalization')))),
        '#type' => 'select',
        '#options' => $style_options,
        '#default_value' => $this->options['image_style'],
      );
    }
  }

  function render($values) {
    if ($this->options['image_style'] && module_exists('image')) {
      // @todo: Switch to always using theme('user_picture') when it starts
      // supporting image styles. See http://drupal.org/node/1021564
      if ($picture_fid = $this->get_value($values)) {
        $picture = file_load($picture_fid);
        $picture_filepath = $picture->uri;
      }
      else {
        $picture_filepath = variable_get('user_picture_default', '');
      }
      if (file_valid_uri($picture_filepath)) {
        $output = theme('image_style', array('style_name' => $this->options['image_style'], 'path' => $picture_filepath));
        if ($this->options['link_photo_to_profile'] && user_access('access user profiles')) {
          $uid = $this->get_value($values, 'uid');
          $output = l($output, "user/$uid", array('html' => TRUE));
        }
      }
      else {
        $output = '';
      }
    }
    else {
      // Fake an account object.
      $account = new stdClass();
      if ($this->options['link_photo_to_profile']) {
        // Prevent template_preprocess_user_picture from adding a link
        // by not setting the uid.
        $account->uid = $this->get_value($values, 'uid');
      }
      $account->name = $this->get_value($values, 'name');
      $account->mail = $this->get_value($values, 'mail');
      $account->picture = $this->get_value($values);
      $output = theme('user_picture', array('account' => $account));
    }

    return $output;
  }
}
