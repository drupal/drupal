<?php declare(strict_types = 1);

$ignoreErrors = [];
$ignoreErrors[] = [
	'message' => '#^Variable \\$data_path in empty\\(\\) always exists and is not falsy\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/../composer/Plugin/Scaffold/Operations/AppendOp.php',
];
$ignoreErrors[] = [
	'message' => '#^Variable \\$app_root might not be defined\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/assets/scaffold/files/default.settings.php',
];
$ignoreErrors[] = [
	'message' => '#^Variable \\$site_path might not be defined\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/assets/scaffold/files/default.settings.php',
];
$ignoreErrors[] = [
	'message' => '#^Variable \\$new_set_index might not be defined\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/includes/form.inc',
];
$ignoreErrors[] = [
	'message' => '#^Call to deprecated method getFromDriverName\\(\\) of class Drupal\\\\Core\\\\Extension\\\\DatabaseDriverList\\:
in drupal\\:10\\.2\\.0 and is removed from drupal\\:11\\.0\\.0\\. Use
  DatabaseDriverList\\:\\:get\\(\\) instead, passing a database driver namespace\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/includes/install.core.inc',
];
$ignoreErrors[] = [
	'message' => '#^Function install_config_download_translations\\(\\) should return string but return statement is missing\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/includes/install.core.inc',
];
$ignoreErrors[] = [
	'message' => '#^Function install_download_translation\\(\\) should return string but return statement is missing\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/includes/install.core.inc',
];
$ignoreErrors[] = [
	'message' => '#^Variable \\$items might not be defined\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/includes/theme.inc',
];
$ignoreErrors[] = [
	'message' => '#^Variable \\$custom_theme might not be defined\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/includes/theme.maintenance.inc',
];
$ignoreErrors[] = [
	'message' => '#^Call to deprecated method registerLoader\\(\\) of class Doctrine\\\\Common\\\\Annotations\\\\AnnotationRegistry\\:
This method is deprecated and will be removed in
            doctrine/annotations 2\\.0\\. Annotations will be autoloaded in 2\\.0\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/lib/Drupal/Component/Annotation/Plugin/Discovery/AnnotatedClassDiscovery.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to an undefined method Drupal\\\\Component\\\\Datetime\\\\DateTimePlus\\:\\:getTimeZone\\(\\)\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/lib/Drupal/Component/Datetime/DateTimePlus.php',
];
$ignoreErrors[] = [
	'message' => '#^Variable \\$value might not be defined\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/lib/Drupal/Component/Datetime/DateTimePlus.php',
];
$ignoreErrors[] = [
	'message' => '#^Usage of deprecated trait Drupal\\\\Component\\\\DependencyInjection\\\\ServiceIdHashTrait in class Drupal\\\\Component\\\\DependencyInjection\\\\Container\\:
in drupal\\:9\\.5\\.1 and is removed from drupal\\:11\\.0\\.0\\. Use the
  \'Drupal\\\\Component\\\\DependencyInjection\\\\ReverseContainer\' service instead\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/lib/Drupal/Component/DependencyInjection/Container.php',
];
$ignoreErrors[] = [
	'message' => '#^Variable \\$x0 might not be defined\\.$#',
	'count' => 4,
	'path' => __DIR__ . '/lib/Drupal/Component/Diff/DiffFormatter.php',
];
$ignoreErrors[] = [
	'message' => '#^Variable \\$xi in empty\\(\\) always exists and is not falsy\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/lib/Drupal/Component/Diff/DiffFormatter.php',
];
$ignoreErrors[] = [
	'message' => '#^Variable \\$y0 might not be defined\\.$#',
	'count' => 4,
	'path' => __DIR__ . '/lib/Drupal/Component/Diff/DiffFormatter.php',
];
$ignoreErrors[] = [
	'message' => '#^Variable \\$yi in empty\\(\\) always exists and is not falsy\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/lib/Drupal/Component/Diff/DiffFormatter.php',
];
$ignoreErrors[] = [
	'message' => '#^Access to an undefined property Drupal\\\\Component\\\\Diff\\\\Engine\\\\DiffEngine\\:\\:\\$in_seq\\.$#',
	'count' => 3,
	'path' => __DIR__ . '/lib/Drupal/Component/Diff/Engine/DiffEngine.php',
];
$ignoreErrors[] = [
	'message' => '#^Access to an undefined property Drupal\\\\Component\\\\Diff\\\\Engine\\\\DiffEngine\\:\\:\\$lcs\\.$#',
	'count' => 4,
	'path' => __DIR__ . '/lib/Drupal/Component/Diff/Engine/DiffEngine.php',
];
$ignoreErrors[] = [
	'message' => '#^Access to an undefined property Drupal\\\\Component\\\\Diff\\\\Engine\\\\DiffEngine\\:\\:\\$seq\\.$#',
	'count' => 7,
	'path' => __DIR__ . '/lib/Drupal/Component/Diff/Engine/DiffEngine.php',
];
$ignoreErrors[] = [
	'message' => '#^Access to an undefined property Drupal\\\\Component\\\\Diff\\\\Engine\\\\DiffEngine\\:\\:\\$xchanged\\.$#',
	'count' => 6,
	'path' => __DIR__ . '/lib/Drupal/Component/Diff/Engine/DiffEngine.php',
];
$ignoreErrors[] = [
	'message' => '#^Access to an undefined property Drupal\\\\Component\\\\Diff\\\\Engine\\\\DiffEngine\\:\\:\\$xind\\.$#',
	'count' => 2,
	'path' => __DIR__ . '/lib/Drupal/Component/Diff/Engine/DiffEngine.php',
];
$ignoreErrors[] = [
	'message' => '#^Access to an undefined property Drupal\\\\Component\\\\Diff\\\\Engine\\\\DiffEngine\\:\\:\\$xv\\.$#',
	'count' => 5,
	'path' => __DIR__ . '/lib/Drupal/Component/Diff/Engine/DiffEngine.php',
];
$ignoreErrors[] = [
	'message' => '#^Access to an undefined property Drupal\\\\Component\\\\Diff\\\\Engine\\\\DiffEngine\\:\\:\\$ychanged\\.$#',
	'count' => 5,
	'path' => __DIR__ . '/lib/Drupal/Component/Diff/Engine/DiffEngine.php',
];
$ignoreErrors[] = [
	'message' => '#^Access to an undefined property Drupal\\\\Component\\\\Diff\\\\Engine\\\\DiffEngine\\:\\:\\$yind\\.$#',
	'count' => 2,
	'path' => __DIR__ . '/lib/Drupal/Component/Diff/Engine/DiffEngine.php',
];
$ignoreErrors[] = [
	'message' => '#^Access to an undefined property Drupal\\\\Component\\\\Diff\\\\Engine\\\\DiffEngine\\:\\:\\$yv\\.$#',
	'count' => 5,
	'path' => __DIR__ . '/lib/Drupal/Component/Diff/Engine/DiffEngine.php',
];
$ignoreErrors[] = [
	'message' => '#^Variable \\$k might not be defined\\.$#',
	'count' => 4,
	'path' => __DIR__ . '/lib/Drupal/Component/Diff/Engine/DiffEngine.php',
];
$ignoreErrors[] = [
	'message' => '#^Variable \\$seps might not be defined\\.$#',
	'count' => 3,
	'path' => __DIR__ . '/lib/Drupal/Component/Diff/Engine/DiffEngine.php',
];
$ignoreErrors[] = [
	'message' => '#^Constructor of class Drupal\\\\Component\\\\FileCache\\\\NullFileCache has an unused parameter \\$cache_backend_class\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/lib/Drupal/Component/FileCache/NullFileCache.php',
];
$ignoreErrors[] = [
	'message' => '#^Constructor of class Drupal\\\\Component\\\\FileCache\\\\NullFileCache has an unused parameter \\$cache_backend_configuration\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/lib/Drupal/Component/FileCache/NullFileCache.php',
];
$ignoreErrors[] = [
	'message' => '#^Constructor of class Drupal\\\\Component\\\\FileCache\\\\NullFileCache has an unused parameter \\$collection\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/lib/Drupal/Component/FileCache/NullFileCache.php',
];
$ignoreErrors[] = [
	'message' => '#^Constructor of class Drupal\\\\Component\\\\FileCache\\\\NullFileCache has an unused parameter \\$prefix\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/lib/Drupal/Component/FileCache/NullFileCache.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Drupal\\\\Component\\\\Gettext\\\\PoMemoryWriter\\:\\:getHeader\\(\\) should return Drupal\\\\Component\\\\Gettext\\\\PoHeader but return statement is missing\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/lib/Drupal/Component/Gettext/PoMemoryWriter.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Drupal\\\\Component\\\\Gettext\\\\PoMemoryWriter\\:\\:getLangcode\\(\\) should return string but return statement is missing\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/lib/Drupal/Component/Gettext/PoMemoryWriter.php',
];
$ignoreErrors[] = [
	'message' => '#^Variable \\$log_vars might not be defined\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/lib/Drupal/Component/Gettext/PoStreamReader.php',
];
$ignoreErrors[] = [
	'message' => '#^Variable \\$permission might not be defined\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/lib/Drupal/Core/Access/AccessResult.php',
];
$ignoreErrors[] = [
	'message' => '#^Class Drupal\\\\Core\\\\Access\\\\CheckProvider implements deprecated interface Symfony\\\\Component\\\\DependencyInjection\\\\ContainerAwareInterface\\:
since Symfony 6\\.4, use dependency injection instead$#',
	'count' => 1,
	'path' => __DIR__ . '/lib/Drupal/Core/Access/CheckProvider.php',
];
$ignoreErrors[] = [
	'message' => '#^Usage of deprecated trait Symfony\\\\Component\\\\DependencyInjection\\\\ContainerAwareTrait in class Drupal\\\\Core\\\\Access\\\\CheckProvider\\:
since Symfony 6\\.4, use dependency injection instead$#',
	'count' => 1,
	'path' => __DIR__ . '/lib/Drupal/Core/Access/CheckProvider.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Drupal\\\\Core\\\\Access\\\\CsrfRequestHeaderAccessCheck\\:\\:applies\\(\\) should return bool but return statement is missing\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/lib/Drupal/Core/Access/CsrfRequestHeaderAccessCheck.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Drupal\\\\Core\\\\Executable\\\\ExecutableInterface\\:\\:execute\\(\\) invoked with 1 parameter, 0 required\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/lib/Drupal/Core/Action/ActionBase.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to method getDefinitions\\(\\) on an unknown class Drupal\\\\Core\\\\Plugin\\\\CategorizingPluginManagerTrait\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/lib/Drupal/Core/Action/ActionManager.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to method getSortedDefinitions\\(\\) on an unknown class Drupal\\\\Core\\\\Plugin\\\\CategorizingPluginManagerTrait\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/lib/Drupal/Core/Action/ActionManager.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Drupal\\\\Core\\\\Archiver\\\\ArchiverManager\\:\\:getInstance\\(\\) should return object\\|false but return statement is missing\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/lib/Drupal/Core/Archiver/ArchiverManager.php',
];
$ignoreErrors[] = [
	'message' => '#^Variable \\$group_keys might not be defined\\.$#',
	'count' => 2,
	'path' => __DIR__ . '/lib/Drupal/Core/Asset/CssCollectionGrouper.php',
];
$ignoreErrors[] = [
	'message' => '#^Variable \\$group_keys might not be defined\\.$#',
	'count' => 2,
	'path' => __DIR__ . '/lib/Drupal/Core/Asset/JsCollectionGrouper.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to deprecated constant REQUEST_TIME\\: Deprecated in drupal\\:8\\.3\\.0 and is removed from drupal\\:11\\.0\\.0\\. Use \\\\Drupal\\:\\:time\\(\\)\\-\\>getRequestTime\\(\\); $#',
	'count' => 1,
	'path' => __DIR__ . '/lib/Drupal/Core/Asset/JsCollectionRenderer.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to method getDefinitions\\(\\) on an unknown class Drupal\\\\Core\\\\Plugin\\\\CategorizingPluginManagerTrait\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/lib/Drupal/Core/Block/BlockManager.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to method getSortedDefinitions\\(\\) on an unknown class Drupal\\\\Core\\\\Plugin\\\\CategorizingPluginManagerTrait\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/lib/Drupal/Core/Block/BlockManager.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to deprecated constant REQUEST_TIME\\: Deprecated in drupal\\:8\\.3\\.0 and is removed from drupal\\:11\\.0\\.0\\. Use \\\\Drupal\\:\\:time\\(\\)\\-\\>getRequestTime\\(\\); $#',
	'count' => 3,
	'path' => __DIR__ . '/lib/Drupal/Core/Cache/ApcuBackend.php',
];
$ignoreErrors[] = [
	'message' => '#^Class Drupal\\\\Core\\\\Cache\\\\CacheFactory implements deprecated interface Symfony\\\\Component\\\\DependencyInjection\\\\ContainerAwareInterface\\:
since Symfony 6\\.4, use dependency injection instead$#',
	'count' => 1,
	'path' => __DIR__ . '/lib/Drupal/Core/Cache/CacheFactory.php',
];
$ignoreErrors[] = [
	'message' => '#^Usage of deprecated trait Symfony\\\\Component\\\\DependencyInjection\\\\ContainerAwareTrait in class Drupal\\\\Core\\\\Cache\\\\CacheFactory\\:
since Symfony 6\\.4, use dependency injection instead$#',
	'count' => 1,
	'path' => __DIR__ . '/lib/Drupal/Core/Cache/CacheFactory.php',
];
$ignoreErrors[] = [
	'message' => '#^Usage of deprecated trait Symfony\\\\Component\\\\DependencyInjection\\\\ContainerAwareTrait in class Drupal\\\\Core\\\\Cache\\\\CacheTagsInvalidator\\:
since Symfony 6\\.4, use dependency injection instead$#',
	'count' => 1,
	'path' => __DIR__ . '/lib/Drupal/Core/Cache/CacheTagsInvalidator.php',
];
$ignoreErrors[] = [
	'message' => '#^Usage of deprecated trait Symfony\\\\Component\\\\DependencyInjection\\\\ContainerAwareTrait in class Drupal\\\\Core\\\\Cache\\\\ChainedFastBackendFactory\\:
since Symfony 6\\.4, use dependency injection instead$#',
	'count' => 1,
	'path' => __DIR__ . '/lib/Drupal/Core/Cache/ChainedFastBackendFactory.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to deprecated constant REQUEST_TIME\\: Deprecated in drupal\\:8\\.3\\.0 and is removed from drupal\\:11\\.0\\.0\\. Use \\\\Drupal\\:\\:time\\(\\)\\-\\>getRequestTime\\(\\); $#',
	'count' => 4,
	'path' => __DIR__ . '/lib/Drupal/Core/Cache/DatabaseBackend.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to deprecated constant REQUEST_TIME\\: Deprecated in drupal\\:8\\.3\\.0 and is removed from drupal\\:11\\.0\\.0\\. Use \\\\Drupal\\:\\:time\\(\\)\\-\\>getRequestTime\\(\\); $#',
	'count' => 1,
	'path' => __DIR__ . '/lib/Drupal/Core/Cache/MemoryBackend.php',
];
$ignoreErrors[] = [
	'message' => '#^Constructor of class Drupal\\\\Core\\\\Cache\\\\NullBackend has an unused parameter \\$bin\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/lib/Drupal/Core/Cache/NullBackend.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to deprecated constant REQUEST_TIME\\: Deprecated in drupal\\:8\\.3\\.0 and is removed from drupal\\:11\\.0\\.0\\. Use \\\\Drupal\\:\\:time\\(\\)\\-\\>getRequestTime\\(\\); $#',
	'count' => 2,
	'path' => __DIR__ . '/lib/Drupal/Core/Cache/PhpBackend.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to method getDefinitions\\(\\) on an unknown class Drupal\\\\Core\\\\Plugin\\\\CategorizingPluginManagerTrait\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/lib/Drupal/Core/Condition/ConditionManager.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to method getSortedDefinitions\\(\\) on an unknown class Drupal\\\\Core\\\\Plugin\\\\CategorizingPluginManagerTrait\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/lib/Drupal/Core/Condition/ConditionManager.php',
];
$ignoreErrors[] = [
	'message' => '#^Fetching deprecated class constant RETURN_AFFECTED of class Drupal\\\\Core\\\\Database\\\\Database\\:
in drupal\\:9\\.4\\.0 and is removed from drupal\\:11\\.0\\.0\\. There is no
  replacement\\.$#',
	'count' => 4,
	'path' => __DIR__ . '/lib/Drupal/Core/Config/DatabaseStorage.php',
];
$ignoreErrors[] = [
	'message' => '#^Variable \\$value in isset\\(\\) always exists and is not nullable\\.$#',
	'count' => 2,
	'path' => __DIR__ . '/lib/Drupal/Core/Config/Entity/Query/Condition.php',
];
$ignoreErrors[] = [
	'message' => '#^Variable \\$theme_list might not be defined\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/lib/Drupal/Core/Config/ExtensionInstallStorage.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Drupal\\\\Core\\\\Config\\\\TypedConfigManager\\:\\:replaceVariable\\(\\) should return string but return statement is missing\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/lib/Drupal/Core/Config/TypedConfigManager.php',
];
$ignoreErrors[] = [
	'message' => '#^Fetching deprecated class constant RETURN_AFFECTED of class Drupal\\\\Core\\\\Database\\\\Database\\:
in drupal\\:9\\.4\\.0 and is removed from drupal\\:11\\.0\\.0\\. There is no
  replacement\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/lib/Drupal/Core/Database/Connection.php',
];
$ignoreErrors[] = [
	'message' => '#^Fetching deprecated class constant RETURN_INSERT_ID of class Drupal\\\\Core\\\\Database\\\\Database\\:
in drupal\\:9\\.4\\.0 and is removed from drupal\\:11\\.0\\.0\\. There is no
  replacement\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/lib/Drupal/Core/Database/Connection.php',
];
$ignoreErrors[] = [
	'message' => '#^Fetching deprecated class constant RETURN_NULL of class Drupal\\\\Core\\\\Database\\\\Database\\:
in drupal\\:9\\.4\\.0 and is removed from drupal\\:11\\.0\\.0\\. There is no
  replacement\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/lib/Drupal/Core/Database/Connection.php',
];
$ignoreErrors[] = [
	'message' => '#^Fetching deprecated class constant RETURN_STATEMENT of class Drupal\\\\Core\\\\Database\\\\Database\\:
in drupal\\:9\\.4\\.0 and is removed from drupal\\:11\\.0\\.0\\. There is no
  replacement\\.$#',
	'count' => 2,
	'path' => __DIR__ . '/lib/Drupal/Core/Database/Connection.php',
];
$ignoreErrors[] = [
	'message' => '#^Variable \\$statement might not be defined\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/lib/Drupal/Core/Database/Connection.php',
];
$ignoreErrors[] = [
	'message' => '#^Fetching deprecated class constant RETURN_AFFECTED of class Drupal\\\\Core\\\\Database\\\\Database\\:
in drupal\\:9\\.4\\.0 and is removed from drupal\\:11\\.0\\.0\\. There is no
  replacement\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/lib/Drupal/Core/Database/Query/Delete.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Drupal\\\\Core\\\\Database\\\\Query\\\\Delete\\:\\:execute\\(\\) should return int but return statement is missing\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/lib/Drupal/Core/Database/Query/Delete.php',
];
$ignoreErrors[] = [
	'message' => '#^Fetching deprecated class constant RETURN_INSERT_ID of class Drupal\\\\Core\\\\Database\\\\Database\\:
in drupal\\:9\\.4\\.0 and is removed from drupal\\:11\\.0\\.0\\. There is no
  replacement\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/lib/Drupal/Core/Database/Query/Insert.php',
];
$ignoreErrors[] = [
	'message' => '#^Fetching deprecated class constant RETURN_AFFECTED of class Drupal\\\\Core\\\\Database\\\\Database\\:
in drupal\\:9\\.4\\.0 and is removed from drupal\\:11\\.0\\.0\\. There is no
  replacement\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/lib/Drupal/Core/Database/Query/Merge.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Drupal\\\\Core\\\\Database\\\\Query\\\\Merge\\:\\:__toString\\(\\) should return string but return statement is missing\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/lib/Drupal/Core/Database/Query/Merge.php',
];
$ignoreErrors[] = [
	'message' => '#^Fetching deprecated class constant RETURN_STATEMENT of class Drupal\\\\Core\\\\Database\\\\Database\\:
in drupal\\:9\\.4\\.0 and is removed from drupal\\:11\\.0\\.0\\. There is no
  replacement\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/lib/Drupal/Core/Database/Query/Select.php',
];
$ignoreErrors[] = [
	'message' => '#^Fetching deprecated class constant RETURN_AFFECTED of class Drupal\\\\Core\\\\Database\\\\Database\\:
in drupal\\:9\\.4\\.0 and is removed from drupal\\:11\\.0\\.0\\. There is no
  replacement\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/lib/Drupal/Core/Database/Query/Truncate.php',
];
$ignoreErrors[] = [
	'message' => '#^Fetching deprecated class constant RETURN_AFFECTED of class Drupal\\\\Core\\\\Database\\\\Database\\:
in drupal\\:9\\.4\\.0 and is removed from drupal\\:11\\.0\\.0\\. There is no
  replacement\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/lib/Drupal/Core/Database/Query/Update.php',
];
$ignoreErrors[] = [
	'message' => '#^Fetching deprecated class constant RETURN_AFFECTED of class Drupal\\\\Core\\\\Database\\\\Database\\:
in drupal\\:9\\.4\\.0 and is removed from drupal\\:11\\.0\\.0\\. There is no
  replacement\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/lib/Drupal/Core/Database/Query/Upsert.php',
];
$ignoreErrors[] = [
	'message' => '#^Variable \\$affected_rows might not be defined\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/lib/Drupal/Core/Database/Query/Upsert.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to deprecated constant REQUEST_TIME\\: Deprecated in drupal\\:8\\.3\\.0 and is removed from drupal\\:11\\.0\\.0\\. Use \\\\Drupal\\:\\:time\\(\\)\\-\\>getRequestTime\\(\\); $#',
	'count' => 2,
	'path' => __DIR__ . '/lib/Drupal/Core/Datetime/DateHelper.php',
];
$ignoreErrors[] = [
	'message' => '#^Class Drupal\\\\Core\\\\DependencyInjection\\\\ClassResolver implements deprecated interface Symfony\\\\Component\\\\DependencyInjection\\\\ContainerAwareInterface\\:
since Symfony 6\\.4, use dependency injection instead$#',
	'count' => 1,
	'path' => __DIR__ . '/lib/Drupal/Core/DependencyInjection/ClassResolver.php',
];
$ignoreErrors[] = [
	'message' => '#^Usage of deprecated trait Symfony\\\\Component\\\\DependencyInjection\\\\ContainerAwareTrait in class Drupal\\\\Core\\\\DependencyInjection\\\\ClassResolver\\:
since Symfony 6\\.4, use dependency injection instead$#',
	'count' => 1,
	'path' => __DIR__ . '/lib/Drupal/Core/DependencyInjection/ClassResolver.php',
];
$ignoreErrors[] = [
	'message' => '#^Usage of deprecated trait Drupal\\\\Component\\\\DependencyInjection\\\\ServiceIdHashTrait in class Drupal\\\\Core\\\\DependencyInjection\\\\ContainerBuilder\\:
in drupal\\:9\\.5\\.1 and is removed from drupal\\:11\\.0\\.0\\. Use the
  \'Drupal\\\\Component\\\\DependencyInjection\\\\ReverseContainer\' service instead\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/lib/Drupal/Core/DependencyInjection/ContainerBuilder.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to deprecated method closing\\(\\) of class Drupal\\\\Component\\\\Diff\\\\WordLevelDiff\\:
in drupal\\:10\\.1\\.0 and is removed from drupal\\:11\\.0\\.0\\. There is no
  replacement\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/lib/Drupal/Core/Diff/DiffFormatter.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to deprecated method orig\\(\\) of class Drupal\\\\Component\\\\Diff\\\\WordLevelDiff\\:
in drupal\\:10\\.1\\.0 and is removed from drupal\\:11\\.0\\.0\\. There is no
  replacement\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/lib/Drupal/Core/Diff/DiffFormatter.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Drupal\\\\Core\\\\DrupalKernel\\:\\:discoverServiceProviders\\(\\) should return array but return statement is missing\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/lib/Drupal/Core/DrupalKernel.php',
];
$ignoreErrors[] = [
	'message' => '#^Variable \\$container might not be defined\\.$#',
	'count' => 2,
	'path' => __DIR__ . '/lib/Drupal/Core/DrupalKernel.php',
];
$ignoreErrors[] = [
	'message' => '#^Variable \\$container_definition might not be defined\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/lib/Drupal/Core/DrupalKernel.php',
];
$ignoreErrors[] = [
	'message' => '#^Interface Drupal\\\\Core\\\\DrupalKernelInterface extends deprecated interface Symfony\\\\Component\\\\DependencyInjection\\\\ContainerAwareInterface\\:
since Symfony 6\\.4, use dependency injection instead$#',
	'count' => 1,
	'path' => __DIR__ . '/lib/Drupal/Core/DrupalKernelInterface.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Drupal\\\\Core\\\\Entity\\\\ContentEntityConfirmFormBase\\:\\:save\\(\\) should return int but return statement is missing\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/lib/Drupal/Core/Entity/ContentEntityConfirmFormBase.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Drupal\\\\Core\\\\Entity\\\\ContentEntityNullStorage\\:\\:doLoadMultiple\\(\\) should return array\\<Drupal\\\\Core\\\\Entity\\\\EntityInterface\\> but return statement is missing\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/lib/Drupal/Core/Entity/ContentEntityNullStorage.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Drupal\\\\Core\\\\Entity\\\\ContentEntityNullStorage\\:\\:doSave\\(\\) should return bool\\|int but return statement is missing\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/lib/Drupal/Core/Entity/ContentEntityNullStorage.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Drupal\\\\Core\\\\Entity\\\\ContentEntityNullStorage\\:\\:has\\(\\) should return bool but return statement is missing\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/lib/Drupal/Core/Entity/ContentEntityNullStorage.php',
];
$ignoreErrors[] = [
	'message' => '#^Variable \\$current_affected in isset\\(\\) always exists and is not nullable\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/lib/Drupal/Core/Entity/ContentEntityStorageBase.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Drupal\\\\Core\\\\Entity\\\\Controller\\\\EntityController\\:\\:deleteTitle\\(\\) should return string but return statement is missing\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/lib/Drupal/Core/Entity/Controller/EntityController.php',
];
$ignoreErrors[] = [
	'message' => '#^Variable \\$candidate_ids might not be defined\\.$#',
	'count' => 2,
	'path' => __DIR__ . '/lib/Drupal/Core/Entity/Entity/EntityFormDisplay.php',
];
$ignoreErrors[] = [
	'message' => '#^Variable \\$string in isset\\(\\) always exists and is not nullable\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/lib/Drupal/Core/Entity/EntityAutocompleteMatcher.php',
];
$ignoreErrors[] = [
	'message' => '#^Access to an undefined property Drupal\\\\Core\\\\Entity\\\\EntityBase\\:\\:\\$id\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/lib/Drupal/Core/Entity/EntityBase.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Drupal\\\\Core\\\\Entity\\\\EntityConfirmFormBase\\:\\:save\\(\\) should return int but return statement is missing\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/lib/Drupal/Core/Entity/EntityConfirmFormBase.php',
];
$ignoreErrors[] = [
	'message' => '#^Access to an undefined property Drupal\\\\Core\\\\Entity\\\\EntityDisplayBase\\:\\:\\$_serializedKeys\\.$#',
	'count' => 2,
	'path' => __DIR__ . '/lib/Drupal/Core/Entity/EntityDisplayBase.php',
];
$ignoreErrors[] = [
	'message' => '#^Class Drupal\\\\Core\\\\Entity\\\\EntityTypeManager implements deprecated interface Symfony\\\\Component\\\\DependencyInjection\\\\ContainerAwareInterface\\:
since Symfony 6\\.4, use dependency injection instead$#',
	'count' => 1,
	'path' => __DIR__ . '/lib/Drupal/Core/Entity/EntityTypeManager.php',
];
$ignoreErrors[] = [
	'message' => '#^Usage of deprecated trait Symfony\\\\Component\\\\DependencyInjection\\\\ContainerAwareTrait in class Drupal\\\\Core\\\\Entity\\\\EntityTypeManager\\:
since Symfony 6\\.4, use dependency injection instead$#',
	'count' => 1,
	'path' => __DIR__ . '/lib/Drupal/Core/Entity/EntityTypeManager.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Drupal\\\\Core\\\\Entity\\\\KeyValueStore\\\\KeyValueContentEntityStorage\\:\\:createTranslation\\(\\) should return Drupal\\\\Core\\\\Entity\\\\ContentEntityInterface but return statement is missing\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/lib/Drupal/Core/Entity/KeyValueStore/KeyValueContentEntityStorage.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Drupal\\\\Core\\\\Entity\\\\KeyValueStore\\\\KeyValueContentEntityStorage\\:\\:createWithSampleValues\\(\\) should return Drupal\\\\Core\\\\Entity\\\\FieldableEntityInterface but return statement is missing\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/lib/Drupal/Core/Entity/KeyValueStore/KeyValueContentEntityStorage.php',
];
$ignoreErrors[] = [
	'message' => '#^Variable \\$selected_bundles might not be defined\\.$#',
	'count' => 2,
	'path' => __DIR__ . '/lib/Drupal/Core/Entity/Plugin/EntityReferenceSelection/DefaultSelection.php',
];
$ignoreErrors[] = [
	'message' => '#^Variable \\$value in isset\\(\\) always exists and is not nullable\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/lib/Drupal/Core/Entity/Plugin/Validation/Constraint/ReferenceAccessConstraintValidator.php',
];
$ignoreErrors[] = [
	'message' => '#^Variable \\$value in isset\\(\\) always exists and is not nullable\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/lib/Drupal/Core/Entity/Plugin/Validation/Constraint/ValidReferenceConstraintValidator.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Drupal\\\\Core\\\\Entity\\\\Query\\\\QueryBase\\:\\:getClass\\(\\) should return string but return statement is missing\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/lib/Drupal/Core/Entity/Query/QueryBase.php',
];
$ignoreErrors[] = [
	'message' => '#^Variable \\$next_index_prefix might not be defined\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/lib/Drupal/Core/Entity/Query/Sql/Tables.php',
];
$ignoreErrors[] = [
	'message' => '#^Variable \\$relationship_specifier might not be defined\\.$#',
	'count' => 5,
	'path' => __DIR__ . '/lib/Drupal/Core/Entity/Query/Sql/Tables.php',
];
$ignoreErrors[] = [
	'message' => '#^Fetching deprecated class constant RETURN_INSERT_ID of class Drupal\\\\Core\\\\Database\\\\Database\\:
in drupal\\:9\\.4\\.0 and is removed from drupal\\:11\\.0\\.0\\. There is no
  replacement\\.$#',
	'count' => 2,
	'path' => __DIR__ . '/lib/Drupal/Core/Entity/Sql/SqlContentEntityStorage.php',
];
$ignoreErrors[] = [
	'message' => '#^Variable \\$revision_query might not be defined\\.$#',
	'count' => 2,
	'path' => __DIR__ . '/lib/Drupal/Core/Entity/Sql/SqlContentEntityStorage.php',
];
$ignoreErrors[] = [
	'message' => '#^Variable \\$transaction in isset\\(\\) always exists and is not nullable\\.$#',
	'count' => 2,
	'path' => __DIR__ . '/lib/Drupal/Core/Entity/Sql/SqlContentEntityStorage.php',
];
$ignoreErrors[] = [
	'message' => '#^Variable \\$initial_storage_value in empty\\(\\) always exists and is not falsy\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/lib/Drupal/Core/Entity/Sql/SqlContentEntityStorageSchema.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to deprecated constant REQUEST_TIME\\: Deprecated in drupal\\:8\\.3\\.0 and is removed from drupal\\:11\\.0\\.0\\. Use \\\\Drupal\\:\\:time\\(\\)\\-\\>getRequestTime\\(\\); $#',
	'count' => 2,
	'path' => __DIR__ . '/lib/Drupal/Core/EventSubscriber/FinishResponseSubscriber.php',
];
$ignoreErrors[] = [
	'message' => '#^Class Drupal\\\\Core\\\\EventSubscriber\\\\KernelDestructionSubscriber implements deprecated interface Symfony\\\\Component\\\\DependencyInjection\\\\ContainerAwareInterface\\:
since Symfony 6\\.4, use dependency injection instead$#',
	'count' => 1,
	'path' => __DIR__ . '/lib/Drupal/Core/EventSubscriber/KernelDestructionSubscriber.php',
];
$ignoreErrors[] = [
	'message' => '#^Usage of deprecated trait Symfony\\\\Component\\\\DependencyInjection\\\\ContainerAwareTrait in class Drupal\\\\Core\\\\EventSubscriber\\\\KernelDestructionSubscriber\\:
since Symfony 6\\.4, use dependency injection instead$#',
	'count' => 1,
	'path' => __DIR__ . '/lib/Drupal/Core/EventSubscriber/KernelDestructionSubscriber.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to deprecated method getFromDriverName\\(\\) of class Drupal\\\\Core\\\\Extension\\\\DatabaseDriverList\\:
in drupal\\:10\\.2\\.0 and is removed from drupal\\:11\\.0\\.0\\. Use
  DatabaseDriverList\\:\\:get\\(\\) instead, passing a database driver namespace\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/lib/Drupal/Core/Extension/DatabaseDriverList.php',
];
$ignoreErrors[] = [
	'message' => '#^Variable \\$minor_version might not be defined\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/lib/Drupal/Core/Extension/ExtensionVersion.php',
];
$ignoreErrors[] = [
	'message' => '#^Variable \\$callback in isset\\(\\) always exists and is not nullable\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/lib/Drupal/Core/Field/FieldDefinition.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Drupal\\\\Core\\\\Field\\\\FieldItemBase\\:\\:generateSampleValue\\(\\) should return array but return statement is missing\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/lib/Drupal/Core/Field/FieldItemBase.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Drupal\\\\Core\\\\Field\\\\FieldItemBase\\:\\:postSave\\(\\) should return bool but return statement is missing\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/lib/Drupal/Core/Field/FieldItemBase.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Drupal\\\\Core\\\\Field\\\\FieldItemList\\:\\:defaultValuesForm\\(\\) should return array but return statement is missing\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/lib/Drupal/Core/Field/FieldItemList.php',
];
$ignoreErrors[] = [
	'message' => '#^Variable \\$values might not be defined\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/lib/Drupal/Core/Field/FieldItemList.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to method getDefinitions\\(\\) on an unknown class Drupal\\\\Core\\\\Plugin\\\\CategorizingPluginManagerTrait\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/lib/Drupal/Core/Field/FieldTypePluginManager.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to method getSortedDefinitions\\(\\) on an unknown class Drupal\\\\Core\\\\Plugin\\\\CategorizingPluginManagerTrait\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/lib/Drupal/Core/Field/FieldTypePluginManager.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to deprecated constant REQUEST_TIME\\: Deprecated in drupal\\:8\\.3\\.0 and is removed from drupal\\:11\\.0\\.0\\. Use \\\\Drupal\\:\\:time\\(\\)\\-\\>getRequestTime\\(\\); $#',
	'count' => 1,
	'path' => __DIR__ . '/lib/Drupal/Core/Field/Plugin/Field/FieldFormatter/TimestampFormatter.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to deprecated constant REQUEST_TIME\\: Deprecated in drupal\\:8\\.3\\.0 and is removed from drupal\\:11\\.0\\.0\\. Use \\\\Drupal\\:\\:time\\(\\)\\-\\>getRequestTime\\(\\); $#',
	'count' => 2,
	'path' => __DIR__ . '/lib/Drupal/Core/Field/Plugin/Field/FieldType/ChangedItem.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to deprecated constant REQUEST_TIME\\: Deprecated in drupal\\:8\\.3\\.0 and is removed from drupal\\:11\\.0\\.0\\. Use \\\\Drupal\\:\\:time\\(\\)\\-\\>getRequestTime\\(\\); $#',
	'count' => 1,
	'path' => __DIR__ . '/lib/Drupal/Core/Field/Plugin/Field/FieldType/CreatedItem.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Drupal\\\\Core\\\\Field\\\\Plugin\\\\Field\\\\FieldType\\\\EntityReferenceItem\\:\\:generateSampleValue\\(\\) should return array but return statement is missing\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/lib/Drupal/Core/Field/Plugin/Field/FieldType/EntityReferenceItem.php',
];
$ignoreErrors[] = [
	'message' => '#^Access to an undefined property Drupal\\\\Core\\\\Field\\\\Plugin\\\\Field\\\\FieldType\\\\NumericItemBase\\:\\:\\$value\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/lib/Drupal/Core/Field/Plugin/Field/FieldType/NumericItemBase.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Drupal\\\\Core\\\\FileTransfer\\\\FileTransfer\\:\\:__get\\(\\) should return bool\\|string but return statement is missing\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/lib/Drupal/Core/FileTransfer/FileTransfer.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to deprecated constant REQUEST_TIME\\: Deprecated in drupal\\:8\\.3\\.0 and is removed from drupal\\:11\\.0\\.0\\. Use \\\\Drupal\\:\\:time\\(\\)\\-\\>getRequestTime\\(\\); $#',
	'count' => 4,
	'path' => __DIR__ . '/lib/Drupal/Core/Flood/DatabaseBackend.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Drupal\\\\Core\\\\Form\\\\FormBuilder\\:\\:setInvalidTokenError\\(\\) should return \\$this\\(Drupal\\\\Core\\\\Form\\\\FormBuilder\\) but return statement is missing\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/lib/Drupal/Core/Form/FormBuilder.php',
];
$ignoreErrors[] = [
	'message' => '#^Variable \\$form_id in isset\\(\\) always exists and is not nullable\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/lib/Drupal/Core/Form/FormBuilder.php',
];
$ignoreErrors[] = [
	'message' => '#^Variable \\$form in isset\\(\\) always exists and is not nullable\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/lib/Drupal/Core/Form/FormCache.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Drupal\\\\Core\\\\Form\\\\FormValidator\\:\\:setInvalidTokenError\\(\\) should return \\$this\\(Drupal\\\\Core\\\\Form\\\\FormValidator\\) but return statement is missing\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/lib/Drupal/Core/Form/FormValidator.php',
];
$ignoreErrors[] = [
	'message' => '#^Variable \\$is_empty_multiple might not be defined\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/lib/Drupal/Core/Form/FormValidator.php',
];
$ignoreErrors[] = [
	'message' => '#^Variable \\$is_empty_null might not be defined\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/lib/Drupal/Core/Form/FormValidator.php',
];
$ignoreErrors[] = [
	'message' => '#^Variable \\$is_empty_string might not be defined\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/lib/Drupal/Core/Form/FormValidator.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to deprecated constant REQUEST_TIME\\: Deprecated in drupal\\:8\\.3\\.0 and is removed from drupal\\:11\\.0\\.0\\. Use \\\\Drupal\\:\\:time\\(\\)\\-\\>getRequestTime\\(\\); $#',
	'count' => 4,
	'path' => __DIR__ . '/lib/Drupal/Core/KeyValueStore/DatabaseStorageExpirable.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to deprecated constant REQUEST_TIME\\: Deprecated in drupal\\:8\\.3\\.0 and is removed from drupal\\:11\\.0\\.0\\. Use \\\\Drupal\\:\\:time\\(\\)\\-\\>getRequestTime\\(\\); $#',
	'count' => 1,
	'path' => __DIR__ . '/lib/Drupal/Core/KeyValueStore/KeyValueDatabaseExpirableFactory.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Drupal\\\\Core\\\\KeyValueStore\\\\NullStorageExpirable\\:\\:setIfNotExists\\(\\) should return bool but return statement is missing\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/lib/Drupal/Core/KeyValueStore/NullStorageExpirable.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Drupal\\\\Core\\\\KeyValueStore\\\\NullStorageExpirable\\:\\:setWithExpireIfNotExists\\(\\) should return bool but return statement is missing\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/lib/Drupal/Core/KeyValueStore/NullStorageExpirable.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Drupal\\\\Core\\\\Lock\\\\NullLockBackend\\:\\:wait\\(\\) should return bool but return statement is missing\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/lib/Drupal/Core/Lock/NullLockBackend.php',
];
$ignoreErrors[] = [
	'message' => '#^Class Drupal\\\\Core\\\\Logger\\\\LoggerChannelFactory implements deprecated interface Symfony\\\\Component\\\\DependencyInjection\\\\ContainerAwareInterface\\:
since Symfony 6\\.4, use dependency injection instead$#',
	'count' => 1,
	'path' => __DIR__ . '/lib/Drupal/Core/Logger/LoggerChannelFactory.php',
];
$ignoreErrors[] = [
	'message' => '#^Usage of deprecated trait Symfony\\\\Component\\\\DependencyInjection\\\\ContainerAwareTrait in class Drupal\\\\Core\\\\Logger\\\\LoggerChannelFactory\\:
since Symfony 6\\.4, use dependency injection instead$#',
	'count' => 1,
	'path' => __DIR__ . '/lib/Drupal/Core/Logger/LoggerChannelFactory.php',
];
$ignoreErrors[] = [
	'message' => '#^Variable \\$parent in isset\\(\\) always exists and is not nullable\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/lib/Drupal/Core/Menu/Form/MenuLinkDefaultForm.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Drupal\\\\Core\\\\Menu\\\\MenuLinkManager\\:\\:getInstance\\(\\) should return object\\|false but return statement is missing\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/lib/Drupal/Core/Menu/MenuLinkManager.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Drupal\\\\Core\\\\Menu\\\\MenuLinkManager\\:\\:menuNameInUse\\(\\) should return bool but return statement is missing\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/lib/Drupal/Core/Menu/MenuLinkManager.php',
];
$ignoreErrors[] = [
	'message' => '#^Missing cache backend declaration for performance\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/lib/Drupal/Core/Menu/MenuLinkManager.php',
];
$ignoreErrors[] = [
	'message' => '#^Fetching deprecated class constant RETURN_INSERT_ID of class Drupal\\\\Core\\\\Database\\\\Database\\:
in drupal\\:9\\.4\\.0 and is removed from drupal\\:11\\.0\\.0\\. There is no
  replacement\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/lib/Drupal/Core/Menu/MenuTreeStorage.php',
];
$ignoreErrors[] = [
	'message' => '#^Variable \\$transaction in isset\\(\\) always exists and is not nullable\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/lib/Drupal/Core/Menu/MenuTreeStorage.php',
];
$ignoreErrors[] = [
	'message' => '#^Missing cache backend declaration for performance\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/lib/Drupal/Core/Plugin/DefaultPluginManager.php',
];
$ignoreErrors[] = [
	'message' => '#^Constructor of class Drupal\\\\Core\\\\Queue\\\\Memory has an unused parameter \\$name\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/lib/Drupal/Core/Queue/Memory.php',
];
$ignoreErrors[] = [
	'message' => '#^Class Drupal\\\\Core\\\\Queue\\\\QueueFactory implements deprecated interface Symfony\\\\Component\\\\DependencyInjection\\\\ContainerAwareInterface\\:
since Symfony 6\\.4, use dependency injection instead$#',
	'count' => 1,
	'path' => __DIR__ . '/lib/Drupal/Core/Queue/QueueFactory.php',
];
$ignoreErrors[] = [
	'message' => '#^Usage of deprecated trait Symfony\\\\Component\\\\DependencyInjection\\\\ContainerAwareTrait in class Drupal\\\\Core\\\\Queue\\\\QueueFactory\\:
since Symfony 6\\.4, use dependency injection instead$#',
	'count' => 1,
	'path' => __DIR__ . '/lib/Drupal/Core/Queue/QueueFactory.php',
];
$ignoreErrors[] = [
	'message' => '#^Variable \\$sort in isset\\(\\) always exists and is not nullable\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/lib/Drupal/Core/Render/Element/RenderElement.php',
];
$ignoreErrors[] = [
	'message' => '#^Variable \\$output in empty\\(\\) always exists and is not falsy\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/lib/Drupal/Core/Render/MainContent/AjaxRenderer.php',
];
$ignoreErrors[] = [
	'message' => '#^Variable \\$page_bottom in empty\\(\\) always exists and is always falsy\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/lib/Drupal/Core/Render/MainContent/HtmlRenderer.php',
];
$ignoreErrors[] = [
	'message' => '#^Variable \\$page_top in empty\\(\\) always exists and is always falsy\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/lib/Drupal/Core/Render/MainContent/HtmlRenderer.php',
];
$ignoreErrors[] = [
	'message' => '#^Variable \\$transaction in isset\\(\\) always exists and is not nullable\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/lib/Drupal/Core/Routing/MatcherDumper.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to deprecated constant REQUEST_TIME\\: Deprecated in drupal\\:8\\.3\\.0 and is removed from drupal\\:11\\.0\\.0\\. Use \\\\Drupal\\:\\:time\\(\\)\\-\\>getRequestTime\\(\\); $#',
	'count' => 2,
	'path' => __DIR__ . '/lib/Drupal/Core/Session/SessionHandler.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to deprecated constant REQUEST_TIME\\: Deprecated in drupal\\:8\\.3\\.0 and is removed from drupal\\:11\\.0\\.0\\. Use \\\\Drupal\\:\\:time\\(\\)\\-\\>getRequestTime\\(\\); $#',
	'count' => 1,
	'path' => __DIR__ . '/lib/Drupal/Core/Session/SessionManager.php',
];
$ignoreErrors[] = [
	'message' => '#^Variable \\$current might not be defined\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/lib/Drupal/Core/Site/SettingsEditor.php',
];
$ignoreErrors[] = [
	'message' => '#^Variable \\$index might not be defined\\.$#',
	'count' => 3,
	'path' => __DIR__ . '/lib/Drupal/Core/Site/SettingsEditor.php',
];
$ignoreErrors[] = [
	'message' => '#^Usage of deprecated trait Symfony\\\\Component\\\\DependencyInjection\\\\ContainerAwareTrait in class Drupal\\\\Core\\\\StackMiddleware\\\\Session\\:
since Symfony 6\\.4, use dependency injection instead$#',
	'count' => 1,
	'path' => __DIR__ . '/lib/Drupal/Core/StackMiddleware/Session.php',
];
$ignoreErrors[] = [
	'message' => '#^Class Drupal\\\\Core\\\\StreamWrapper\\\\StreamWrapperManager implements deprecated interface Symfony\\\\Component\\\\DependencyInjection\\\\ContainerAwareInterface\\:
since Symfony 6\\.4, use dependency injection instead$#',
	'count' => 1,
	'path' => __DIR__ . '/lib/Drupal/Core/StreamWrapper/StreamWrapperManager.php',
];
$ignoreErrors[] = [
	'message' => '#^Usage of deprecated trait Symfony\\\\Component\\\\DependencyInjection\\\\ContainerAwareTrait in class Drupal\\\\Core\\\\StreamWrapper\\\\StreamWrapperManager\\:
since Symfony 6\\.4, use dependency injection instead$#',
	'count' => 1,
	'path' => __DIR__ . '/lib/Drupal/Core/StreamWrapper/StreamWrapperManager.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Drupal\\\\Core\\\\Template\\\\AttributeValueBase\\:\\:render\\(\\) should return string but return statement is missing\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/lib/Drupal/Core/Template/AttributeValueBase.php',
];
$ignoreErrors[] = [
	'message' => '#^Constructor of class Drupal\\\\Core\\\\Template\\\\TwigEnvironment has an unused parameter \\$root\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/lib/Drupal/Core/Template/TwigEnvironment.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to deprecated constant REQUEST_TIME\\: Deprecated in drupal\\:8\\.3\\.0 and is removed from drupal\\:11\\.0\\.0\\. Use \\\\Drupal\\:\\:time\\(\\)\\-\\>getRequestTime\\(\\); $#',
	'count' => 1,
	'path' => __DIR__ . '/lib/Drupal/Core/Template/TwigPhpStorageCache.php',
];
$ignoreErrors[] = [
	'message' => '#^Constructor of class Drupal\\\\Core\\\\Test\\\\TestRunnerKernel has an unused parameter \\$allow_dumping\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/lib/Drupal/Core/Test/TestRunnerKernel.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Drupal\\\\Core\\\\Theme\\\\ThemeInitialization\\:\\:resolveStyleSheetPlaceholders\\(\\) should return string but return statement is missing\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/lib/Drupal/Core/Theme/ThemeInitialization.php',
];
$ignoreErrors[] = [
	'message' => '#^Variable \\$candidate might not be defined\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/lib/Drupal/Core/Theme/ThemeManager.php',
];
$ignoreErrors[] = [
	'message' => '#^Variable \\$theme_engine in isset\\(\\) always exists and is not nullable\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/lib/Drupal/Core/Theme/ThemeManager.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Drupal\\\\Core\\\\TypedData\\\\ListDataDefinition\\:\\:setDataType\\(\\) should return static\\(Drupal\\\\Core\\\\TypedData\\\\ListDataDefinition\\) but return statement is missing\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/lib/Drupal/Core/TypedData/ListDataDefinition.php',
];
$ignoreErrors[] = [
	'message' => '#^Access to an undefined property Drupal\\\\Core\\\\TypedData\\\\TypedData\\:\\:\\$value\\.$#',
	'count' => 2,
	'path' => __DIR__ . '/lib/Drupal/Core/TypedData/TypedData.php',
];
$ignoreErrors[] = [
	'message' => '#^Variable \\$cache_key in isset\\(\\) always exists and is not nullable\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/lib/Drupal/Core/TypedData/Validation/RecursiveContextualValidator.php',
];
$ignoreErrors[] = [
	'message' => '#^Class Drupal\\\\Core\\\\Update\\\\UpdateHookRegistryFactory implements deprecated interface Symfony\\\\Component\\\\DependencyInjection\\\\ContainerAwareInterface\\:
since Symfony 6\\.4, use dependency injection instead$#',
	'count' => 1,
	'path' => __DIR__ . '/lib/Drupal/Core/Update/UpdateHookRegistryFactory.php',
];
$ignoreErrors[] = [
	'message' => '#^Usage of deprecated trait Symfony\\\\Component\\\\DependencyInjection\\\\ContainerAwareTrait in class Drupal\\\\Core\\\\Update\\\\UpdateHookRegistryFactory\\:
since Symfony 6\\.4, use dependency injection instead$#',
	'count' => 1,
	'path' => __DIR__ . '/lib/Drupal/Core/Update/UpdateHookRegistryFactory.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Drupal\\\\Core\\\\Update\\\\UpdateKernel\\:\\:discoverServiceProviders\\(\\) should return array but return statement is missing\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/lib/Drupal/Core/Update/UpdateKernel.php',
];
$ignoreErrors[] = [
	'message' => '#^Class Drupal\\\\Core\\\\Update\\\\UpdateRegistryFactory implements deprecated interface Symfony\\\\Component\\\\DependencyInjection\\\\ContainerAwareInterface\\:
since Symfony 6\\.4, use dependency injection instead$#',
	'count' => 1,
	'path' => __DIR__ . '/lib/Drupal/Core/Update/UpdateRegistryFactory.php',
];
$ignoreErrors[] = [
	'message' => '#^Usage of deprecated trait Symfony\\\\Component\\\\DependencyInjection\\\\ContainerAwareTrait in class Drupal\\\\Core\\\\Update\\\\UpdateRegistryFactory\\:
since Symfony 6\\.4, use dependency injection instead$#',
	'count' => 1,
	'path' => __DIR__ . '/lib/Drupal/Core/Update/UpdateRegistryFactory.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Drupal\\\\Core\\\\Updater\\\\Module\\:\\:postUpdateTasks\\(\\) should return array but return statement is missing\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/lib/Drupal/Core/Updater/Module.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Drupal\\\\action\\\\Form\\\\ActionFormBase\\:\\:save\\(\\) should return int but return statement is missing\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/modules/action/src/Form/ActionFormBase.php',
];
$ignoreErrors[] = [
	'message' => '#^Variable \\$position might not be defined\\.$#',
	'count' => 4,
	'path' => __DIR__ . '/modules/block/tests/src/Functional/BlockRenderOrderTest.php',
];
$ignoreErrors[] = [
	'message' => '#^Variable \\$edit might not be defined\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/modules/block/tests/src/Functional/BlockUiTest.php',
];
$ignoreErrors[] = [
	'message' => '#^Variable \\$dependency in empty\\(\\) always exists and is not falsy\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/modules/block_content/src/BlockContentAccessControlHandler.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Drupal\\\\block_content\\\\BlockContentForm\\:\\:save\\(\\) should return int but return statement is missing\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/modules/block_content/src/BlockContentForm.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Drupal\\\\block_content\\\\BlockContentTypeForm\\:\\:save\\(\\) should return int but return statement is missing\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/modules/block_content/src/BlockContentTypeForm.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to deprecated constant REQUEST_TIME\\: Deprecated in drupal\\:8\\.3\\.0 and is removed from drupal\\:11\\.0\\.0\\. Use \\\\Drupal\\:\\:time\\(\\)\\-\\>getRequestTime\\(\\); $#',
	'count' => 1,
	'path' => __DIR__ . '/modules/block_content/tests/src/Functional/BlockContentRevisionsTest.php',
];
$ignoreErrors[] = [
	'message' => '#^Variable \\$loaded might not be defined\\.$#',
	'count' => 9,
	'path' => __DIR__ . '/modules/block_content/tests/src/Functional/BlockContentRevisionsTest.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to deprecated constant REQUEST_TIME\\: Deprecated in drupal\\:8\\.3\\.0 and is removed from drupal\\:11\\.0\\.0\\. Use \\\\Drupal\\:\\:time\\(\\)\\-\\>getRequestTime\\(\\); $#',
	'count' => 1,
	'path' => __DIR__ . '/modules/block_content/tests/src/Functional/BlockContentSaveTest.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to deprecated constant REQUEST_TIME\\: Deprecated in drupal\\:8\\.3\\.0 and is removed from drupal\\:11\\.0\\.0\\. Use \\\\Drupal\\:\\:time\\(\\)\\-\\>getRequestTime\\(\\); $#',
	'count' => 2,
	'path' => __DIR__ . '/modules/block_content/tests/src/Kernel/Migrate/d6/MigrateBlockContentTest.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to deprecated constant REQUEST_TIME\\: Deprecated in drupal\\:8\\.3\\.0 and is removed from drupal\\:11\\.0\\.0\\. Use \\\\Drupal\\:\\:time\\(\\)\\-\\>getRequestTime\\(\\); $#',
	'count' => 3,
	'path' => __DIR__ . '/modules/block_content/tests/src/Kernel/Migrate/d6/MigrateCustomBlockContentTranslationTest.php',
];
$ignoreErrors[] = [
	'message' => '#^Variable \\$callable in empty\\(\\) always exists and is not falsy\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/modules/book/src/BookExport.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Drupal\\\\book\\\\BookOutline\\:\\:nextLink\\(\\) should return array but return statement is missing\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/modules/book/src/BookOutline.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Drupal\\\\book\\\\BookOutline\\:\\:prevLink\\(\\) should return array but return statement is missing\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/modules/book/src/BookOutline.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Drupal\\\\book\\\\Form\\\\BookOutlineForm\\:\\:save\\(\\) should return int but return statement is missing\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/modules/book/src/Form/BookOutlineForm.php',
];
$ignoreErrors[] = [
	'message' => '#^Variable \\$ghs_config_classes in empty\\(\\) always exists and is always falsy\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/modules/ckeditor5/src/Plugin/Validation/Constraint/StyleSensibleElementConstraintValidator.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to deprecated constant REQUEST_TIME\\: Deprecated in drupal\\:8\\.3\\.0 and is removed from drupal\\:11\\.0\\.0\\. Use \\\\Drupal\\:\\:time\\(\\)\\-\\>getRequestTime\\(\\); $#',
	'count' => 1,
	'path' => __DIR__ . '/modules/comment/src/CommentForm.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Drupal\\\\comment\\\\CommentForm\\:\\:save\\(\\) should return int but return statement is missing\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/modules/comment/src/CommentForm.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to deprecated constant REQUEST_TIME\\: Deprecated in drupal\\:8\\.3\\.0 and is removed from drupal\\:11\\.0\\.0\\. Use \\\\Drupal\\:\\:time\\(\\)\\-\\>getRequestTime\\(\\); $#',
	'count' => 2,
	'path' => __DIR__ . '/modules/comment/src/CommentStatistics.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Drupal\\\\comment\\\\CommentTypeForm\\:\\:save\\(\\) should return int but return statement is missing\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/modules/comment/src/CommentTypeForm.php',
];
$ignoreErrors[] = [
	'message' => '#^Variable \\$state might not be defined\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/modules/comment/src/Plugin/migrate/destination/EntityComment.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Drupal\\\\comment\\\\Plugin\\\\views\\\\field\\\\NodeNewComments\\:\\:render\\(\\) should return Drupal\\\\Component\\\\Render\\\\MarkupInterface\\|string but return statement is missing\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/modules/comment/src/Plugin/views/field/NodeNewComments.php',
];
$ignoreErrors[] = [
	'message' => '#^Variable \\$comment in empty\\(\\) always exists and is not falsy\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/modules/comment/src/Plugin/views/row/Rss.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to deprecated constant REQUEST_TIME\\: Deprecated in drupal\\:8\\.3\\.0 and is removed from drupal\\:11\\.0\\.0\\. Use \\\\Drupal\\:\\:time\\(\\)\\-\\>getRequestTime\\(\\); $#',
	'count' => 1,
	'path' => __DIR__ . '/modules/comment/tests/src/Functional/CommentBlockTest.php',
];
$ignoreErrors[] = [
	'message' => '#^Variable \\$position might not be defined\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/modules/comment/tests/src/Functional/CommentBlockTest.php',
];
$ignoreErrors[] = [
	'message' => '#^Variable \\$comment_values might not be defined\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/modules/comment/tests/src/Functional/CommentLanguageTest.php',
];
$ignoreErrors[] = [
	'message' => '#^Variable \\$mode_text might not be defined\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/modules/comment/tests/src/Functional/CommentTestBase.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to deprecated constant REQUEST_TIME\\: Deprecated in drupal\\:8\\.3\\.0 and is removed from drupal\\:11\\.0\\.0\\. Use \\\\Drupal\\:\\:time\\(\\)\\-\\>getRequestTime\\(\\); $#',
	'count' => 1,
	'path' => __DIR__ . '/modules/comment/tests/src/Functional/CommentTranslationUITest.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to deprecated constant REQUEST_TIME\\: Deprecated in drupal\\:8\\.3\\.0 and is removed from drupal\\:11\\.0\\.0\\. Use \\\\Drupal\\:\\:time\\(\\)\\-\\>getRequestTime\\(\\); $#',
	'count' => 1,
	'path' => __DIR__ . '/modules/comment/tests/src/Functional/Views/DefaultViewRecentCommentsTest.php',
];
$ignoreErrors[] = [
	'message' => '#^Variable \\$data might not be defined\\.$#',
	'count' => 2,
	'path' => __DIR__ . '/modules/config/src/Form/ConfigSingleImportForm.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to deprecated constant REQUEST_TIME\\: Deprecated in drupal\\:8\\.3\\.0 and is removed from drupal\\:11\\.0\\.0\\. Use \\\\Drupal\\:\\:time\\(\\)\\-\\>getRequestTime\\(\\); $#',
	'count' => 1,
	'path' => __DIR__ . '/modules/config_translation/src/FormElement/DateFormat.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Drupal\\\\contact\\\\ContactFormEditForm\\:\\:save\\(\\) should return int but return statement is missing\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/modules/contact/src/ContactFormEditForm.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Drupal\\\\contact\\\\Entity\\\\Message\\:\\:getPersonalRecipient\\(\\) should return Drupal\\\\user\\\\UserInterface but return statement is missing\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/modules/contact/src/Entity/Message.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Drupal\\\\contact\\\\MessageForm\\:\\:save\\(\\) should return int but return statement is missing\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/modules/contact/src/MessageForm.php',
];
$ignoreErrors[] = [
	'message' => '#^Variable \\$state in isset\\(\\) always exists and is not nullable\\.$#',
	'count' => 3,
	'path' => __DIR__ . '/modules/content_moderation/src/Form/ContentModerationStateForm.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Drupal\\\\content_moderation\\\\ModerationInformation\\:\\:getAffectedRevisionTranslation\\(\\) should return Drupal\\\\Core\\\\Entity\\\\ContentEntityInterface but return statement is missing\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/modules/content_moderation/src/ModerationInformation.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Drupal\\\\content_moderation\\\\ModerationInformation\\:\\:getDefaultRevisionId\\(\\) should return int but return statement is missing\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/modules/content_moderation/src/ModerationInformation.php',
];
$ignoreErrors[] = [
	'message' => '#^Variable \\$checkbox_id might not be defined\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/modules/content_translation/content_translation.admin.inc',
];
$ignoreErrors[] = [
	'message' => '#^Variable \\$locked_languages might not be defined\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/modules/content_translation/content_translation.admin.inc',
];
$ignoreErrors[] = [
	'message' => '#^Variable \\$locked_languages might not be defined\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/modules/content_translation/content_translation.module',
];
$ignoreErrors[] = [
	'message' => '#^Call to deprecated constant REQUEST_TIME\\: Deprecated in drupal\\:8\\.3\\.0 and is removed from drupal\\:11\\.0\\.0\\. Use \\\\Drupal\\:\\:time\\(\\)\\-\\>getRequestTime\\(\\); $#',
	'count' => 3,
	'path' => __DIR__ . '/modules/content_translation/src/ContentTranslationHandler.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to deprecated constant REQUEST_TIME\\: Deprecated in drupal\\:8\\.3\\.0 and is removed from drupal\\:11\\.0\\.0\\. Use \\\\Drupal\\:\\:time\\(\\)\\-\\>getRequestTime\\(\\); $#',
	'count' => 1,
	'path' => __DIR__ . '/modules/content_translation/src/Controller/ContentTranslationController.php',
];
$ignoreErrors[] = [
	'message' => '#^Variable \\$source_name might not be defined\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/modules/content_translation/src/Controller/ContentTranslationController.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to deprecated constant REQUEST_TIME\\: Deprecated in drupal\\:8\\.3\\.0 and is removed from drupal\\:11\\.0\\.0\\. Use \\\\Drupal\\:\\:time\\(\\)\\-\\>getRequestTime\\(\\); $#',
	'count' => 1,
	'path' => __DIR__ . '/modules/content_translation/tests/src/Functional/ContentTranslationUITestBase.php',
];
$ignoreErrors[] = [
	'message' => '#^Variable \\$unrestricted_tab_count might not be defined\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/modules/contextual/tests/src/FunctionalJavascript/EditModeTest.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Drupal\\\\datetime\\\\Plugin\\\\Field\\\\FieldType\\\\DateTimeFieldItemList\\:\\:defaultValuesForm\\(\\) should return array but return statement is missing\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/modules/datetime/src/Plugin/Field/FieldType/DateTimeFieldItemList.php',
];
$ignoreErrors[] = [
	'message' => '#^Access to an undefined property Drupal\\\\datetime\\\\Plugin\\\\Field\\\\FieldType\\\\DateTimeItem\\:\\:\\$date\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/modules/datetime/src/Plugin/Field/FieldType/DateTimeItem.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to deprecated constant REQUEST_TIME\\: Deprecated in drupal\\:8\\.3\\.0 and is removed from drupal\\:11\\.0\\.0\\. Use \\\\Drupal\\:\\:time\\(\\)\\-\\>getRequestTime\\(\\); $#',
	'count' => 1,
	'path' => __DIR__ . '/modules/datetime/src/Plugin/Field/FieldType/DateTimeItem.php',
];
$ignoreErrors[] = [
	'message' => '#^Variable \\$date_part_order might not be defined\\.$#',
	'count' => 3,
	'path' => __DIR__ . '/modules/datetime/src/Plugin/Field/FieldWidget/DateTimeDatelistWidget.php',
];
$ignoreErrors[] = [
	'message' => '#^Variable \\$item in isset\\(\\) always exists and is not nullable\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/modules/datetime/src/Plugin/Validation/Constraint/DateTimeFormatConstraintValidator.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to deprecated constant REQUEST_TIME\\: Deprecated in drupal\\:8\\.3\\.0 and is removed from drupal\\:11\\.0\\.0\\. Use \\\\Drupal\\:\\:time\\(\\)\\-\\>getRequestTime\\(\\); $#',
	'count' => 4,
	'path' => __DIR__ . '/modules/datetime/tests/src/Functional/DateTimeFieldTest.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to deprecated constant REQUEST_TIME\\: Deprecated in drupal\\:8\\.3\\.0 and is removed from drupal\\:11\\.0\\.0\\. Use \\\\Drupal\\:\\:time\\(\\)\\-\\>getRequestTime\\(\\); $#',
	'count' => 1,
	'path' => __DIR__ . '/modules/datetime/tests/src/Kernel/Views/FilterDateTimeTest.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Drupal\\\\datetime_range\\\\Plugin\\\\Field\\\\FieldType\\\\DateRangeFieldItemList\\:\\:defaultValuesForm\\(\\) should return array but return statement is missing\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/modules/datetime_range/src/Plugin/Field/FieldType/DateRangeFieldItemList.php',
];
$ignoreErrors[] = [
	'message' => '#^Access to an undefined property Drupal\\\\datetime_range\\\\Plugin\\\\Field\\\\FieldType\\\\DateRangeItem\\:\\:\\$end_date\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/modules/datetime_range/src/Plugin/Field/FieldType/DateRangeItem.php',
];
$ignoreErrors[] = [
	'message' => '#^Access to an undefined property Drupal\\\\datetime_range\\\\Plugin\\\\Field\\\\FieldType\\\\DateRangeItem\\:\\:\\$start_date\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/modules/datetime_range/src/Plugin/Field/FieldType/DateRangeItem.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to deprecated constant REQUEST_TIME\\: Deprecated in drupal\\:8\\.3\\.0 and is removed from drupal\\:11\\.0\\.0\\. Use \\\\Drupal\\:\\:time\\(\\)\\-\\>getRequestTime\\(\\); $#',
	'count' => 1,
	'path' => __DIR__ . '/modules/datetime_range/src/Plugin/Field/FieldType/DateRangeItem.php',
];
$ignoreErrors[] = [
	'message' => '#^Variable \\$view in isset\\(\\) always exists and is not nullable\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/modules/dblog/dblog.module',
];
$ignoreErrors[] = [
	'message' => '#^Call to deprecated constant REQUEST_TIME\\: Deprecated in drupal\\:8\\.3\\.0 and is removed from drupal\\:11\\.0\\.0\\. Use \\\\Drupal\\:\\:time\\(\\)\\-\\>getRequestTime\\(\\); $#',
	'count' => 3,
	'path' => __DIR__ . '/modules/dblog/tests/src/Functional/DbLogTest.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to deprecated constant REQUEST_TIME\\: Deprecated in drupal\\:8\\.3\\.0 and is removed from drupal\\:11\\.0\\.0\\. Use \\\\Drupal\\:\\:time\\(\\)\\-\\>getRequestTime\\(\\); $#',
	'count' => 1,
	'path' => __DIR__ . '/modules/dblog/tests/src/Kernel/DbLogTest.php',
];
$ignoreErrors[] = [
	'message' => '#^Variable \\$items in empty\\(\\) always exists and is not falsy\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/modules/field/tests/modules/field_test/src/Plugin/Field/FieldFormatter/TestFieldEmptySettingFormatter.php',
];
$ignoreErrors[] = [
	'message' => '#^Variable \\$items in empty\\(\\) always exists and is not falsy\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/modules/field/tests/modules/field_test/src/Plugin/Field/FieldFormatter/TestFieldMultipleFormatter.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to deprecated constant REQUEST_TIME\\: Deprecated in drupal\\:8\\.3\\.0 and is removed from drupal\\:11\\.0\\.0\\. Use \\\\Drupal\\:\\:time\\(\\)\\-\\>getRequestTime\\(\\); $#',
	'count' => 2,
	'path' => __DIR__ . '/modules/field/tests/src/Functional/NestedFormTest.php',
];
$ignoreErrors[] = [
	'message' => '#^Variable \\$values might not be defined\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/modules/field/tests/src/Kernel/FieldAttachStorageTest.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to deprecated constant REQUEST_TIME\\: Deprecated in drupal\\:8\\.3\\.0 and is removed from drupal\\:11\\.0\\.0\\. Use \\\\Drupal\\:\\:time\\(\\)\\-\\>getRequestTime\\(\\); $#',
	'count' => 1,
	'path' => __DIR__ . '/modules/field/tests/src/Kernel/Timestamp/TimestampFormatterTest.php',
];
$ignoreErrors[] = [
	'message' => '#^Variable \\$entity_display might not be defined\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/modules/field_ui/src/Access/FormModeAccessCheck.php',
];
$ignoreErrors[] = [
	'message' => '#^Variable \\$entity_display might not be defined\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/modules/field_ui/src/Access/ViewModeAccessCheck.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Drupal\\\\field_ui\\\\FieldUI\\:\\:getOverviewRouteInfo\\(\\) should return Drupal\\\\Core\\\\Url but return statement is missing\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/modules/field_ui/src/FieldUI.php',
];
$ignoreErrors[] = [
	'message' => '#^Variable \\$updated_columns might not be defined\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/modules/field_ui/src/Form/EntityDisplayFormBase.php',
];
$ignoreErrors[] = [
	'message' => '#^Variable \\$updated_rows might not be defined\\.$#',
	'count' => 2,
	'path' => __DIR__ . '/modules/field_ui/src/Form/EntityDisplayFormBase.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Drupal\\\\field_ui\\\\Form\\\\EntityDisplayModeFormBase\\:\\:save\\(\\) should return int but return statement is missing\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/modules/field_ui/src/Form/EntityDisplayModeFormBase.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Drupal\\\\field_ui\\\\Form\\\\FieldConfigEditForm\\:\\:save\\(\\) should return int but return statement is missing\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/modules/field_ui/src/Form/FieldConfigEditForm.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Drupal\\\\field_ui\\\\Form\\\\FieldStorageConfigEditForm\\:\\:save\\(\\) should return int but return statement is missing\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/modules/field_ui/src/Form/FieldStorageConfigEditForm.php',
];
$ignoreErrors[] = [
	'message' => '#^Variable \\$value might not be defined\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/modules/file/file.install',
];
$ignoreErrors[] = [
	'message' => '#^Variable \\$file_upload in empty\\(\\) always exists and is not falsy\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/modules/file/file.module',
];
$ignoreErrors[] = [
	'message' => '#^Variable \\$message might not be defined\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/modules/file/file.module',
];
$ignoreErrors[] = [
	'message' => '#^Variable \\$rows in empty\\(\\) always exists and is not falsy\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/modules/file/src/Plugin/Field/FieldFormatter/TableFormatter.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Drupal\\\\file\\\\Plugin\\\\Field\\\\FieldType\\\\FileFieldItemList\\:\\:defaultValuesForm\\(\\) should return array but return statement is missing\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/modules/file/src/Plugin/Field/FieldType/FileFieldItemList.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Drupal\\\\file\\\\Plugin\\\\Field\\\\FieldType\\\\FileFieldItemList\\:\\:postSave\\(\\) should return bool but return statement is missing\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/modules/file/src/Plugin/Field/FieldType/FileFieldItemList.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to deprecated constant REQUEST_TIME\\: Deprecated in drupal\\:8\\.3\\.0 and is removed from drupal\\:11\\.0\\.0\\. Use \\\\Drupal\\:\\:time\\(\\)\\-\\>getRequestTime\\(\\); $#',
	'count' => 2,
	'path' => __DIR__ . '/modules/file/tests/src/Functional/FileFieldPathTest.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to deprecated constant REQUEST_TIME\\: Deprecated in drupal\\:8\\.3\\.0 and is removed from drupal\\:11\\.0\\.0\\. Use \\\\Drupal\\:\\:time\\(\\)\\-\\>getRequestTime\\(\\); $#',
	'count' => 2,
	'path' => __DIR__ . '/modules/file/tests/src/Functional/FileFieldRevisionTest.php',
];
$ignoreErrors[] = [
	'message' => '#^Variable \\$unexpected in empty\\(\\) always exists and is not falsy\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/modules/file/tests/src/Functional/FileManagedTestBase.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to an undefined method Drupal\\\\Tests\\\\file\\\\Functional\\\\FileUploadJsonBasicAuthTest\\:\\:getExpectedUnauthorizedEntityAccessCacheability\\(\\)\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/modules/file/tests/src/Functional/FileUploadJsonBasicAuthTest.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to an undefined method Drupal\\\\Tests\\\\file\\\\Functional\\\\FileUploadJsonCookieTest\\:\\:getExpectedUnauthorizedEntityAccessCacheability\\(\\)\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/modules/file/tests/src/Functional/FileUploadJsonCookieTest.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to deprecated constant REQUEST_TIME\\: Deprecated in drupal\\:8\\.3\\.0 and is removed from drupal\\:11\\.0\\.0\\. Use \\\\Drupal\\:\\:time\\(\\)\\-\\>getRequestTime\\(\\); $#',
	'count' => 2,
	'path' => __DIR__ . '/modules/file/tests/src/Kernel/DeleteTest.php',
];
$ignoreErrors[] = [
	'message' => '#^Variable \\$unexpected in empty\\(\\) always exists and is not falsy\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/modules/file/tests/src/Kernel/FileManagedUnitTestBase.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to deprecated constant REQUEST_TIME\\: Deprecated in drupal\\:8\\.3\\.0 and is removed from drupal\\:11\\.0\\.0\\. Use \\\\Drupal\\:\\:time\\(\\)\\-\\>getRequestTime\\(\\); $#',
	'count' => 2,
	'path' => __DIR__ . '/modules/file/tests/src/Kernel/UsageTest.php',
];
$ignoreErrors[] = [
	'message' => '#^Variable \\$rows might not be defined\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/modules/filter/src/Plugin/Filter/FilterHtml.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Drupal\\\\forum\\\\ForumManager\\:\\:getTopicOrder\\(\\) should return array but return statement is missing\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/modules/forum/src/ForumManager.php',
];
$ignoreErrors[] = [
	'message' => '#^Variable \\$vocabulary in empty\\(\\) always exists and is not falsy\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/modules/forum/src/ForumUninstallValidator.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Drupal\\\\history\\\\Plugin\\\\views\\\\field\\\\HistoryUserTimestamp\\:\\:render\\(\\) should return Drupal\\\\Component\\\\Render\\\\MarkupInterface\\|string but return statement is missing\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/modules/history/src/Plugin/views/field/HistoryUserTimestamp.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to deprecated constant REQUEST_TIME\\: Deprecated in drupal\\:8\\.3\\.0 and is removed from drupal\\:11\\.0\\.0\\. Use \\\\Drupal\\:\\:time\\(\\)\\-\\>getRequestTime\\(\\); $#',
	'count' => 1,
	'path' => __DIR__ . '/modules/history/src/Plugin/views/filter/HistoryUserTimestamp.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to deprecated constant REQUEST_TIME\\: Deprecated in drupal\\:8\\.3\\.0 and is removed from drupal\\:11\\.0\\.0\\. Use \\\\Drupal\\:\\:time\\(\\)\\-\\>getRequestTime\\(\\); $#',
	'count' => 2,
	'path' => __DIR__ . '/modules/history/tests/src/Kernel/Views/HistoryTimestampTest.php',
];
$ignoreErrors[] = [
	'message' => '#^Variable \\$image_style in empty\\(\\) always exists and is not falsy\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/modules/image/src/Controller/ImageStyleDownloadController.php',
];
$ignoreErrors[] = [
	'message' => '#^Variable \\$lock_name might not be defined\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/modules/image/src/Controller/ImageStyleDownloadController.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Drupal\\\\image\\\\Form\\\\ImageStyleEditForm\\:\\:save\\(\\) should return int but return statement is missing\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/modules/image/src/Form/ImageStyleEditForm.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Drupal\\\\image\\\\Form\\\\ImageStyleFormBase\\:\\:save\\(\\) should return int but return statement is missing\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/modules/image/src/Form/ImageStyleFormBase.php',
];
$ignoreErrors[] = [
	'message' => '#^Access to an undefined property Drupal\\\\image\\\\Plugin\\\\Field\\\\FieldType\\\\ImageItem\\:\\:\\$height\\.$#',
	'count' => 2,
	'path' => __DIR__ . '/modules/image/src/Plugin/Field/FieldType/ImageItem.php',
];
$ignoreErrors[] = [
	'message' => '#^Access to an undefined property Drupal\\\\image\\\\Plugin\\\\Field\\\\FieldType\\\\ImageItem\\:\\:\\$width\\.$#',
	'count' => 2,
	'path' => __DIR__ . '/modules/image/src/Plugin/Field/FieldType/ImageItem.php',
];
$ignoreErrors[] = [
	'message' => '#^Variable \\$image_that_is_too_small_file might not be defined\\.$#',
	'count' => 2,
	'path' => __DIR__ . '/modules/image/tests/src/Functional/ImageFieldValidateTest.php',
];
$ignoreErrors[] = [
	'message' => '#^Variable \\$edit in empty\\(\\) always exists and is not falsy\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/modules/image/tests/src/Functional/ImageStyleFlushTest.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to deprecated method withConsecutive\\(\\) of class PHPUnit\\\\Framework\\\\MockObject\\\\Builder\\\\InvocationMocker\\.$#',
	'count' => 2,
	'path' => __DIR__ . '/modules/inline_form_errors/tests/src/Unit/FormErrorHandlerTest.php',
];
$ignoreErrors[] = [
	'message' => '#^Variable \\$reason in empty\\(\\) always exists and is not falsy\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/modules/jsonapi/src/Context/FieldResolver.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Drupal\\\\jsonapi\\\\JsonApiResource\\\\ResourceIdentifier\\:\\:getDataReferencePropertyName\\(\\) should return string but return statement is missing\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/modules/jsonapi/src/JsonApiResource/ResourceIdentifier.php',
];
$ignoreErrors[] = [
	'message' => '#^Variable \\$entity in isset\\(\\) always exists and is not nullable\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/modules/jsonapi/src/Normalizer/EntityAccessDeniedHttpExceptionNormalizer.php',
];
$ignoreErrors[] = [
	'message' => '#^Variable \\$group might not be defined\\.$#',
	'count' => 5,
	'path' => __DIR__ . '/modules/jsonapi/src/Query/Filter.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Drupal\\\\jsonapi\\\\Revisions\\\\VersionNegotiator\\:\\:getRevision\\(\\) should return Drupal\\\\Core\\\\Entity\\\\EntityInterface but return statement is missing\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/modules/jsonapi/src/Revisions/VersionNegotiator.php',
];
$ignoreErrors[] = [
	'message' => '#^Variable \\$created_entity might not be defined\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/modules/jsonapi/tests/src/Functional/ResourceTestBase.php',
];
$ignoreErrors[] = [
	'message' => '#^Variable \\$dynamic_cache might not be defined\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/modules/jsonapi/tests/src/Functional/ResourceTestBase.php',
];
$ignoreErrors[] = [
	'message' => '#^Variable \\$parseable_invalid_request_body might not be defined\\.$#',
	'count' => 2,
	'path' => __DIR__ . '/modules/jsonapi/tests/src/Functional/ResourceTestBase.php',
];
$ignoreErrors[] = [
	'message' => '#^Variable \\$reason in empty\\(\\) always exists and is not falsy\\.$#',
	'count' => 2,
	'path' => __DIR__ . '/modules/jsonapi/tests/src/Functional/ResourceTestBase.php',
];
$ignoreErrors[] = [
	'message' => '#^Variable \\$id in isset\\(\\) always exists and is not nullable\\.$#',
	'count' => 2,
	'path' => __DIR__ . '/modules/jsonapi/tests/src/Unit/Normalizer/JsonApiDocumentTopLevelNormalizerTest.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Drupal\\\\language\\\\Form\\\\LanguageAddForm\\:\\:save\\(\\) should return int but return statement is missing\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/modules/language/src/Form/LanguageAddForm.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Drupal\\\\language\\\\Form\\\\LanguageEditForm\\:\\:save\\(\\) should return int but return statement is missing\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/modules/language/src/Form/LanguageEditForm.php',
];
$ignoreErrors[] = [
	'message' => '#^Variable \\$key might not be defined\\.$#',
	'count' => 2,
	'path' => __DIR__ . '/modules/language/src/Form/NegotiationBrowserForm.php',
];
$ignoreErrors[] = [
	'message' => '#^Variable \\$method_id might not be defined\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/modules/language/src/LanguageNegotiator.php',
];
$ignoreErrors[] = [
	'message' => '#^Variable \\$langcode might not be defined\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/modules/language/src/Plugin/LanguageNegotiation/LanguageNegotiationContentEntity.php',
];
$ignoreErrors[] = [
	'message' => '#^Constructor of class Drupal\\\\link\\\\Plugin\\\\migrate\\\\process\\\\FieldLink has an unused parameter \\$migration\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/modules/link/src/Plugin/migrate/process/FieldLink.php',
];
$ignoreErrors[] = [
	'message' => '#^Function locale_config_batch_update_components\\(\\) should return array but return statement is missing\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/modules/locale/locale.bulk.inc',
];
$ignoreErrors[] = [
	'message' => '#^Variable \\$item in empty\\(\\) always exists and is not falsy\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/modules/locale/src/Form/ExportForm.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to deprecated constant REQUEST_TIME\\: Deprecated in drupal\\:8\\.3\\.0 and is removed from drupal\\:11\\.0\\.0\\. Use \\\\Drupal\\:\\:time\\(\\)\\-\\>getRequestTime\\(\\); $#',
	'count' => 1,
	'path' => __DIR__ . '/modules/locale/src/Form/TranslationStatusForm.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Drupal\\\\locale\\\\LocaleDefaultConfigStorage\\:\\:read\\(\\) should return array but return statement is missing\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/modules/locale/src/LocaleDefaultConfigStorage.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Drupal\\\\locale\\\\LocaleProjectStorage\\:\\:deleteAll\\(\\) should return array but return statement is missing\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/modules/locale/src/LocaleProjectStorage.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Drupal\\\\locale\\\\PoDatabaseReader\\:\\:readItem\\(\\) should return Drupal\\\\Component\\\\Gettext\\\\PoItem but return statement is missing\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/modules/locale/src/PoDatabaseReader.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Drupal\\\\locale\\\\PoDatabaseWriter\\:\\:importString\\(\\) should return int but return statement is missing\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/modules/locale/src/PoDatabaseWriter.php',
];
$ignoreErrors[] = [
	'message' => '#^Variable \\$plural in isset\\(\\) always exists and is not nullable\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/modules/locale/src/PoDatabaseWriter.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Drupal\\\\locale\\\\StringDatabaseStorage\\:\\:dbStringTable\\(\\) should return string but return statement is missing\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/modules/locale/src/StringDatabaseStorage.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to deprecated constant REQUEST_TIME\\: Deprecated in drupal\\:8\\.3\\.0 and is removed from drupal\\:11\\.0\\.0\\. Use \\\\Drupal\\:\\:time\\(\\)\\-\\>getRequestTime\\(\\); $#',
	'count' => 5,
	'path' => __DIR__ . '/modules/locale/tests/src/Functional/LocaleUpdateBase.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to deprecated constant REQUEST_TIME\\: Deprecated in drupal\\:8\\.3\\.0 and is removed from drupal\\:11\\.0\\.0\\. Use \\\\Drupal\\:\\:time\\(\\)\\-\\>getRequestTime\\(\\); $#',
	'count' => 1,
	'path' => __DIR__ . '/modules/locale/tests/src/Functional/LocaleUpdateCronTest.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to deprecated constant REQUEST_TIME\\: Deprecated in drupal\\:8\\.3\\.0 and is removed from drupal\\:11\\.0\\.0\\. Use \\\\Drupal\\:\\:time\\(\\)\\-\\>getRequestTime\\(\\); $#',
	'count' => 2,
	'path' => __DIR__ . '/modules/locale/tests/src/Functional/LocaleUpdateInterfaceTest.php',
];
$ignoreErrors[] = [
	'message' => '#^Variable \\$error in empty\\(\\) always exists and is not falsy\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/modules/media/media.install',
];
$ignoreErrors[] = [
	'message' => '#^Method Drupal\\\\media\\\\MediaTypeForm\\:\\:save\\(\\) should return int but return statement is missing\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/modules/media/src/MediaTypeForm.php',
];
$ignoreErrors[] = [
	'message' => '#^Variable \\$source in empty\\(\\) always exists and is not falsy\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/modules/media/src/MediaTypeForm.php',
];
$ignoreErrors[] = [
	'message' => '#^Variable \\$resource_url on left side of \\?\\? always exists and is not nullable\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/modules/media/src/OEmbed/UrlResolver.php',
];
$ignoreErrors[] = [
	'message' => '#^Usage of deprecated trait Symfony\\\\Component\\\\DependencyInjection\\\\ContainerAwareTrait in class Drupal\\\\media_library\\\\OpenerResolver\\:
since Symfony 6\\.4, use dependency injection instead$#',
	'count' => 1,
	'path' => __DIR__ . '/modules/media_library/src/OpenerResolver.php',
];
$ignoreErrors[] = [
	'message' => '#^Variable \\$jpg_image might not be defined\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/modules/media_library/tests/src/FunctionalJavascript/EmbeddedFormWidgetTest.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Drupal\\\\menu_link_content\\\\Form\\\\MenuLinkContentForm\\:\\:save\\(\\) should return int but return statement is missing\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/modules/menu_link_content/src/Form/MenuLinkContentForm.php',
];
$ignoreErrors[] = [
	'message' => '#^Variable \\$uuid might not be defined\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/modules/menu_link_content/src/Plugin/Menu/MenuLinkContent.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to deprecated constant REQUEST_TIME\\: Deprecated in drupal\\:8\\.3\\.0 and is removed from drupal\\:11\\.0\\.0\\. Use \\\\Drupal\\:\\:time\\(\\)\\-\\>getRequestTime\\(\\); $#',
	'count' => 2,
	'path' => __DIR__ . '/modules/menu_link_content/tests/src/Kernel/MenuLinksTest.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Drupal\\\\menu_ui\\\\MenuForm\\:\\:save\\(\\) should return int but return statement is missing\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/modules/menu_ui/src/MenuForm.php',
];
$ignoreErrors[] = [
	'message' => '#^Constructor of class Drupal\\\\migrate\\\\MigrateException has an unused parameter \\$code\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/modules/migrate/src/MigrateException.php',
];
$ignoreErrors[] = [
	'message' => '#^Constructor of class Drupal\\\\migrate\\\\MigrateException has an unused parameter \\$previous\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/modules/migrate/src/MigrateException.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to deprecated method registerLoader\\(\\) of class Doctrine\\\\Common\\\\Annotations\\\\AnnotationRegistry\\:
This method is deprecated and will be removed in
            doctrine/annotations 2\\.0\\. Annotations will be autoloaded in 2\\.0\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/modules/migrate/src/Plugin/Discovery/AnnotatedClassDiscoveryAutomatedProviders.php',
];
$ignoreErrors[] = [
	'message' => '#^Missing cache backend declaration for performance\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/modules/migrate/src/Plugin/MigrateDestinationPluginManager.php',
];
$ignoreErrors[] = [
	'message' => '#^Missing cache backend declaration for performance\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/modules/migrate/src/Plugin/MigrateSourcePluginManager.php',
];
$ignoreErrors[] = [
	'message' => '#^Constructor of class Drupal\\\\migrate\\\\Plugin\\\\MigrationPluginManager has an unused parameter \\$language_manager\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/modules/migrate/src/Plugin/MigrationPluginManager.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Drupal\\\\migrate\\\\Plugin\\\\migrate\\\\destination\\\\ComponentEntityDisplayBase\\:\\:fields\\(\\) should return array but return statement is missing\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/modules/migrate/src/Plugin/migrate/destination/ComponentEntityDisplayBase.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Drupal\\\\migrate\\\\Plugin\\\\migrate\\\\destination\\\\Config\\:\\:fields\\(\\) should return array but return statement is missing\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/modules/migrate/src/Plugin/migrate/destination/Config.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Drupal\\\\migrate\\\\Plugin\\\\migrate\\\\destination\\\\Entity\\:\\:fields\\(\\) should return array but return statement is missing\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/modules/migrate/src/Plugin/migrate/destination/Entity.php',
];
$ignoreErrors[] = [
	'message' => '#^Variable \\$config might not be defined\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/modules/migrate/src/Plugin/migrate/destination/EntityConfigBase.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Drupal\\\\migrate\\\\Plugin\\\\migrate\\\\destination\\\\NullDestination\\:\\:import\\(\\) should return array\\|bool but return statement is missing\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/modules/migrate/src/Plugin/migrate/destination/NullDestination.php',
];
$ignoreErrors[] = [
	'message' => '#^@dataProvider providerSource related method not found\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/modules/migrate/tests/src/Kernel/MigrateSourceTestBase.php',
];
$ignoreErrors[] = [
	'message' => '#^@dataProvider providerSource related method not found\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/modules/migrate/tests/src/Kernel/MigrateSqlSourceTestBase.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to an undefined static method static\\(Drupal\\\\Tests\\\\migrate\\\\Kernel\\\\MigrateTestBase\\)\\:\\:migrateDumpAlter\\(\\)\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/modules/migrate/tests/src/Kernel/MigrateTestBase.php',
];
$ignoreErrors[] = [
	'message' => '#^Variable \\$sub_process_plugins might not be defined\\.$#',
	'count' => 2,
	'path' => __DIR__ . '/modules/migrate/tests/src/Unit/process/SubProcessTest.php',
];
$ignoreErrors[] = [
	'message' => '#^Missing cache backend declaration for performance\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/modules/migrate_drupal/src/MigrationPluginManager.php',
];
$ignoreErrors[] = [
	'message' => '#^Variable \\$patterns might not be defined\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/modules/migrate_drupal/src/NodeMigrateType.php',
];
$ignoreErrors[] = [
	'message' => '#^Constructor of class Drupal\\\\migrate_drupal\\\\Plugin\\\\migrate\\\\EntityReferenceTranslationDeriver has an unused parameter \\$base_plugin_id\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/modules/migrate_drupal/src/Plugin/migrate/EntityReferenceTranslationDeriver.php',
];
$ignoreErrors[] = [
	'message' => '#^Constructor of class Drupal\\\\migrate_drupal\\\\Plugin\\\\migrate\\\\source\\\\ContentEntityDeriver has an unused parameter \\$base_plugin_id\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/modules/migrate_drupal/src/Plugin/migrate/source/ContentEntityDeriver.php',
];
$ignoreErrors[] = [
	'message' => '#^Variable \\$patterns might not be defined\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/modules/migrate_drupal/tests/src/Kernel/StateFileExistsTest.php',
];
$ignoreErrors[] = [
	'message' => '#^Variable \\$patterns might not be defined\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/modules/migrate_drupal/tests/src/Kernel/d6/ValidateMigrationStateTest.php',
];
$ignoreErrors[] = [
	'message' => '#^Variable \\$patterns might not be defined\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/modules/migrate_drupal/tests/src/Kernel/d7/ValidateMigrationStateTest.php',
];
$ignoreErrors[] = [
	'message' => '#^Missing cache backend declaration for performance\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/modules/migrate_drupal/tests/src/Unit/MigrateFieldPluginManagerTest.php',
];
$ignoreErrors[] = [
	'message' => '#^Variable \\$patterns might not be defined\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/modules/migrate_drupal/tests/src/Unit/MigrationConfigurationTraitTest.php',
];
$ignoreErrors[] = [
	'message' => '#^Variable \\$statement might not be defined\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/modules/migrate_drupal/tests/src/Unit/MigrationConfigurationTraitTest.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to deprecated constant REQUEST_TIME\\: Deprecated in drupal\\:8\\.3\\.0 and is removed from drupal\\:11\\.0\\.0\\. Use \\\\Drupal\\:\\:time\\(\\)\\-\\>getRequestTime\\(\\); $#',
	'count' => 2,
	'path' => __DIR__ . '/modules/migrate_drupal_ui/src/Batch/MigrateUpgradeImportBatch.php',
];
$ignoreErrors[] = [
	'message' => '#^Variable \\$connection might not be defined\\.$#',
	'count' => 2,
	'path' => __DIR__ . '/modules/migrate_drupal_ui/src/Form/CredentialForm.php',
];
$ignoreErrors[] = [
	'message' => '#^Variable \\$version might not be defined\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/modules/migrate_drupal_ui/src/Form/CredentialForm.php',
];
$ignoreErrors[] = [
	'message' => '#^Variable \\$patterns might not be defined\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/modules/migrate_drupal_ui/src/Form/MigrateUpgradeFormBase.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to deprecated constant REQUEST_TIME\\: Deprecated in drupal\\:8\\.3\\.0 and is removed from drupal\\:11\\.0\\.0\\. Use \\\\Drupal\\:\\:time\\(\\)\\-\\>getRequestTime\\(\\); $#',
	'count' => 1,
	'path' => __DIR__ . '/modules/migrate_drupal_ui/src/Form/ReviewForm.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to an undefined method Drupal\\\\Tests\\\\migrate_drupal_ui\\\\Functional\\\\CredentialFormTest\\:\\:installEntitySchema\\(\\)\\.$#',
	'count' => 8,
	'path' => __DIR__ . '/modules/migrate_drupal_ui/tests/src/Functional/CredentialFormTest.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to an undefined method Drupal\\\\Tests\\\\migrate_drupal_ui\\\\Functional\\\\MigrateUpgradeExecuteTestBase\\:\\:installEntitySchema\\(\\)\\.$#',
	'count' => 8,
	'path' => __DIR__ . '/modules/migrate_drupal_ui/tests/src/Functional/MigrateUpgradeExecuteTestBase.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to an undefined method Drupal\\\\Tests\\\\migrate_drupal_ui\\\\Functional\\\\MigrateUpgradeFormStepsTest\\:\\:installEntitySchema\\(\\)\\.$#',
	'count' => 8,
	'path' => __DIR__ . '/modules/migrate_drupal_ui/tests/src/Functional/MigrateUpgradeFormStepsTest.php',
];
$ignoreErrors[] = [
	'message' => '#^Variable \\$patterns might not be defined\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/modules/migrate_drupal_ui/tests/src/Functional/MigrateUpgradeFormStepsTest.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to an undefined method Drupal\\\\Tests\\\\migrate_drupal_ui\\\\Functional\\\\MigrateUpgradeTestBase\\:\\:getManagedFiles\\(\\)\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/modules/migrate_drupal_ui/tests/src/Functional/MigrateUpgradeTestBase.php',
];
$ignoreErrors[] = [
	'message' => '#^Variable \\$patterns might not be defined\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/modules/migrate_drupal_ui/tests/src/Functional/MigrateUpgradeTestBase.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to an undefined method Drupal\\\\Tests\\\\migrate_drupal_ui\\\\Functional\\\\MultilingualReviewPageTestBase\\:\\:installEntitySchema\\(\\)\\.$#',
	'count' => 8,
	'path' => __DIR__ . '/modules/migrate_drupal_ui/tests/src/Functional/MultilingualReviewPageTestBase.php',
];
$ignoreErrors[] = [
	'message' => '#^Access to deprecated property \\$needsCleanup of class Drupal\\\\mysql\\\\Driver\\\\Database\\\\mysql\\\\Connection\\:
in drupal\\:10\\.2\\.0 and is removed from drupal\\:11\\.0\\.0\\. There\'s no
   replacement\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/modules/mysql/src/Driver/Database/mysql/Connection.php',
];
$ignoreErrors[] = [
	'message' => '#^Variable \\$last_insert_id might not be defined\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/modules/mysql/src/Driver/Database/mysql/Insert.php',
];
$ignoreErrors[] = [
	'message' => '#^Variable \\$string_ascii_check might not be defined\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/modules/mysql/tests/src/Kernel/mysql/SchemaTest.php',
];
$ignoreErrors[] = [
	'message' => '#^Variable \\$string_check might not be defined\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/modules/mysql/tests/src/Kernel/mysql/SchemaTest.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Drupal\\\\node\\\\ConfigTranslation\\\\NodeTypeMapper\\:\\:setEntity\\(\\) should return bool but return statement is missing\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/modules/node/src/ConfigTranslation/NodeTypeMapper.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Drupal\\\\node\\\\NodeForm\\:\\:save\\(\\) should return int but return statement is missing\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/modules/node/src/NodeForm.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Drupal\\\\node\\\\NodeGrantDatabaseStorage\\:\\:alterQuery\\(\\) should return int but return statement is missing\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/modules/node/src/NodeGrantDatabaseStorage.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Drupal\\\\node\\\\NodeTypeForm\\:\\:save\\(\\) should return int but return statement is missing\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/modules/node/src/NodeTypeForm.php',
];
$ignoreErrors[] = [
	'message' => '#^Variable \\$node in empty\\(\\) always exists and is not falsy\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/modules/node/src/Plugin/views/row/Rss.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to deprecated constant REQUEST_TIME\\: Deprecated in drupal\\:8\\.3\\.0 and is removed from drupal\\:11\\.0\\.0\\. Use \\\\Drupal\\:\\:time\\(\\)\\-\\>getRequestTime\\(\\); $#',
	'count' => 1,
	'path' => __DIR__ . '/modules/node/tests/src/Functional/NodeAdminTest.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to deprecated constant REQUEST_TIME\\: Deprecated in drupal\\:8\\.3\\.0 and is removed from drupal\\:11\\.0\\.0\\. Use \\\\Drupal\\:\\:time\\(\\)\\-\\>getRequestTime\\(\\); $#',
	'count' => 1,
	'path' => __DIR__ . '/modules/node/tests/src/Functional/NodeRevisionsAllTest.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to deprecated constant REQUEST_TIME\\: Deprecated in drupal\\:8\\.3\\.0 and is removed from drupal\\:11\\.0\\.0\\. Use \\\\Drupal\\:\\:time\\(\\)\\-\\>getRequestTime\\(\\); $#',
	'count' => 1,
	'path' => __DIR__ . '/modules/node/tests/src/Functional/NodeRevisionsTest.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to deprecated constant REQUEST_TIME\\: Deprecated in drupal\\:8\\.3\\.0 and is removed from drupal\\:11\\.0\\.0\\. Use \\\\Drupal\\:\\:time\\(\\)\\-\\>getRequestTime\\(\\); $#',
	'count' => 2,
	'path' => __DIR__ . '/modules/node/tests/src/Functional/NodeSaveTest.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to deprecated constant REQUEST_TIME\\: Deprecated in drupal\\:8\\.3\\.0 and is removed from drupal\\:11\\.0\\.0\\. Use \\\\Drupal\\:\\:time\\(\\)\\-\\>getRequestTime\\(\\); $#',
	'count' => 1,
	'path' => __DIR__ . '/modules/node/tests/src/Functional/NodeTranslationUITest.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to deprecated constant REQUEST_TIME\\: Deprecated in drupal\\:8\\.3\\.0 and is removed from drupal\\:11\\.0\\.0\\. Use \\\\Drupal\\:\\:time\\(\\)\\-\\>getRequestTime\\(\\); $#',
	'count' => 3,
	'path' => __DIR__ . '/modules/node/tests/src/Functional/Views/FrontPageTest.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to deprecated constant REQUEST_TIME\\: Deprecated in drupal\\:8\\.3\\.0 and is removed from drupal\\:11\\.0\\.0\\. Use \\\\Drupal\\:\\:time\\(\\)\\-\\>getRequestTime\\(\\); $#',
	'count' => 1,
	'path' => __DIR__ . '/modules/node/tests/src/Functional/Views/NodeIntegrationTest.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to deprecated constant REQUEST_TIME\\: Deprecated in drupal\\:8\\.3\\.0 and is removed from drupal\\:11\\.0\\.0\\. Use \\\\Drupal\\:\\:time\\(\\)\\-\\>getRequestTime\\(\\); $#',
	'count' => 5,
	'path' => __DIR__ . '/modules/node/tests/src/Functional/Views/Wizard/NodeRevisionWizardTest.php',
];
$ignoreErrors[] = [
	'message' => '#^Variable \\$changed in isset\\(\\) always exists and is not nullable\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/modules/node/tests/src/Kernel/Migrate/d7/MigrateNodeTest.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Drupal\\\\options\\\\Plugin\\\\Field\\\\FieldType\\\\ListFloatItem\\:\\:validateAllowedValue\\(\\) should return string but return statement is missing\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/modules/options/src/Plugin/Field/FieldType/ListFloatItem.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Drupal\\\\options\\\\Plugin\\\\Field\\\\FieldType\\\\ListIntegerItem\\:\\:validateAllowedValue\\(\\) should return string but return statement is missing\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/modules/options/src/Plugin/Field/FieldType/ListIntegerItem.php',
];
$ignoreErrors[] = [
	'message' => '#^Access to an undefined property Drupal\\\\options\\\\Plugin\\\\Field\\\\FieldType\\\\ListItemBase\\:\\:\\$value\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/modules/options/src/Plugin/Field/FieldType/ListItemBase.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Drupal\\\\options\\\\Plugin\\\\Field\\\\FieldType\\\\ListItemBase\\:\\:validateAllowedValue\\(\\) should return string but return statement is missing\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/modules/options/src/Plugin/Field/FieldType/ListItemBase.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Drupal\\\\options\\\\Plugin\\\\Field\\\\FieldType\\\\ListStringItem\\:\\:validateAllowedValue\\(\\) should return string but return statement is missing\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/modules/options/src/Plugin/Field/FieldType/ListStringItem.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Drupal\\\\path\\\\PathAliasForm\\:\\:save\\(\\) should return int but return statement is missing\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/modules/path/src/PathAliasForm.php',
];
$ignoreErrors[] = [
	'message' => '#^Access to an undefined property Drupal\\\\path\\\\Plugin\\\\Field\\\\FieldType\\\\PathItem\\:\\:\\$alias\\.$#',
	'count' => 3,
	'path' => __DIR__ . '/modules/path/src/Plugin/Field/FieldType/PathItem.php',
];
$ignoreErrors[] = [
	'message' => '#^Access to an undefined property Drupal\\\\path\\\\Plugin\\\\Field\\\\FieldType\\\\PathItem\\:\\:\\$langcode\\.$#',
	'count' => 2,
	'path' => __DIR__ . '/modules/path/src/Plugin/Field/FieldType/PathItem.php',
];
$ignoreErrors[] = [
	'message' => '#^Access to an undefined property Drupal\\\\path\\\\Plugin\\\\Field\\\\FieldType\\\\PathItem\\:\\:\\$pid\\.$#',
	'count' => 5,
	'path' => __DIR__ . '/modules/path/src/Plugin/Field/FieldType/PathItem.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Drupal\\\\path\\\\Plugin\\\\Field\\\\FieldType\\\\PathItem\\:\\:postSave\\(\\) should return bool but return statement is missing\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/modules/path/src/Plugin/Field/FieldType/PathItem.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to deprecated constant REQUEST_TIME\\: Deprecated in drupal\\:8\\.3\\.0 and is removed from drupal\\:11\\.0\\.0\\. Use \\\\Drupal\\:\\:time\\(\\)\\-\\>getRequestTime\\(\\); $#',
	'count' => 1,
	'path' => __DIR__ . '/modules/path_alias/src/AliasManager.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to deprecated method makeSequenceName\\(\\) of class Drupal\\\\Core\\\\Database\\\\Connection\\:
in drupal\\:10\\.2\\.0 and is removed from drupal\\:11\\.0\\.0\\. There is
  no replacement\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/modules/pgsql/src/Driver/Database/pgsql/Schema.php',
];
$ignoreErrors[] = [
	'message' => '#^Variable \\$table_field might not be defined\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/modules/pgsql/src/Driver/Database/pgsql/Select.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Drupal\\\\pgsql\\\\Driver\\\\Database\\\\pgsql\\\\Upsert\\:\\:execute\\(\\) should return int but return statement is missing\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/modules/pgsql/src/Driver/Database/pgsql/Upsert.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to deprecated method makeSequenceName\\(\\) of class Drupal\\\\Core\\\\Database\\\\Connection\\:
in drupal\\:10\\.2\\.0 and is removed from drupal\\:11\\.0\\.0\\. There is
  no replacement\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/modules/pgsql/src/Update10101.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to deprecated method withConsecutive\\(\\) of class PHPUnit\\\\Framework\\\\MockObject\\\\Builder\\\\InvocationMocker\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/modules/pgsql/tests/src/Unit/SchemaTest.php',
];
$ignoreErrors[] = [
	'message' => '#^Variable \\$responsive_image_styles in empty\\(\\) always exists and is not falsy\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/modules/responsive_image/src/Plugin/Field/FieldFormatter/ResponsiveImageFormatter.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Drupal\\\\responsive_image\\\\ResponsiveImageStyleForm\\:\\:save\\(\\) should return int but return statement is missing\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/modules/responsive_image/src/ResponsiveImageStyleForm.php',
];
$ignoreErrors[] = [
	'message' => '#^Variable \\$created_entity might not be defined\\.$#',
	'count' => 4,
	'path' => __DIR__ . '/modules/rest/tests/src/Functional/EntityResource/EntityResourceTestBase.php',
];
$ignoreErrors[] = [
	'message' => '#^Variable \\$working_to might not be defined\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/modules/search/search.module',
];
$ignoreErrors[] = [
	'message' => '#^Method Drupal\\\\search\\\\Form\\\\SearchPageAddForm\\:\\:save\\(\\) should return int but return statement is missing\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/modules/search/src/Form/SearchPageAddForm.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Drupal\\\\search\\\\Form\\\\SearchPageEditForm\\:\\:save\\(\\) should return int but return statement is missing\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/modules/search/src/Form/SearchPageEditForm.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Drupal\\\\search\\\\Form\\\\SearchPageFormBase\\:\\:save\\(\\) should return int but return statement is missing\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/modules/search/src/Form/SearchPageFormBase.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to deprecated constant REQUEST_TIME\\: Deprecated in drupal\\:8\\.3\\.0 and is removed from drupal\\:11\\.0\\.0\\. Use \\\\Drupal\\:\\:time\\(\\)\\-\\>getRequestTime\\(\\); $#',
	'count' => 1,
	'path' => __DIR__ . '/modules/search/src/SearchIndex.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Drupal\\\\search\\\\SearchPageRepository\\:\\:setDefaultSearchPage\\(\\) should return static\\(Drupal\\\\search\\\\SearchPageRepository\\) but return statement is missing\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/modules/search/src/SearchPageRepository.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to deprecated constant REQUEST_TIME\\: Deprecated in drupal\\:8\\.3\\.0 and is removed from drupal\\:11\\.0\\.0\\. Use \\\\Drupal\\:\\:time\\(\\)\\-\\>getRequestTime\\(\\); $#',
	'count' => 1,
	'path' => __DIR__ . '/modules/search/tests/src/Functional/SearchMultilingualEntityTest.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to deprecated constant REQUEST_TIME\\: Deprecated in drupal\\:8\\.3\\.0 and is removed from drupal\\:11\\.0\\.0\\. Use \\\\Drupal\\:\\:time\\(\\)\\-\\>getRequestTime\\(\\); $#',
	'count' => 2,
	'path' => __DIR__ . '/modules/search/tests/src/Functional/SearchRankingTest.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to an undefined method Drupal\\\\serialization\\\\Normalizer\\\\EntityNormalizer\\:\\:getCustomSerializedPropertyNames\\(\\)\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/modules/serialization/src/Normalizer/EntityNormalizer.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Drupal\\\\shortcut\\\\Form\\\\SetCustomize\\:\\:save\\(\\) should return int but return statement is missing\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/modules/shortcut/src/Form/SetCustomize.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Drupal\\\\shortcut\\\\ShortcutForm\\:\\:save\\(\\) should return int but return statement is missing\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/modules/shortcut/src/ShortcutForm.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Drupal\\\\shortcut\\\\ShortcutSetForm\\:\\:save\\(\\) should return int but return statement is missing\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/modules/shortcut/src/ShortcutSetForm.php',
];
$ignoreErrors[] = [
	'message' => '#^Variable \\$args might not be defined\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/modules/sqlite/src/Driver/Database/sqlite/Connection.php',
];
$ignoreErrors[] = [
	'message' => '#^Variable \\$statement might not be defined\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/modules/sqlite/src/Driver/Database/sqlite/Connection.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Drupal\\\\system\\\\Controller\\\\BatchController\\:\\:batchPage\\(\\) should return array\\|Symfony\\\\Component\\\\HttpFoundation\\\\Response but return statement is missing\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/modules/system/src/Controller/BatchController.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to deprecated constant REQUEST_TIME\\: Deprecated in drupal\\:8\\.3\\.0 and is removed from drupal\\:11\\.0\\.0\\. Use \\\\Drupal\\:\\:time\\(\\)\\-\\>getRequestTime\\(\\); $#',
	'count' => 1,
	'path' => __DIR__ . '/modules/system/src/DateFormatListBuilder.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to deprecated constant REQUEST_TIME\\: Deprecated in drupal\\:8\\.3\\.0 and is removed from drupal\\:11\\.0\\.0\\. Use \\\\Drupal\\:\\:time\\(\\)\\-\\>getRequestTime\\(\\); $#',
	'count' => 1,
	'path' => __DIR__ . '/modules/system/src/Form/DateFormatDeleteForm.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to deprecated constant REQUEST_TIME\\: Deprecated in drupal\\:8\\.3\\.0 and is removed from drupal\\:11\\.0\\.0\\. Use \\\\Drupal\\:\\:time\\(\\)\\-\\>getRequestTime\\(\\); $#',
	'count' => 1,
	'path' => __DIR__ . '/modules/system/src/Form/DateFormatEditForm.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Drupal\\\\system\\\\Form\\\\DateFormatFormBase\\:\\:save\\(\\) should return int but return statement is missing\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/modules/system/src/Form/DateFormatFormBase.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Drupal\\\\system\\\\Form\\\\ModulesListNonStableConfirmForm\\:\\:getQuestion\\(\\) should return Drupal\\\\Core\\\\StringTranslation\\\\TranslatableMarkup but return statement is missing\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/modules/system/src/Form/ModulesListNonStableConfirmForm.php',
];
$ignoreErrors[] = [
	'message' => '#^Variable \\$default_theme in isset\\(\\) always exists and is not nullable\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/modules/system/src/Form/ThemeSettingsForm.php',
];
$ignoreErrors[] = [
	'message' => '#^Variable \\$theme_settings in isset\\(\\) always exists and is not nullable\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/modules/system/src/Plugin/migrate/destination/d7/ThemeSettings.php',
];
$ignoreErrors[] = [
	'message' => '#^Variable \\$violation_messages might not be defined\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/modules/system/src/SecurityAdvisories/SecurityAdvisory.php',
];
$ignoreErrors[] = [
	'message' => '#^Variable \\$directories might not be defined\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/modules/system/system.install',
];
$ignoreErrors[] = [
	'message' => '#^Variable \\$pdo_message might not be defined\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/modules/system/system.install',
];
$ignoreErrors[] = [
	'message' => '#^Variable \\$site_path might not be defined\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/modules/system/system.install',
];
$ignoreErrors[] = [
	'message' => '#^Variable \\$messages might not be defined\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/modules/system/tests/modules/batch_test/batch_test.callbacks.inc',
];
$ignoreErrors[] = [
	'message' => '#^Call to deprecated function deprecation_test_function\\(\\)\\:
in drupal\\:8\\.4\\.0 and is removed from drupal\\:9\\.0\\.0\\. This is
  the deprecation message for deprecated_test_function\\(\\)\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/modules/system/tests/modules/deprecation_test/src/DeprecatedController.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to deprecated constant REQUEST_TIME\\: Deprecated in drupal\\:8\\.3\\.0 and is removed from drupal\\:11\\.0\\.0\\. Use \\\\Drupal\\:\\:time\\(\\)\\-\\>getRequestTime\\(\\); $#',
	'count' => 1,
	'path' => __DIR__ . '/modules/system/tests/modules/entity_test/src/Plugin/Field/FieldType/ChangedTestItem.php',
];
$ignoreErrors[] = [
	'message' => '#^Variable \\$options might not be defined\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/modules/system/tests/modules/form_test/src/Form/FormTestTableSelectJsSelectForm.php',
];
$ignoreErrors[] = [
	'message' => '#^Constructor of class Drupal\\\\lazy_route_provider_install_test\\\\PluginManager has an unused parameter \\$cache_backend\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/modules/system/tests/modules/lazy_route_provider_install_test/src/PluginManager.php',
];
$ignoreErrors[] = [
	'message' => '#^Constructor of class Drupal\\\\lazy_route_provider_install_test\\\\PluginManager has an unused parameter \\$url_generator\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/modules/system/tests/modules/lazy_route_provider_install_test/src/PluginManager.php',
];
$ignoreErrors[] = [
	'message' => '#^Missing cache backend declaration for performance\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/modules/system/tests/modules/lazy_route_provider_install_test/src/PluginManager.php',
];
$ignoreErrors[] = [
	'message' => '#^Missing cache backend declaration for performance\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/modules/system/tests/modules/module_test/src/PluginManagerCacheClearer.php',
];
$ignoreErrors[] = [
	'message' => '#^Missing cache backend declaration for performance\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/modules/system/tests/modules/plugin_test/src/Plugin/DefaultsTestPluginManager.php',
];
$ignoreErrors[] = [
	'message' => '#^Missing cache backend declaration for performance\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/modules/system/tests/modules/plugin_test/src/Plugin/MockBlockManager.php',
];
$ignoreErrors[] = [
	'message' => '#^Missing cache backend declaration for performance\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/modules/system/tests/modules/plugin_test/src/Plugin/TestPluginManager.php',
];
$ignoreErrors[] = [
	'message' => '#^Class Drupal\\\\service_provider_test\\\\TestClass implements deprecated interface Symfony\\\\Component\\\\DependencyInjection\\\\ContainerAwareInterface\\:
since Symfony 6\\.4, use dependency injection instead$#',
	'count' => 1,
	'path' => __DIR__ . '/modules/system/tests/modules/service_provider_test/src/TestClass.php',
];
$ignoreErrors[] = [
	'message' => '#^Usage of deprecated trait Symfony\\\\Component\\\\DependencyInjection\\\\ContainerAwareTrait in class Drupal\\\\service_provider_test\\\\TestClass\\:
since Symfony 6\\.4, use dependency injection instead$#',
	'count' => 1,
	'path' => __DIR__ . '/modules/system/tests/modules/service_provider_test/src/TestClass.php',
];
$ignoreErrors[] = [
	'message' => '#^Variable \\$form_output in empty\\(\\) always exists and is not falsy\\.$#',
	'count' => 2,
	'path' => __DIR__ . '/modules/system/tests/src/Functional/Form/FormTest.php',
];
$ignoreErrors[] = [
	'message' => '#^Variable \\$parent might not be defined\\.$#',
	'count' => 3,
	'path' => __DIR__ . '/modules/system/tests/src/Functional/Menu/BreadcrumbTest.php',
];
$ignoreErrors[] = [
	'message' => '#^Variable \\$expected_required_list_items might not be defined\\.$#',
	'count' => 2,
	'path' => __DIR__ . '/modules/system/tests/src/Functional/Theme/ThemeUiTest.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to deprecated method withConsecutive\\(\\) of class PHPUnit\\\\Framework\\\\MockObject\\\\Builder\\\\InvocationMocker\\.$#',
	'count' => 2,
	'path' => __DIR__ . '/modules/system/tests/src/Kernel/System/CronQueueTest.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to deprecated constant REQUEST_TIME\\: Deprecated in drupal\\:8\\.3\\.0 and is removed from drupal\\:11\\.0\\.0\\. Use \\\\Drupal\\:\\:time\\(\\)\\-\\>getRequestTime\\(\\); $#',
	'count' => 1,
	'path' => __DIR__ . '/modules/system/tests/src/Kernel/Token/TokenReplaceKernelTest.php',
];
$ignoreErrors[] = [
	'message' => '#^Variable \\$help_message might not be defined\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/modules/taxonomy/src/Form/OverviewTerms.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Drupal\\\\taxonomy\\\\Plugin\\\\migrate\\\\source\\\\d7\\\\TermTranslation\\:\\:prepareRow\\(\\) should return bool but return statement is missing\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/modules/taxonomy/src/Plugin/migrate/source/d7/TermTranslation.php',
];
$ignoreErrors[] = [
	'message' => '#^Variable \\$inner_count might not be defined\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/modules/taxonomy/src/Plugin/views/argument/IndexTidDepth.php',
];
$ignoreErrors[] = [
	'message' => '#^Variable \\$inner_count might not be defined\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/modules/taxonomy/src/Plugin/views/filter/TaxonomyIndexTidDepth.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Drupal\\\\taxonomy\\\\TermForm\\:\\:save\\(\\) should return int but return statement is missing\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/modules/taxonomy/src/TermForm.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Drupal\\\\taxonomy\\\\TermForm\\:\\:validateForm\\(\\) should return Drupal\\\\Core\\\\Entity\\\\ContentEntityInterface but return statement is missing\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/modules/taxonomy/src/TermForm.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Drupal\\\\taxonomy\\\\VocabularyForm\\:\\:save\\(\\) should return int but return statement is missing\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/modules/taxonomy/src/VocabularyForm.php',
];
$ignoreErrors[] = [
	'message' => '#^Variable \\$depth_count might not be defined\\.$#',
	'count' => 4,
	'path' => __DIR__ . '/modules/taxonomy/tests/src/Kernel/TermKernelTest.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to deprecated constant REQUEST_TIME\\: Deprecated in drupal\\:8\\.3\\.0 and is removed from drupal\\:11\\.0\\.0\\. Use \\\\Drupal\\:\\:time\\(\\)\\-\\>getRequestTime\\(\\); $#',
	'count' => 1,
	'path' => __DIR__ . '/modules/toolbar/src/Controller/ToolbarController.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Drupal\\\\tour\\\\TipPluginBase\\:\\:get\\(\\) should return string but return statement is missing\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/modules/tour/src/TipPluginBase.php',
];
$ignoreErrors[] = [
	'message' => '#^Variable \\$location on left side of \\?\\? always exists and is not nullable\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/modules/tour/src/TipPluginBase.php',
];
$ignoreErrors[] = [
	'message' => '#^Variable \\$violation_messages might not be defined\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/modules/update/src/ProjectRelease.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to deprecated constant REQUEST_TIME\\: Deprecated in drupal\\:8\\.3\\.0 and is removed from drupal\\:11\\.0\\.0\\. Use \\\\Drupal\\:\\:time\\(\\)\\-\\>getRequestTime\\(\\); $#',
	'count' => 4,
	'path' => __DIR__ . '/modules/update/src/UpdateProcessor.php',
];
$ignoreErrors[] = [
	'message' => '#^Class Drupal\\\\update\\\\ProjectCoreCompatibility constructor invoked with 3 parameters, 2 required\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/modules/update/tests/src/Unit/ProjectCoreCompatibilityTest.php',
];
$ignoreErrors[] = [
	'message' => '#^Variable \\$users might not be defined\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/modules/user/src/Controller/UserAuthenticationController.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to deprecated constant REQUEST_TIME\\: Deprecated in drupal\\:8\\.3\\.0 and is removed from drupal\\:11\\.0\\.0\\. Use \\\\Drupal\\:\\:time\\(\\)\\-\\>getRequestTime\\(\\); $#',
	'count' => 1,
	'path' => __DIR__ . '/modules/user/src/Controller/UserController.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to deprecated constant REQUEST_TIME\\: Deprecated in drupal\\:8\\.3\\.0 and is removed from drupal\\:11\\.0\\.0\\. Use \\\\Drupal\\:\\:time\\(\\)\\-\\>getRequestTime\\(\\); $#',
	'count' => 2,
	'path' => __DIR__ . '/modules/user/src/EventSubscriber/UserRequestSubscriber.php',
];
$ignoreErrors[] = [
	'message' => '#^Variable \\$route_object might not be defined\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/modules/user/src/Plugin/LanguageNegotiation/LanguageNegotiationUserAdmin.php',
];
$ignoreErrors[] = [
	'message' => '#^Variable \\$account in isset\\(\\) always exists and is not nullable\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/modules/user/src/Plugin/Validation/Constraint/ProtectedUserFieldConstraintValidator.php',
];
$ignoreErrors[] = [
	'message' => '#^Variable \\$account in isset\\(\\) always exists and is not nullable\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/modules/user/src/Plugin/Validation/Constraint/UserMailRequiredValidator.php',
];
$ignoreErrors[] = [
	'message' => '#^Variable \\$items in isset\\(\\) always exists and is not nullable\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/modules/user/src/Plugin/Validation/Constraint/UserMailRequiredValidator.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Drupal\\\\user\\\\Plugin\\\\views\\\\field\\\\UserData\\:\\:render\\(\\) should return Drupal\\\\Component\\\\Render\\\\MarkupInterface\\|string but return statement is missing\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/modules/user/src/Plugin/views/field/UserData.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Drupal\\\\user\\\\ProfileForm\\:\\:save\\(\\) should return int but return statement is missing\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/modules/user/src/ProfileForm.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Drupal\\\\user\\\\RegisterForm\\:\\:save\\(\\) should return int but return statement is missing\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/modules/user/src/RegisterForm.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Drupal\\\\user\\\\RoleForm\\:\\:save\\(\\) should return int but return statement is missing\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/modules/user/src/RoleForm.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to deprecated constant REQUEST_TIME\\: Deprecated in drupal\\:8\\.3\\.0 and is removed from drupal\\:11\\.0\\.0\\. Use \\\\Drupal\\:\\:time\\(\\)\\-\\>getRequestTime\\(\\); $#',
	'count' => 2,
	'path' => __DIR__ . '/modules/user/tests/src/Functional/UserCreateTest.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to deprecated constant REQUEST_TIME\\: Deprecated in drupal\\:8\\.3\\.0 and is removed from drupal\\:11\\.0\\.0\\. Use \\\\Drupal\\:\\:time\\(\\)\\-\\>getRequestTime\\(\\); $#',
	'count' => 1,
	'path' => __DIR__ . '/modules/user/tests/src/Functional/UserEditTest.php',
];
$ignoreErrors[] = [
	'message' => '#^Variable \\$name in isset\\(\\) always exists and is not nullable\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/modules/user/tests/src/Functional/UserLoginHttpTest.php',
];
$ignoreErrors[] = [
	'message' => '#^Variable \\$pass in isset\\(\\) always exists and is not nullable\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/modules/user/tests/src/Functional/UserLoginHttpTest.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to deprecated constant REQUEST_TIME\\: Deprecated in drupal\\:8\\.3\\.0 and is removed from drupal\\:11\\.0\\.0\\. Use \\\\Drupal\\:\\:time\\(\\)\\-\\>getRequestTime\\(\\); $#',
	'count' => 5,
	'path' => __DIR__ . '/modules/user/tests/src/Functional/UserPasswordResetTest.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to deprecated constant REQUEST_TIME\\: Deprecated in drupal\\:8\\.3\\.0 and is removed from drupal\\:11\\.0\\.0\\. Use \\\\Drupal\\:\\:time\\(\\)\\-\\>getRequestTime\\(\\); $#',
	'count' => 1,
	'path' => __DIR__ . '/modules/user/tests/src/Functional/UserPictureTest.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to an undefined method Drupal\\\\Tests\\\\user\\\\Functional\\\\UserRegistrationRestTest\\:\\:getExpectedUnauthorizedEntityAccessCacheability\\(\\)\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/modules/user/tests/src/Functional/UserRegistrationRestTest.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to deprecated constant REQUEST_TIME\\: Deprecated in drupal\\:8\\.3\\.0 and is removed from drupal\\:11\\.0\\.0\\. Use \\\\Drupal\\:\\:time\\(\\)\\-\\>getRequestTime\\(\\); $#',
	'count' => 1,
	'path' => __DIR__ . '/modules/user/tests/src/Functional/UserRegistrationTest.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to deprecated constant REQUEST_TIME\\: Deprecated in drupal\\:8\\.3\\.0 and is removed from drupal\\:11\\.0\\.0\\. Use \\\\Drupal\\:\\:time\\(\\)\\-\\>getRequestTime\\(\\); $#',
	'count' => 1,
	'path' => __DIR__ . '/modules/user/tests/src/Functional/Views/UserChangedTest.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to deprecated method expectWarning\\(\\) of class PHPUnit\\\\Framework\\\\TestCase\\:
https\\://github\\.com/sebastianbergmann/phpunit/issues/5062$#',
	'count' => 1,
	'path' => __DIR__ . '/modules/user/tests/src/Kernel/Views/HandlerFilterRolesTest.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to deprecated method expectWarningMessage\\(\\) of class PHPUnit\\\\Framework\\\\TestCase\\:
https\\://github\\.com/sebastianbergmann/phpunit/issues/5062$#',
	'count' => 1,
	'path' => __DIR__ . '/modules/user/tests/src/Kernel/Views/HandlerFilterRolesTest.php',
];
$ignoreErrors[] = [
	'message' => '#^Variable \\$result in isset\\(\\) always exists and is not nullable\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/modules/user/tests/src/Unit/UserAccessControlHandlerTest.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Drupal\\\\views\\\\Form\\\\ViewsFormMainForm\\:\\:getFormId\\(\\) should return string but return statement is missing\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/modules/views/src/Form/ViewsFormMainForm.php',
];
$ignoreErrors[] = [
	'message' => '#^Variable \\$display in isset\\(\\) always exists and is not nullable\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/modules/views/src/Plugin/Derivative/ViewsBlock.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to an undefined method \\$this\\(Drupal\\\\views\\\\Plugin\\\\views\\\\HandlerBase\\)\\:\\:getFormula\\(\\)\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/modules/views/src/Plugin/views/HandlerBase.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Drupal\\\\views\\\\Plugin\\\\views\\\\HandlerBase\\:\\:getTableJoin\\(\\) should return Drupal\\\\views\\\\Plugin\\\\views\\\\join\\\\JoinPluginBase but return statement is missing\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/modules/views/src/Plugin/views/HandlerBase.php',
];
$ignoreErrors[] = [
	'message' => '#^Variable \\$group_types might not be defined\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/modules/views/src/Plugin/views/HandlerBase.php',
];
$ignoreErrors[] = [
	'message' => '#^Variable \\$items might not be defined\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/modules/views/src/Plugin/views/area/Broken.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Drupal\\\\views\\\\Plugin\\\\views\\\\area\\\\HTTPStatusCode\\:\\:render\\(\\) should return array but return statement is missing\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/modules/views/src/Plugin/views/area/HTTPStatusCode.php',
];
$ignoreErrors[] = [
	'message' => '#^Variable \\$options_name might not be defined\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/modules/views/src/Plugin/views/argument/ArgumentPluginBase.php',
];
$ignoreErrors[] = [
	'message' => '#^Variable \\$plugin_name might not be defined\\.$#',
	'count' => 2,
	'path' => __DIR__ . '/modules/views/src/Plugin/views/argument/ArgumentPluginBase.php',
];
$ignoreErrors[] = [
	'message' => '#^Variable \\$validate_types might not be defined\\.$#',
	'count' => 2,
	'path' => __DIR__ . '/modules/views/src/Plugin/views/argument/ArgumentPluginBase.php',
];
$ignoreErrors[] = [
	'message' => '#^Variable \\$items might not be defined\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/modules/views/src/Plugin/views/argument/Broken.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to deprecated constant REQUEST_TIME\\: Deprecated in drupal\\:8\\.3\\.0 and is removed from drupal\\:11\\.0\\.0\\. Use \\\\Drupal\\:\\:time\\(\\)\\-\\>getRequestTime\\(\\); $#',
	'count' => 1,
	'path' => __DIR__ . '/modules/views/src/Plugin/views/argument/Date.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Drupal\\\\views\\\\Plugin\\\\views\\\\cache\\\\CachePluginBase\\:\\:cacheGet\\(\\) should return bool but return statement is missing\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/modules/views/src/Plugin/views/cache/CachePluginBase.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to deprecated constant REQUEST_TIME\\: Deprecated in drupal\\:8\\.3\\.0 and is removed from drupal\\:11\\.0\\.0\\. Use \\\\Drupal\\:\\:time\\(\\)\\-\\>getRequestTime\\(\\); $#',
	'count' => 1,
	'path' => __DIR__ . '/modules/views/src/Plugin/views/cache/Time.php',
];
$ignoreErrors[] = [
	'message' => '#^Constructor of class Drupal\\\\views\\\\Plugin\\\\views\\\\display\\\\DisplayPluginBase has an unused parameter \\$configuration\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/modules/views/src/Plugin/views/display/DisplayPluginBase.php',
];
$ignoreErrors[] = [
	'message' => '#^Variable \\$pager in isset\\(\\) always exists and is not nullable\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/modules/views/src/Plugin/views/display/DisplayPluginBase.php',
];
$ignoreErrors[] = [
	'message' => '#^Variable \\$style in empty\\(\\) always exists and is not falsy\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/modules/views/src/Plugin/views/display/DisplayPluginBase.php',
];
$ignoreErrors[] = [
	'message' => '#^Variable \\$access_plugin in isset\\(\\) always exists and is not nullable\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/modules/views/src/Plugin/views/display/PathPluginBase.php',
];
$ignoreErrors[] = [
	'message' => '#^Variable \\$options might not be defined\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/modules/views/src/Plugin/views/field/Boolean.php',
];
$ignoreErrors[] = [
	'message' => '#^Variable \\$items might not be defined\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/modules/views/src/Plugin/views/field/Broken.php',
];
$ignoreErrors[] = [
	'message' => '#^Variable \\$entity in empty\\(\\) always exists and is not falsy\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/modules/views/src/Plugin/views/field/BulkForm.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to deprecated constant REQUEST_TIME\\: Deprecated in drupal\\:8\\.3\\.0 and is removed from drupal\\:11\\.0\\.0\\. Use \\\\Drupal\\:\\:time\\(\\)\\-\\>getRequestTime\\(\\); $#',
	'count' => 2,
	'path' => __DIR__ . '/modules/views/src/Plugin/views/field/Date.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Drupal\\\\views\\\\Plugin\\\\views\\\\field\\\\Date\\:\\:render\\(\\) should return Drupal\\\\Component\\\\Render\\\\MarkupInterface\\|string but return statement is missing\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/modules/views/src/Plugin/views/field/Date.php',
];
$ignoreErrors[] = [
	'message' => '#^Variable \\$custom_format might not be defined\\.$#',
	'count' => 9,
	'path' => __DIR__ . '/modules/views/src/Plugin/views/field/Date.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Drupal\\\\views\\\\Plugin\\\\views\\\\field\\\\EntityField\\:\\:getFieldStorageDefinition\\(\\) should return Drupal\\\\Core\\\\Field\\\\FieldStorageDefinitionInterface but return statement is missing\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/modules/views/src/Plugin/views/field/EntityField.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Drupal\\\\views\\\\Plugin\\\\views\\\\field\\\\EntityField\\:\\:renderItems\\(\\) should return string but return statement is missing\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/modules/views/src/Plugin/views/field/EntityField.php',
];
$ignoreErrors[] = [
	'message' => '#^Variable \\$field_item_list in isset\\(\\) always exists and is not nullable\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/modules/views/src/Plugin/views/field/EntityField.php',
];
$ignoreErrors[] = [
	'message' => '#^Variable \\$options in empty\\(\\) always exists and is not falsy\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/modules/views/src/Plugin/views/field/FieldPluginBase.php',
];
$ignoreErrors[] = [
	'message' => '#^Variable \\$raw_items might not be defined\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/modules/views/src/Plugin/views/field/FieldPluginBase.php',
];
$ignoreErrors[] = [
	'message' => '#^Variable \\$style in isset\\(\\) always exists and is not nullable\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/modules/views/src/Plugin/views/field/FieldPluginBase.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Drupal\\\\views\\\\Plugin\\\\views\\\\field\\\\Markup\\:\\:render\\(\\) should return Drupal\\\\Component\\\\Render\\\\MarkupInterface\\|string but return statement is missing\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/modules/views/src/Plugin/views/field/Markup.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Drupal\\\\views\\\\Plugin\\\\views\\\\field\\\\PrerenderList\\:\\:renderItems\\(\\) should return string but return statement is missing\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/modules/views/src/Plugin/views/field/PrerenderList.php',
];
$ignoreErrors[] = [
	'message' => '#^Variable \\$items might not be defined\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/modules/views/src/Plugin/views/filter/Broken.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to an undefined method Drupal\\\\views\\\\Plugin\\\\views\\\\filter\\\\FilterPluginBase\\:\\:operators\\(\\)\\.$#',
	'count' => 2,
	'path' => __DIR__ . '/modules/views/src/Plugin/views/filter/FilterPluginBase.php',
];
$ignoreErrors[] = [
	'message' => '#^Variable \\$groups might not be defined\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/modules/views/src/Plugin/views/filter/FilterPluginBase.php',
];
$ignoreErrors[] = [
	'message' => '#^Variable \\$source might not be defined\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/modules/views/src/Plugin/views/filter/InOperator.php',
];
$ignoreErrors[] = [
	'message' => '#^Variable \\$source might not be defined\\.$#',
	'count' => 2,
	'path' => __DIR__ . '/modules/views/src/Plugin/views/filter/NumericFilter.php',
];
$ignoreErrors[] = [
	'message' => '#^Variable \\$source might not be defined\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/modules/views/src/Plugin/views/filter/StringFilter.php',
];
$ignoreErrors[] = [
	'message' => '#^Variable \\$left in isset\\(\\) always exists and is not nullable\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/modules/views/src/Plugin/views/join/JoinPluginBase.php',
];
$ignoreErrors[] = [
	'message' => '#^Variable \\$join in empty\\(\\) always exists and is not falsy\\.$#',
	'count' => 2,
	'path' => __DIR__ . '/modules/views/src/Plugin/views/query/Sql.php',
];
$ignoreErrors[] = [
	'message' => '#^Variable \\$items might not be defined\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/modules/views/src/Plugin/views/relationship/Broken.php',
];
$ignoreErrors[] = [
	'message' => '#^Variable \\$sort_field might not be defined\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/modules/views/src/Plugin/views/relationship/GroupwiseMax.php',
];
$ignoreErrors[] = [
	'message' => '#^Variable \\$sort_table might not be defined\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/modules/views/src/Plugin/views/relationship/GroupwiseMax.php',
];
$ignoreErrors[] = [
	'message' => '#^Variable \\$items might not be defined\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/modules/views/src/Plugin/views/sort/Broken.php',
];
$ignoreErrors[] = [
	'message' => '#^Variable \\$plugin in empty\\(\\) always exists and is not falsy\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/modules/views/src/Plugin/views/style/StylePluginBase.php',
];
$ignoreErrors[] = [
	'message' => '#^Variable \\$created_column in isset\\(\\) always exists and is not nullable\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/modules/views/src/Plugin/views/wizard/WizardPluginBase.php',
];
$ignoreErrors[] = [
	'message' => '#^Variable \\$default_field might not be defined\\.$#',
	'count' => 6,
	'path' => __DIR__ . '/modules/views/src/Plugin/views/wizard/WizardPluginBase.php',
];
$ignoreErrors[] = [
	'message' => '#^Variable \\$sorts in empty\\(\\) always exists and is not falsy\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/modules/views/src/Plugin/views/wizard/WizardPluginBase.php',
];
$ignoreErrors[] = [
	'message' => '#^Variable \\$style_plugin in isset\\(\\) always exists and is not nullable\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/modules/views/src/Plugin/views/wizard/WizardPluginBase.php',
];
$ignoreErrors[] = [
	'message' => '#^Variable \\$view in empty\\(\\) always exists and is not falsy\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/modules/views/src/Plugin/views/wizard/WizardPluginBase.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to deprecated constant REQUEST_TIME\\: Deprecated in drupal\\:8\\.3\\.0 and is removed from drupal\\:11\\.0\\.0\\. Use \\\\Drupal\\:\\:time\\(\\)\\-\\>getRequestTime\\(\\); $#',
	'count' => 1,
	'path' => __DIR__ . '/modules/views/src/ViewExecutable.php',
];
$ignoreErrors[] = [
	'message' => '#^Variable \\$view in isset\\(\\) always exists and is not nullable\\.$#',
	'count' => 2,
	'path' => __DIR__ . '/modules/views/tests/modules/views_test_data/views_test_data.views_execution.inc',
];
$ignoreErrors[] = [
	'message' => '#^Call to deprecated constant REQUEST_TIME\\: Deprecated in drupal\\:8\\.3\\.0 and is removed from drupal\\:11\\.0\\.0\\. Use \\\\Drupal\\:\\:time\\(\\)\\-\\>getRequestTime\\(\\); $#',
	'count' => 1,
	'path' => __DIR__ . '/modules/views/tests/src/Functional/BulkFormTest.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to deprecated constant REQUEST_TIME\\: Deprecated in drupal\\:8\\.3\\.0 and is removed from drupal\\:11\\.0\\.0\\. Use \\\\Drupal\\:\\:time\\(\\)\\-\\>getRequestTime\\(\\); $#',
	'count' => 2,
	'path' => __DIR__ . '/modules/views/tests/src/Functional/DefaultViewsTest.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to deprecated constant REQUEST_TIME\\: Deprecated in drupal\\:8\\.3\\.0 and is removed from drupal\\:11\\.0\\.0\\. Use \\\\Drupal\\:\\:time\\(\\)\\-\\>getRequestTime\\(\\); $#',
	'count' => 2,
	'path' => __DIR__ . '/modules/views/tests/src/Functional/GlossaryTest.php',
];
$ignoreErrors[] = [
	'message' => '#^Variable \\$link might not be defined\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/modules/views/tests/src/Functional/TaxonomyGlossaryTest.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to deprecated constant REQUEST_TIME\\: Deprecated in drupal\\:8\\.3\\.0 and is removed from drupal\\:11\\.0\\.0\\. Use \\\\Drupal\\:\\:time\\(\\)\\-\\>getRequestTime\\(\\); $#',
	'count' => 6,
	'path' => __DIR__ . '/modules/views/tests/src/Functional/Wizard/ItemsPerPageTest.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to deprecated constant REQUEST_TIME\\: Deprecated in drupal\\:8\\.3\\.0 and is removed from drupal\\:11\\.0\\.0\\. Use \\\\Drupal\\:\\:time\\(\\)\\-\\>getRequestTime\\(\\); $#',
	'count' => 1,
	'path' => __DIR__ . '/modules/views/tests/src/Functional/Wizard/PagerTest.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to deprecated constant REQUEST_TIME\\: Deprecated in drupal\\:8\\.3\\.0 and is removed from drupal\\:11\\.0\\.0\\. Use \\\\Drupal\\:\\:time\\(\\)\\-\\>getRequestTime\\(\\); $#',
	'count' => 3,
	'path' => __DIR__ . '/modules/views/tests/src/Functional/Wizard/SortingTest.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to deprecated constant REQUEST_TIME\\: Deprecated in drupal\\:8\\.3\\.0 and is removed from drupal\\:11\\.0\\.0\\. Use \\\\Drupal\\:\\:time\\(\\)\\-\\>getRequestTime\\(\\); $#',
	'count' => 2,
	'path' => __DIR__ . '/modules/views/tests/src/FunctionalJavascript/ClickSortingAJAXTest.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to deprecated constant REQUEST_TIME\\: Deprecated in drupal\\:8\\.3\\.0 and is removed from drupal\\:11\\.0\\.0\\. Use \\\\Drupal\\:\\:time\\(\\)\\-\\>getRequestTime\\(\\); $#',
	'count' => 3,
	'path' => __DIR__ . '/modules/views/tests/src/Kernel/Handler/FieldDropbuttonTest.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to deprecated constant REQUEST_TIME\\: Deprecated in drupal\\:8\\.3\\.0 and is removed from drupal\\:11\\.0\\.0\\. Use \\\\Drupal\\:\\:time\\(\\)\\-\\>getRequestTime\\(\\); $#',
	'count' => 1,
	'path' => __DIR__ . '/modules/views/tests/src/Kernel/Handler/FieldFieldTest.php',
];
$ignoreErrors[] = [
	'message' => '#^Variable \\$rand1 might not be defined\\.$#',
	'count' => 2,
	'path' => __DIR__ . '/modules/views/tests/src/Kernel/Plugin/StyleTest.php',
];
$ignoreErrors[] = [
	'message' => '#^Variable \\$rand2 might not be defined\\.$#',
	'count' => 2,
	'path' => __DIR__ . '/modules/views/tests/src/Kernel/Plugin/StyleTest.php',
];
$ignoreErrors[] = [
	'message' => '#^Variable \\$rand3 might not be defined\\.$#',
	'count' => 2,
	'path' => __DIR__ . '/modules/views/tests/src/Kernel/Plugin/StyleTest.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to deprecated constant REQUEST_TIME\\: Deprecated in drupal\\:8\\.3\\.0 and is removed from drupal\\:11\\.0\\.0\\. Use \\\\Drupal\\:\\:time\\(\\)\\-\\>getRequestTime\\(\\); $#',
	'count' => 2,
	'path' => __DIR__ . '/modules/views/tests/src/Kernel/RenderCacheIntegrationTest.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to deprecated method withConsecutive\\(\\) of class PHPUnit\\\\Framework\\\\MockObject\\\\Builder\\\\InvocationMocker\\.$#',
	'count' => 10,
	'path' => __DIR__ . '/modules/views/tests/src/Unit/ViewsDataTest.php',
];
$ignoreErrors[] = [
	'message' => '#^Variable \\$relationship_handler in empty\\(\\) always exists and is not falsy\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/modules/views_ui/src/Form/Ajax/ConfigHandler.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot unset offset \'\\#title\' on array\\{\\#theme_wrappers\\: array\\{\'container\'\\}, \\#attributes\\: array\\{class\\: array\\{\'scroll\'\\}, data\\-drupal\\-views\\-scroll\\: true\\}\\}\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/modules/views_ui/src/Form/Ajax/Display.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Drupal\\\\views_ui\\\\ViewEditForm\\:\\:save\\(\\) should return int but return statement is missing\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/modules/views_ui/src/ViewEditForm.php',
];
$ignoreErrors[] = [
	'message' => '#^Variable \\$group_info might not be defined\\.$#',
	'count' => 2,
	'path' => __DIR__ . '/modules/views_ui/src/ViewEditForm.php',
];
$ignoreErrors[] = [
	'message' => '#^Variable \\$display_plugin in empty\\(\\) always exists and is not falsy\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/modules/views_ui/src/ViewFormBase.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Drupal\\\\views_ui\\\\ViewUI\\:\\:getTypedData\\(\\) should return Drupal\\\\Core\\\\TypedData\\\\ComplexDataInterface but return statement is missing\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/modules/views_ui/src/ViewUI.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Drupal\\\\views_ui\\\\ViewUI\\:\\:set\\(\\) should return \\$this\\(Drupal\\\\views_ui\\\\ViewUI\\) but return statement is missing\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/modules/views_ui/src/ViewUI.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Drupal\\\\views_ui\\\\ViewUI\\:\\:setSyncing\\(\\) should return \\$this\\(Drupal\\\\views_ui\\\\ViewUI\\) but return statement is missing\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/modules/views_ui/src/ViewUI.php',
];
$ignoreErrors[] = [
	'message' => '#^Variable \\$message in isset\\(\\) always exists and is not nullable\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/modules/views_ui/tests/src/FunctionalJavascript/PreviewTest.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Drupal\\\\workflows\\\\Form\\\\WorkflowEditForm\\:\\:save\\(\\) should return int but return statement is missing\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/modules/workflows/src/Form/WorkflowEditForm.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Drupal\\\\workflows\\\\Form\\\\WorkflowStateAddForm\\:\\:save\\(\\) should return int but return statement is missing\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/modules/workflows/src/Form/WorkflowStateAddForm.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Drupal\\\\workflows\\\\Form\\\\WorkflowStateEditForm\\:\\:save\\(\\) should return int but return statement is missing\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/modules/workflows/src/Form/WorkflowStateEditForm.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Drupal\\\\workflows\\\\Form\\\\WorkflowTransitionAddForm\\:\\:save\\(\\) should return int but return statement is missing\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/modules/workflows/src/Form/WorkflowTransitionAddForm.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Drupal\\\\workflows\\\\Form\\\\WorkflowTransitionEditForm\\:\\:save\\(\\) should return int but return statement is missing\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/modules/workflows/src/Form/WorkflowTransitionEditForm.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Drupal\\\\workspaces\\\\EntityTypeInfo\\:\\:entityBaseFieldInfo\\(\\) should return array\\<Drupal\\\\Core\\\\Field\\\\FieldDefinitionInterface\\> but return statement is missing\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/modules/workspaces/src/EntityTypeInfo.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Drupal\\\\workspaces\\\\Form\\\\WorkspaceForm\\:\\:save\\(\\) should return int but return statement is missing\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/modules/workspaces/src/Form/WorkspaceForm.php',
];
$ignoreErrors[] = [
	'message' => '#^Variable \\$value in isset\\(\\) always exists and is not nullable\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/modules/workspaces/src/Plugin/Validation/Constraint/DeletedWorkspaceConstraintValidator.php',
];
$ignoreErrors[] = [
	'message' => '#^Variable \\$entity in isset\\(\\) always exists and is not nullable\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/modules/workspaces/src/Plugin/Validation/Constraint/EntityWorkspaceConflictConstraintValidator.php',
];
$ignoreErrors[] = [
	'message' => '#^Variable \\$transaction in isset\\(\\) always exists and is not nullable\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/modules/workspaces/src/WorkspaceAssociation.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Drupal\\\\workspaces\\\\WorkspaceMerger\\:\\:checkConflictsOnTarget\\(\\) should return array but return statement is missing\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/modules/workspaces/src/WorkspaceMerger.php',
];
$ignoreErrors[] = [
	'message' => '#^Variable \\$transaction in isset\\(\\) always exists and is not nullable\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/modules/workspaces/src/WorkspaceMerger.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Drupal\\\\workspaces\\\\WorkspacePublisher\\:\\:checkConflictsOnTarget\\(\\) should return array but return statement is missing\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/modules/workspaces/src/WorkspacePublisher.php',
];
$ignoreErrors[] = [
	'message' => '#^Variable \\$keyed_content might not be defined\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/profiles/demo_umami/modules/demo_umami_content/src/InstallHelper.php',
];
$ignoreErrors[] = [
	'message' => '#^Variable \\$structured_content might not be defined\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/profiles/demo_umami/modules/demo_umami_content/src/InstallHelper.php',
];
$ignoreErrors[] = [
	'message' => '#^Variable \\$project_stabilities might not be defined\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/tests/Drupal/BuildTests/Composer/Template/ComposerProjectTemplatesTest.php',
];
$ignoreErrors[] = [
	'message' => '#^Usage of deprecated trait Drupal\\\\BuildTests\\\\Framework\\\\ExternalCommandRequirementsTrait in class Drupal\\\\BuildTests\\\\Framework\\\\Tests\\\\ClassRequiresAvailable\\:
in drupal\\:10\\.2\\.0 and is removed from drupal\\:11\\.0\\.0\\. Use
Drupal\\\\\\\\TestTools\\\\\\\\Extension\\\\\\\\RequiresComposerTrait instead\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/tests/Drupal/BuildTests/Framework/Tests/ExternalCommandRequirementTest.php',
];
$ignoreErrors[] = [
	'message' => '#^Usage of deprecated trait Drupal\\\\BuildTests\\\\Framework\\\\ExternalCommandRequirementsTrait in class Drupal\\\\BuildTests\\\\Framework\\\\Tests\\\\ClassRequiresUnavailable\\:
in drupal\\:10\\.2\\.0 and is removed from drupal\\:11\\.0\\.0\\. Use
Drupal\\\\\\\\TestTools\\\\\\\\Extension\\\\\\\\RequiresComposerTrait instead\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/tests/Drupal/BuildTests/Framework/Tests/ExternalCommandRequirementTest.php',
];
$ignoreErrors[] = [
	'message' => '#^Usage of deprecated trait Drupal\\\\BuildTests\\\\Framework\\\\ExternalCommandRequirementsTrait in class Drupal\\\\BuildTests\\\\Framework\\\\Tests\\\\MethodRequires\\:
in drupal\\:10\\.2\\.0 and is removed from drupal\\:11\\.0\\.0\\. Use
Drupal\\\\\\\\TestTools\\\\\\\\Extension\\\\\\\\RequiresComposerTrait instead\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/tests/Drupal/BuildTests/Framework/Tests/ExternalCommandRequirementTest.php',
];
$ignoreErrors[] = [
	'message' => '#^Usage of deprecated trait Drupal\\\\BuildTests\\\\Framework\\\\ExternalCommandRequirementsTrait in class Drupal\\\\BuildTests\\\\Framework\\\\Tests\\\\UsesCommandRequirements\\:
in drupal\\:10\\.2\\.0 and is removed from drupal\\:11\\.0\\.0\\. Use
Drupal\\\\\\\\TestTools\\\\\\\\Extension\\\\\\\\RequiresComposerTrait instead\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/tests/Drupal/BuildTests/Framework/Tests/ExternalCommandRequirementTest.php',
];
$ignoreErrors[] = [
	'message' => '#^Variable \\$found might not be defined\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/tests/Drupal/KernelTests/Core/Cache/ApcuBackendTest.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to deprecated constant REQUEST_TIME\\: Deprecated in drupal\\:8\\.3\\.0 and is removed from drupal\\:11\\.0\\.0\\. Use \\\\Drupal\\:\\:time\\(\\)\\-\\>getRequestTime\\(\\); $#',
	'count' => 12,
	'path' => __DIR__ . '/tests/Drupal/KernelTests/Core/Cache/GenericCacheBackendUnitTestBase.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to deprecated method expectWarning\\(\\) of class PHPUnit\\\\Framework\\\\TestCase\\:
https\\://github\\.com/sebastianbergmann/phpunit/issues/5062$#',
	'count' => 1,
	'path' => __DIR__ . '/tests/Drupal/KernelTests/Core/Config/ConfigInstallTest.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to deprecated method expectWarningMessage\\(\\) of class PHPUnit\\\\Framework\\\\TestCase\\:
https\\://github\\.com/sebastianbergmann/phpunit/issues/5062$#',
	'count' => 1,
	'path' => __DIR__ . '/tests/Drupal/KernelTests/Core/Config/ConfigInstallTest.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to deprecated method expectError\\(\\) of class PHPUnit\\\\Framework\\\\TestCase\\:
https\\://github\\.com/sebastianbergmann/phpunit/issues/5062$#',
	'count' => 2,
	'path' => __DIR__ . '/tests/Drupal/KernelTests/Core/Database/ConnectionTest.php',
];
$ignoreErrors[] = [
	'message' => '#^Variable \\$expected_driver might not be defined\\.$#',
	'count' => 2,
	'path' => __DIR__ . '/tests/Drupal/KernelTests/Core/Database/DriverSpecificKernelTestBase.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to deprecated method expectError\\(\\) of class PHPUnit\\\\Framework\\\\TestCase\\:
https\\://github\\.com/sebastianbergmann/phpunit/issues/5062$#',
	'count' => 3,
	'path' => __DIR__ . '/tests/Drupal/KernelTests/Core/Database/StatementTest.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to deprecated method expectErrorMessage\\(\\) of class PHPUnit\\\\Framework\\\\TestCase\\:
https\\://github\\.com/sebastianbergmann/phpunit/issues/5062$#',
	'count' => 3,
	'path' => __DIR__ . '/tests/Drupal/KernelTests/Core/Database/StatementTest.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to deprecated constant REQUEST_TIME\\: Deprecated in drupal\\:8\\.3\\.0 and is removed from drupal\\:11\\.0\\.0\\. Use \\\\Drupal\\:\\:time\\(\\)\\-\\>getRequestTime\\(\\); $#',
	'count' => 4,
	'path' => __DIR__ . '/tests/Drupal/KernelTests/Core/Entity/ContentEntityChangedTest.php',
];
$ignoreErrors[] = [
	'message' => '#^Variable \\$title might not be defined\\.$#',
	'count' => 2,
	'path' => __DIR__ . '/tests/Drupal/KernelTests/Core/Entity/CreateSampleEntityTest.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to deprecated constant REQUEST_TIME\\: Deprecated in drupal\\:8\\.3\\.0 and is removed from drupal\\:11\\.0\\.0\\. Use \\\\Drupal\\:\\:time\\(\\)\\-\\>getRequestTime\\(\\); $#',
	'count' => 9,
	'path' => __DIR__ . '/tests/Drupal/KernelTests/Core/Entity/EntityCrudHookTest.php',
];
$ignoreErrors[] = [
	'message' => '#^Variable \\$previous_untranslatable_field_value in isset\\(\\) always exists and is not nullable\\.$#',
	'count' => 2,
	'path' => __DIR__ . '/tests/Drupal/KernelTests/Core/Entity/EntityDecoupledTranslationRevisionsTest.php',
];
$ignoreErrors[] = [
	'message' => '#^Variable \\$field might not be defined\\.$#',
	'count' => 9,
	'path' => __DIR__ . '/tests/Drupal/KernelTests/Core/Entity/EntitySchemaTest.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to deprecated constant REQUEST_TIME\\: Deprecated in drupal\\:8\\.3\\.0 and is removed from drupal\\:11\\.0\\.0\\. Use \\\\Drupal\\:\\:time\\(\\)\\-\\>getRequestTime\\(\\); $#',
	'count' => 1,
	'path' => __DIR__ . '/tests/Drupal/KernelTests/Core/Entity/EntityTypeConstraintsTest.php',
];
$ignoreErrors[] = [
	'message' => '#^Variable \\$e might not be defined\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/tests/Drupal/KernelTests/Core/Entity/FieldableEntityDefinitionUpdateTest.php',
];
$ignoreErrors[] = [
	'message' => '#^Variable \\$entity might not be defined\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/tests/Drupal/KernelTests/Core/Entity/FieldableEntityDefinitionUpdateTest.php',
];
$ignoreErrors[] = [
	'message' => '#^Variable \\$new_field_schema_data might not be defined\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/tests/Drupal/KernelTests/Core/Entity/FieldableEntityDefinitionUpdateTest.php',
];
$ignoreErrors[] = [
	'message' => '#^Variable \\$x might not be defined\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/tests/Drupal/KernelTests/Core/Image/ToolkitGdTest.php',
];
$ignoreErrors[] = [
	'message' => '#^Variable \\$y might not be defined\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/tests/Drupal/KernelTests/Core/Image/ToolkitGdTest.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to deprecated constant REQUEST_TIME\\: Deprecated in drupal\\:8\\.3\\.0 and is removed from drupal\\:11\\.0\\.0\\. Use \\\\Drupal\\:\\:time\\(\\)\\-\\>getRequestTime\\(\\); $#',
	'count' => 1,
	'path' => __DIR__ . '/tests/Drupal/KernelTests/Core/KeyValueStore/GarbageCollectionTest.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to deprecated method expectErrorMessage\\(\\) of class PHPUnit\\\\Framework\\\\TestCase\\:
https\\://github\\.com/sebastianbergmann/phpunit/issues/5062$#',
	'count' => 1,
	'path' => __DIR__ . '/tests/Drupal/KernelTests/Core/Render/Element/MachineNameTest.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to deprecated constant REQUEST_TIME\\: Deprecated in drupal\\:8\\.3\\.0 and is removed from drupal\\:11\\.0\\.0\\. Use \\\\Drupal\\:\\:time\\(\\)\\-\\>getRequestTime\\(\\); $#',
	'count' => 1,
	'path' => __DIR__ . '/tests/Drupal/KernelTests/Core/TempStore/TempStoreDatabaseTest.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to deprecated constant REQUEST_TIME\\: Deprecated in drupal\\:8\\.3\\.0 and is removed from drupal\\:11\\.0\\.0\\. Use \\\\Drupal\\:\\:time\\(\\)\\-\\>getRequestTime\\(\\); $#',
	'count' => 5,
	'path' => __DIR__ . '/tests/Drupal/KernelTests/Core/TypedData/TypedDataTest.php',
];
$ignoreErrors[] = [
	'message' => '#^Variable \\$value in isset\\(\\) always exists and is not nullable\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/tests/Drupal/KernelTests/KernelTestBase.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to an undefined method Drupal\\\\TestSite\\\\Commands\\\\TestSiteInstallCommand\\:\\:assertTrue\\(\\)\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/tests/Drupal/TestSite/Commands/TestSiteInstallCommand.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to an undefined method Drupal\\\\TestSite\\\\Commands\\\\TestSiteInstallCommand\\:\\:fail\\(\\)\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/tests/Drupal/TestSite/Commands/TestSiteInstallCommand.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to deprecated constant REQUEST_TIME\\: Deprecated in drupal\\:8\\.3\\.0 and is removed from drupal\\:11\\.0\\.0\\. Use \\\\Drupal\\:\\:time\\(\\)\\-\\>getRequestTime\\(\\); $#',
	'count' => 1,
	'path' => __DIR__ . '/tests/Drupal/TestSite/Commands/TestSiteInstallCommand.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to deprecated constant REQUEST_TIME\\: Deprecated in drupal\\:8\\.3\\.0 and is removed from drupal\\:11\\.0\\.0\\. Use \\\\Drupal\\:\\:time\\(\\)\\-\\>getRequestTime\\(\\); $#',
	'count' => 1,
	'path' => __DIR__ . '/tests/Drupal/Tests/BrowserTestBase.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to deprecated method getConfig\\(\\) of class GuzzleHttp\\\\Client\\:
Client\\:\\:getConfig will be removed in guzzlehttp/guzzle\\:8\\.0\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/tests/Drupal/Tests/BrowserTestBase.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to deprecated method registerAutoloadNamespace\\(\\) of class Doctrine\\\\Common\\\\Annotations\\\\AnnotationRegistry\\:
This method is deprecated and will be removed in
            doctrine/annotations 2\\.0\\. Annotations will be autoloaded in 2\\.0\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/tests/Drupal/Tests/Component/Annotation/Doctrine/DocParserTest.php',
];
$ignoreErrors[] = [
	'message' => '#^Constructor of class Symfony\\\\Component\\\\ExpressionLanguage\\\\Expression has an unused parameter \\$expression\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/tests/Drupal/Tests/Component/DependencyInjection/Dumper/OptimizedPhpArrayDumperTest.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to deprecated method expectWarning\\(\\) of class PHPUnit\\\\Framework\\\\TestCase\\:
https\\://github\\.com/sebastianbergmann/phpunit/issues/5062$#',
	'count' => 1,
	'path' => __DIR__ . '/tests/Drupal/Tests/Component/PhpStorage/FileStorageTest.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to deprecated method expectWarningMessage\\(\\) of class PHPUnit\\\\Framework\\\\TestCase\\:
https\\://github\\.com/sebastianbergmann/phpunit/issues/5062$#',
	'count' => 1,
	'path' => __DIR__ . '/tests/Drupal/Tests/Component/PhpStorage/FileStorageTest.php',
];
$ignoreErrors[] = [
	'message' => '#^Constructor of class Drupal\\\\Tests\\\\Component\\\\Plugin\\\\Factory\\\\ArgumentsAllNull has an unused parameter \\$charismatic\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/tests/Drupal/Tests/Component/Plugin/Factory/ReflectionFactoryTest.php',
];
$ignoreErrors[] = [
	'message' => '#^Constructor of class Drupal\\\\Tests\\\\Component\\\\Plugin\\\\Factory\\\\ArgumentsAllNull has an unused parameter \\$delightful\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/tests/Drupal/Tests/Component/Plugin/Factory/ReflectionFactoryTest.php',
];
$ignoreErrors[] = [
	'message' => '#^Constructor of class Drupal\\\\Tests\\\\Component\\\\Plugin\\\\Factory\\\\ArgumentsAllNull has an unused parameter \\$demure\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/tests/Drupal/Tests/Component/Plugin/Factory/ReflectionFactoryTest.php',
];
$ignoreErrors[] = [
	'message' => '#^Constructor of class Drupal\\\\Tests\\\\Component\\\\Plugin\\\\Factory\\\\ArgumentsAllNull has an unused parameter \\$electrostatic\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/tests/Drupal/Tests/Component/Plugin/Factory/ReflectionFactoryTest.php',
];
$ignoreErrors[] = [
	'message' => '#^Constructor of class Drupal\\\\Tests\\\\Component\\\\Plugin\\\\Factory\\\\ArgumentsConfigArrayKey has an unused parameter \\$config_name\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/tests/Drupal/Tests/Component/Plugin/Factory/ReflectionFactoryTest.php',
];
$ignoreErrors[] = [
	'message' => '#^Constructor of class Drupal\\\\Tests\\\\Component\\\\Plugin\\\\Factory\\\\ArgumentsMany has an unused parameter \\$configuration\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/tests/Drupal/Tests/Component/Plugin/Factory/ReflectionFactoryTest.php',
];
$ignoreErrors[] = [
	'message' => '#^Constructor of class Drupal\\\\Tests\\\\Component\\\\Plugin\\\\Factory\\\\ArgumentsMany has an unused parameter \\$foo\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/tests/Drupal/Tests/Component/Plugin/Factory/ReflectionFactoryTest.php',
];
$ignoreErrors[] = [
	'message' => '#^Constructor of class Drupal\\\\Tests\\\\Component\\\\Plugin\\\\Factory\\\\ArgumentsMany has an unused parameter \\$plugin_definition\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/tests/Drupal/Tests/Component/Plugin/Factory/ReflectionFactoryTest.php',
];
$ignoreErrors[] = [
	'message' => '#^Constructor of class Drupal\\\\Tests\\\\Component\\\\Plugin\\\\Factory\\\\ArgumentsMany has an unused parameter \\$plugin_id\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/tests/Drupal/Tests/Component/Plugin/Factory/ReflectionFactoryTest.php',
];
$ignoreErrors[] = [
	'message' => '#^Constructor of class Drupal\\\\Tests\\\\Component\\\\Plugin\\\\Factory\\\\ArgumentsMany has an unused parameter \\$what_am_i_doing_here\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/tests/Drupal/Tests/Component/Plugin/Factory/ReflectionFactoryTest.php',
];
$ignoreErrors[] = [
	'message' => '#^Constructor of class Drupal\\\\Tests\\\\Component\\\\Plugin\\\\Factory\\\\ArgumentsPluginId has an unused parameter \\$plugin_id\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/tests/Drupal/Tests/Component/Plugin/Factory/ReflectionFactoryTest.php',
];
$ignoreErrors[] = [
	'message' => '#^Missing cache backend declaration for performance\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/tests/Drupal/Tests/Component/Plugin/StubPluginManagerBaseWithMapper.php',
];
$ignoreErrors[] = [
	'message' => '#^Result of static method Drupal\\\\Composer\\\\Composer\\:\\:ensureComposerVersion\\(\\) \\(void\\) is used\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/tests/Drupal/Tests/Composer/ComposerTest.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to deprecated method expectError\\(\\) of class PHPUnit\\\\Framework\\\\TestCase\\:
https\\://github\\.com/sebastianbergmann/phpunit/issues/5062$#',
	'count' => 1,
	'path' => __DIR__ . '/tests/Drupal/Tests/Core/Config/ConfigTest.php',
];
$ignoreErrors[] = [
	'message' => '#^Class Drupal\\\\Tests\\\\Core\\\\Controller\\\\MockContainerAware implements deprecated interface Symfony\\\\Component\\\\DependencyInjection\\\\ContainerAwareInterface\\:
since Symfony 6\\.4, use dependency injection instead$#',
	'count' => 1,
	'path' => __DIR__ . '/tests/Drupal/Tests/Core/Controller/ControllerResolverTest.php',
];
$ignoreErrors[] = [
	'message' => '#^Usage of deprecated trait Symfony\\\\Component\\\\DependencyInjection\\\\ContainerAwareTrait in class Drupal\\\\Tests\\\\Core\\\\Controller\\\\MockContainerAware\\:
since Symfony 6\\.4, use dependency injection instead$#',
	'count' => 1,
	'path' => __DIR__ . '/tests/Drupal/Tests/Core/Controller/ControllerResolverTest.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to deprecated method expectError\\(\\) of class PHPUnit\\\\Framework\\\\TestCase\\:
https\\://github\\.com/sebastianbergmann/phpunit/issues/5062$#',
	'count' => 1,
	'path' => __DIR__ . '/tests/Drupal/Tests/Core/Database/ConditionTest.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to deprecated method findCaller\\(\\) of class Drupal\\\\Core\\\\Database\\\\Log\\:
in drupal\\:10\\.1\\.0 and is removed from drupal\\:11\\.0\\.0\\. Use
  Connection\\:\\:findCallerFromDebugBacktrace\\(\\)\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/tests/Drupal/Tests/Core/Database/Stub/StubConnection.php',
];
$ignoreErrors[] = [
	'message' => '#^Fetching class constant class of deprecated class Drupal\\\\Core\\\\Database\\\\StatementWrapper\\:
in drupal\\:10\\.1\\.0 and is removed from drupal\\:11\\.0\\.0\\. Use
  \\\\Drupal\\\\Core\\\\Database\\\\StatementWrapperIterator instead\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/tests/Drupal/Tests/Core/Database/Stub/StubConnection.php',
];
$ignoreErrors[] = [
	'message' => '#^Class Drupal\\\\Tests\\\\Core\\\\DependencyInjection\\\\DependencySerializationTestDummy implements deprecated interface Symfony\\\\Component\\\\DependencyInjection\\\\ContainerAwareInterface\\:
since Symfony 6\\.4, use dependency injection instead$#',
	'count' => 1,
	'path' => __DIR__ . '/tests/Drupal/Tests/Core/DependencyInjection/DependencySerializationTest.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to deprecated method withConsecutive\\(\\) of class PHPUnit\\\\Framework\\\\MockObject\\\\Builder\\\\InvocationMocker\\.$#',
	'count' => 2,
	'path' => __DIR__ . '/tests/Drupal/Tests/Core/Entity/EntityUnitTest.php',
];
$ignoreErrors[] = [
	'message' => '#^Trying to mock an undefined method getRevisionId\\(\\) on class Drupal\\\\Tests\\\\Core\\\\Entity\\\\UrlTestEntity\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/tests/Drupal/Tests/Core/Entity/EntityUrlTest.php',
];
$ignoreErrors[] = [
	'message' => '#^Trying to mock an undefined method isDefaultRevision\\(\\) on class Drupal\\\\Tests\\\\Core\\\\Entity\\\\UrlTestEntity\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/tests/Drupal/Tests/Core/Entity/EntityUrlTest.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to deprecated method expectError\\(\\) of class PHPUnit\\\\Framework\\\\TestCase\\:
https\\://github\\.com/sebastianbergmann/phpunit/issues/5062$#',
	'count' => 2,
	'path' => __DIR__ . '/tests/Drupal/Tests/Core/EventSubscriber/RedirectResponseSubscriberTest.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to deprecated method expectWarning\\(\\) of class PHPUnit\\\\Framework\\\\TestCase\\:
https\\://github\\.com/sebastianbergmann/phpunit/issues/5062$#',
	'count' => 1,
	'path' => __DIR__ . '/tests/Drupal/Tests/Core/EventSubscriber/SpecialAttributesRouteSubscriberTest.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to deprecated method expectWarningMessage\\(\\) of class PHPUnit\\\\Framework\\\\TestCase\\:
https\\://github\\.com/sebastianbergmann/phpunit/issues/5062$#',
	'count' => 1,
	'path' => __DIR__ . '/tests/Drupal/Tests/Core/EventSubscriber/SpecialAttributesRouteSubscriberTest.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to deprecated method withConsecutive\\(\\) of class PHPUnit\\\\Framework\\\\MockObject\\\\Builder\\\\InvocationMocker\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/tests/Drupal/Tests/Core/Extension/ModuleHandlerTest.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to deprecated method withConsecutive\\(\\) of class PHPUnit\\\\Framework\\\\MockObject\\\\Builder\\\\InvocationMocker\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/tests/Drupal/Tests/Core/Form/FormCacheTest.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to deprecated method withConsecutive\\(\\) of class PHPUnit\\\\Framework\\\\MockObject\\\\Builder\\\\InvocationMocker\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/tests/Drupal/Tests/Core/Form/FormErrorHandlerTest.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to deprecated method getConfig\\(\\) of class GuzzleHttp\\\\Client\\:
Client\\:\\:getConfig will be removed in guzzlehttp/guzzle\\:8\\.0\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/tests/Drupal/Tests/Core/Http/ClientFactoryTest.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to deprecated method withConsecutive\\(\\) of class PHPUnit\\\\Framework\\\\MockObject\\\\Builder\\\\InvocationMocker\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/tests/Drupal/Tests/Core/Menu/ContextualLinkManagerTest.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to deprecated method withConsecutive\\(\\) of class PHPUnit\\\\Framework\\\\MockObject\\\\Builder\\\\InvocationMocker\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/tests/Drupal/Tests/Core/Menu/DefaultMenuLinkTreeManipulatorsTest.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to deprecated method withConsecutive\\(\\) of class PHPUnit\\\\Framework\\\\MockObject\\\\Builder\\\\InvocationMocker\\.$#',
	'count' => 2,
	'path' => __DIR__ . '/tests/Drupal/Tests/Core/Menu/LocalTaskManagerTest.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to deprecated method withConsecutive\\(\\) of class PHPUnit\\\\Framework\\\\MockObject\\\\Builder\\\\InvocationMocker\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/tests/Drupal/Tests/Core/Menu/StaticMenuLinkOverridesTest.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to method getDefinitions\\(\\) on an unknown class Drupal\\\\Core\\\\Plugin\\\\CategorizingPluginManagerTrait\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/tests/Drupal/Tests/Core/Plugin/CategorizingPluginManagerTraitTest.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to method getSortedDefinitions\\(\\) on an unknown class Drupal\\\\Core\\\\Plugin\\\\CategorizingPluginManagerTrait\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/tests/Drupal/Tests/Core/Plugin/CategorizingPluginManagerTraitTest.php',
];
$ignoreErrors[] = [
	'message' => '#^Missing cache backend declaration for performance\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/tests/Drupal/Tests/Core/Plugin/CategorizingPluginManagerTraitTest.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to deprecated method withConsecutive\\(\\) of class PHPUnit\\\\Framework\\\\MockObject\\\\Builder\\\\InvocationMocker\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/tests/Drupal/Tests/Core/Plugin/Discovery/DerivativeDiscoveryDecoratorTest.php',
];
$ignoreErrors[] = [
	'message' => '#^Constructor of class Drupal\\\\Tests\\\\Core\\\\Plugin\\\\Discovery\\\\TestContainerDerivativeDiscovery has an unused parameter \\$example_service\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/tests/Drupal/Tests/Core/Plugin/Discovery/TestContainerDerivativeDiscovery.php',
];
$ignoreErrors[] = [
	'message' => '#^Missing cache backend declaration for performance\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/tests/Drupal/Tests/Core/Plugin/FilteredPluginManagerTraitTest.php',
];
$ignoreErrors[] = [
	'message' => '#^Constructor of class Drupal\\\\Tests\\\\Core\\\\Plugin\\\\TestPluginManager has an unused parameter \\$namespaces\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/tests/Drupal/Tests/Core/Plugin/TestPluginManager.php',
];
$ignoreErrors[] = [
	'message' => '#^Missing cache backend declaration for performance\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/tests/Drupal/Tests/Core/Plugin/TestPluginManager.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to deprecated method expectError\\(\\) of class PHPUnit\\\\Framework\\\\TestCase\\:
https\\://github\\.com/sebastianbergmann/phpunit/issues/5062$#',
	'count' => 1,
	'path' => __DIR__ . '/tests/Drupal/Tests/Core/Render/ElementTest.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to deprecated method expectErrorMessage\\(\\) of class PHPUnit\\\\Framework\\\\TestCase\\:
https\\://github\\.com/sebastianbergmann/phpunit/issues/5062$#',
	'count' => 1,
	'path' => __DIR__ . '/tests/Drupal/Tests/Core/Render/ElementTest.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to deprecated method withConsecutive\\(\\) of class PHPUnit\\\\Framework\\\\MockObject\\\\Builder\\\\InvocationMocker\\.$#',
	'count' => 3,
	'path' => __DIR__ . '/tests/Drupal/Tests/Core/Routing/RouteBuilderTest.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to deprecated method expectWarning\\(\\) of class PHPUnit\\\\Framework\\\\TestCase\\:
https\\://github\\.com/sebastianbergmann/phpunit/issues/5062$#',
	'count' => 1,
	'path' => __DIR__ . '/tests/Drupal/Tests/Core/Security/DoTrustedCallbackTraitTest.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to deprecated method expectWarningMessage\\(\\) of class PHPUnit\\\\Framework\\\\TestCase\\:
https\\://github\\.com/sebastianbergmann/phpunit/issues/5062$#',
	'count' => 1,
	'path' => __DIR__ . '/tests/Drupal/Tests/Core/Security/DoTrustedCallbackTraitTest.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to deprecated method withConsecutive\\(\\) of class PHPUnit\\\\Framework\\\\MockObject\\\\Builder\\\\InvocationMocker\\.$#',
	'count' => 2,
	'path' => __DIR__ . '/tests/Drupal/Tests/Core/TempStore/PrivateTempStoreTest.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to deprecated method withConsecutive\\(\\) of class PHPUnit\\\\Framework\\\\MockObject\\\\Builder\\\\InvocationMocker\\.$#',
	'count' => 3,
	'path' => __DIR__ . '/tests/Drupal/Tests/Core/TempStore/SharedTempStoreTest.php',
];
$ignoreErrors[] = [
	'message' => '#^Variable \\$value in isset\\(\\) always exists and is not nullable\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/tests/Drupal/Tests/Core/Test/AssertContentTraitTest.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to deprecated method withConsecutive\\(\\) of class PHPUnit\\\\Framework\\\\MockObject\\\\Builder\\\\InvocationMocker\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/tests/Drupal/Tests/Core/UrlTest.php',
];
$ignoreErrors[] = [
	'message' => '#^Class Drupal\\\\Tests\\\\Core\\\\Utility\\\\MockContainerAware implements deprecated interface Symfony\\\\Component\\\\DependencyInjection\\\\ContainerAwareInterface\\:
since Symfony 6\\.4, use dependency injection instead$#',
	'count' => 1,
	'path' => __DIR__ . '/tests/Drupal/Tests/Core/Utility/CallableResolverTest.php',
];
$ignoreErrors[] = [
	'message' => '#^Usage of deprecated trait Symfony\\\\Component\\\\DependencyInjection\\\\ContainerAwareTrait in class Drupal\\\\Tests\\\\Core\\\\Utility\\\\MockContainerAware\\:
since Symfony 6\\.4, use dependency injection instead$#',
	'count' => 1,
	'path' => __DIR__ . '/tests/Drupal/Tests/Core/Utility/CallableResolverTest.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to deprecated method getConfig\\(\\) of class GuzzleHttp\\\\ClientInterface\\:
ClientInterface\\:\\:getConfig will be removed in guzzlehttp/guzzle\\:8\\.0\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/tests/Drupal/Tests/DrupalTestBrowser.php',
];
$ignoreErrors[] = [
	'message' => '#^Class Drupal\\\\Tests\\\\Listeners\\\\DrupalListener implements deprecated interface PHPUnit\\\\Framework\\\\TestListener\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/tests/Drupal/Tests/Listeners/DrupalListener.php',
];
$ignoreErrors[] = [
	'message' => '#^Usage of deprecated trait PHPUnit\\\\Framework\\\\TestListenerDefaultImplementation in class Drupal\\\\Tests\\\\Listeners\\\\DrupalListener\\:
The `TestListener` interface is deprecated$#',
	'count' => 1,
	'path' => __DIR__ . '/tests/Drupal/Tests/Listeners/DrupalListener.php',
];

return ['parameters' => ['ignoreErrors' => $ignoreErrors]];
