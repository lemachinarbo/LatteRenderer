# LatteRenderer

ProcessWire module that renders pages using [Latte](https://latte.nette.org/) templates.

> Latte must be installed via Composer in your project.

## Why use this?

Clean integration of Latte syntax in ProcessWire with:

- Full page rendering with layout inheritance
- Optional block rendering for partial responses (AJAX, Datastar, htmx, etc.)
- Automatic fallback to `.php` templates when no `.latte` template exists

## Install

1) Install Latte (project level):

```bash
composer require latte/latte
```

2) Install the module:

Download and unzip to `/site/modules/LatteRenderer/`, then install via ProcessWire admin.

## How it works

The module creates a `_latte.php` bridge in `/site/templates/`.

When a page is rendered:
- If a `.latte` template exists (e.g., `home.latte`), it's used
- If not, ProcessWire uses the default `.php` template (e.g., `home.php`)

Note: `_latte.php` is auto-generated and overwritten on module updates.

**Defaults:**

- Layout: `layouts/html.latte`
- Templates directory: `pages/`

Both paths are relative to `/site/templates` unless you pass an absolute path.

## Configuration

Set config in `config.php` or in the module UI.

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

$latte = $modules->get('LatteRenderer');
if ($latte) {
  return $latte->renderPage($page);
}
```

The module handles checking for template existence and fallback automatically.

### Integration in Page classes

```php
<?php
namespace ProcessWire;

class DefaultPage extends Page {
  public function ___render($options = [], $options2 = null) {
    $latte = $this->wire('modules')->get('LatteRenderer');
    
    // Try Latte, fall back to ProcessWire's default if no .latte template exists
    return $latte->renderPage($this) ?? parent::___render($options, $options2);
  }
}
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

### Render blocks (for AJAX, Datastar, htmx, etc.)

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

### `renderPage(Page $page): ?string`
Renders full page with layout. Returns `null` when no Latte template exists (signals fallback to ProcessWire's default PHP template). Admin templates are bypassed.

### `hasLatteTemplate(string $templateName): bool`
Check if a Latte template exists for a given template name.

### `renderBlocks(Page $page, array $blockNames): string`
Renders one or more Latte blocks from the page template.

### `buildPageScope(Page $page): array`
Builds the default ProcessWire scope (hookable).

### `setGlobalParams(array $params): self`
Sets global params passed to every template.


## Quick setup

1. Install Latte via Composer: `composer require latte/latte`
2. Install this module in ProcessWire admin.
3. Choose one rendering approach:

### Option A: Use _latte.php (automatic)

Set your templates to use `_latte.php` as the template file:
- Admin > Setup > Templates > [Your Template] > "Alternative Template file name" = "_latte.php"
- Check "Disable automatic append of file: _main.php"

Now all pages using that template will use Latte when a `.latte` file exists, or fall back to `.php` when it doesn't.
    
### Option B: Use in Page classes (explicit)

```php
<?php

namespace ProcessWire;

class DefaultPage extends Page {
  public function ___render($options = [], $options2 = null) {
    $latte = $this->wire('modules')->get('LatteRenderer');
    
    // Your custom logic here (optional)
    
    // Try Latte, fall back to ProcessWire's default
    return $latte->renderPage($this) ?? parent::___render($options, $options2);
  }
}
```

## Template examples

Create a layout file at `site/templates/layouts/html.latte` with a basic HTML structure:

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

Create page templates in `site/templates/pages/` (e.g., `home.latte`):

```latte
{block content}
  <h1>Hello world</h1>
  <p>This is my creative content.</p>
{/block}
```

That's it. Your page will use Latte templates when they exist, and fall back to `.php` templates when they don't.


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
