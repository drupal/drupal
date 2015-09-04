# Changelog

All notable changes to this project will be documented in this file, in reverse chronological order by release.

## 2.6.0 - 2015-07-21

### Added

- [#13](https://github.com/zendframework/zend-stdlib/pull/13) adds
  `Zend\Stdlib\Hydrator\Iterator`, which provides mechanisms for hydrating
  objects when iterating a traversable. This allows creating generic collection
  resultsets; the original idea was pulled from
  [PhlyMongo](https://github.com/phly/PhlyMongo), where it was used to hydrate
  collections retrieved from MongoDB.

### Deprecated

- Nothing.

### Removed

- Nothing.

### Fixed

- Nothing.

## 2.5.2 - 2015-07-21

### Added

- Nothing.

### Deprecated

- Nothing.

### Removed

- Nothing.

### Fixed

- [#9](https://github.com/zendframework/zend-stdlib/pull/9) fixes an issue with
  count incrementation during insert in PriorityList, ensuring that incrementation only
  occurs when the item inserted was not previously present in the list.

## 2.4.4 - 2015-07-21

### Added

- Nothing.

### Deprecated

- Nothing.

### Removed

- Nothing.

### Fixed

- [#9](https://github.com/zendframework/zend-stdlib/pull/9) fixes an issue with
  count incrementation during insert in PriorityList, ensuring that incrementation only
  occurs when the item inserted was not previously present in the list.
