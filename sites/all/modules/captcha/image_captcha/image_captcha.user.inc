<?php
// $Id: image_captcha.user.inc,v 1.28 2010/11/29 00:12:19 soxofaan Exp $

/**
 * @file
 * Functions for the generation of the CAPTCHA image.
 *
 * Loosely Based on MyCaptcha by Heine Deelstra
 * (http://heine.familiedeelstra.com/mycaptcha-download)
 */

/**
 * Menu callback function that generates the CAPTCHA image.
 */
function image_captcha_image($captcha_sid=NULL) {
  if (!$captcha_sid) {
    exit();
  }

  // Get solution (the code to show).
  $code = db_query("SELECT solution FROM {captcha_sessions} WHERE csid = :csid",
    array(':csid' => $captcha_sid)
  )->fetchField();

  // Only generate captcha if code exists in the session.
  if ($code !== FALSE) {
    // generate the image
    $image = @_image_captcha_generate_image($code);
    // check of generation was successful
    if (!$image) {
      watchdog('CAPTCHA', 'Generation of image CAPTCHA failed. Check your image CAPTCHA configuration and especially the used font.', array(), WATCHDOG_ERROR);
      exit();
    }
    // Send the image resource as an image file to the client.
    $file_format = variable_get('image_captcha_file_format', IMAGE_CAPTCHA_FILE_FORMAT_JPG);
    if ($file_format == IMAGE_CAPTCHA_FILE_FORMAT_JPG) {
      drupal_add_http_header('Content-Type', 'image/jpeg');
      imagejpeg($image);
    }
    else {
      drupal_add_http_header('Content-Type', 'image/png');
      imagepng($image);
    }
    // Clean up the image resource.
    imagedestroy($image);
  }
  exit();
}


/**
 * Small helper function for parsing a hexadecimal color to a RGB tuple.
 */
function _image_captcha_hex_to_rgb($hex) {
  // handle #RGB format
  if (strlen($hex) == 4) {
    $hex = $hex[1] . $hex[1] . $hex[2] . $hex[2] . $hex[3] . $hex[3];
  }
  $c = hexdec($hex);
  $rgb = array();
  for ($i = 16; $i >= 0; $i -= 8) {
    $rgb[] = ($c >> $i) & 0xFF;
  }
  return $rgb;
}


/**
 * Base function for generating a image CAPTCHA.
 */
