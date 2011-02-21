<?php
// $Id: stylizer.inc,v 1.3 2011/01/05 22:35:46 merlinofchaos Exp $
/**
 * @file
 * Create customized CSS and images from palettes created by user input.
 */

/**
 * Fetch metadata on a specific style_base plugin.
 *
 * @param $content type
 *   Name of a panel content type.
 *
 * @return
 *   An array with information about the requested stylizer style base.
 */
function ctools_get_style_base($style_base) {
  ctools_include('plugins');
  return ctools_get_plugins('stylizer', 'style_bases', $style_base);
}

/**
 * Fetch metadata for all style_base plugins.
 *
 * @return
 *   An array of arrays with information about all available styleizer style bases.
 */
function ctools_get_style_bases() {
  ctools_include('plugins');
  return ctools_get_plugins('stylizer', 'style_bases');
}

/**
 * Fetch metadata about all of the style base types that are available.
 */
function ctools_get_style_base_types() {
  $types = array();
  foreach (module_implements('ctools_style_base_types') as $module) {
    $types[$module] = module_invoke($module, 'ctools_style_base_types');
  }

  return $types;
}

/**
 * Render the icon for a style base.
 */
function ctools_stylizer_print_style_icon($plugin, $print_title = TRUE) {
  $file = $plugin['path'] . '/' . $plugin['icon'];
  $title = $print_title ? $plugin['title'] : '';
  return theme('ctools_style_icon', array('image' => theme('image', array('path' => $file)), 'title' => $title));
}

/**
 * Theme the style icon image
 */
function theme_ctools_style_icon($vars) {
  $image = $vars['image'];
  ctools_add_css('stylizer');
  ctools_add_js('stylizer');
  $output = '<div class="ctools-style-icon">';
  $output .= $vars['image'];
  if ($vars['title']) {
    $output .= '<div class="caption">' . $vars['title'] . '</div>';
  }
  $output .= '</div>';
  return $output;
}

/**
 * Add the necessary CSS for a stylizer plugin to the page.
 *
 * This will check to see if the images directory and the cached CSS
 * exists and, if not, will regenerate everything needed.
 */
function ctools_stylizer_add_css($plugin, $settings) {
  if (!file_exists(ctools_stylizer_get_image_path($plugin, $settings, FALSE))) {
    ctools_stylizer_build_style($plugin, $settings, TRUE);
    return;
  }

  ctools_include('css');
  $filename = ctools_css_retrieve(ctools_stylizer_get_css_id($plugin, $settings));
  if (!$filename) {
    ctools_stylizer_build_style($plugin, $settings, TRUE);
  }
  else {
    drupal_add_css($filename);
  }
}

/**
 * Build the files for a stylizer given the proper settings.
 */
function ctools_stylizer_build_style($plugin, $settings, $add_css = FALSE) {
  $path = ctools_stylizer_get_image_path($plugin, $settings);
  if (!$path) {
    return;
  }

  $replacements = array();

  // Set up palette conversions
  foreach ($settings['palette'] as $key => $color) {
    $replacements['%' . $key ] = $color;
  }

  // Process image actions:
  if (!empty($plugin['actions'])) {
    $processor = new ctools_stylizer_image_processor;
    $processor->execute($path, $plugin, $settings);

// @todo -- there needs to be an easier way to get at this.
//  dsm($processor->message_log);
    // Add filenames to our conversions.
  }

  // Convert and write the CSS file.
  $css = file_get_contents($plugin['path'] . '/' . $plugin['css']);

  // Replace %style keyword with our generated class name.
  // @todo We need one more unique identifier I think.
  $class = ctools_stylizer_get_css_class($plugin, $settings);
  $replacements['%style'] = '.' . $class;

  if (!empty($processor) && !empty($processor->paths)) {
    foreach ($processor->paths as $file => $image) {
      $replacements[$file] = file_create_url($image);
    }
  }

  if (!empty($plugin['build']) && function_exists($plugin['build'])) {
    $plugin['build']($plugin, $settings, $css, $replacements);
  }

  $css = strtr($css, $replacements);
  ctools_include('css');
  $filename = ctools_css_store(ctools_stylizer_get_css_id($plugin, $settings), $css, FALSE);

  if ($add_css) {
    drupal_add_css($filename);
  }
}

/**
 * Clean up no longer used files.
 *
 * To prevent excess clutter in the files directory, this should be called
 * whenever a style is going out of use. When being deleted, but also when
 * the palette is being changed.
 */
function ctools_stylizer_cleanup_style($plugin, $settings) {
  ctools_include('css');
  $path = ctools_stylizer_get_image_path($plugin, $settings, FALSE);
  if ($path) {
    ctools_stylizer_recursive_delete($path);
  }

  ctools_css_clear(ctools_stylizer_get_css_id($plugin, $settings));
}

/**
 * Recursively delete all files and folders in the specified filepath, then
 * delete the containing folder.
 *
 * Note that this only deletes visible files with write permission.
 *
 * @param string $path
 *   A filepath relative to file_directory_path.
 */
function ctools_stylizer_recursive_delete($path) {
  if (empty($path)) {
    return;
  }

  $listing = $path . '/*';

  foreach (glob($listing) as $file) {
    if (is_file($file) === TRUE) {
      @unlink($file);
    }
    elseif (is_dir($file) === TRUE) {
      ctools_stylizer_recursive_delete($file);
    }
  }

  @rmdir($path);
}

/**
 * Get a safe name for the settings.
 *
 * This uses an md5 of the palette if the name is temporary so
 * that multiple temporary styles on the same page can coexist
 * safely.
 */
function ctools_stylizer_get_settings_name($settings) {
  if ($settings['name'] != '_temporary') {
    return $settings['name'];
  }

  return $settings['name'] . '-' . md5(serialize($settings['palette']));
}

/**
 * Get the path where images will be stored for a given style plugin and settings.
 *
 * This function will make sure the path exists.
 */
function ctools_stylizer_get_image_path($plugin, $settings, $check = TRUE) {
  $path = 'public://ctools/style/' . $settings['name'] . '/' . md5(serialize($settings['palette']));
  if (!file_prepare_directory($path, FILE_CREATE_DIRECTORY | FILE_MODIFY_PERMISSIONS)) {
    drupal_set_message(t('Unable to create CTools styles cache directory @path. Check the permissions on your files directory.', array('@path' => $path)), 'error');
    return;
  }

  return $path;
}

/**
 * Get the id used to cache CSS for a given style plugin and settings.
 */
function ctools_stylizer_get_css_id($plugin, $settings) {
  return 'ctools-stylizer:' . $settings['name'] . ':' . md5(serialize($settings['palette']));
}

