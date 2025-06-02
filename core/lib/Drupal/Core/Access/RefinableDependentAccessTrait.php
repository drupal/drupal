<?php

namespace Drupal\Core\Access;

/**
 * Trait for \Drupal\Core\Access\RefinableDependentAccessInterface.
 *
 * @internal
 */
trait RefinableDependentAccessTrait {

  /**
   * The access dependency.
   *
   * @var \Drupal\Core\Access\AccessibleInterface
   */
  protected $accessDependency;

  /**
   * {@inheritdoc}
   */
  public function setAccessDependency(AccessibleInterface $access_dependency) {
    $this->accessDependency = $access_dependency;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getAccessDependency() {
    return $this->accessDependency;
  }

  /**
   * {@inheritdoc}
   */
  public function addAccessDependency(AccessibleInterface $access_dependency) {
    if (empty($this->accessDependency)) {
      $this->accessDependency = $access_dependency;
      return $this;
    }
    if (!$this->accessDependency instanceof AccessGroupAnd) {
      $accessGroup = new AccessGroupAnd();
      $this->accessDependency = $accessGroup->addDependency($this->accessDependency);
    }
    $this->accessDependency->addDependency($access_dependency);
    return $this;
  }

}
