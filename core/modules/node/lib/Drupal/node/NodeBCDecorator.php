<?php

/**
 * @file
 * Contains \Drupal\node\NodeBCDecorator.
 */

namespace Drupal\node;

use Drupal\Core\Entity\EntityBCDecorator;

/**
 * Defines the node specific entity BC decorator.
 */
class NodeBCDecorator extends EntityBCDecorator implements NodeInterface {

  /**
   * {@inheritdoc}
   */
  public function setTitle($title) {
    $this->decorated->setTitle($title);
  }

  /**
   * {@inheritdoc}
   */
  public function getCreatedTime() {
    return $this->decorated->getCreatedTime();
  }

  /**
   * {@inheritdoc}
   */
  public function setCreatedTime($timestamp) {
    return $this->decorated->setCreatedTime($timestamp);
  }

  /**
   * {@inheritdoc}
   */
  public function getChangedTime() {
    return $this->decorated->getChangedTime();
  }

  /**
   * {@inheritdoc}
   */
  public function isPromoted() {
    return $this->decorated->isPromoted();
  }

  /**
   * {@inheritdoc}
   */
  public function setPromoted($promoted) {
    $this->decorated->setPromoted($promoted);
  }

  /**
   * {@inheritdoc}
   */
  public function isSticky() {
    return $this->decorated->isSticky();
  }

  /**
   * {@inheritdoc}
   */
  public function setSticky($sticky) {
    $this->decorated->setSticky($sticky);
  }
  /**
   * {@inheritdoc}
   */
  public function getAuthor() {
    return $this->decorated->getAuthor();
  }

  /**
   * {@inheritdoc}
   */
  public function getAuthorId() {
    return $this->decorated->getAuthorId();
  }

  /**
   * {@inheritdoc}
   */
  public function setAuthorId($uid) {
    $this->decorated->setAuthorId($uid);
  }

  /**
   * {@inheritdoc}
   */
  public function isPublished() {
    return $this->decorated->isPublished();
  }

  /**
   * {@inheritdoc}
   */
  public function setPublished($published) {
    $this->decorated->setPublished($published);
  }

  /**
   * {@inheritdoc}
   */
  public function getRevisionCreationTime() {
    return $this->decorated->getRevisionCreationTime();
  }

  /**
   * {@inheritdoc}
   */
  public function setRevisionCreationTime($timestamp) {
    return $this->decorated->setRevisionCreationTime($timestamp);
  }

  /**
   * {@inheritdoc}
   */
  public function getRevisionAuthor() {
    return $this->decorated->getRevisionAuthor();
  }

  /**
   * {@inheritdoc}
   */
  public function setRevisionAuthorId($uid) {
    return $this->decorated->setRevisionAuthorId($uid);
  }

}