/**
 * Get the class to use for a stylizer plugin.
 */
function ctools_stylizer_get_css_class($plugin, $settings) {
  ctools_include('cleanstring');
  return ctools_cleanstring($plugin['name'] . '-' . ctools_stylizer_get_settings_name($settings));
}

class ctools_stylizer_image_processor {
  var $workspace = NULL;
  var $name = NULL;

  var $workspaces = array();

  var $message_log = array();
  var $error_log = array();

  function execute($path, $plugin, $settings) {
    $this->path = $path;
    $this->plugin = $plugin;
    $this->settings = $settings;
    $this->palette = $settings['palette'];

    if (is_string($plugin['actions']) && function_exists($plugin['actions'])) {
      $actions = $plugin['actions']($plugin, $settings);
    }
    else if (is_array($plugin['actions'])) {
      $actions = $plugin['actions'];
    }

    if (!empty($actions) && is_array($actions)) {
      foreach ($plugin['actions'] as $action) {
        $command = 'command_' . array_shift($action);
        if (method_exists($this, $command)) {
          call_user_func_array(array($this, $command), $action);
        }
      }
    }

    // Clean up buffers.
    foreach ($this->workspaces as $name => $workspace) {
      imagedestroy($this->workspaces[$name]);
    }
  }

  function log($message, $type = 'normal') {
    $this->message_log[] = $message;
    if ($type == 'error') {
      $this->error_log[] = $message;
    }
  }

  function set_current_workspace($workspace) {
    $this->log("Set current workspace: $workspace");
    $this->workspace = &$this->workspaces[$workspace];
    $this->name = $workspace;
  }

  /**
   * Create a new workspace.
   */
  function command_new($name, $width, $height) {
    $this->log("New workspace: $name ($width x $height)");
    // Clean up if there was already a workspace there.
    if (isset($this->workspaces[$name])) {
      imagedestroy($this->workspaces[$name]);
    }

    $this->workspaces[$name] = imagecreatetruecolor($width, $height);
    $this->set_current_workspace($name);

    // Make sure the new workspace has a transparent color.

    // Turn off transparency blending (temporarily)
    imagealphablending($this->workspace, FALSE);

    // Create a new transparent color for image
    $color = imagecolorallocatealpha($this->workspace, 0, 0, 0, 127);

    // Completely fill the background of the new image with allocated color.
    imagefill($this->workspace, 0, 0, $color);

    // Restore transparency blending
    imagesavealpha($this->workspace, TRUE);

  }

  /**
   * Create a new workspace a file.
   *
   * This will make the new workspace the current workspace.
   */
  function command_load($name, $file) {
    $this->log("New workspace: $name (from $file)");
    if (!file_exists($file)) {
      // Try it relative to the plugin
      $file = $this->plugin['path'] . '/' . $file;
      if (!file_exists($file)) {
        $this->log("Unable to open $file");
        return;
      }
    }

    // Clean up if there was already a workspace there.
    if (isset($this->workspaces[$name])) {
      imagedestroy($this->workspaces[$name]);
    }

    $this->workspaces[$name] = imagecreatefrompng($file);
    $this->set_current_workspace($name);
  }

  /**
   * Create a new workspace using the properties of an existing workspace
   */
  function command_new_from($name, $workspace) {
    $this->log("New workspace: $name from existing $workspace");
    if (empty($this->workspaces[$workspace])) {
      $this->log("Workspace $name does not exist.", 'error');
      return;
    }

    // Clean up if there was already a workspace there.
    if (isset($this->workspaces[$name])) {
      imagedestroy($this->workspaces[$name]);
    }

    $this->workspaces[$name] = $this->new_image($this->workspace[$workspace]);
    $this->set_current_workspace($name);
  }

  /**
   * Set the current workspace.
   */
  function command_workspace($name) {
    $this->log("Set workspace: $name");
    if (empty($this->workspaces[$name])) {
      $this->log("Workspace $name does not exist.", 'error');
      return;
    }
    $this->set_current_workspace($name);
  }

  /**
   * Copy the contents of one workspace into the current workspace.
   */
  function command_merge_from($workspace, $x = 0, $y = 0) {
    $this->log("Merge from: $workspace ($x, $y)");
    if (empty($this->workspaces[$workspace])) {
      $this->log("Workspace $name does not exist.", 'error');
      return;
    }

    $this->merge($this->workspaces[$workspace], $this->workspace, $x, $y);
  }

  function command_merge_to($workspace, $x = 0, $y = 0) {
    $this->log("Merge to: $workspace ($x, $y)");
    if (empty($this->workspaces[$workspace])) {
      $this->log("Workspace $name does not exist.", 'error');
      return;
    }

    $this->merge($this->workspace, $this->workspaces[$workspace], $x, $y);
    $this->set_current_workspace($workspace);
  }

  /**
   * Blend an image into the current workspace.
   */
  function command_merge_from_file($file, $x = 0, $y = 0) {
    $this->log("Merge from file: $file ($x, $y)");
    if (!file_exists($file)) {
      // Try it relative to the plugin
      $file = $this->plugin['path'] . '/' . $file;
      if (!file_exists($file)) {
        $this->log("Unable to open $file");
        return;
      }
    }

    $source = imagecreatefrompng($file);

    $this->merge($source, $this->workspace, $x, $y);

    imagedestroy($source);
  }

  function command_fill($color, $x, $y, $width, $height) {
    $this->log("Fill: $color ($x, $y, $width, $height)");
    imagefilledrectangle($this->workspace, $x, $y, $x + $width, $y + $height, _color_gd($this->workspace, $this->palette[$color]));
  }

  function command_gradient($from, $to, $x, $y, $width, $height, $direction = 'down') {
    $this->log("Gradient: $from to $to ($x, $y, $width, $height) $direction");

    if ($direction == 'down') {
      for ($i = 0; $i < $height; ++$i) {
        $color = _color_blend($this->workspace, $this->palette[$from], $this->palette[$to], $i / ($height - 1));
        imagefilledrectangle($this->workspace, $x, $y + $i, $x + $width, $y + $i + 1, $color);
      }
    }
    else {
      for ($i = 0; $i < $width; ++$i) {
        $color = _color_blend($this->workspace, $this->palette[$from], $this->palette[$to], $i / ($width - 1));
        imagefilledrectangle($this->workspace, $x + $i, $y, $x + $i + 1, $y + $height, $color);
      }
    }
  }

