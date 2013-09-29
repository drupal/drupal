<?php

define('DEVEL_GENERATE_IMAGE_MAX', 5);

function image_devel_generate($object, $field, $instance, $bundle) {
  if (function_exists('imagejpeg')) {
    $devel_generate_image_function = variable_get('devel_generate_image_function', '_image_devel_generate');
    if (!function_exists($devel_generate_image_function)) {
      $devel_generate_image_function = '_image_devel_generate';
    }
    if (field_behaviors_widget('multiple values', $instance) == FIELD_BEHAVIOR_CUSTOM) {
      return devel_generate_multiple($devel_generate_image_function, $object, $field, $instance, $bundle);
    }
    else {
      return $devel_generate_image_function($object, $field, $instance, $bundle);
    }
  }
}

function _image_devel_generate($object, $field, $instance, $bundle) {
  $object_field = array();
  static $images = array();

  $min_resolution = empty($instance['settings']['min_resolution']) ? '100x100' : $instance['settings']['min_resolution'];
  $max_resolution = empty($instance['settings']['max_resolution']) ? '600x600' : $instance['settings']['max_resolution'];
  $extensions = array_intersect(explode(' ', $instance['settings']['file_extensions']), array('png', 'jpg'));
  $extension = array_rand(drupal_map_assoc($extensions));

  // Generate a max of 5 different images.
  if (!isset($images[$extension][$min_resolution][$max_resolution]) || count($images[$extension][$min_resolution][$max_resolution]) <= DEVEL_GENERATE_IMAGE_MAX) {
    if ($path = devel_generate_image($extension, $min_resolution, $max_resolution)) {
      $source = entity_create('file', array());
      $source->uri = $path;
      $source->uid = 1; // TODO: randomize? Use case specific.
      $source->filemime = 'image/' . pathinfo($path, PATHINFO_EXTENSION);
      $source->filename = drupal_basename($path);
      $destination_dir = $field['settings']['uri_scheme'] . '://' . $instance['settings']['file_directory'];
      file_prepare_directory($destination_dir, FILE_CREATE_DIRECTORY);
      $destination = $destination_dir . '/' . basename($path);
      $file = file_move($source, $destination, FILE_CREATE_DIRECTORY);
      $images[$extension][$min_resolution][$max_resolution][$file->fid] = $file;
    }
    else {
      return FALSE;
    }
  }
  else {
    // Select one of the images we've already generated for this field.
    $file = new stdClass();
    $file->fid = array_rand($images[$extension][$min_resolution][$max_resolution]);
  }

  $object_field['fid'] = $file->fid;
  $object_field['alt'] = devel_create_greeking(4);
  $object_field['title'] = devel_create_greeking(4);
  return $object_field;
}

/**
 * Private function for creating a random image.
 *
 * This function only works with the GD toolkit. ImageMagick is not supported.
 */
function devel_generate_image($extension = 'png', $min_resolution, $max_resolution) {
  if ($tmp_file = drupal_tempnam('temporary://', 'imagefield_')) {
    $destination = $tmp_file . '.' . $extension;
    file_unmanaged_move($tmp_file, $destination, FILE_CREATE_DIRECTORY);

    $min = explode('x', $min_resolution);
    $max = explode('x', $max_resolution);

    $width = rand((int)$min[0], (int)$max[0]);
    $height = rand((int)$min[1], (int)$max[1]);

    // Make an image split into 4 sections with random colors.
    $im = imagecreate($width, $height);
    for ($n = 0; $n < 4; $n++) {
      $color = imagecolorallocate($im, rand(0, 255), rand(0, 255), rand(0, 255));
      $x = $width/2 * ($n % 2);
      $y = $height/2 * (int) ($n >= 2);
      imagefilledrectangle($im, $x, $y, $x + $width/2, $y + $height/2, $color);
    }

    // Make a perfect circle in the image middle.
    $color = imagecolorallocate($im, rand(0, 255), rand(0, 255), rand(0, 255));
    $smaller_dimension = min($width, $height);
    $smaller_dimension = ($smaller_dimension % 2) ? $smaller_dimension : $smaller_dimension;
    imageellipse($im, $width/2, $height/2, $smaller_dimension, $smaller_dimension, $color);

    $save_function = 'image'. ($extension == 'jpg' ? 'jpeg' : $extension);
    $save_function($im, drupal_realpath($destination));
  }
  return $destination;
}
