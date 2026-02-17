# LatteRenderer

This module renders your Processwire pages using [Latte](https://latte.nette.org/).

> Latte must be installed via Composer in your project.

## Why use this?

If you want Latte syntax on ProcessWire, this gives you a clean bridge with:

- Full page rendering with layout inheritance
- Optional block rendering for partial responses

## Install

1) Install Latte (project level):

```bash
composer require latte/latte
```

2) Install the module:

Download and unzip to `/site/modules/LatteRenderer/`, then install via ProcessWire admin.

## How it works

The module creates a `_latte.php` bridge in `/site/templates/`.

That file routes all template rendering to Latte, so `home` uses `home.latte` instead of `home.php`.

Note: `_latte.php` is auto-generated and overwritten on module updates.

Defaults:

- Layout: `layouts/html.latte`
- Templates directory: `pages/`

Both paths are relative to `/site/templates` unless you pass an absolute path.

## Configuration

You can set config in `config.php` or in the module UI.

```php
$config->LatteRenderer = [
  "layoutFile" => "layouts/custom.latte",
  "templateDir" => "my-templates"
];
```

Absolute paths are allowed:

```php
$config->LatteRenderer = [
  "layoutFile" => "/var/www/shared/layout.latte",
  "templateDir" => $config->paths->projectTemplates . "pages"
];
```

## Usage

### Basic template bridge (auto-generated)

`/site/templates/_latte.php`:

```php
<?php
namespace ProcessWire;

/** @var LatteRenderer $latte */
$latte = $modules->get('LatteRenderer');

return $latte->renderPage($page);
```

### Add global params

```php
<?php
namespace ProcessWire;

$latte = $modules->get('LatteRenderer');
$latte->setGlobalParams([
  'header' => [...],
  'footer' => [...],
]);

return $latte->renderPage($page);
```

### Render blocks

```php
$latte = $this->modules->get('LatteRenderer');
$html = $latte->renderBlocks($this, ['header', 'footer']);
```

### Customize scope

Hook into `buildPageScope` to add variables:

```php
// site/ready.php
$wire->addHookAfter('LatteRenderer::buildPageScope', function($event) {
  $vars = &$event->return;
  $vars['customData'] = 'my value';
  $vars['apiKey'] = getenv('API_KEY');
});
```

## API reference

### `renderPage(Page $page): string`
Renders full page with layout. Admin templates are bypassed.

### `renderBlocks(Page $page, array $blockNames): string`
Renders one or more Latte blocks from the page template.

### `buildPageScope(Page $page): array`
Builds the default ProcessWire scope (hookable).

### `setGlobalParams(array $params): self`
Sets global params passed to every template.


## Quick how-to

1. Install Latte via Composer: `composer require latte/latte`
2. Install this module in ProcessWire admin.
3. Choose one rendering path:

a. Set your templates to use `_latte.php` as the template file 
(Admin > Setup > Templates > [Your Template] > "Alternative Template file name" = "_latte.php")

Tip: be sure to check the `Disable automatic append of file: _main.php` checkbox.

    
b. Or skip that and hook rendering in your page class (explicit and flexible)

```php
<?php

namespace ProcessWire;

class DefaultPage extends Page {
  public function ___render($options = [], $options2 = null) {
    $latte = $this->wire('modules')->get('LatteRenderer');

    // Your logic here (optional)

    return $latte->renderPage($this);
  }
}
```  

4. Create a layout file at `site/templates/layouts/html.latte` with a basic HTML and a content block.

```html
<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>{$page->title}</title>
</head>
<body>
	<main>
	{block content}
	{/block}
	</main>
</body>
</html>
```

5. Create `home.latte` in your templates folder with a simple block.

```latte
{block content}
  <h1>Hello world</h1>
  <p>This is my creative content.</p>
{/block}
```

That's it. Coffee is ready.


## Requirements

- ProcessWire 3.0+
- PHP 8.1+
- Latte 3.0+ (via Composer)

## License

UBC+P.

Use it.
Break it.
Change it.
And if you make money, buy Nette guys some pizza.

## Credits

Credit goes to [Latte by Nette](https://latte.nette.org/).