  /**
   * Colorize the current workspace with the given location.
   *
   * This uses simple color blending to colorize the image.
   *
   * @todo it is possible that this colorize could allow different methods for
   * determining how to blend colors?
   */
  function command_colorize($color, $x = NULL, $y = NULL, $width = NULL, $height = NULL) {
    if (!isset($x)) {
      $whole_image = TRUE;
      $x = $y = 0;
      $width = imagesx($this->workspace);
      $height = imagesy($this->workspace);
    }
    $this->log("Colorize: $color ($x, $y, $width, $height)");

    $c = _color_unpack($this->palette[$color]);

    imagealphablending($this->workspace, FALSE);
    imagesavealpha($this->workspace, TRUE);

    // If PHP 5 use the nice imagefilter which is faster.
    if (!empty($whole_image) && version_compare(phpversion(), '5.2.5', '>=') && function_exists('imagefilter')) {
      imagefilter($this->workspace, IMG_FILTER_COLORIZE, $c[0], $c[1], $c[2]);
    }
    else {
      // Otherwise we can do it the brute force way.
      for ($j = 0; $j < $height; $j++) {
        for ($i = 0; $i < $width; $i++) {
          $current = imagecolorsforindex($this->workspace, imagecolorat($this->workspace, $i, $j));
          $new_index = imagecolorallocatealpha($this->workspace, $c[0], $c[1], $c[2], $current['alpha']);
          imagesetpixel($this->workspace, $i, $j, $new_index);
        }
      }
    }
  }

  /**
   * Colorize the current workspace with the given location.
   *
   * This uses a color replacement algorithm that retains luminosity but
   * turns replaces all color with the specified color.
   */
  function command_hue($color, $x = NULL, $y = NULL, $width = NULL, $height = NULL) {
    if (!isset($x)) {
      $whole_image = TRUE;
      $x = $y = 0;
      $width = imagesx($this->workspace);
      $height = imagesy($this->workspace);
    }
    $this->log("Hue: $color ($x, $y, $width, $height)");

    list($red, $green, $blue) = _color_unpack($this->palette[$color]);

    // We will create a monochromatic palette based on the input color
    // which will go from black to white.

    // Input color luminosity: this is equivalent to the position of the
    // input color in the monochromatic palette
    $luminosity_input = round(255 * ($red + $green + $blue) / 765); // 765 = 255 * 3

    // We fill the palette entry with the input color at itscorresponding position
    $palette[$luminosity_input]['red'] = $red;
    $palette[$luminosity_input]['green'] = $green;
    $palette[$luminosity_input]['blue'] = $blue;

    // Now we complete the palette, first we'll do it tothe black, and then to
    // the white.

    // From input to black
    $steps_to_black = $luminosity_input;

    // The step size for each component
    if ($steps_to_black) {
      $step_size_red = $red / $steps_to_black;
      $step_size_green = $green / $steps_to_black;
      $step_size_blue = $blue / $steps_to_black;

      for ($i = $steps_to_black; $i >= 0; $i--) {
        $palette[$steps_to_black-$i]['red'] = $red - round($step_size_red * $i);
        $palette[$steps_to_black-$i]['green'] = $green - round($step_size_green * $i);
        $palette[$steps_to_black-$i]['blue'] = $blue - round($step_size_blue * $i);
      }
    }

    // From input to white
    $steps_to_white = 255 - $luminosity_input;

    if ($steps_to_white) {
      $step_size_red = (255 - $red) / $steps_to_white;
      $step_size_green = (255 - $green) / $steps_to_white;
      $step_size_blue = (255 - $blue) / $steps_to_white;
    }
    else {
      $step_size_red=$step_size_green=$step_size_blue=0;
    }

    // The step size for each component
    for($i = ($luminosity_input + 1); $i <= 255; $i++) {
      $palette[$i]['red'] = $red + round($step_size_red * ($i - $luminosity_input));
      $palette[$i]['green'] = $green + round($step_size_green * ($i - $luminosity_input));
      $palette[$i]['blue']= $blue + round($step_size_blue * ($i - $luminosity_input));
    }

    // Go over the specified area of the image and update the colors.
    for ($j = $x; $j < $height; $j++) {
      for ($i = $y; $i < $width; $i++) {
        $color = imagecolorsforindex($this->workspace, imagecolorat($this->workspace, $i, $j));
        $luminosity = round(255 * ($color['red'] + $color['green'] + $color['blue']) / 765);
        $new_color = imagecolorallocatealpha($this->workspace, $palette[$luminosity]['red'], $palette[$luminosity]['green'], $palette[$luminosity]['blue'], $color['alpha']);
        imagesetpixel($this->workspace, $i, $j, $new_color);
      }
    }
  }

  /**
   * Take a slice out of the current workspace and save it as an image.
   */
  function command_slice($file, $x = NULL, $y = NULL, $width = NULL, $height = NULL) {
    if (!isset($x)) {
      $x = $y = 0;
      $width = imagesx($this->workspace);
      $height = imagesy($this->workspace);
    }

    $this->log("Slice: $file ($x, $y, $width, $height)");

    $base = basename($file);
    $image = $this->path . '/' . $base;

    $slice = $this->new_image($this->workspace, $width, $height);
    imagecopy($slice, $this->workspace, 0, 0, $x, $y, $width, $height);

    // Make sure alphas are saved:
    imagealphablending($slice, FALSE);
    imagesavealpha($slice, TRUE);

    // Save image.
    $temp_name = drupal_tempnam('temporary://', 'file');

    imagepng($slice, drupal_realpath($temp_name));
    file_unmanaged_move($temp_name, $image);
    imagedestroy($slice);

    // Set standard file permissions for webserver-generated files
    @chmod(realpath($image), 0664);

    $this->paths[$file] = $image;
  }

  /**
   * Prepare a new image for being copied or worked on, preserving transparency.
   */
  function &new_image(&$source, $width = NULL, $height = NULL) {
    if (!isset($width)) {
      $width = imagesx($source);
    }

    if (!isset($height)) {
      $height = imagesy($source);
    }

    $target = imagecreatetruecolor($width, $height);
    imagealphablending($target, FALSE);
      imagesavealpha($target, TRUE);

    $transparency_index = imagecolortransparent($source);

    // If we have a specific transparent color
    if ($transparency_index >= 0) {
      // Get the original image's transparent color's RGB values
      $transparent_color = imagecolorsforindex($source, $transparency_index);

      // Allocate the same color in the new image resource
      $transparency_index = imagecolorallocate($target, $transparent_color['red'], $transparent_color['green'], $transparent_color['blue']);

      // Completely fill the background of the new image with allocated color.
      imagefill($target, 0, 0, $transparency_index);

      // Set the background color for new image to transparent
      imagecolortransparent($target, $transparency_index);
    }
    // Always make a transparent background color for PNGs that don't have one allocated already
    else {
      // Create a new transparent color for image
      $color = imagecolorallocatealpha($target, 0, 0, 0, 127);

      // Completely fill the background of the new image with allocated color.
      imagefill($target, 0, 0, $color);
    }

    return $target;
  }

