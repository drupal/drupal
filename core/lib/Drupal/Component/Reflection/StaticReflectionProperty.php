<?php
// phpcs:ignoreFile

/**
 * @file
 *
 * This class is a near-copy of
 * Doctrine\Common\Reflection\StaticReflectionProperty, which is part of the
 * Doctrine project: <http://www.doctrine-project.org>. It was copied from
 * version 1.2.2.
 *
 * Original copyright:
 *
 * Copyright (c) 2006-2015 Doctrine Project
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy of
 * this software and associated documentation files (the "Software"), to deal in
 * the Software without restriction, including without limitation the rights to
 * use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies
 * of the Software, and to permit persons to whom the Software is furnished to do
 * so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 */


namespace Drupal\Component\Reflection;

use ReflectionException;
use ReflectionProperty;

class StaticReflectionProperty extends ReflectionProperty {

  /**
   * The PSR-0 parser object.
   *
   * @var StaticReflectionParser
   */
  protected $staticReflectionParser;

  /**
   * The name of the property.
   *
   * @var string|null
   */
  protected $propertyName;

  /**
   * @param string|null $propertyName
   */
  public function __construct(StaticReflectionParser $staticReflectionParser, $propertyName) {
    $this->staticReflectionParser = $staticReflectionParser;
    $this->propertyName           = $propertyName;
  }

  /**
   * {@inheritdoc}
   */
  public function getName() {
    return $this->propertyName;
  }

  /**
   * @return StaticReflectionParser
   */
  protected function getStaticReflectionParser() {
    return $this->staticReflectionParser->getStaticReflectionParserForDeclaringClass('property', $this->propertyName);
  }

  /**
   * {@inheritdoc}
   */
  public function getDeclaringClass() {
    return $this->getStaticReflectionParser()->getReflectionClass();
  }

  /**
   * {@inheritdoc}
   */
  public function getDocComment() {
    return $this->getStaticReflectionParser()->getDocComment('property', $this->propertyName);
  }

  /**
   * @return string[]
   */
  public function getUseStatements() {
    return $this->getStaticReflectionParser()->getUseStatements();
  }

  /**
   * {@inheritdoc}
   */
  public static function export($class, $name, $return = false) {
    throw new ReflectionException('Method not implemented');
  }

  /**
   * {@inheritdoc}
   */
  public function getModifiers() {
    throw new ReflectionException('Method not implemented');
  }

  /**
   * {@inheritdoc}
   */
  public function getValue($object = null) {
    throw new ReflectionException('Method not implemented');
  }

  /**
   * {@inheritdoc}
   */
  public function isDefault() {
    throw new ReflectionException('Method not implemented');
  }

  /**
   * {@inheritdoc}
   */
  public function isPrivate() {
    throw new ReflectionException('Method not implemented');
  }

  /**
   * {@inheritdoc}
   */
  public function isProtected() {
    throw new ReflectionException('Method not implemented');
  }

  /**
   * {@inheritdoc}
   */
  public function isPublic() {
    throw new ReflectionException('Method not implemented');
  }

  /**
   * {@inheritdoc}
   */
  public function isStatic() {
    throw new ReflectionException('Method not implemented');
  }

  /**
   * {@inheritdoc}
   */
  public function setAccessible($accessible) {
    throw new ReflectionException('Method not implemented');
  }

  /**
   * {@inheritdoc}
   */
  public function setValue($object, $value = null) {
    throw new ReflectionException('Method not implemented');
  }

  /**
   * {@inheritdoc}
   */
  public function __toString() {
    throw new ReflectionException('Method not implemented');
  }

}
