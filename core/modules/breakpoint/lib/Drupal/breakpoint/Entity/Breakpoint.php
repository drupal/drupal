<?php

/**
 * @file
 * Definition of Drupal\breakpoint\Entity\Breakpoint.
 */

namespace Drupal\breakpoint\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\breakpoint\BreakpointInterface;
use Drupal\breakpoint\InvalidBreakpointException;
use Drupal\breakpoint\InvalidBreakpointNameException;
use Drupal\breakpoint\InvalidBreakpointSourceException;
use Drupal\breakpoint\InvalidBreakpointSourceTypeException;
use Drupal\breakpoint\InvalidBreakpointMediaQueryException;

/**
 * Defines the Breakpoint entity.
 *
 * @ConfigEntityType(
 *   id = "breakpoint",
 *   label = @Translation("Breakpoint"),
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label"
 *   }
 * )
 */
class Breakpoint extends ConfigEntityBase implements BreakpointInterface {

  /**
   * Denotes that a breakpoint or breakpoint group is defined by a theme.
   */
  const SOURCE_TYPE_THEME = 'theme';

  /**
   * Denotes that a breakpoint or breakpoint group is defined by a module.
   */
  const SOURCE_TYPE_MODULE = 'module';

  /**
   * Denotes that a breakpoint or breakpoint group is defined by the user.
   */
  const SOURCE_TYPE_USER_DEFINED = 'custom';

  /**
   * The breakpoint ID (config name).
   *
   * @var string
   */
  public $id;

  /**
   * The breakpoint name (machine name) as specified by theme or module.
   *
   * @var string
   */
  public $name;

  /**
   * The breakpoint label.
   *
   * @var string
   */
  public $label;

  /**
   * The breakpoint media query.
   *
   * @var string
   */
  public $mediaQuery = '';

  /**
   * The breakpoint source.
   *
   * @var string
   */
  public $source = 'user';

  /**
   * The breakpoint source type.
   *
   * @var string
   *   Allowed values:
   *     Breakpoint::SOURCE_TYPE_THEME
   *     Breakpoint::SOURCE_TYPE_MODULE
   *     Breakpoint::SOURCE_TYPE_USER_DEFINED
   */
  public $sourceType = Breakpoint::SOURCE_TYPE_USER_DEFINED;

  /**
   * The breakpoint weight.
   *
   * @var weight
   */
  public $weight = 0;

  /**
   * The breakpoint multipliers.
   *
   * @var multipliers
   */
  public $multipliers = array();

  /**
   * Overrides Drupal\config\ConfigEntityBase::save().
   */
  public function save() {
    // Check if everything is valid.
    if (!$this->isValid()) {
      throw new InvalidBreakpointException('Invalid data detected.');
    }

    // Build an id if none is set.
    // Since a particular name can be used by multiple theme/modules we need
    // to make a unique id.
    if (empty($this->id)) {
      $this->id = $this->sourceType . '.' . $this->source . '.' . $this->name;
    }

    // Set the label if none is set.
    if (empty($this->label)) {
      $this->label = $this->name;
    }

    // Remove unused multipliers.
    $this->multipliers = array_filter($this->multipliers);

    // Always add '1x' multiplier, use array_key_exists since the value might
    // be NULL.
    if (!array_key_exists('1x', $this->multipliers)) {
      $this->multipliers = array('1x' => '1x') + $this->multipliers;
    }
    return parent::save();
  }

  /**
   * {@inheritdoc}
   */
  public function isValid() {
    // Check for illegal values in breakpoint source type.
    if (!in_array($this->sourceType, array(
        Breakpoint::SOURCE_TYPE_USER_DEFINED,
        Breakpoint::SOURCE_TYPE_MODULE,
        Breakpoint::SOURCE_TYPE_THEME)
      )) {
      throw new InvalidBreakpointSourceTypeException(format_string('Invalid source type @source_type', array(
        '@source_type' => $this->sourceType,
      )));
    }
    // Check for illegal characters in breakpoint source.
    if (preg_match('/[^0-9a-z_]+/', $this->source)) {
      throw new InvalidBreakpointSourceException(format_string("Invalid value '@source' for breakpoint source property. Breakpoint source property can only contain lowercase alphanumeric characters and underscores.", array('@source' => $this->source)));
    }
    // Check for illegal characters in breakpoint names.
    if (preg_match('/[^0-9a-z_\-]/', $this->name)) {
      throw new InvalidBreakpointNameException(format_string("Invalid value '@name' for breakpoint name property. Breakpoint name property can only contain lowercase alphanumeric characters, underscores (_), and hyphens (-).", array('@name' => $this->name)));
    }
    return $this::isValidMediaQuery($this->mediaQuery);
  }

  /**
   * {@inheritdoc}
   */
  public static function isValidMediaQuery($media_query) {
    // Array describing all known media features and the expected value type or
    // an array containing the allowed values.
    $media_features = array(
      'width' => 'length', 'min-width' => 'length', 'max-width' => 'length',
      'height' => 'length', 'min-height' => 'length', 'max-height' => 'length',
      'device-width' => 'length', 'min-device-width' => 'length', 'max-device-width' => 'length',
      'device-height' => 'length', 'min-device-height' => 'length', 'max-device-height' => 'length',
      'orientation' => array('portrait', 'landscape'),
      'aspect-ratio' => 'ratio', 'min-aspect-ratio' => 'ratio', 'max-aspect-ratio' => 'ratio',
      'device-aspect-ratio' => 'ratio', 'min-device-aspect-ratio' => 'ratio', 'max-device-aspect-ratio' => 'ratio',
      'color' => 'integer', 'min-color' => 'integer', 'max-color' => 'integer',
      'color-index' => 'integer', 'min-color-index' => 'integer', 'max-color-index' => 'integer',
      'monochrome' => 'integer', 'min-monochrome' => 'integer', 'max-monochrome' => 'integer',
      'resolution' => 'resolution', 'min-resolution' => 'resolution', 'max-resolution' => 'resolution',
      'scan' => array('progressive', 'interlace'),
      'grid' => 'integer',
    );
    if ($media_query) {
      // Strip new lines and trim.
      $media_query = str_replace(array("\r", "\n"), ' ', trim($media_query));

      // Remove comments /* ... */.
      $media_query = preg_replace('/\/\*[\s\S]*?\*\//', '', $media_query);

      // Check media list.
      $parts = explode(',', $media_query);
      foreach ($parts as $part) {
        // Split on ' and '
        $query_parts = explode(' and ', trim($part));
        $media_type_found = FALSE;
        foreach ($query_parts as $query_part) {
          $matches = array();
          // Try to match: '(media_feature: value)' and variants.
          if (preg_match('/^\(([\w\-]+)(:\s?([\w\-\.]+))?\)/', trim($query_part), $matches)) {
            // Single expression like '(color)'.
            if (isset($matches[1]) && !isset($matches[2])) {
              if (!array_key_exists($matches[1], $media_features)) {
                throw new InvalidBreakpointMediaQueryException('Invalid media feature detected.');
              }
            }
            // Full expression like '(min-width: 20em)'.
            elseif (isset($matches[3]) && !isset($matches[4])) {
              $value = trim($matches[3]);
              if (!array_key_exists($matches[1], $media_features)) {
                // We need to allow vendor prefixed media features and make sure
                // we are future proof, so only check allowed characters.
                if (!preg_match('/^[a-zA-Z0-9\:\-\\ ]+$/i', trim($matches[1]))) {
                  throw new InvalidBreakpointMediaQueryException('Invalid media query detected.');
                }
              }
              elseif (is_array($media_features[$matches[1]])) {
                // Check if value is allowed.
                if (!array_key_exists($value, $media_features[$matches[1]])) {
                  throw new InvalidBreakpointMediaQueryException('Value is not allowed.');
                }
              }
              elseif (isset ($media_features[$matches[1]])) {
                switch ($media_features[$matches[1]]) {
                  case 'length':
                    $length_matches = array();
                    // Check for a valid number and an allowed unit.
                    if (preg_match('/^(\-)?(\d+(?:\.\d+)?)?((?:|em|ex|px|cm|mm|in|pt|pc|deg|rad|grad|ms|s|hz|khz|dpi|dpcm))$/i', trim($value), $length_matches)) {
                      // Only -0 is allowed.
                      if ($length_matches[1] === '-' && $length_matches[2] !== '0') {
                        throw new InvalidBreakpointMediaQueryException('Invalid length detected.');
                      }
                      // If there's a unit, a number is needed as well.
                      if ($length_matches[2] === '' && $length_matches[3] !== '') {
                        throw new InvalidBreakpointMediaQueryException('Unit found, value is missing.');
                      }
                    }
                    else {
                      throw new InvalidBreakpointMediaQueryException('Invalid unit detected.');
                    }
                    break;
                }
              }
            }
          }

          // Check for screen, only screen, not screen and variants.
          elseif (preg_match('/^((?:only|not)?\s?)([\w\-]+)$/i', trim($query_part), $matches)) {
            if ($media_type_found) {
              throw new InvalidBreakpointMediaQueryException('Only one media type is allowed.');
            }
            $media_type_found = TRUE;
          }
          // Check for (scan), (only scan), (not scan) and variants.
          elseif (preg_match('/^((?:only|not)\s?)\(([\w\-]+)\)$/i', trim($query_part), $matches)) {
            throw new InvalidBreakpointMediaQueryException('Invalid media query detected.');
          }
          else {
            // We need to allow vendor prefixed media fetures and make sure we
            // are future proof, so only check allowed characters.
            if (!preg_match('/^[a-zA-Z0-9\-\\ ]+$/i', trim($query_part), $matches)) {
              throw new InvalidBreakpointMediaQueryException('Invalid media query detected.');
            }
          }
        }
      }
      return TRUE;
    }
    throw new InvalidBreakpointMediaQueryException('Media query is empty.');
  }
}