  /**
   * Merge two images together, preserving alpha transparency.
   */
  function merge(&$from, &$to, $x, $y) {
    // Blend over template.
    $width = imagesx($from);
    $height = imagesy($from);

    // Re-enable alpha blending to make sure transparency merges.
    imagealphablending($to, TRUE);
    imagecopy($to, $from, $x, $y, 0, 0, $width, $height);
    imagealphablending($to, FALSE);
  }
}

/**
 * Get the cached changes to a given task handler.
 */
function ctools_stylizer_get_settings_cache($name) {
  ctools_include('object-cache');
  return ctools_object_cache_get('ctools_stylizer_settings', $name);
}

/**
 * Store changes to a task handler in the object cache.
 */
function ctools_stylizer_set_settings_cache($name, $settings) {
  ctools_include('object-cache');
  ctools_object_cache_set('ctools_stylizer_settings', $name, $settings);
}

/**
 * Remove an item from the object cache.
 */
function ctools_stylizer_clear_settings_cache($name) {
  ctools_include('object-cache');
  ctools_object_cache_clear('ctools_stylizer_settings', $name);
}

/**
 * Add a new style of the specified type.
 */
function ctools_stylizer_edit_style(&$info, $js, $step = NULL) {
  $name = '::new';
  $form_info = array(
    'id' => 'ctools_stylizer_edit_style',
    'path' => $info['path'],
    'show trail' => TRUE,
    'show back' => TRUE,
    'show return' => FALSE,
    'next callback' => 'ctools_stylizer_edit_style_next',
    'finish callback' => 'ctools_stylizer_edit_style_finish',
    'return callback' => 'ctools_stylizer_edit_style_finish',
    'cancel callback' => 'ctools_stylizer_edit_style_cancel',
    'forms' => array(
      'choose' => array(
        'form id' => 'ctools_stylizer_edit_style_form_choose',
      ),
    ),
  );

  if (empty($info['settings'])) {
    $form_info['order'] = array(
      'choose' => t('Select base style'),
    );
    if (empty($step)) {
      $step = 'choose';
    }

    if ($step != 'choose') {
      $cache = ctools_stylizer_get_settings_cache($name);
      if (!$cache) {
        $output = t('Missing settings cache.');
        if ($js) {
          return ctools_modal_form_render($form_state, $output);
        }
        else {
          return $output;
        }
      }

      if (!empty($cache['owner settings'])) {
        $info['owner settings'] = $cache['owner settings'];
      }
      $settings = $cache['settings'];
    }
    else {
      $settings = array(
        'name' => '_temporary',
        'style_base' => NULL,
        'palette' => array(),
      );
      ctools_stylizer_clear_settings_cache($name);
    }
    $op = 'add';
  }
  else {
    $cache = ctools_stylizer_get_settings_cache($info['settings']['name']);

    if (!empty($cache)) {
      if (!empty($cache['owner settings'])) {
        $info['owner settings'] = $cache['owner settings'];
      }
      $settings = $cache['settings'];
    }
    else {
      $settings = $info['settings'];
    }
    $op = 'edit';
  }

  if (!empty($info['op'])) {
    // Allow this to override. Necessary to allow cloning properly.
    $op = $info['op'];
  }

  $plugin = NULL;
  if (!empty($settings['style_base'])) {
    $plugin = ctools_get_style_base($settings['style_base']);
    $info['type'] = $plugin['type'];
    ctools_stylizer_add_plugin_forms($form_info, $plugin, $op);
  }
  else {
    // This is here so the 'finish' button does not show up, and because
    // we don't have the selected style we don't know what the next form(s)
    // will be.
    $form_info['order']['next'] = t('Configure style');
  }

  if (count($form_info['order']) < 2 || $step == 'choose') {
    $form_info['show trail'] = FALSE;
  }

  $form_state = array(
    'module' => $info['module'],
    'type' => $info['type'],
    'owner info' => &$info,
    'base_style_plugin' => $plugin,
    'name' => $name,
    'step' => $step,
    'settings' => $settings,
    'ajax' => $js,
    'op' => $op,
  );

  if (!empty($info['modal'])) {
    $form_state['modal'] = TRUE;
    $form_state['title'] = $info['modal'];
    $form_state['modal return'] = TRUE;
  }

  ctools_include('wizard');
  $output = ctools_wizard_multistep_form($form_info, $step, $form_state);

  if (!empty($form_state['complete'])) {
    $info['complete'] = TRUE;
    $info['settings'] = $form_state['settings'];
  }

  if ($js && !$output && !empty($form_state['clicked_button']['#next'])) {
    // We have to do a separate redirect here because the formula that adds
    // stuff to the wizard after being chosen hasn't happened. The wizard
    // tried to go to the next step which did not exist.
    return ctools_stylizer_edit_style($info, $js, $form_state['clicked_button']['#next']);
  }

  if ($js) {
    return ctools_modal_form_render($form_state, $output);
  }
  else {
    return $output;
  }
}

/**
 * Add wizard forms specific to a style base plugin.
 *
 * The plugin can store forms either as a simple 'edit form'
 * => 'form callback' or if it needs the more complicated wizard
 * functionality, it can set 'forms' and 'order' with values suitable
 * for the wizard $form_info array.
 *
 * @param &$form_info
 *   The form info to modify.
 * @param $plugin
 *   The plugin to use.
 * @param $op
 *   Either 'add' or 'edit' so we can get the right forms.
 */
function ctools_stylizer_add_plugin_forms(&$form_info, $plugin, $op) {
  if (empty($plugin['forms'])) {
    if ($op == 'add' && isset($plugin['add form'])) {
      $id = $plugin['add form'];
    }
    else if (isset($plugin['edit form'])) {
      $id = $plugin['edit form'];
    }
    else {
      $id = 'ctools_stylizer_edit_style_form_default';
    }

    $form_info['forms']['settings'] = array(
      'form id' => $id,
    );
    $form_info['order']['settings'] = t('Settings');
  }
  else {
    $form_info['forms'] += $plugin['forms'];
    $form_info['order'] += $plugin['order'];
  }
}

/**
 * Callback generated when the add style process is finished.
 */
function ctools_stylizer_edit_style_finish(&$form_state) {
  $form_state['complete'] = TRUE;
  ctools_stylizer_clear_settings_cache($form_state['name']);

  if (isset($form_state['settings']['old_settings'])) {
    unset($form_state['settings']['old_settings']);
  }
}

