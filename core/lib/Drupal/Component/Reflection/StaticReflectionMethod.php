<?php

namespace Drupal\Component\Reflection;

use ReflectionException;
use ReflectionMethod;

class StaticReflectionMethod extends ReflectionMethod {

  /**
   * The PSR-0 parser object.
   *
   * @var StaticReflectionParser
   */
  protected $staticReflectionParser;

  /**
   * The name of the method.
   *
   * @var string
   */
  protected $methodName;

  /**
   * @param string $methodName
   */
  public function __construct(StaticReflectionParser $staticReflectionParser, $methodName) {
    $this->staticReflectionParser = $staticReflectionParser;
    $this->methodName             = $methodName;
  }

  /**
   * {@inheritdoc}
   */
  public function getName() {
    return $this->methodName;
  }

  /**
   * @return StaticReflectionParser
   */
  protected function getStaticReflectionParser() {
    return $this->staticReflectionParser->getStaticReflectionParserForDeclaringClass('method', $this->methodName);
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
  public function getNamespaceName() {
    return $this->getStaticReflectionParser()->getNamespaceName();
  }

  /**
   * {@inheritdoc}
   */
  public function getDocComment() {
    return $this->getStaticReflectionParser()->getDocComment('method', $this->methodName);
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
  public function getClosure($object = null) {
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
  public function getPrototype() {
    throw new ReflectionException('Method not implemented');
  }

  /**
   * {@inheritdoc}
   */
  public function invoke($object, $parameter = null) {
    throw new ReflectionException('Method not implemented');
  }

  /**
   * {@inheritdoc}
   */
  public function invokeArgs($object, array $args) {
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
  public function isConstructor() {
    throw new ReflectionException('Method not implemented');
  }

  /**
   * {@inheritdoc}
   */
  public function isDestructor() {
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
  public function __toString() {
    throw new ReflectionException('Method not implemented');
  }

  /**
   * {@inheritdoc}
   */
  public function getClosureThis() {
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
  public function getNumberOfParameters() {
    throw new ReflectionException('Method not implemented');
  }

  /**
   * {@inheritdoc}
   */
  public function getNumberOfRequiredParameters() {
    throw new ReflectionException('Method not implemented');
  }

  /**
   * {@inheritdoc}
   */
  public function getParameters() {
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
  public function getStaticVariables() {
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
  public function isClosure() {
    throw new ReflectionException('Method not implemented');
  }

  /**
   * {@inheritdoc}
   */
  public function isDeprecated() {
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
  public function isUserDefined() {
    throw new ReflectionException('Method not implemented');
  }

  /**
   * {@inheritdoc}
   */
  public function returnsReference() {
    throw new ReflectionException('Method not implemented');
  }

}
