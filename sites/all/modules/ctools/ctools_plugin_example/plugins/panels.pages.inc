<?php
// $Id: panels.pages.inc,v 1.3 2009/10/17 22:58:26 sdboyer Exp $

/**
 * @file
 * Holds the panels pages export.
 */

/**
 * Implements hook_default_panel_pages()
 */
function ctools_plugin_example_default_panel_pages() {
  $page = new stdClass();
  $page->pid = 'new';
  $page->did = 'new';
  $page->name = 'ctools_plugin_example_demo_panel';
  $page->title = 'Panels Plugin Example Demo Panel';
  $page->access = array();
  $page->path = 'demo_panel';
  $page->load_flags = 1;
  $page->css_id = '';
  $page->arguments = array(
  0 =>
  array(
      'name' => 'simplecontext_arg',
      'id' => 1,
      'default' => '404',
      'title' => '',
      'identifier' => 'Simplecontext arg',
      'keyword' => 'simplecontext',
  ),
  );
  $page->relationships = array(
  0 =>
  array(
      'context' => 'argument_simplecontext_arg_1',
      'name' => 'relcontext_from_simplecontext',
      'id' => 1,
      'identifier' => 'Relcontext from Simplecontext',
      'keyword' => 'relcontext',
  ),
  );
  $page->no_blocks = '0';
  $page->switcher_options = array();
  $page->menu = '0';
  $page->contexts = array();
  $display = new ctools_display();
  $display->did = 'new';
  $display->layout = 'threecol_33_34_33_stacked';
  $display->layout_settings = array();
  $display->panel_settings = array();
  $display->content = array();
  $display->panels = array();
  $pane = new stdClass();
  $pane->pid = 'new-1';
  $pane->panel = 'left';
  $pane->type = 'custom';
  $pane->shown = '1';
  $pane->subtype = 'custom';
  $pane->access = array();
  $pane->configuration = array(
      'style' => 'default',
      'override_title' => 0,
      'override_title_text' => '',
      'css_id' => '',
      'css_class' => '',
      'title' => '"No Context Item"',
      'body' => 'The "no context item" content type is here to demonstrate that you can create a content_type that does not require a context. This is probably the same as just creating a custom php block on the fly, and might serve the same purpose.',
      'format' => '1',
  );
  $pane->cache = array();
  $display->content['new-1'] = $pane;
  $display->panels['left'][0] = 'new-1';
  $pane = new stdClass();
  $pane->pid = 'new-2';
  $pane->panel = 'left';
  $pane->type = 'no_context_item';
  $pane->shown = '1';
  $pane->subtype = 'description';
  $pane->access = array();
  $pane->configuration = array(
      'style' => 'default',
      'override_title' => 0,
      'override_title_text' => '',
      'css_id' => '',
      'css_class' => '',
      'item1' => 'one',
      'item2' => 'two',
      'item3' => 'three',
  );
  $pane->cache = array();
  $display->content['new-2'] = $pane;
  $display->panels['left'][1] = 'new-2';
  $pane = new stdClass();
  $pane->pid = 'new-3';
  $pane->panel = 'middle';
  $pane->type = 'custom';
  $pane->shown = '1';
  $pane->subtype = 'custom';
  $pane->access = array();
  $pane->configuration = array(
      'style' => 'default',
      'override_title' => 0,
      'override_title_text' => '',
      'css_id' => '',
      'css_class' => '',
      'title' => 'Simplecontext',
      'body' => 'The "Simplecontext" content and content type demonstrate a very basic context and how to display it.

    Simplecontext includes configuration, so it can get info from the config. It can also get its information to run from a simplecontext context, generated either from an arg to the panels page or via explicitly adding a context to the page.',
      'format' => '1',
  );
  $pane->cache = array();
  $display->content['new-3'] = $pane;
  $display->panels['middle'][0] = 'new-3';
  $pane = new stdClass();
  $pane->pid = 'new-4';
  $pane->panel = 'middle';
  $pane->type = 'simplecontext_item';
  $pane->shown = '1';
  $pane->subtype = 'description';
  $pane->access = array(
  0 => '2',
  1 => '4',
  );
  $pane->configuration = array(
      'context' => 'argument_simplecontext_arg_1',
      'style' => 'default',
      'override_title' => 0,
      'override_title_text' => '',
      'css_id' => '',
      'css_class' => '',
      'config_item_1' => 'simplecontext called from arg',
  );
  $pane->cache = array();
  $display->content['new-4'] = $pane;
  $display->panels['middle'][1] = 'new-4';
  $pane = new stdClass();
  $pane->pid = 'new-5';
  $pane->panel = 'right';
  $pane->type = 'custom';
  $pane->shown = '1';
  $pane->subtype = 'custom';
  $pane->access = array();
  $pane->configuration = array(
      'style' => 'default',
      'override_title' => 0,
      'override_title_text' => '',
      'css_id' => '',
      'css_class' => '',
      'title' => 'Relcontext',
      'body' => 'The relcontext content_type gets its data from a relcontext, which is an example of a relationship. This panel should be run with an argument like "/xxx", which allows the simplecontext to get its context, and then the relcontext is configured in this panel to get (create) its data from the simplecontext.',
      'format' => '1',
  );
  $pane->cache = array();
  $display->content['new-5'] = $pane;
  $display->panels['right'][0] = 'new-5';
  $pane = new stdClass();
  $pane->pid = 'new-6';
  $pane->panel = 'right';
  $pane->type = 'relcontext_item';
  $pane->shown = '1';
  $pane->subtype = 'description';
  $pane->access = array();
  $pane->configuration = array(
      'context' => 'relationship_relcontext_from_simplecontext_1',
      'style' => 'default',
      'override_title' => 0,
      'override_title_text' => '',
      'css_id' => '',
      'css_class' => '',
      'config_item_1' => 'default1',
  );
  $pane->cache = array();
  $display->content['new-6'] = $pane;
  $display->panels['right'][1] = 'new-6';
  $pane = new stdClass();
  $pane->pid = 'new-7';
  $pane->panel = 'top';
  $pane->type = 'custom_php';
  $pane->shown = '1';
  $pane->subtype = 'custom_php';
  $pane->access = array();
  $pane->configuration = array(
      'style' => 'default',
      'override_title' => 0,
      'override_title_text' => '',
      'css_id' => '',
      'css_class' => '',
      'title' => '',
      'body' => '$arg = arg(1);
    $arg0 = arg(0);
    if (!$arg) {
    $block->content = <<<END
    <em>This page is intended to run with an arg and you don\'t have one.</em>
    <br />
    Without an arg, the page doesn\'t have any context.
    <br />Please try something like "/$arg0/xxx"
END;

    $block->title = "This is intended to run with an argument";
    } else {
    $block->content = "The arg for this page is \'$arg\'";
    }',
  );
  $pane->cache = array();
  $display->content['new-7'] = $pane;
  $display->panels['top'][0] = 'new-7';
  $page->display = $display;
  $page->displays = array();
  $pages['ctools_plugin_example'] = $page;


  return $pages;
}