function _image_captcha_generate_image($code) {
  // Get font.
  $fonts = _image_captcha_get_enabled_fonts();

  // get other settings
  $font_size = (int) variable_get('image_captcha_font_size', 30);
  list($width, $height) = _image_captcha_image_size($code);

  // create image resource
  $image = imagecreatetruecolor($width, $height);
  if (!$image) {
    return FALSE;
  }

  // Get the background color and paint the background.
  $background_rgb = _image_captcha_hex_to_rgb(variable_get('image_captcha_background_color', '#ffffff'));
  $background_color = imagecolorallocate($image, $background_rgb[0], $background_rgb[1], $background_rgb[2]);
  // Set transparency if needed.
  $file_format = variable_get('image_captcha_file_format', IMAGE_CAPTCHA_FILE_FORMAT_JPG);
  if ($file_format == IMAGE_CAPTCHA_FILE_FORMAT_TRANSPARENT_PNG) {
    imagecolortransparent($image, $background_color);
  }
  imagefilledrectangle($image, 0, 0, $width, $height, $background_color);

  // draw text
  $result = _image_captcha_image_generator_print_string($image, $width, $height, $fonts, $font_size, $code);
  if (!$result) {
    return FALSE;
  }

  // add noise
  $noise_colors = array();
  for ($i = 0; $i < 20; $i++) {
    $noise_colors[] = imagecolorallocate($image, mt_rand(0, 255), mt_rand(0, 255), mt_rand(0, 255));
  }
  // Add additional noise.
  if (variable_get('image_captcha_dot_noise', 0)) {
    _image_captcha_image_generator_add_dots($image, $width, $height, $noise_colors);
  }
  if (variable_get('image_captcha_line_noise', 0)) {
    _image_captcha_image_generator_add_lines($image, $width, $height, $noise_colors);
  }

  // Distort the image.
  $distortion_amplitude = .25 * $font_size * variable_get('image_captcha_distortion_amplitude', 0) / 10.0;
  if ($distortion_amplitude > 1) {
    // distortion parameters
    $wavelength_xr = (2+3*lcg_value())*$font_size;
    $wavelength_yr = (2+3*lcg_value())*$font_size;
    $freq_xr = 2 * 3.141592 / $wavelength_xr;
    $freq_yr = 2 * 3.141592 / $wavelength_yr;
    $wavelength_xt = (2+3*lcg_value())*$font_size;
    $wavelength_yt = (2+3*lcg_value())*$font_size;
    $freq_xt = 2 * 3.141592 / $wavelength_xt;
    $freq_yt = 2 * 3.141592 / $wavelength_yt;

    $distorted_image = imagecreatetruecolor($width, $height);
    if ($file_format == IMAGE_CAPTCHA_FILE_FORMAT_TRANSPARENT_PNG) {
      imagecolortransparent($distorted_image, $background_color);
    }
    if (!$distorted_image) {
      return FALSE;
    }

    if (variable_get('image_captcha_bilinear_interpolation', FALSE)) {
      // distortion with bilinear interpolation
      for ($x = 0; $x < $width; $x++) {
        for ($y = 0; $y < $height; $y++) {
          // get distorted sample point in source image
          $r = $distortion_amplitude * sin($x * $freq_xr + $y * $freq_yr);
          $theta = $x * $freq_xt + $y * $freq_yt;
          $sx = $x + $r * cos($theta);
          $sy = $y + $r * sin($theta);
          $sxf = (int)floor($sx);
          $syf = (int)floor($sy);
          if ($sxf < 0 || $syf < 0 || $sxf >= $width - 1 || $syf >= $height - 1) {
            $color = $background_color;
          }
          else {
            // bilinear interpolation: sample at four corners
            $color_00 = imagecolorat($image, $sxf  , $syf  );
            $color_00_r = ($color_00 >> 16) & 0xFF;
            $color_00_g = ($color_00 >> 8) & 0xFF;
            $color_00_b = $color_00 & 0xFF;
            $color_10 = imagecolorat($image, $sxf+1, $syf  );
            $color_10_r = ($color_10 >> 16) & 0xFF;
            $color_10_g = ($color_10 >> 8) & 0xFF;
            $color_10_b = $color_10 & 0xFF;
            $color_01 = imagecolorat($image, $sxf  , $syf+1);
            $color_01_r = ($color_01 >> 16) & 0xFF;
            $color_01_g = ($color_01 >> 8) & 0xFF;
            $color_01_b = $color_01 & 0xFF;
            $color_11 = imagecolorat($image, $sxf+1, $syf+1);
            $color_11_r = ($color_11 >> 16) & 0xFF;
            $color_11_g = ($color_11 >> 8) & 0xFF;
            $color_11_b = $color_11 & 0xFF;
            // interpolation factors
            $u  = $sx - $sxf;
            $v  = $sy - $syf;
            // interpolate
            $r = (int)((1-$v)*((1-$u)*$color_00_r + $u*$color_10_r) + $v*((1-$u)*$color_01_r + $u*$color_11_r));
            $g = (int)((1-$v)*((1-$u)*$color_00_g + $u*$color_10_g) + $v*((1-$u)*$color_01_g + $u*$color_11_g));
            $b = (int)((1-$v)*((1-$u)*$color_00_b + $u*$color_10_b) + $v*((1-$u)*$color_01_b + $u*$color_11_b));
            // build color
            $color = ($r<<16) + ($g<<8) + $b;
          }
          imagesetpixel($distorted_image, $x, $y, $color);
        }
      }
    }
    else {
      // distortion with nearest neighbor interpolation
      for ($x = 0; $x < $width; $x++) {
        for ($y = 0; $y < $height; $y++) {
          // get distorted sample point in source image
          $r = $distortion_amplitude * sin($x * $freq_xr + $y * $freq_yr);
          $theta = $x * $freq_xt + $y * $freq_yt;
          $sx = $x + $r * cos($theta);
          $sy = $y + $r * sin($theta);
          $sxf = (int)floor($sx);
          $syf = (int)floor($sy);
          if ($sxf < 0 || $syf < 0 || $sxf >= $width - 1 || $syf >= $height - 1) {
            $color = $background_color;
          }
          else {
            $color = imagecolorat($image, $sxf, $syf);
          }
          imagesetpixel($distorted_image, $x, $y, $color);
        }
      }
    }
    // release undistorted image
    imagedestroy($image);
    // return distorted image
    return $distorted_image;
  }
  else {
    return $image;
  }
}

function _image_captcha_image_generator_add_lines(&$image, $width, $height, $colors) {
  $line_quantity = $width * $height/200.0 * ((int) variable_get('image_captcha_noise_level', 5)) / 10.0;
  for ($i = 0; $i <  $line_quantity; $i++) {
    imageline($image, mt_rand(0, $width), mt_rand(0, $height), mt_rand(0, $width), mt_rand(0, $height), $colors[array_rand($colors)]);
  }
}

function _image_captcha_image_generator_add_dots(&$image, $width, $height, $colors) {
  $noise_quantity = $width * $height * ((int) variable_get('image_captcha_noise_level', 5)) / 10.0;
  for ($i = 0; $i < $noise_quantity; $i++ ) {
    imagesetpixel($image, mt_rand(0, $width), mt_rand(0, $height), $colors[array_rand($colors)]);
  }
}

