[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/TYPO3/phar-stream-wrapper/badges/quality-score.png?b=v2)](https://scrutinizer-ci.com/g/TYPO3/phar-stream-wrapper/?branch=v2)
[![Travis CI Build Status](https://travis-ci.org/TYPO3/phar-stream-wrapper.svg?branch=v2)](https://travis-ci.org/TYPO3/phar-stream-wrapper)

# PHP Phar Stream Wrapper

## Abstract & History

Based on Sam Thomas' findings concerning
[insecure deserialization in combination with obfuscation strategies](https://blog.secarma.co.uk/labs/near-phar-dangerous-unserialization-wherever-you-are)
allowing to hide Phar files inside valid image resources, the TYPO3 project
decided back then to introduce a `PharStreamWrapper` to intercept invocations
of the `phar://` stream in PHP and only allow usage for defined locations in
the file system.

Since the TYPO3 mission statement is **inspiring people to share**, we thought
it would be helpful for others to release our `PharStreamWrapper` as standalone
package to the PHP community.

The mentioned security issue was reported to TYPO3 on 10th June 2018 by Sam Thomas
and has been addressed concerning the specific attack vector and for this generic
`PharStreamWrapper` in TYPO3 versions 7.6.30 LTS, 8.7.17 LTS and 9.3.1 on 12th
July 2018.

* https://typo3.org/security/advisory/typo3-core-sa-2018-002/
* https://blog.secarma.co.uk/labs/near-phar-dangerous-unserialization-wherever-you-are
* https://youtu.be/GePBmsNJw6Y

## License

In general the TYPO3 core is released under the GNU General Public License version
2 or any later version (`GPL-2.0-or-later`). In order to avoid licensing issues and
incompatibilities this `PharStreamWrapper` is licenced under the MIT License. In case
you duplicate or modify source code, credits are not required but really appreciated.

## Credits

Thanks to [Alex Pott](https://github.com/alexpott), Drupal for creating
back-ports of all sources in order to provide compatibility with PHP v5.3.

## Installation

The `PharStreamWrapper` is provided as composer package `typo3/phar-stream-wrapper`
and has minimum requirements of PHP v5.3 ([`v2`](https://github.com/TYPO3/phar-stream-wrapper/tree/v2) branch) and PHP v7.0 ([`master`](https://github.com/TYPO3/phar-stream-wrapper) branch).

### Installation for PHP v7.0

```
composer require typo3/phar-stream-wrapper ^3.0
```

### Installation for PHP v5.3

```
composer require typo3/phar-stream-wrapper ^2.0
```

## Example

The following example is bundled within this package, the shown
`PharExtensionInterceptor` denies all stream wrapper invocations files
not having the `.phar` suffix. Interceptor logic has to be individual and
adjusted to according requirements.

```
$behavior = new \TYPO3\PharStreamWrapper\Behavior();
\TYPO3\PharStreamWrapper\Manager::initialize(
    $behavior->withAssertion(new PharExtensionInterceptor())
);

if (in_array('phar', stream_get_wrappers())) {
    stream_wrapper_unregister('phar');
    stream_wrapper_register('phar', 'TYPO3\\PharStreamWrapper\\PharStreamWrapper');
}
```

* `PharStreamWrapper` defined as class reference will be instantiated each time
  `phar://` streams shall be processed.
* `Manager` as singleton pattern being called by `PharStreamWrapper` instances
  in order to retrieve individual behavior and settings.
* `Behavior` holds reference to interceptor(s) that shall assert correct/allowed
  invocation of a given `$path` for a given `$command`. Interceptors implement
  the interface `Assertable`. Interceptors can act individually on following
  commands or handle all of them in case not defined specifically:  
  + `COMMAND_DIR_OPENDIR`
  + `COMMAND_MKDIR`
  + `COMMAND_RENAME`
  + `COMMAND_RMDIR`
  + `COMMAND_STEAM_METADATA`
  + `COMMAND_STREAM_OPEN`
  + `COMMAND_UNLINK`
  + `COMMAND_URL_STAT`

## Interceptors

The following interceptor is shipped with the package and ready to use in order
to block any Phar invocation of files not having a `.phar` suffix. Besides that
individual interceptors are possible of course.

```
class PharExtensionInterceptor implements Assertable
{
    /**
     * Determines whether the base file name has a ".phar" suffix.
     *
     * @param string $path
     * @param string $command
     * @return bool
     * @throws Exception
     */
    public function assert($path, $command)
    {
        if ($this->baseFileContainsPharExtension($path)) {
            return true;
        }
        throw new Exception(
            sprintf(
                'Unexpected file extension in "%s"',
                $path
            ),
            1535198703
        );
    }

    /**
     * @param string $path
     * @return bool
     */
    private function baseFileContainsPharExtension($path)
    {
        $baseFile = Helper::determineBaseFile($path);
        if ($baseFile === null) {
            return false;
        }
        $fileExtension = pathinfo($baseFile, PATHINFO_EXTENSION);
        return strtolower($fileExtension) === 'phar';
    }
}
```

### ConjunctionInterceptor

This interceptor combines multiple interceptors implementing `Assertable`.
It succeeds when all nested interceptors succeed as well (logical `AND`).

```
$behavior = new \TYPO3\PharStreamWrapper\Behavior();
\TYPO3\PharStreamWrapper\Manager::initialize(
    $behavior->withAssertion(new ConjunctionInterceptor(array(
        new PharExtensionInterceptor(),
        new PharMetaDataInterceptor()
    )))
);
```

### PharExtensionInterceptor

This (basic) interceptor just checks whether the invoked Phar archive has
an according `.phar` file extension. Resolving symbolic links as well as
Phar internal alias resolving are considered as well.

```
$behavior = new \TYPO3\PharStreamWrapper\Behavior();
\TYPO3\PharStreamWrapper\Manager::initialize(
    $behavior->withAssertion(new PharExtensionInterceptor())
);
```

### PharMetaDataInterceptor

This interceptor is actually checking serialized Phar meta-data against
PHP objects and would consider a Phar archive malicious in case not only
scalar values are found. A custom low-level `Phar\Reader` is used in order to
avoid using PHP's `Phar` object which would trigger the initial vulnerability.

```
$behavior = new \TYPO3\PharStreamWrapper\Behavior();
\TYPO3\PharStreamWrapper\Manager::initialize(
    $behavior->withAssertion(new PharMetaDataInterceptor())
);
```

## Reader

* `Phar\Reader::__construct(string $fileName)`: Creates low-level reader for Phar archive
* `Phar\Reader::resolveContainer(): Phar\Container`: Resolves model representing Phar archive
* `Phar\Container::getStub(): Phar\Stub`: Resolves (plain PHP) stub section of Phar archive
* `Phar\Container::getManifest(): Phar\Manifest`: Resolves parsed Phar archive manifest as
  documented at http://php.net/manual/en/phar.fileformat.manifestfile.php
* `Phar\Stub::getMappedAlias(): string`: Resolves internal Phar archive alias defined in stub
  using `Phar::mapPhar('alias.phar')` - actually the plain PHP source is analyzed here
* `Phar\Manifest::getAlias(): string` - Resolves internal Phar archive alias defined in manifest
  using `Phar::setAlias('alias.phar')`
* `Phar\Manifest::getMetaData(): string`: Resolves serialized Phar archive meta-data
* `Phar\Manifest::deserializeMetaData(): mixed`: Resolves deserialized Phar archive meta-data
  containing only scalar values - in case an object is determined, an according
  `Phar\DeserializationException` will be thrown

```
$reader = new Phar\Reader('example.phar');
var_dump($reader->resolveContainer()->getManifest()->deserializeMetaData());
```

## Helper

* `Helper::determineBaseFile(string $path): string`: Determines base file that can be
  accessed using the regular file system. For instance the following path
  `phar:///home/user/bundle.phar/content.txt` would be resolved to
  `/home/user/bundle.phar`.
* `Helper::resetOpCache()`: Resets PHP's OPcache if enabled as work-around for
  issues in `include()` or `require()` calls and OPcache delivering wrong
  results. More details can be found in PHP's bug tracker, for instance like
  https://bugs.php.net/bug.php?id=66569

## Security Contact

In case of finding additional security issues in the TYPO3 project or in this
`PharStreamWrapper` package in particular, please get in touch with the
[TYPO3 Security Team](mailto:security@typo3.org).
