<?php
/**
 * This file is part of the Composer Merge plugin.
 *
 * Copyright (C) 2015 Bryan Davis, Wikimedia Foundation, and contributors
 *
 * This software may be modified and distributed under the terms of the MIT
 * license. See the LICENSE file for details.
 */

namespace Wikimedia\Composer\Merge;

use Wikimedia\Composer\Logger;

use Composer\Composer;
use Composer\Json\JsonFile;
use Composer\Package\BasePackage;
use Composer\Package\CompletePackage;
use Composer\Package\Link;
use Composer\Package\Loader\ArrayLoader;
use Composer\Package\RootAliasPackage;
use Composer\Package\RootPackage;
use Composer\Package\RootPackageInterface;
use Composer\Package\Version\VersionParser;
use UnexpectedValueException;

/**
 * Processing for a composer.json file that will be merged into
 * a RootPackageInterface
 *
 * @author Bryan Davis <bd808@bd808.com>
 */
class ExtraPackage
{

    /**
     * @var Composer $composer
     */
    protected $composer;

    /**
     * @var Logger $logger
     */
    protected $logger;

    /**
     * @var string $path
     */
    protected $path;

    /**
     * @var array $json
     */
    protected $json;

    /**
     * @var CompletePackage $package
     */
    protected $package;

    /**
     * @param string $path Path to composer.json file
     * @param Composer $composer
     * @param Logger $logger
     */
    public function __construct($path, Composer $composer, Logger $logger)
    {
        $this->path = $path;
        $this->composer = $composer;
        $this->logger = $logger;
        $this->json = $this->readPackageJson($path);
        $this->package = $this->loadPackage($this->json);
    }

    /**
     * Get list of additional packages to include if precessing recursively.
     *
     * @return array
     */
    public function getIncludes()
    {
        return isset($this->json['extra']['merge-plugin']['include']) ?
            $this->json['extra']['merge-plugin']['include'] : array();
    }

    /**
     * Get list of additional packages to require if precessing recursively.
     *
     * @return array
     */
    public function getRequires()
    {
        return isset($this->json['extra']['merge-plugin']['require']) ?
            $this->json['extra']['merge-plugin']['require'] : array();
    }

    /**
     * Read the contents of a composer.json style file into an array.
     *
     * The package contents are fixed up to be usable to create a Package
     * object by providing dummy "name" and "version" values if they have not
     * been provided in the file. This is consistent with the default root
     * package loading behavior of Composer.
     *
     * @param string $path
     * @return array
     */
    protected function readPackageJson($path)
    {
        $file = new JsonFile($path);
        $json = $file->read();
        if (!isset($json['name'])) {
            $json['name'] = 'merge-plugin/' .
                strtr($path, DIRECTORY_SEPARATOR, '-');
        }
        if (!isset($json['version'])) {
            $json['version'] = '1.0.0';
        }
        return $json;
    }

    /**
     * @return CompletePackage
     */
    protected function loadPackage($json)
    {
        $loader = new ArrayLoader();
        $package = $loader->load($json);
        // @codeCoverageIgnoreStart
        if (!$package instanceof CompletePackage) {
            throw new UnexpectedValueException(
                'Expected instance of CompletePackage, got ' .
                get_class($package)
            );
        }
        // @codeCoverageIgnoreEnd
        return $package;
    }

    /**
     * Merge this package into a RootPackageInterface
     *
     * @param RootPackageInterface $root
     * @param PluginState $state
     */
    public function mergeInto(RootPackageInterface $root, PluginState $state)
    {
        $this->addRepositories($root);

        $this->mergeRequires('require', $root, $state);
        if ($state->isDevMode()) {
            $this->mergeRequires('require-dev', $root, $state);
        }

        $this->mergePackageLinks('conflict', $root);
        $this->mergePackageLinks('replace', $root);
        $this->mergePackageLinks('provide', $root);

        $this->mergeSuggests($root);

        $this->mergeAutoload('autoload', $root);
        if ($state->isDevMode()) {
            $this->mergeAutoload('devAutoload', $root);
        }

        $this->mergeExtra($root, $state);
    }

