<?php

namespace Drupal\layout_builder;

/**
 * Provides a domain object for layout sections.
 *
 * A section is a multi-dimensional array, keyed first by region machine name,
 * then by block UUID, containing block configuration values.
 */
class Section {

  /**
   * The section data.
   *
   * @var array
   */
  protected $section;

  /**
   * Constructs a new Section.
   *
   * @param array $section
   *   The section data.
   */
  public function __construct(array $section) {
    $this->section = $section;
  }

  /**
   * Returns the value of the section.
   *
   * @return array
   *   The section data.
   */
  public function getValue() {
    return $this->section;
  }

  /**
   * Gets the configuration of a given block from a region.
   *
   * @param string $region
   *   The region name.
   * @param string $uuid
   *   The UUID of the block to retrieve.
   *
   * @return array
   *   The block configuration.
   *
   * @throws \InvalidArgumentException
   *   Thrown when the expected region or UUID do not exist.
   */
  public function getBlock($region, $uuid) {
    if (!isset($this->section[$region])) {
      throw new \InvalidArgumentException('Invalid region');
    }

    if (!isset($this->section[$region][$uuid])) {
      throw new \InvalidArgumentException('Invalid UUID');
    }

    return $this->section[$region][$uuid];
  }

  /**
   * Updates the configuration of a given block from a region.
   *
   * @param string $region
   *   The region name.
   * @param string $uuid
   *   The UUID of the block to retrieve.
   * @param array $configuration
   *   The block configuration.
   *
   * @return $this
   *
   * @throws \InvalidArgumentException
   *   Thrown when the expected region or UUID do not exist.
   */
  public function updateBlock($region, $uuid, array $configuration) {
    if (!isset($this->section[$region])) {
      throw new \InvalidArgumentException('Invalid region');
    }

    if (!isset($this->section[$region][$uuid])) {
      throw new \InvalidArgumentException('Invalid UUID');
    }

    $this->section[$region][$uuid] = $configuration;

    return $this;
  }

  /**
   * Removes a given block from a region.
   *
   * @param string $region
   *   The region name.
   * @param string $uuid
   *   The UUID of the block to remove.
   *
   * @return $this
   */
  public function removeBlock($region, $uuid) {
    unset($this->section[$region][$uuid]);
    $this->section = array_filter($this->section);
    return $this;
  }

  /**
   * Adds a block to the front of a region.
   *
   * @param string $region
   *   The region name.
   * @param string $uuid
   *   The UUID of the block to add.
   * @param array $configuration
   *   The block configuration.
   *
   * @return $this
   */
  public function addBlock($region, $uuid, array $configuration) {
    $this->section += [$region => []];
    $this->section[$region] = array_merge([$uuid => $configuration], $this->section[$region]);
    return $this;
  }

  /**
   * Inserts a block after a specified existing block in a region.
   *
   * @param string $region
   *   The region name.
   * @param string $uuid
   *   The UUID of the block to insert.
   * @param array $configuration
   *   The block configuration.
   * @param string $preceding_uuid
   *   The UUID of the existing block to insert after.
   *
   * @return $this
   *
   * @throws \InvalidArgumentException
   *   Thrown when the expected region does not exist.
   */
  public function insertBlock($region, $uuid, array $configuration, $preceding_uuid) {
    if (!isset($this->section[$region])) {
      throw new \InvalidArgumentException('Invalid region');
    }

    $slice_id = array_search($preceding_uuid, array_keys($this->section[$region]));
    if ($slice_id === FALSE) {
      throw new \InvalidArgumentException('Invalid preceding UUID');
    }

    $before = array_slice($this->section[$region], 0, $slice_id + 1);
    $after = array_slice($this->section[$region], $slice_id + 1);
    $this->section[$region] = array_merge($before, [$uuid => $configuration], $after);
    return $this;
  }

}