/**
 * Helper function for drawing text on the image.
 */
function _image_captcha_image_generator_print_string(&$image, $width, $height, $fonts, $font_size, $text) {
  // get characters
  $characters = _image_captcha_utf8_split($text);
  $character_quantity = count($characters);

  // get colors
  $background_rgb = _image_captcha_hex_to_rgb(variable_get('image_captcha_background_color', '#ffffff'));
  $foreground_rgb = _image_captcha_hex_to_rgb(variable_get('image_captcha_foreground_color', '#000000'));
  $background_color = imagecolorallocate($image, $background_rgb[0], $background_rgb[1], $background_rgb[2]);
  $foreground_color = imagecolorallocate($image, $foreground_rgb[0], $foreground_rgb[1], $foreground_rgb[2]);
  // precalculate the value ranges for color randomness
  $foreground_randomness = (int)(variable_get('image_captcha_foreground_color_randomness', 100));
  if ($foreground_randomness) {
    $foreground_color_range = array();
    for ($i=0; $i<3; $i++) {
      $foreground_color_range[$i] = array(max(0, $foreground_rgb[$i] - $foreground_randomness), min(255, $foreground_rgb[$i] + $foreground_randomness));
    }
  }

  // set default text color
  $color = $foreground_color;

  // the image is seperated in different character cages, one for each character
  // each character will be somewhere inside that cage
  $ccage_width = $width / $character_quantity;
  $ccage_height = $height;

  foreach ($characters as $c => $character) {
    // initial position of character: in the center of its cage
    $center_x = ($c + 0.5) * $ccage_width;
    $center_y = 0.5 * $height;

    // Pick a random font from the list.
    $font = $fonts[array_rand($fonts)];

    // Get character dimensions for TrueType fonts.
    if ($font != 'BUILTIN') {
      $bbox = imagettfbbox($font_size, 0, drupal_realpath($font), $character);
      // In very rare cases with some versions of the GD library, the x-value
      // of the left side of the bounding box as returned by the first call of
      // imagettfbbox is corrupt (value -2147483648 = 0x80000000).
      // The weird thing is that calling the function a second time
      // can be used as workaround.
      // This issue is discussed at http://drupal.org/node/349218.
      if ($bbox[2] < 0) {
        $bbox = imagettfbbox($font_size, 0, drupal_realpath($font), $character);
      }
    }
    else {
      $character_width = imagefontwidth(5);
      $character_height = imagefontheight(5);
      $bbox = array(0, $character_height, $character_width, $character_height, $character_width, 0, 0, 0);
    }

    // Random (but small) rotation of the character.
    // TODO: add a setting for this?
    $angle = mt_rand(-10, 10);

    // Determine print position: at what coordinate should the character be
    // printed so that the bounding box would be nicely centered in the cage?
    $bb_center_x = .5 * ($bbox[0] + $bbox[2]);
    $bb_center_y = .5 * ($bbox[1] + $bbox[7]);
    $angle_cos = cos($angle*3.1415/180);
    $angle_sin = sin($angle*3.1415/180);
    $pos_x = $center_x - ($angle_cos * $bb_center_x + $angle_sin * $bb_center_y);
    $pos_y = $center_y - (-$angle_sin * $bb_center_x + $angle_cos * $bb_center_y);

    // Calculate available room to jitter: how much can the character be moved
    // so that it stays inside its cage?
    $bb_width = $bbox[2] - $bbox[0];
    $bb_height = $bbox[1] - $bbox[7];
    $dev_x = .5 * max(0, $ccage_width - abs($angle_cos) * $bb_width - abs($angle_sin) * $bb_height);
    $dev_y = .5 * max(0, $ccage_height - abs($angle_cos) * $bb_height - abs($angle_sin) * $bb_width);

    // add jitter to position
    $pos_x = $pos_x + mt_rand(-$dev_x, $dev_x);
    $pos_y = $pos_y + mt_rand(-$dev_y, $dev_y);

    // calculate text color in case of randomness
    if ($foreground_randomness) {
      $color = imagecolorallocate($image,
        mt_rand($foreground_color_range[0][0], $foreground_color_range[0][1]),
        mt_rand($foreground_color_range[1][0], $foreground_color_range[1][1]),
        mt_rand($foreground_color_range[2][0], $foreground_color_range[2][1])
      );
    }

    // draw character
    if ($font == 'BUILTIN') {
      imagestring($image, 5, $pos_x, $pos_y, $character, $color);
    }
    else {
      imagettftext($image, $font_size, $angle, $pos_x, $pos_y, $color, drupal_realpath($font), $character);
    }

    // For debugging purposes: draw character bounding box (only valid when rotation is disabled).
    // imagerectangle($image, $pos_x + $bbox[0], $pos_y + $bbox[1], $pos_x + $bbox[2], $pos_y + $bbox[7], $color);

  }

  // return a sign of success
  return TRUE;
}