/**
 * Callback generated when the 'next' button is clicked.
 */
function ctools_stylizer_edit_style_next(&$form_state) {
  $form_state['form_info']['path'] = str_replace('%name', $form_state['name'], $form_state['form_info']['path']);
  $form_state['redirect'] = ctools_wizard_get_path($form_state['form_info'], $form_state['clicked_button']['#next']);

  // Update the cache with changes.
  $cache = array('settings' => $form_state['settings']);
  if (!empty($form_state['owner info']['owner settings'])) {
    $cache['owner settings'] = $form_state['owner info']['owner settings'];
  }
  ctools_stylizer_set_settings_cache($form_state['name'], $cache);
}

/**
 * Callback generated when the 'cancel' button is clicked.
 *
 * We might have some temporary data lying around. We must remove it.
 */
function ctools_stylizer_edit_style_cancel(&$form_state) {
  if (!empty($form_state['name'])) {
    ctools_stylizer_clear_settings_cache($form_state['name']);
  }
}

/**
 * Choose which plugin to use to create a new style.
 */
function ctools_stylizer_edit_style_form_choose($form, &$form_state) {
  $plugins = ctools_get_style_bases();
  $options = array();

  $categories = array();
  foreach ($plugins as $name => $plugin) {
    if ($form_state['module'] == $plugin['module'] && $form_state['type'] == $plugin['type']) {
      $categories[$plugin['category']] = $plugin['category'];
      $unsorted_options[$plugin['category']][$name] = ctools_stylizer_print_style_icon($plugin, TRUE);
    }
  }

  asort($categories);

  foreach ($categories as $category) {
    $options[$category] = $unsorted_options[$category];
  }

  $form['style_base'] = array(
    '#prefix' => '<div class="ctools-style-icons clearfix">',
    '#suffix' => '</div>',
  );

  ctools_include('cleanstring');
  foreach ($options as $category => $radios) {
    $cat = ctools_cleanstring($category);
    $form['style_base'][$cat] = array(
      '#prefix' => '<div class="ctools-style-category clearfix"><label>' . $category . '</label>',
      '#suffix' => '</div>',
    );

    foreach ($radios as $key => $choice) {
      // Generate the parents as the autogenerator does, so we will have a
      // unique id for each radio button.
      $form['style_base'][$cat][$key] = array(
        '#type' => 'radio',
        '#title' => $choice,
        '#parents' => array('style_base'),
        '#id' => drupal_clean_css_identifier('edit-style-base-' . $key),
        '#return_value' => check_plain($key),
      );
    }
  }

  return $form;
}

function ctools_stylizer_edit_style_form_choose_submit($form, &$form_state) {
  $form_state['settings']['style_base'] = $form_state['values']['style_base'];

  // The 'next' form will show up as 'next' but that's not accurate now that
  // we have a style. Figure out what next really is and update.
  $plugin = ctools_get_style_base($form_state['settings']['style_base']);
  if (empty($plugin['forms'])) {
    $form_state['clicked_button']['#next'] = 'settings';
  }
  else {
    $forms = array_keys($form_info['forms']);
    $form_state['clicked_button']['#next'] = array_shift($forms);
  }

  // Fill in the defaults for the settings.
  if (!empty($plugin['defaults'])) {
    // @todo allow a callback
    $form_state['settings'] += $plugin['defaults'];
  }

  return $form;
}

/**
 * The default stylizer style editing form.
 *
 * Even when not using this, styles should call through to this form in
 * their own edit forms.
 */
function ctools_stylizer_edit_style_form_default($form, &$form_state) {
  ctools_add_js('stylizer');
  ctools_add_css('stylizer');
  drupal_add_js('misc/farbtastic/farbtastic.js');
  drupal_add_css('misc/farbtastic/farbtastic.css');

  $plugin = &$form_state['base_style_plugin'];
  $settings = &$form_state['settings'];

  $form['top box'] = array(
    '#prefix' => '<div id="ctools-stylizer-top-box" class="clearfix">',
    '#suffix' => '</div>',
  );
  $form['top box']['left'] = array(
    '#prefix' => '<div id="ctools-stylizer-left-box">',
    '#suffix' => '</div>',
  );
  $form['top box']['preview'] = array(
    // We have a copy of the $form_state on $form because form theme functions
    // do not get $form_state.
    '#theme' => 'ctools_stylizer_preview_form',
    '#form_state' => &$form_state,
  );

  $form['top box']['preview']['submit'] = array(
    '#type' => 'submit',
    '#value' => t('Preview'),
  );

  if (!empty($plugin['palette'])) {
    $form['top box']['color'] = array(
      '#type' => 'fieldset',
      '#title' => t('Color scheme'),
      '#attributes' => array('id' => 'ctools_stylizer_color_scheme_form', 'class' => array('ctools-stylizer-color-edit')),
      '#theme' => 'ctools_stylizer_color_scheme_form',
    );

    $form['top box']['color']['palette']['#tree'] = true;

    foreach ($plugin['palette'] as $key => $color) {
      if (empty($settings['palette'][$key])) {
        $settings['palette'][$key] = $color['default_value'];
      }

      $form['top box']['color']['palette'][$key] = array(
        '#type' => 'textfield',
        '#title' => $color['label'],
        '#default_value' => $settings['palette'][$key],
        '#size' => 8,
      );
    }
  }

  if (!empty($plugin['settings form']) && function_exists($plugin['settings form'])) {
    $plugin['settings form']($form, $form_state);
  }

  if (!empty($form_state['owner info']['owner form']) && function_exists($form_state['owner info']['owner form'])) {
    $form_state['owner info']['owner form']($form, $form_state);
  }

  return $form;
}

/**
 * Theme the stylizer color scheme form.
 */
function theme_ctools_stylizer_color_scheme_form($vars) {
  $form = &$vars['form'];
  $output = '';

  // Wrapper
  $output .= '<div class="color-form clearfix">';

  // Color schemes
//  $output .= drupal_render($form['scheme']);

  // Palette
  $output .= '<div id="palette" class="clearfix">';
  foreach (element_children($form['palette']) as $name) {
    $output .= render($form['palette'][$name]);
  }
  $output .= '</div>'; // palette

  $output .= '</div>'; // color form

  return $output;
}

/**
 * Theme the stylizer preview form.
 */
