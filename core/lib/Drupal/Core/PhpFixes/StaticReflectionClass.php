<?php

// cSpell:disable
// phpcs:ignoreFile
namespace Drupal\Core\PhpFixes;

use Doctrine\Common\Reflection\StaticReflectionParser;

use ReflectionClass;
use ReflectionException;

class StaticReflectionClass extends ReflectionClass
{
  /**
   * {@inheritDoc}
   */
  public function getConstants(?int $filter = null) : array
  {
    throw new ReflectionException('Method not implemented');
  }

  /**
   * {@inheritDoc}
   */
  public function newInstance(mixed ...$args) : object
  {
    throw new ReflectionException('Method not implemented');
  }

    /**
     * The static reflection parser object.
     *
     * @var StaticReflectionParser
     */
    private $staticReflectionParser;

    public function __construct(StaticReflectionParser $staticReflectionParser)
    {
      $this->staticReflectionParser = $staticReflectionParser;
    }

    /**
     * {@inheritDoc}
     */
    public function getName() : string
    {
      return $this->staticReflectionParser->getClassName();
    }

    /**
     * {@inheritDoc}
     */
    public function getDocComment(): string|false
    {
      return $this->staticReflectionParser->getDocComment();
    }

    /**
     * {@inheritDoc}
     */
    public function getNamespaceName(): string
    {
      return $this->staticReflectionParser->getNamespaceName();
    }

    /**
     * @return string[]
     */
    public function getUseStatements()
    {
      return $this->staticReflectionParser->getUseStatements();
    }

    /**
     * {@inheritDoc}
     */
    public function getMethod(string $name): \ReflectionMethod
    {
      return $this->staticReflectionParser->getReflectionMethod($name);
    }

    /**
     * {@inheritDoc}
     */
    public function getProperty(string $name): \ReflectionProperty
    {
      return $this->staticReflectionParser->getReflectionProperty($name);
    }

    /**
     * {@inheritDoc}
     */
    public static function export($argument, $return = false)
    {
      throw new ReflectionException('Method not implemented');
    }

    /**
     * {@inheritDoc}
     */
    public function getConstant(string $name) : mixed
    {
      throw new ReflectionException('Method not implemented');
    }

    /**
     * {@inheritDoc}
     */
    public function getConstructor(): ?\ReflectionMethod
    {
      throw new ReflectionException('Method not implemented');
    }

    /**
     * {@inheritDoc}
     */
    public function getDefaultProperties() : array
    {
      throw new ReflectionException('Method not implemented');
    }

    /**
     * {@inheritDoc}
     */
    public function getEndLine() : int|false
    {
      throw new ReflectionException('Method not implemented');
    }

    /**
     * {@inheritDoc}
     */
    public function getExtension() : ?\ReflectionExtension
    {
      throw new ReflectionException('Method not implemented');
    }

    /**
     * {@inheritDoc}
     */
    public function getExtensionName() : string|false
    {
      throw new ReflectionException('Method not implemented');
    }

    /**
     * {@inheritDoc}
     */
    public function getFileName() : string|false
    {
      throw new ReflectionException('Method not implemented');
    }

    /**
     * {@inheritDoc}
     */
    public function getInterfaceNames(): array
    {
      throw new ReflectionException('Method not implemented');
    }

    /**
     * {@inheritDoc}
     */
    public function getInterfaces(): array
    {
      throw new ReflectionException('Method not implemented');
    }

    /**
     * {@inheritDoc}
     */
    public function getMethods(?int $filter = null): array
    {
      throw new ReflectionException('Method not implemented');
    }

    /**
     * {@inheritDoc}
     */
    public function getModifiers(): int
    {
      throw new ReflectionException('Method not implemented');
    }

    /**
     * {@inheritDoc}
     */
    public function getParentClass(): ReflectionClass|false
    {
      throw new ReflectionException('Method not implemented');
    }

    /**
     * {@inheritDoc}
     */
    public function getProperties($filter = null) : array
    {
      throw new ReflectionException('Method not implemented');
    }

