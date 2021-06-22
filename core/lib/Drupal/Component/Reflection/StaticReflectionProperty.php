<?php

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