function theme_ctools_stylizer_preview_form($vars) {
  $form = &$vars['form'];

  $plugin = $form['#form_state']['base_style_plugin'];
  $settings = $form['#form_state']['settings'];

  if (!empty($form['#form_state']['settings']['old_settings'])) {
    ctools_stylizer_cleanup_style($plugin, $form['#form_state']['settings']['old_settings']);
  }
  $preview = '';
  if (!empty($plugin['preview'])) {
    $preview = $plugin['preview'];
  }
  else {
    $base_types = ctools_get_style_base_types();
    if (!empty($base_types[$plugin['module']][$plugin['type']]['preview'])) {
      $preview = $base_types[$plugin['module']][$plugin['type']]['preview'];
    }
  }

  if (!empty($preview) && function_exists($preview)) {
    $output = '<fieldset id="preview"><legend>' . t('Preview') . '</legend>';
    $output .= $preview($plugin, $settings);
    $output .= drupal_render_children($form);
    $output .= '</fieldset>';

    return $output;
  }
}

function ctools_stylizer_edit_style_form_default_validate($form, &$form_state) {
  if (!empty($form_state['owner info']['owner form validate']) && function_exists($form_state['owner info']['owner form validate'])) {
    $form_state['owner info']['owner form validate']($form, $form_state);
  }

  if (!empty($form_state['base_style_plugin']['settings form validate']) && function_exists($form_state['base_style_plugin']['settings form validate'])) {
    $form_state['base_style_plugin']['settings form validate']($form, $form_state);
  }
}

function ctools_stylizer_edit_style_form_default_submit($form, &$form_state) {
  // Store old settings for the purposes of cleaning up.
  $form_state['settings']['old_settings'] = $form_state['settings'];
  $form_state['settings']['palette'] = $form_state['values']['palette'];

  if (!empty($form_state['owner info']['owner form submit']) && function_exists($form_state['owner info']['owner form submit'])) {
    $form_state['owner info']['owner form submit']($form, $form_state);
  }

  if (!empty($form_state['base_style_plugin']['settings form submit']) && function_exists($form_state['base_style_plugin']['settings form submit'])) {
    $form_state['base_style_plugin']['settings form submit']($form, $form_state);
  }

  if ($form_state['clicked_button']['#value'] == t('Preview')) {
    $form_state['rerender'] = TRUE;
    // Update the cache with changes.
    if (!empty($form_state['name'])) {
      $cache = array('settings' => $form_state['settings']);
      if (!empty($form_state['owner info']['owner settings'])) {
        $cache['owner settings'] = $form_state['owner info']['owner settings'];
      }
      ctools_stylizer_set_settings_cache($form_state['name'], $cache);
    }
  }
}

// --------------------------------------------------------------------------
// CSS forms and tools that plugins can use.

/**
 * Font selector form
 */
function ctools_stylizer_font_selector_form(&$form, &$form_state, $label, $settings) {
  // Family
  $form['#prefix'] = '<div class="ctools-stylizer-spacing-form clearfix">';
  $form['#type'] = 'fieldset';
  $form['#title'] = $label;
  $form['#suffix'] = '</div>';
  $form['#tree'] = TRUE;

  $form['font'] = array(
    '#title' => t('Font family'),
    '#type' => 'select',
    '#default_value' => isset($settings['font']) ? $settings['font'] : '',
    '#options' => array(
      '' => '',
      'Arial, Helvetica, sans-serif' => t('Arial, Helvetica, sans-serif'),
      'Times New Roman, Times, serif' => t('Times New Roman, Times, serif'),
      'Courier New, Courier, monospace' => t('Courier New, Courier, monospace'),
      'Georgia, Times New Roman, Times, serif' => t('Georgia, Times New Roman, Times, serif'),
      'Verdana, Arial, Helvetica, sans-serif' => t('Verdana, Arial, Helvetica, sans-serif'),
      'Geneva, Arial, Helvetica, sans-serif' => t('Geneva, Arial, Helvetica, sans-serif'),
      'Trebuchet MS, Trebuchet, Verdana, sans-serif' => t('Trebuchet MS, Trebuchet, Verdana, sans-serif'),
    ),
  );

  // size
  $form['size'] = array(
    '#title' => t('Size'),
    '#type' => 'select',
    '#default_value' => isset($settings['size']) ? $settings['size'] : '',
    '#options' => array(
      '' => '',
      'xx-small' => t('XX-Small'),
      'x-small' => t('X-Small'),
      'small' => t('Small'),
      'medium' => t('Medium'),
      'large' => t('Large'),
      'x-large' => t('X-Large'),
      'xx-large' => t('XX-Large'),
    ),
  );

  // letter spacing
  $form['letter_spacing'] = array(
    '#title' => t('Letter spacing'),
    '#type' => 'select',
    '#default_value' => isset($settings['letter_spacing']) ? $settings['letter_spacing'] : '',
    '#options' => array(
      '' => '',
      "-10px" => '10px',
      "-9px" => '9px',
      "-8px" => '8px',
      "-7px" => '7px',
      "-6px" => '6px',
      "-5px" => '5px',
      "-4px" => '4px',
      "-3px" => '3px',
      "-2px" => '2px',
      "-1px" => '1px',
      "0" => '0',
      "1px" => '1px',
      "2px" => '2px',
      "3px" => '3px',
      "4px" => '4px',
      "5px" => '5px',
      "6px" => '6px',
      "7px" => '7px',
      "8px" => '8px',
      "9px" => '9px',
      "10px" => '10px',
      "11px" => '11px',
      "12px" => '12px',
      "13px" => '13px',
      "14px" => '14px',
      "15px" => '15px',
      "16px" => '16px',
      "17px" => '17px',
      "18px" => '18px',
      "19px" => '19px',
      "20px" => '20px',
      "21px" => '21px',
      "22px" => '22px',
      "23px" => '23px',
      "24px" => '24px',
      "25px" => '25px',
      "26px" => '26px',
      "27px" => '27px',
      "28px" => '28px',
      "29px" => '29px',
      "30px" => '30px',
      "31px" => '31px',
      "32px" => '32px',
      "33px" => '33px',
      "34px" => '34px',
      "35px" => '35px',
      "36px" => '36px',
      "37px" => '37px',
      "38px" => '38px',
      "39px" => '39px',
      "40px" => '40px',
      "41px" => '41px',
      "42px" => '42px',
      "43px" => '43px',
      "44px" => '44px',
      "45px" => '45px',
      "46px" => '46px',
      "47px" => '47px',
      "48px" => '48px',
      "49px" => '49px',
      "50px" => '50px',
    ),
  );

  // word space
  $form['word_spacing'] = array(
    '#title' => t('Word spacing'),
    '#type' => 'select',
    '#default_value' => isset($settings['word_spacing']) ? $settings['word_spacing'] : '',
    '#options' => array(
      '' => '',
      "-1em" => '-1em',
      "-0.95em" => '-0.95em',
      "-0.9em" => '-0.9em',
      "-0.85em" => '-0.85em',
      "-0.8em" => '-0.8em',
      "-0.75em" => '-0.75em',
      "-0.7em" => '-0.7em',
      "-0.65em" => '-0.65em',
      "-0.6em" => '-0.6em',
      "-0.55em" => '-0.55em',
      "-0.5em" => '-0.5em',
      "-0.45em" => '-0.45em',
      "-0.4em" => '-0.4em',
      "-0.35em" => '-0.35em',
      "-0.3em" => '-0.3em',
      "-0.25em" => '-0.25em',
      "-0.2em" => '-0.2em',
      "-0.15em" => '-0.15em',
      "-0.1em" => '-0.1em',
      "-0.05em" => '-0.05em',
      "normal" => 'normal',
      "0.05em" => '0.05em',
      "0.1em" => '0.1em',
      "0.15em" => '0.15em',
      "0.2em" => '0.2em',
      "0.25em" => '0.25em',
      "0.3em" => '0.3em',
      "0.35em" => '0.35em',
      "0.4em" => '0.4em',
      "0.45em" => '0.45em',
      "0.5em" => '0.5em',
      "0.55em" => '0.55em',
      "0.6em" => '0.6em',
      "0.65em" => '0.65em',
      "0.7em" => '0.7em',
      "0.75em" => '0.75em',
      "0.8em" => '0.8em',
      "0.85em" => '0.85em',
      "0.9em" => '0.9em',
      "0.95em" => '0.95em',
      "1em" => '1em',
    ),
  );

  // decoration
  $form['decoration'] = array(
    '#title' => t('Decoration'),
    '#type' => 'select',
    '#default_value' => isset($settings['decoration']) ? $settings['decoration'] : '',
    '#options' => array(
      '' => '',
      'none' => t('None'),
      'underline' => t('Underline'),
      'overline' => t('Overline'),
      'line-through' => t('Line-through'),
    ),
  );

  // weight
  $form['weight'] = array(
    '#title' => t('Weight'),
    '#type' => 'select',
    '#default_value' => isset($settings['weight']) ? $settings['weight'] : '',
    '#options' => array(
      '' => '',
      'normal' => t('Normal'),
      'bold' => t('Bold'),
      'bolder' => t('Bolder'),
      'lighter' => t('Lighter'),
    ),
  );

  // style
  $form['style'] = array(
    '#title' => t('Style'),
    '#type' => 'select',
    '#default_value' => isset($settings['style']) ? $settings['style'] : '',
    '#options' => array(
      '' => '',
      'normal' => t('Normal'),
      'italic' => t('Italic'),
      'oblique' => t('Oblique'),
    ),
  );

  // variant
  $form['variant'] = array(
    '#title' => t('Variant'),
    '#type' => 'select',
    '#default_value' => isset($settings['variant']) ? $settings['variant'] : '',
    '#options' => array(
      '' => '',
      'normal' => t('Normal'),
      'small-caps' => t('Small-caps'),
    ),
  );

  // case
  $form['case'] = array(
    '#title' => t('Case'),
    '#type' => 'select',
    '#default_value' => isset($settings['case']) ? $settings['case'] : '',
    '#options' => array(
      '' => '',
      'capitalize' => t('Capitalize'),
      'uppercase' => t('Uppercase'),
      'lowercase' => t('Lowercase'),
      'none' => t('None'),
    ),
  );

  // alignment
  $form['alignment'] = array(
    '#title' => t('Align'),
    '#type' => 'select',
    '#default_value' => isset($settings['alignment']) ? $settings['alignment'] : '',
    '#options' => array(
      '' => '',
      'justify' => t('Justify'),
      'left' => t('Left'),
      'right' => t('Right'),
      'center' => t('Center'),
    ),
  );
}

