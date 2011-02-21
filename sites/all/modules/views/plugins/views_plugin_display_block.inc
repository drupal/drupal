<?php
// $Id: views_plugin_display_block.inc,v 1.5.4.3 2010/08/04 05:54:08 dereine Exp $
/**
 * @file
 * Contains the block display plugin.
 */

/**
 * The plugin that handles a block.
 *
 * @ingroup views_display_plugins
 */
class views_plugin_display_block extends views_plugin_display {
  function option_definition() {
    $options = parent::option_definition();

    $options['block_description'] = array('default' => '', 'translatable' => TRUE);
    $options['block_caching'] = array('default' => DRUPAL_NO_CACHE);

    return $options;
  }

  /**
   * The default block handler doesn't support configurable items,
   * but extended block handlers might be able to do interesting
   * stuff with it.
   */
  function execute_hook_block_list($delta = 0, $edit = array()) {
    $delta = $this->view->name . '-' . $this->display->id;
    $desc = $this->get_option('block_description');

    if (empty($desc)) {
      $desc = t('@view: @display', array('@view' => $this->view->name, '@display' => $this->display->display_title));
    }
    return array(
      $delta => array(
        'info' => $desc,
        'cache' => $this->get_cache_type()
      ),
    );
  }

  /**
   * The display block handler returns the structure necessary for a block.
   */
  function execute() {
    // Prior to this being called, the $view should already be set to this
    // display, and arguments should be set on the view.
    $info['content'] = $this->view->render();
    $info['subject'] = filter_xss_admin($this->view->get_title());
    if (!empty($this->view->result) || $this->get_option('empty') || !empty($this->view->style_plugin->definition['even empty'])) {
      return $info;
    }
  }

  /**
   * Provide the summary for page options in the views UI.
   *
   * This output is returned as an array.
   */
  function options_summary(&$categories, &$options) {
    // It is very important to call the parent function here:
    parent::options_summary($categories, $options);

    $categories['block'] = array(
      'title' => t('Block settings'),
    );

    $block_description = strip_tags($this->get_option('block_description'));
    if (empty($block_description)) {
      $block_description = t('None');
    }

    if (strlen($block_description) > 16) {
      $block_description = substr($block_description, 0, 16) . '...';
    }

    $options['block_description'] = array(
      'category' => 'block',
      'title' => t('Admin'),
      'value' => $block_description,
    );

    $cache_type = $this->get_option('block_caching');
    if (empty($cache_type)) {
      $cache_type = DRUPAL_NO_CACHE;
    }

    $types = $this->block_caching_modes();
    $options['block_caching'] = array(
      'category' => 'block',
      'title' => t('Caching'),
      'value' => $types[$this->get_cache_type()],
    );
  }

  /**
   * Provide a list of core's block caching modes.
   */
  function block_caching_modes() {
    return array(
      DRUPAL_NO_CACHE => t('Do not cache'),
      DRUPAL_CACHE_GLOBAL => t('Cache once for everything (global)'),
      DRUPAL_CACHE_PER_PAGE => t('Per page'),
      DRUPAL_CACHE_PER_ROLE => t('Per role'),
      DRUPAL_CACHE_PER_ROLE | DRUPAL_CACHE_PER_PAGE => t('Per role per page'),
      DRUPAL_CACHE_PER_USER => t('Per user'),
      DRUPAL_CACHE_PER_USER | DRUPAL_CACHE_PER_PAGE => t('Per user per page'),
    );
  }

  /**
   * Provide a single method to figure caching type, keeping a sensible default
   * for when it's unset.
   */
  function get_cache_type() {
    $cache_type = $this->get_option('block_caching');
    if (empty($cache_type)) {
      $cache_type = DRUPAL_NO_CACHE;
    }
    return $cache_type;
  }

  /**
   * Provide the default form for setting options.
   */
  function options_form(&$form, &$form_state) {
    // It is very important to call the parent function here:
    parent::options_form($form, $form_state);

    switch ($form_state['section']) {
      case 'block_description':
        $form['#title'] .= t('Block admin description');
        $form['block_description'] = array(
          '#type' => 'textfield',
          '#description' => t('This will appear as the name of this block in administer >> structure >> blocks.'),
          '#default_value' => $this->get_option('block_description'),
        );
        break;
      case 'block_caching':
        $form['#title'] .= t('Block caching type');

        $form['block_caching'] = array(
          '#type' => 'radios',
          '#description' => t("This sets the default status for Drupal's built-in block caching method; this requires that caching be turned on in block administration, and be careful because you have little control over when this cache is flushed."),
          '#options' => $this->block_caching_modes(),
          '#default_value' => $this->get_cache_type(),
        );
        break;
    }
  }

  /**
   * Perform any necessary changes to the form values prior to storage.
   * There is no need for this function to actually store the data.
   */
  function options_submit(&$form, &$form_state) {
    // It is very important to call the parent function here:
    parent::options_submit($form, $form_state);
    switch ($form_state['section']) {
      case 'block_description':
        $this->set_option('block_description', $form_state['values']['block_description']);
        break;
      case 'block_caching':
        $this->set_option('block_caching', $form_state['values']['block_caching']);
        $this->save_block_cache($form_state['view']->name.'-'.$form_state['display_id'], $form_state['values']['block_caching']);
        break;
    }
  }

  /**
   * Block views use exposed widgets only if AJAX is set.
   */
  function uses_exposed() {
    if ($this->use_ajax()) {
      return parent::uses_exposed();
    }

    return FALSE;
  }

  /**
   * Save the block cache setting in the blocks table if this block allready
   * exists in the blocks table. Dirty fix untill http://drupal.org/node/235673 gets in.
   */
  function save_block_cache($delta, $cache_setting) {
    if ($bid = db_query("SELECT bid FROM {block} WHERE module = 'views' AND delta = :delta", array(
        ':delta' => $delta))->fetchField()) {
      db_update('block')
        ->fields(array(
        'cache' => $cache_setting,
        ))
        ->condition('module','views')
        ->condition('delta', $delta)
        ->execute();
    }
  }
}
