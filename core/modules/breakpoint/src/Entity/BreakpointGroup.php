<?php

/**
 * @file
 * Definition of Drupal\breakpoint\Entity\BreakpointGroup.
 */

namespace Drupal\breakpoint\Entity;

use Drupal\breakpoint\InvalidBreakpointNameException;
use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\breakpoint\BreakpointGroupInterface;
use Drupal\breakpoint\InvalidBreakpointSourceException;
use Drupal\breakpoint\InvalidBreakpointSourceTypeException;
use Drupal\Core\Entity\EntityStorageInterface;

/**
 * Defines the BreakpointGroup entity.
 *
 * @ConfigEntityType(
 *   id = "breakpoint_group",
 *   label = @Translation("Breakpoint group"),
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label"
 *   }
 * )
 */
class BreakpointGroup extends ConfigEntityBase implements BreakpointGroupInterface {

  /**
   * The breakpoint group ID.
   *
   * @var string
   */
  public $id;

  /**
   * The breakpoint group machine name.
   *
   * @var string
   */
  public $name;

  /**
   * The breakpoint group label.
   *
   * @var string
   */
  public $label;

  /**
   * The breakpoint group breakpoint IDs.
   *
   * @var array
   *   Array containing all breakpoints IDs of this group.
   *
   * @see \Drupal\breakpoint\Entity\Breakpoint
   */
  protected $breakpoint_ids = array();

  /**
   * The breakpoint group breakpoints.
   *
   * @var array
   *   Array containing all breakpoints objects of this group.
   *
   * @see \Drupal\breakpoint\Entity\Breakpoint
   */
  protected $breakpoints = array();

  /**
   * The breakpoint group source: theme or module name. Use 'user' for
   * user-created groups.
   *
   * @var string
   */
  public $source = 'user';

  /**
   * The breakpoint group source type.
   *
   * @var string
   *   Allowed values:
   *     Breakpoint::SOURCE_TYPE_THEME
   *     Breakpoint::SOURCE_TYPE_MODULE
   *     Breakpoint::SOURCE_TYPE_USER_DEFINED
   *
   * @see \Drupal\breakpoint\Entity\Breakpoint
   */
  public $sourceType = Breakpoint::SOURCE_TYPE_USER_DEFINED;

  /**
   * {@inheritdoc}
   *
   * @throws \Drupal\breakpoint\InvalidBreakpointNameException
   *   Exception thrown if $values['name'] is empty.
   */
  public function __construct(array $values, $entity_type = 'breakpoint_group') {
    // Check required properties.
    if (empty($values['name'])) {
      throw new InvalidBreakpointNameException('Attempt to create an unnamed breakpoint group.');
    }
    parent::__construct($values, $entity_type);
  }

  /**
   * Overrides Drupal\Core\Entity\Entity::save().
   */
  public function save() {
    // Check if everything is valid.
    if (!$this->isValid()) {
      throw new InvalidBreakpointException('Invalid data detected.');
    }
    parent::save();
  }

  /**
   * {@inheritdoc}
   */
  public function id() {
    // If no ID is specified, build one from the properties that uniquely define
    // this breakpoint group.
    if (!isset($this->id)) {
      $this->id = $this->sourceType . '.' . $this->source . '.' . $this->name;
    }
    return $this->id;
  }

  /**
   * {@inheritdoc}
   */
  public function isValid() {
    // Check for illegal values in breakpoint group source type.
    if (!in_array($this->sourceType, array(
        Breakpoint::SOURCE_TYPE_USER_DEFINED,
        Breakpoint::SOURCE_TYPE_MODULE,
        Breakpoint::SOURCE_TYPE_THEME)
      )) {
      throw new InvalidBreakpointSourceTypeException(format_string('Invalid source type @source_type', array(
        '@source_type' => $this->sourceType,
      )));
    }
    // Check for illegal characters in breakpoint group source.
    if (preg_match('/[^a-z_]+/', $this->source) || empty($this->source)) {
      throw new InvalidBreakpointSourceException(format_string("Invalid value '@source' for breakpoint group source property. Breakpoint group source property can only contain lowercase letters and underscores.", array('@source' => $this->source)));
    }
    // Check for illegal characters in breakpoint group name.
    if (preg_match('/[^a-z0-9_]+/', $this->name || empty($this->name))) {
      throw new InvalidBreakpointNameException(format_string("Invalid value '@name' for breakpoint group name property. Breakpoint group name property can only contain lowercase letters, numbers and underscores.", array('@name' => $this->name)));
    }
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function addBreakpointFromMediaQuery($name, $media_query) {
    // Use the existing breakpoint if it exists.
    $breakpoint = Breakpoint::load($this->sourceType . '.' . $this->name . '.' . $name);
    if (!$breakpoint) {
      // Build a new breakpoint.
      $breakpoint = entity_create('breakpoint', array(
        'name' => $name,
        'label' => $name,
        'mediaQuery' => $media_query,
        'source' => $this->name,
        'sourceType' => $this->sourceType,
        'weight' => count($this->breakpoint_ids),
      ));
      $breakpoint->save();
    }
    return $this->addBreakpoints(array($breakpoint));
  }

  /**
   * {@inheritdoc}
   */
  public function addBreakpoints($breakpoints) {
    foreach ($breakpoints as $breakpoint) {
      // Add breakpoint to group.
      $this->breakpoints[$breakpoint->id()] = $breakpoint;
      $this->breakpoint_ids[] = $breakpoint->id();
    }
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getBreakpoints() {
    if (empty($this->breakpoints)) {
      foreach ($this->breakpoint_ids as $breakpoint_id) {
        $breakpoint = Breakpoint::load($breakpoint_id);
        if ($breakpoint) {
          $this->breakpoints[$breakpoint_id] = $breakpoint;
        }
      }
    }
    return $this->breakpoints;
  }

  /**
   * {@inheritdoc}
   */
  public function getBreakpointById($id) {
    $breakpoints = $this->getBreakpoints();
    if (isset($breakpoints[$id])) {
      return $breakpoints[$id];
    }
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function calculateDependencies() {
    parent::calculateDependencies();

    $this->dependencies = array();
    if ($this->sourceType == Breakpoint::SOURCE_TYPE_MODULE) {
      $this->addDependency('module', $this->source);
    }
    elseif ($this->sourceType == Breakpoint::SOURCE_TYPE_THEME) {
      $this->addDependency('theme', $this->source);
    }
    $breakpoints = $this->getBreakpoints();
    foreach ($breakpoints as $breakpoint) {
      $this->addDependency('entity', $breakpoint->getConfigDependencyName());
    }
    return $this->dependencies;
  }

}