/**
 * Copy font selector information into the settings
 */
function ctools_stylizer_font_selector_form_submit(&$form, &$form_state, &$values, &$settings) {
  $settings = $values;
}

function ctools_stylizer_font_apply_style(&$stylesheet, $selector, $settings) {
  $css = '';
  if (isset($settings['font']) && $settings['font'] !== '') {
    $css .= '  font-family: ' . $settings['font'] . ";\n";
  }

  if (isset($settings['size']) && $settings['size'] !== '') {
    $css .= '  font-size: ' . $settings['size'] . ";\n";
  }

  if (isset($settings['weight']) && $settings['weight'] !== '') {
    $css .= '  font-weight: ' . $settings['weight'] . ";\n";
  }

  if (isset($settings['style']) && $settings['style'] !== '') {
    $css .= '  font-style: ' . $settings['style'] . ";\n";
  }

  if (isset($settings['variant']) && $settings['variant'] !== '') {
    $css .= '  font-variant: ' . $settings['variant'] . ";\n";
  }

  if (isset($settings['case']) && $settings['case'] !== '') {
    $css .= '  text-transform: ' . $settings['case'] . ";\n";
  }

  if (isset($settings['decoration']) && $settings['decoration'] !== '') {
    $css .= '  text-decoration: ' . $settings['decoration'] . ";\n";
  }

  if (isset($settings['alignment']) && $settings['alignment'] !== '') {
    $css .= '  text-align: ' . $settings['alignment'] . ";\n";
  }

  if (isset($settings['letter_spacing']) && $settings['letter_spacing'] !== '') {
    $css .= '  letter-spacing: ' . $settings['letter_spacing'] . ";\n";
  }

  if (isset($settings['word_spacing']) && $settings['word_spacing'] !== '') {
    $css .= '  word-spacing: ' . $settings['word_spacing'] . ";\n";
  }

  if ($css) {
    $stylesheet .= $selector . " {\n" . $css . "}\n";
  }
}

/**
 * Border selector form
 */
function ctools_stylizer_border_selector_form(&$form, &$form_state, $label, $settings) {
  // Family
  $form['#prefix'] = '<div class="ctools-stylizer-spacing-form clearfix">';
  $form['#type'] = 'fieldset';
  $form['#title'] = $label;
  $form['#suffix'] = '</div>';
  $form['#tree'] = TRUE;

  $form['thickness'] = array(
    '#title' => t('Thickness'),
    '#type' => 'select',
    '#default_value' => isset($settings['thickness']) ? $settings['thickness'] : '',
    '#options' => array(
      '' => '',
      "none" => t('None'),
      "1px" => '1px',
      "2px" => '2px',
      "3px" => '3px',
      "4px" => '4px',
      "5px" => '5px',
    ),
  );

  $form['style'] = array(
    '#title' => t('style'),
    '#type' => 'select',
    '#default_value' => isset($settings['style']) ? $settings['style'] : '',
    '#options' => array(
      '' => '',
      'solid' => t('Solid'),
      'dotted' => t('Dotted'),
      'dashed' => t('Dashed'),
      'double' => t('Double'),
      'groove' => t('Groove'),
      'ridge' => t('Ridge'),
      'inset' => t('Inset'),
      'outset' => t('Outset'),
    ),
  );
}