    /**
     * Add a collection of repositories described by the given configuration
     * to the given package and the global repository manager.
     *
     * @param RootPackageInterface $root
     */
    protected function addRepositories(RootPackageInterface $root)
    {
        if (!isset($this->json['repositories'])) {
            return;
        }
        $repoManager = $this->composer->getRepositoryManager();
        $newRepos = array();

        foreach ($this->json['repositories'] as $repoJson) {
            if (!isset($repoJson['type'])) {
                continue;
            }
            $this->logger->info("Adding {$repoJson['type']} repository");
            $repo = $repoManager->createRepository(
                $repoJson['type'],
                $repoJson
            );
            $repoManager->addRepository($repo);
            $newRepos[] = $repo;
        }

        $unwrapped = self::unwrapIfNeeded($root, 'setRepositories');
        $unwrapped->setRepositories(array_merge(
            $newRepos,
            $root->getRepositories()
        ));
    }

    /**
     * Merge require or require-dev into a RootPackageInterface
     *
     * @param string $type 'require' or 'require-dev'
     * @param RootPackageInterface $root
     * @param PluginState $state
     */
    protected function mergeRequires(
        $type,
        RootPackageInterface $root,
        PluginState $state
    ) {
        $linkType = BasePackage::$supportedLinkTypes[$type];
        $getter = 'get' . ucfirst($linkType['method']);
        $setter = 'set' . ucfirst($linkType['method']);

        $requires = $this->package->{$getter}();
        if (empty($requires)) {
            return;
        }

        $this->mergeStabilityFlags($root, $requires);

        $requires = $this->replaceSelfVersionDependencies(
            $type,
            $requires,
            $root
        );

        $root->{$setter}($this->mergeOrDefer(
            $type,
            $root->{$getter}(),
            $requires,
            $state
        ));
    }

    /**
     * Merge two collections of package links and collect duplicates for
     * subsequent processing.
     *
     * @param string $type 'require' or 'require-dev'
     * @param array $origin Primary collection
     * @param array $merge Additional collection
     * @param PluginState $state
     * @return array Merged collection
     */
    protected function mergeOrDefer(
        $type,
        array $origin,
        array $merge,
        $state
    ) {
        $dups = array();
        foreach ($merge as $name => $link) {
            if (!isset($origin[$name]) || $state->replaceDuplicateLinks()) {
                $this->logger->info("Merging <comment>{$name}</comment>");
                $origin[$name] = $link;
            } else {
                // Defer to solver.
                $this->logger->info(
                    "Deferring duplicate <comment>{$name}</comment>"
                );
                $dups[] = $link;
            }
        }
        $state->addDuplicateLinks($type, $dups);
        return $origin;
    }

    /**
     * Merge autoload or autoload-dev into a RootPackageInterface
     *
     * @param string $type 'autoload' or 'devAutoload'
     * @param RootPackageInterface $root
     */
    protected function mergeAutoload($type, RootPackageInterface $root)
    {
        $getter = 'get' . ucfirst($type);
        $setter = 'set' . ucfirst($type);

        $autoload = $this->package->{$getter}();
        if (empty($autoload)) {
            return;
        }

        $unwrapped = self::unwrapIfNeeded($root, $setter);
        $unwrapped->{$setter}(array_merge_recursive(
            $root->{$getter}(),
            $this->fixRelativePaths($autoload)
        ));
    }

    /**
     * Fix a collection of paths that are relative to this package to be
     * relative to the base package.
     *
     * @param array $paths
     * @return array
     */
    protected function fixRelativePaths(array $paths)
    {
        $base = dirname($this->path);
        $base = ($base === '.') ? '' : "{$base}/";

        array_walk_recursive(
            $paths,
            function (&$path) use ($base) {
                $path = "{$base}{$path}";
            }
        );
        return $paths;
    }

    /**
     * Extract and merge stability flags from the given collection of
     * requires and merge them into a RootPackageInterface
     *
     * @param RootPackageInterface $root
     * @param array $requires
     */
    protected function mergeStabilityFlags(
        RootPackageInterface $root,
        array $requires
    ) {
        $flags = $root->getStabilityFlags();
        $sf = new StabilityFlags($flags, $root->getMinimumStability());

        $unwrapped = self::unwrapIfNeeded($root, 'setStabilityFlags');
        $unwrapped->setStabilityFlags(array_merge(
            $flags,
            $sf->extractAll($requires)
        ));
    }

