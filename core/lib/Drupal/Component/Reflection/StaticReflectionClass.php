<?php

namespace Drupal\Component\Reflection;

use ReflectionClass;
use ReflectionException;

class StaticReflectionClass extends ReflectionClass {

  /**
   * The static reflection parser object.
   *
   * @var StaticReflectionParser
   */
  private $staticReflectionParser;

  public function __construct(StaticReflectionParser $staticReflectionParser) {
    $this->staticReflectionParser = $staticReflectionParser;
  }

  /**
   * {@inheritdoc}
   */
  public function getName() {
    return $this->staticReflectionParser->getClassName();
  }

  /**
   * {@inheritdoc}
   */
  public function getDocComment() {
    return $this->staticReflectionParser->getDocComment();
  }

  /**
   * {@inheritdoc}
   */
  public function getNamespaceName() {
    return $this->staticReflectionParser->getNamespaceName();
  }

  /**
   * @return string[]
   */
  public function getUseStatements() {
    return $this->staticReflectionParser->getUseStatements();
  }

  /**
   * {@inheritdoc}
   */
  public function getMethod($name) {
    return $this->staticReflectionParser->getReflectionMethod($name);
  }

  /**
   * {@inheritdoc}
   */
  public function getProperty($name) {
    return $this->staticReflectionParser->getReflectionProperty($name);
  }

  /**
   * {@inheritdoc}
   */
  public static function export($argument, $return = false) {
    throw new ReflectionException('Method not implemented');
  }

  /**
   * {@inheritdoc}
   */
  public function getConstant($name) {
    throw new ReflectionException('Method not implemented');
  }

  /**
   * {@inheritdoc}
   */
  public function getConstants() {
    throw new ReflectionException('Method not implemented');
  }

  /**
   * {@inheritdoc}
   */
  public function getConstructor() {
    throw new ReflectionException('Method not implemented');
  }

  /**
   * {@inheritdoc}
   */
  public function getDefaultProperties() {
    throw new ReflectionException('Method not implemented');
  }

  /**
   * {@inheritdoc}
   */
  public function getEndLine() {
    throw new ReflectionException('Method not implemented');
  }

  /**
   * {@inheritdoc}
   */
  public function getExtension() {
    throw new ReflectionException('Method not implemented');
  }

  /**
   * {@inheritdoc}
   */
  public function getExtensionName() {
    throw new ReflectionException('Method not implemented');
  }

  /**
   * {@inheritdoc}
   */
  public function getFileName() {
    throw new ReflectionException('Method not implemented');
  }

  /**
   * {@inheritdoc}
   */
  public function getInterfaceNames() {
    throw new ReflectionException('Method not implemented');
  }

  /**
   * {@inheritdoc}
   */
  public function getInterfaces() {
    throw new ReflectionException('Method not implemented');
  }

  /**
   * {@inheritdoc}
   */
  public function getMethods($filter = null) {
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
  public function getParentClass() {
    throw new ReflectionException('Method not implemented');
  }

  /**
   * {@inheritdoc}
   */
  public function getProperties($filter = null) {
    throw new ReflectionException('Method not implemented');
  }

  /**
   * {@inheritdoc}
   */
  public function getShortName() {
    throw new ReflectionException('Method not implemented');
  }

  /**
   * {@inheritdoc}
   */
  public function getStartLine() {
    throw new ReflectionException('Method not implemented');
  }

  /**
   * {@inheritdoc}
   */
  public function getStaticProperties() {
    throw new ReflectionException('Method not implemented');
  }

  /**
   * {@inheritdoc}
   */
  public function getStaticPropertyValue($name, $default = '') {
    throw new ReflectionException('Method not implemented');
  }

  /**
   * {@inheritdoc}
   */
  public function getTraitAliases() {
    throw new ReflectionException('Method not implemented');
  }

  /**
   * {@inheritdoc}
   */
  public function getTraitNames() {
    throw new ReflectionException('Method not implemented');
  }

  /**
   * {@inheritdoc}
   */
  public function getTraits() {
    throw new ReflectionException('Method not implemented');
  }

  /**
   * {@inheritdoc}
   */
  public function hasConstant($name) {
    throw new ReflectionException('Method not implemented');
  }

  /**
   * {@inheritdoc}
   */
  public function hasMethod($name) {
    throw new ReflectionException('Method not implemented');
  }

  /**
   * {@inheritdoc}
   */
  public function hasProperty($name) {
    throw new ReflectionException('Method not implemented');
  }

  /**
   * {@inheritdoc}
   */
  public function implementsInterface($interface) {
    throw new ReflectionException('Method not implemented');
  }

  /**
   * {@inheritdoc}
   */
  public function inNamespace() {
    throw new ReflectionException('Method not implemented');
  }

  /**
   * {@inheritdoc}
   */
  public function isAbstract() {
    throw new ReflectionException('Method not implemented');
  }

  /**
   * {@inheritdoc}
   */
  public function isCloneable() {
    throw new ReflectionException('Method not implemented');
  }

  /**
   * {@inheritdoc}
   */
  public function isFinal() {
    throw new ReflectionException('Method not implemented');
  }

  /**
   * {@inheritdoc}
   */
  public function isInstance($object) {
    throw new ReflectionException('Method not implemented');
  }

  /**
   * {@inheritdoc}
   */
  public function isInstantiable() {
    throw new ReflectionException('Method not implemented');
  }

  /**
   * {@inheritdoc}
   */
  public function isInterface() {
    throw new ReflectionException('Method not implemented');
  }

  /**
   * {@inheritdoc}
   */
  public function isInternal() {
    throw new ReflectionException('Method not implemented');
  }

  /**
   * {@inheritdoc}
   */
  public function isIterateable() {
    throw new ReflectionException('Method not implemented');
  }

  /**
   * {@inheritdoc}
   */
  public function isSubclassOf($class) {
    throw new ReflectionException('Method not implemented');
  }

  /**
   * {@inheritdoc}
   */
  public function isTrait() {
    throw new ReflectionException('Method not implemented');
  }

  /**
   * {@inheritdoc}
   */
  public function isUserDefined() {
    throw new ReflectionException('Method not implemented');
  }

  /**
   * {@inheritdoc}
   */
  public function newInstance($args) {
    throw new ReflectionException('Method not implemented');
  }

  /**
   * {@inheritdoc}
   */
  public function newInstanceArgs(array $args = []) {
    throw new ReflectionException('Method not implemented');
  }

  /**
   * {@inheritdoc}
   */
  public function newInstanceWithoutConstructor() {
    throw new ReflectionException('Method not implemented');
  }

  /**
   * {@inheritdoc}
   */
  public function setStaticPropertyValue($name, $value) {
    throw new ReflectionException('Method not implemented');
  }

  /**
   * {@inheritdoc}
   */
  public function __toString() {
    throw new ReflectionException('Method not implemented');
  }
}