/**
 * Copy border selector information into the settings
 */
function ctools_stylizer_border_selector_form_submit(&$form, &$form_state, &$values, &$settings) {
  $settings = $values;
}

function ctools_stylizer_border_apply_style(&$stylesheet, $selector, $settings, $color, $which = NULL) {
  $border = 'border';
  if ($which) {
    $border .= '-' . $which;
  }

  $css = '';
  if (isset($settings['thickness']) && $settings['thickness'] !== '') {
    if ($settings['thickness'] == 'none') {
      $css .= '  ' . $border . ': none';
    }
    else {
      $css .= '  ' . $border . '-width: ' . $settings['thickness'] . ";\n";

      if (isset($settings['style']) && $settings['style'] !== '') {
        $css .= '  ' . $border . '-style: ' . $settings['style'] . ";\n";
      }

      $css .= '  ' . $border . '-color: ' . $color . ";\n";
    }
  }

  if ($css) {
    $stylesheet .= $selector . " {\n" . $css . "}\n";
  }

}

/**
 * padding selector form
 */
function ctools_stylizer_padding_selector_form(&$form, &$form_state, $label, $settings) {
  // Family
  $form['#prefix'] = '<div class="ctools-stylizer-spacing-form clearfix">';
  $form['#type'] = 'fieldset';
  $form['#title'] = $label;
  $form['#suffix'] = '</div>';
  $form['#tree'] = TRUE;

  $options = array(
    '' => '',
    "0.05em" => '0.05em',
    "0.1em" => '0.1em',
    "0.15em" => '0.15em',
    "0.2em" => '0.2em',
    "0.25em" => '0.25em',
    "0.3em" => '0.3em',
    "0.35em" => '0.35em',
    "0.4em" => '0.4em',
    "0.45em" => '0.45em',
    "0.5em" => '0.5em',
    "0.55em" => '0.55em',
    "0.6em" => '0.6em',
    "0.65em" => '0.65em',
    "0.7em" => '0.7em',
    "0.75em" => '0.75em',
    "0.8em" => '0.8em',
    "0.85em" => '0.85em',
    "0.9em" => '0.9em',
    "0.95em" => '0.95em',
    "1.0em" => '1.0em',
    "1.05em" => '1.05em',
    "1.1em" => '1.1em',
    "1.15em" => '1.15em',
    "1.2em" => '1.2em',
    "1.25em" => '1.25em',
    "1.3em" => '1.3em',
    "1.35em" => '1.35em',
    "1.4em" => '1.4em',
    "1.45em" => '1.45em',
    "1.5em" => '1.5em',
    "1.55em" => '1.55em',
    "1.6em" => '1.6em',
    "1.65em" => '1.65em',
    "1.7em" => '1.7em',
    "1.75em" => '1.75em',
    "1.8em" => '1.8em',
    "1.85em" => '1.85em',
    "1.9em" => '1.9em',
    "1.95em" => '1.95em',
    "2.0em" => '2.0em',
    "2.05em" => '2.05em',
    "2.1em" => '2.1em',
    "2.15em" => '2.15em',
    "2.2em" => '2.2em',
    "2.25em" => '2.25em',
    "2.3em" => '2.3em',
    "2.35em" => '2.35em',
    "2.4em" => '2.4em',
    "2.45em" => '2.45em',
    "2.5em" => '2.5em',
    "2.55em" => '2.55em',
    "2.6em" => '2.6em',
    "2.65em" => '2.65em',
    "2.7em" => '2.7em',
    "2.75em" => '2.75em',
    "2.8em" => '2.8em',
    "2.85em" => '2.85em',
    "2.9em" => '2.9em',
    "2.95em" => '2.95em',
    "3.0em" => '3.0em',
    "3.05em" => '3.05em',
    "3.1em" => '3.1em',
    "3.15em" => '3.15em',
    "3.2em" => '3.2em',
    "3.25em" => '3.25em',
    "3.3em" => '3.3em',
    "3.35em" => '3.35em',
    "3.4em" => '3.4em',
    "3.45em" => '3.45em',
    "3.5em" => '3.5em',
    "3.55em" => '3.55em',
    "3.6em" => '3.6em',
    "3.65em" => '3.65em',
    "3.7em" => '3.7em',
    "3.75em" => '3.75em',
    "3.8em" => '3.8em',
    "3.85em" => '3.85em',
    "3.9em" => '3.9em',
    "3.95em" => '3.95em',
  );

  $form['top'] = array(
    '#title' => t('Top'),
    '#type' => 'select',
    '#default_value' => isset($settings['top']) ? $settings['top'] : '',
    '#options' => $options,
  );

  $form['right'] = array(
    '#title' => t('Right'),
    '#type' => 'select',
    '#default_value' => isset($settings['right']) ? $settings['right'] : '',
    '#options' => $options,
  );

  $form['bottom'] = array(
    '#title' => t('Bottom'),
    '#type' => 'select',
    '#default_value' => isset($settings['bottom']) ? $settings['bottom'] : '',
    '#options' => $options,
  );

  $form['left'] = array(
    '#title' => t('Left'),
    '#type' => 'select',
    '#default_value' => isset($settings['left']) ? $settings['left'] : '',
    '#options' => $options,
  );
}

/**
 * Copy padding selector information into the settings
 */
function ctools_stylizer_padding_selector_form_submit(&$form, &$form_state, &$values, &$settings) {
  $settings = $values;
}

function ctools_stylizer_padding_apply_style(&$stylesheet, $selector, $settings) {
  $css = '';

  if (isset($settings['top']) && $settings['top'] !== '') {
    $css .= '  padding-top: ' . $settings['top'] . ";\n";
  }

  if (isset($settings['right']) && $settings['right'] !== '') {
    $css .= '  padding-right: ' . $settings['right'] . ";\n";
  }

  if (isset($settings['bottom']) && $settings['bottom'] !== '') {
    $css .= '  padding-bottom: ' . $settings['bottom'] . ";\n";
  }

  if (isset($settings['left']) && $settings['left'] !== '') {
    $css .= '  padding-left: ' . $settings['left'] . ";\n";
  }

  if ($css) {
    $stylesheet .= $selector . " {\n" . $css . "}\n";
  }

}