    /**
     * Merge package links of the given type  into a RootPackageInterface
     *
     * @param string $type 'conflict', 'replace' or 'provide'
     * @param RootPackageInterface $root
     */
    protected function mergePackageLinks($type, RootPackageInterface $root)
    {
        $linkType = BasePackage::$supportedLinkTypes[$type];
        $getter = 'get' . ucfirst($linkType['method']);
        $setter = 'set' . ucfirst($linkType['method']);

        $links = $this->package->{$getter}();
        if (!empty($links)) {
            $unwrapped = self::unwrapIfNeeded($root, $setter);
            if ($root !== $unwrapped) {
                $this->logger->warning(
                    'This Composer version does not support ' .
                    "'{$type}' merging for aliased packages."
                );
            }
            $unwrapped->{$setter}(array_merge(
                $root->{$getter}(),
                $this->replaceSelfVersionDependencies($type, $links, $root)
            ));
        }
    }

    /**
     * Merge suggested packages into a RootPackageInterface
     *
     * @param RootPackageInterface $root
     */
    protected function mergeSuggests(RootPackageInterface $root)
    {
        $suggests = $this->package->getSuggests();
        if (!empty($suggests)) {
            $unwrapped = self::unwrapIfNeeded($root, 'setSuggests');
            $unwrapped->setSuggests(array_merge(
                $root->getSuggests(),
                $suggests
            ));
        }
    }

    /**
     * Merge extra config into a RootPackageInterface
     *
     * @param RootPackageInterface $root
     * @param PluginState $state
     */
    public function mergeExtra(RootPackageInterface $root, PluginState $state)
    {
        $extra = $this->package->getExtra();
        unset($extra['merge-plugin']);
        if (!$state->shouldMergeExtra() || empty($extra)) {
            return;
        }

        $rootExtra = $root->getExtra();
        $unwrapped = self::unwrapIfNeeded($root, 'setExtra');

        if ($state->replaceDuplicateLinks()) {
            $unwrapped->setExtra(
                array_merge($rootExtra, $extra)
            );

        } else {
            foreach (array_intersect(
                array_keys($extra),
                array_keys($rootExtra)
            ) as $key) {
                $this->logger->info(
                    "Ignoring duplicate <comment>{$key}</comment> in ".
                    "<comment>{$this->path}</comment> extra config."
                );
            }
            $unwrapped->setExtra(
                array_merge($extra, $rootExtra)
            );
        }
    }

    /**
     * Update Links with a 'self.version' constraint with the root package's
     * version.
     *
     * @param string $type Link type
     * @param array $links
     * @param RootPackageInterface $root
     * @return array
     */
    protected function replaceSelfVersionDependencies(
        $type,
        array $links,
        RootPackageInterface $root
    ) {
        $linkType = BasePackage::$supportedLinkTypes[$type];
        $version = $root->getVersion();
        $prettyVersion = $root->getPrettyVersion();
        $vp = new VersionParser();

        return array_map(
            function ($link) use ($linkType, $version, $prettyVersion, $vp) {
                if ('self.version' === $link->getPrettyConstraint()) {
                    return new Link(
                        $link->getSource(),
                        $link->getTarget(),
                        $vp->parseConstraints($version),
                        $linkType['description'],
                        $prettyVersion
                    );
                }
                return $link;
            },
            $links
        );
    }

    /**
     * Get a full featured Package from a RootPackageInterface.
     *
     * In Composer versions before 599ad77 the RootPackageInterface only
     * defines a sub-set of operations needed by composer-merge-plugin and
     * RootAliasPackage only implemented those methods defined by the
     * interface. Most of the unimplemented methods in RootAliasPackage can be
     * worked around because the getter methods that are implemented proxy to
     * the aliased package which we can modify by unwrapping. The exception
     * being modifying the 'conflicts', 'provides' and 'replaces' collections.
     * We have no way to actually modify those collections unfortunately in
     * older versions of Composer.
     *
     * @param RootPackageInterface $root
     * @param string $method Method needed
     * @return RootPackageInterface|RootPackage
     */
    public static function unwrapIfNeeded(
        RootPackageInterface $root,
        $method = 'setExtra'
    ) {
        if ($root instanceof RootAliasPackage &&
            !method_exists($root, $method)
        ) {
            // Unwrap and return the aliased RootPackage.
            $root = $root->getAliasOf();
        }
        return $root;
    }
}
// vim:sw=4:ts=4:sts=4:et:
