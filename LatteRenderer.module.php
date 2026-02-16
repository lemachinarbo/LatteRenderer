<?php

namespace ProcessWire;

require_once __DIR__ . '/LatteEngine.php';

/**
 * LatteRenderer Module
 *
 * Provides Latte template engine integration for ProcessWire.
 * Handles scope building, admin page guards, and template rendering.
 *
 * HOW IT WORKS:
 * 1. Module provides two rendering methods:
 *    - renderPage(): Full page with layout
 *    - renderBlocks(): Specific blocks only (for partial rendering)
 *
 * 2. Scope building is automatic and hookable:
 *    - buildPageScope() adds all ProcessWire fuel globals
 *    - Callback (e.g., DefaultPage::getBaseParams) provides project-specific globals
 *    - Both are merged and passed to Latte templates
 *
 * 3. Template inheritance is preserved:
 *    - renderPage() uses page template which extends layout
 *    - renderBlocks() uses renderBlockByTemplate() to maintain inheritance chain
 *    - This keeps all CSS, styling, and parent context intact
 *
 * 4. Admin guard is built-in:
 *    - Admin template pages return empty string (ProcessWire handles them)
 *    - No user code needed to check template type
 *
 * SEPARATION OF CONCERNS:
 * - This module: Renders Latte templates, builds scope, guards admin
 * - DefaultPage: Entry point for rendering, calls module
 * - DatastarResponseHandler: Optional helper for mappinKg partial requests to blocks
 *
 * @property string $layoutFile Path to layout file relative to templates directory
 * @property string $templateDir Directory containing page templates
 */
class LatteRenderer extends WireData implements Module, ConfigurableModule
{
  private ?\Latte\Engine $latte = null;
  private ?LatteEngine $engine = null;

  public static function getModuleInfo()
  {
    return [
      'title' => 'Latte Template Renderer',
      'summary' => 'Latte template engine integration for ProcessWire',
      'version' => '0.0.1',
      'author' => 'Lemachi Narbo',
      'autoload' => true,
      'singular' => true,
      'requires' => 'ProcessWire>=3.0.0',
    ];
  }

  public function init()
  {
    // Auto-configuration from config if not set
    if (!$this->layoutFile) {
      $this->layoutFile = 'layouts/html.latte';
    }
    if (!$this->templateDir) {
      $this->templateDir = 'pages';
    }
  }

  /**
   * Get or create Latte engine instance
   */
  protected function getLatte(): \Latte\Engine
  {
    if ($this->latte === null) {
      $this->latte = new \Latte\Engine();
      $tempDir = $this->wire('config')->paths->cache . 'latte';
      $this->latte->setTempDirectory($tempDir);
    }
    return $this->latte;
  }

  /**
   * Get or create LatteEngine (rendering orchestrator)
   */
  protected function getEngine(): LatteEngine
  {
    if ($this->engine === null) {
      $config = $this->wire('config');
      $this->engine = new LatteEngine($this->getLatte(), [
        'layout' => $config->paths->templates . $this->layoutFile,
        'pagesDir' => $config->paths->templates . $this->templateDir,
      ]);
    }
    return $this->engine;
  }

  /**
   * Build standard ProcessWire page scope (hookable)
   *
   * Automatically includes all fuel globals and common page variables.
   * Hook LatteRenderer::buildPageScope to customize variables.
   *
   * @param Page $page
   * @return array
   */
  public function ___buildPageScope(Page $page): array
  {
    $config = $this->wire('config');

    $vars = [
      'config' => $config,
      'urls' => $config->urls,
      'page' => $page,
      'pages' => $this->wire('pages'),
      'user' => $this->wire('user'),
      'input' => $this->wire('input'),
      'sanitizer' => $this->wire('sanitizer'),
      'session' => $this->wire('session'),
      'fields' => $this->wire('fields'),
      'modules' => $this->wire('modules'),
    ];

    // Add all fuel globals (lower priority)
    $vars = array_replace((array) wire('fuel'), $vars);

    return $vars;
  }

  /**
   * Render full page with layout
   *
   * Admin pages return empty string (ProcessWire handles them).
   *
   * @param Page $page
   * @return string
   */
  public function renderPage(Page $page): string
  {
    if ($page->template->name === 'admin') {
      return $page->___renderPage();
    }

    $vars = $this->buildPageScope($page);
    return $this->getEngine()->renderToString($vars);
  }

  /**
   * Set global parameters available to all templates
   *
   * @param array $params
   * @return self
   */
  public function setGlobalParams(array $params): self
  {
    $this->getEngine()->setGlobalParams($params);
    return $this;
  }

  /**
   * Render specific blocks without full page layout
   *
   * Renders blocks from the page template (which inherits from layout).
   * This preserves all CSS, styling context, and inheritance chains.
   *
   * @param Page $page Current page
   * @param array $blockNames Block names to render (e.g., ['header_wrapper', 'main_wrapper'])
   * @return string Concatenated HTML of all requested blocks
   */
  public function renderBlocks(Page $page, array $blockNames): string
  {
    $vars = $this->buildPageScope($page);

    $output = '';
    foreach ($blockNames as $blockName) {
      $output .= $this->getEngine()->renderBlockByTemplate(
        $page->template->name,
        $vars,
        $blockName,
      );
    }

    return $output;
  }

  /**
   * Module configuration
   */
  public static function getModuleConfigInputfields(array $data)
  {
    $inputfields = new InputfieldWrapper();

    $f = wire('modules')->get('InputfieldText');
    $f->name = 'layoutFile';
    $f->label = 'Layout File';
    $f->description =
      'Path to main layout file relative to templates directory';
    $f->value = $data['layoutFile'] ?? 'layouts/html.latte';
    $inputfields->add($f);

    $f = wire('modules')->get('InputfieldText');
    $f->name = 'templateDir';
    $f->label = 'Template Directory';
    $f->description =
      'Directory containing page templates (relative to templates directory)';
    $f->value = $data['templateDir'] ?? 'pages';
    $inputfields->add($f);

    return $inputfields;
  }
}
