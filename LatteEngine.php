<?php

namespace ProcessWire;

/**
 * LatteEngine: internal rendering orchestrator for LatteRenderer module.
 *
 * Usage:
 *   $renderer = new LatteRenderer($latte, [
 *     'layout' => 'layouts/html.latte'
 *   ]);
 *   $renderer->setGlobalParams(['key' => 'value']);
 *
 *   // Standalone:
 *   $renderer = new LatteRenderer($latte, [
 *     'layout' => '/path/to/layout.latte',
 *     'pagesDir' => '/path/to/pages'
 *   ]);
 *
 * Defaults:
 *   pagesDir: $config->paths->templates . 'pages'
 *   layout: REQUIRED
 *   globalParams: OPTIONAL
 *
 * Paths are resolved relative to $config->paths->templates unless absolute.
 */

use Latte\Engine;

class LatteEngine
{
  private Engine $latte;
  private array $config;
  private array|string|null $globalParams = null;
  private string $pagesDir;

  public function __construct(Engine $latte, array $config)
  {
    $this->latte = $latte;
    $this->config = $config;

    // Layout is required - no default
    if (!isset($this->config['layout'])) {
      throw new \RuntimeException(
        'Layout path is required in LatteRenderer config.',
      );
    }

    // Resolve pagesDir default once
    $this->pagesDir = isset($this->config['pagesDir'])
      ? $this->config['pagesDir']
      : wire('config')->paths->templates . 'pages';

    $this->latte->addProvider(
      'coreParentFinder',
      fn() => $this->config['layout'],
    );
  }

  /**
   * Set global parameters that will be available to all templates.
   *
   * @param array|string $globalParams Array of global params or path to params file
   * @return self For method chaining
   */
  public function setGlobalParams(array|string $globalParams): self
  {
    $this->globalParams = $globalParams;
    return $this;
  }

  /**
   * Render a Latte template for the current page.
   *
   * @param array $vars All template variables, including ProcessWire globals
   * @throws \RuntimeException If page or template file is missing
   */
  public function render(array $vars): void
  {
    $template = $vars['page']?->template->name ?? null;

    if (!$template || !preg_match('/^[a-zA-Z0-9_-]+$/', $template)) {
      throw new \RuntimeException('Invalid or missing template name.');
    }

    $latteFile = $this->findTemplate($template);

    $params = $this->mergeParams($vars);

    $this->latte->render($latteFile, $params);
  }

  /**
   * Check if a Latte template exists for the given template name.
   *
   * @param string $template Template name
   * @return bool True if template or default.latte exists
   */
  public function hasTemplate(string $template): bool
  {
    $pagesDir = rtrim($this->pagesDir, '/') . '/';
    if (!$pagesDir) {
      return false;
    }
    $file = $pagesDir . $template . '.latte';
    if (is_readable($file)) {
      return true;
    }
    $default = $pagesDir . 'default.latte';
    return is_readable($default);
  }

  /**
   * Find the appropriate template file for rendering.
   *
   * @param string $template Template name
   * @return string Path to the template file
   * @throws \RuntimeException If no template is found
   */
  private function findTemplate(string $template): string
  {
    // Use provided pagesDir
    $pagesDir = rtrim($this->pagesDir, '/') . '/';
    if (!$pagesDir) {
      throw new \RuntimeException(
        'No pagesDir configured.',
      );
    }
    $file = $pagesDir . $template . '.latte';
    if (is_readable($file)) {
      return $file;
    }

    $default = $pagesDir . 'default.latte';
    if (is_readable($default)) {
      return $default;
    }

    throw new \RuntimeException("Latte template not found for '{$template}'.");
  }



  /**
   * Render a partial template and return as string.
   *
   * @param string $file Path to the partial latte file
   * @param array $vars Variables to pass to the partial
   * @return string Rendered HTML
   */
  public function renderPartial(string $file, array $vars = []): string
  {
    ob_start();
    $params = $this->mergeParams($vars); // uses your existing merge logic
    $this->latte->render($file, $params);
    return ob_get_clean();
  }

  /**
   * Merge globals + page params with provided vars.
   *
   * Automatically provides all ProcessWire fuel globals including:
   * - $user (ProcessWire\User): Current user object
   * - $pages, $config, $permissions, $roles, $users (ProcessWire APIs)
   * - $urls (ProcessWire URLs)
   * - Plus page-specific params from getTemplateParams()
   *
   * @param array $vars
   * @return array
   */
  private function mergeParams(array $vars): array
  {
    $params = $vars;

    // Merge global params (lower priority)
    if ($this->globalParams) {
      $global = is_array($this->globalParams)
        ? $this->globalParams
        : [];
      $params = array_replace($global, $params);
    }

    // NOTE: We no longer inject wire('fuel') here because:
    // 1. _latte.php already passes get_defined_vars() which includes all globals
    // 2. Block renders now use getTemplateScopeVars() which explicitly sets them
    // This eliminates duplicate injection and makes precedence explicit.

    // Merge page-specific params (highest priority)
    $pageParams = [];
    if (
      isset($vars['page']) &&
      method_exists($vars['page'], 'getTemplateParams')
    ) {
      $pageParams = $vars['page']->getTemplateParams();
    }

    $params = array_replace(
      $params,
      is_object($pageParams)
        ? get_object_vars($pageParams)
        : (array) $pageParams,
    );

    return $params;
  }

  public function renderToString(array $vars): string
  {
    ob_start();
    $this->render($vars);
    return (string) ob_get_clean();
  }

  /**
   * Render a specific Latte block from a template file and return as string.
   *
   * @param string $file Path to the Latte file
   * @param array $vars Variables to pass to the template
   * @param string $blockName Name of the Latte block to render
   * @return string Rendered block HTML
   */
  /**
   * Render a specific Latte block from a template file and return as string.
   * Uses the same Latte engine instance and param merging as the rest of LatteRenderer.
   *
   * @param string $file Path to the Latte file
   * @param array $vars Variables to pass to the template
   * @param string $blockName Name of the Latte block to render
   * @return string Rendered block HTML
   * @throws \RuntimeException If the file is not readable
   */
  public function renderBlockToString(
    string $file,
    array $vars,
    string $blockName,
  ): string {
    if (!is_readable($file)) {
      throw new \RuntimeException(
        "Latte template not found or not readable: $file",
      );
    }
    $params = $this->mergeParams($vars);
    // Use the same Latte engine instance as the rest of LatteRenderer
    return $this->latte->renderToString($file, $params, $blockName);
  }

  /**
   * Render a specific Latte block by template name and return as string.
   *
   * @param string $templateName Template name (e.g., 'basic-page')
   * @param array $vars Variables to pass to the template
   * @param string $blockName Name of the Latte block to render
   * @return string Rendered block HTML
   */
  public function renderBlockByTemplate(
    string $templateName,
    array $vars,
    string $blockName,
  ): string {
    $file = $this->findTemplate($templateName);
    return $this->renderBlockToString($file, $vars, $blockName);
  }
}