    /**
     * {@inheritDoc}
     */
    public function getShortName() : string
    {
      throw new ReflectionException('Method not implemented');
    }

    /**
     * {@inheritDoc}
     */
    public function getStartLine() : int|false
    {
      throw new ReflectionException('Method not implemented');
    }

    /**
     * {@inheritDoc}
     */
    public function getStaticProperties() : array
    {
      throw new ReflectionException('Method not implemented');
    }

    /**
     * {@inheritDoc}
     */
    public function getStaticPropertyValue(string $name, mixed $default = '') : mixed
    {
      throw new ReflectionException('Method not implemented');
    }

    /**
     * {@inheritDoc}
     */
    public function getTraitAliases() : array
    {
      throw new ReflectionException('Method not implemented');
    }

    /**
     * {@inheritDoc}
     */
    public function getTraitNames() : array
    {
      throw new ReflectionException('Method not implemented');
    }

    /**
     * {@inheritDoc}
     */
    public function getTraits() : array
    {
      throw new ReflectionException('Method not implemented');
    }

    /**
     * {@inheritDoc}
     */
    public function hasConstant($name) : bool
    {
      throw new ReflectionException('Method not implemented');
    }

    /**
     * {@inheritDoc}
     */
    public function hasMethod($name) : bool
    {
      throw new ReflectionException('Method not implemented');
    }

    /**
     * {@inheritDoc}
     */
    public function hasProperty($name) : bool
    {
      throw new ReflectionException('Method not implemented');
    }

    /**
     * {@inheritDoc}
     */
    public function implementsInterface($interface) : bool
    {
      throw new ReflectionException('Method not implemented');
    }

    /**
     * {@inheritDoc}
     */
    public function inNamespace() : bool
    {
      throw new ReflectionException('Method not implemented');
    }

    /**
     * {@inheritDoc}
     */
    public function isAbstract() : bool
    {
      throw new ReflectionException('Method not implemented');
    }

    /**
     * {@inheritDoc}
     */
    public function isCloneable() : bool
    {
      throw new ReflectionException('Method not implemented');
    }

    /**
     * {@inheritDoc}
     */
    public function isFinal() : bool
    {
      throw new ReflectionException('Method not implemented');
    }

    /**
     * {@inheritDoc}
     */
    public function isInstance($object) : bool
    {
      throw new ReflectionException('Method not implemented');
    }

    /**
     * {@inheritDoc}
     */
    public function isInstantiable() : bool
    {
      throw new ReflectionException('Method not implemented');
    }

    /**
     * {@inheritDoc}
     */
    public function isInterface() : bool
    {
      throw new ReflectionException('Method not implemented');
    }

    /**
     * {@inheritDoc}
     */
    public function isInternal() : bool
    {
      throw new ReflectionException('Method not implemented');
    }

    /**
     * {@inheritDoc}
     */
    public function isIterateable() : bool
    {
      throw new ReflectionException('Method not implemented');
    }

    /**
     * {@inheritDoc}
     */
    public function isSubclassOf($class) : bool
    {
      throw new ReflectionException('Method not implemented');
    }

    /**
     * {@inheritDoc}
     */
    public function isTrait() : bool
    {
      throw new ReflectionException('Method not implemented');
    }

    /**
     * {@inheritDoc}
     */
    public function isUserDefined() : bool
    {
      throw new ReflectionException('Method not implemented');
    }

    /**
     * {@inheritDoc}
     */
    public function newInstanceArgs(array $args = []): ?object
    {
      throw new ReflectionException('Method not implemented');
    }

    /**
     * {@inheritDoc}
     */
    public function newInstanceWithoutConstructor() : object
    {
      throw new ReflectionException('Method not implemented');
    }

    /**
     * {@inheritDoc}
     */
    public function setStaticPropertyValue(string $name, mixed $value): void
    {
      throw new ReflectionException('Method not implemented');
    }

    /**
     * {@inheritDoc}
     */
    public function __toString()
    {
      throw new ReflectionException('Method not implemented');
    }
}
