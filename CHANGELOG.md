# Changelog

## [Unreleased]

### Features

- add LatteEngine unit test scaffolding and execution helpers

### Bug Fixes

- harden template name validation in template lookup to reduce path traversal risk
- add direct-access prevention to the _latte.php bridge file
- reduce redundant filesystem checks in template lookup paths
