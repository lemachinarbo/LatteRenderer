# Changelog

## [0.1.0](https://github.com/lemachinarbo/LatteRenderer/compare/v0.0.2...v0.1.0) (2026-04-19)


### Features

* automate module version updates ([e3dc384](https://github.com/lemachinarbo/LatteRenderer/commit/e3dc384290681035033e84c6f592fb463528f032))
* update release workflow ([db91275](https://github.com/lemachinarbo/LatteRenderer/commit/db912755498df390744566920600715665b21057))


### Miscellaneous Chores

* add agents guidance files ([cc3b7d3](https://github.com/lemachinarbo/LatteRenderer/commit/cc3b7d3bf29acbc24b4d5640708e06855ebb44ba))
* bootstrap release pipeline and backfill changelog ([8549949](https://github.com/lemachinarbo/LatteRenderer/commit/85499499113d077874a39d0ed724e58c840d06f6))

## [Unreleased]

### Features

- add LatteEngine unit test scaffolding and execution helpers

### Bug Fixes

- harden template name validation in template lookup to reduce path traversal risk
- add direct-access prevention to the _latte.php bridge file
- reduce redundant filesystem checks in template lookup paths
