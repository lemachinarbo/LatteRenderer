<?php

namespace Latte {
    if (!class_exists('Latte\Engine')) {
        class Engine {
            public array $providers = [];
            public array $rendered = [];

            public function addProvider(string $name, $value): void {
                $this->providers[$name] = $value;
            }

            public function render(string $file, array $params = []): void {
                $this->rendered[] = ['file' => $file, 'params' => $params];
                echo "Rendered $file";
            }

            public function renderToString(string $file, array $params = [], ?string $blockName = null): string {
                $this->rendered[] = ['file' => $file, 'params' => $params, 'block' => $blockName];
                return "Rendered $file" . ($blockName ? " block $blockName" : "");
            }
        }
    }
}

namespace ProcessWire {
    if (!class_exists('ProcessWire\Paths')) {
        class Paths {
            public string $templates = '/tmp/templates/';
            public string $cache = '/tmp/cache/';
        }
    }

    if (!class_exists('ProcessWire\Config')) {
        class Config {
            public Paths $paths;
            public function __construct() {
                $this->paths = new Paths();
            }
        }
    }

    if (!class_exists('ProcessWire\Template')) {
        class Template {
            public string $name = 'default';
        }
    }

    if (!class_exists('ProcessWire\Page')) {
        class Page {
            public Template $template;
            public $templateParams = [];
            public function __construct() {
                $this->template = new Template();
            }
            public function getTemplateParams() {
                return $this->templateParams;
            }
        }
    }

    if (!function_exists('ProcessWire\wire')) {
        function wire($name = 'config') {
            static $config = null;
            if ($name === 'config') {
                if ($config === null) $config = new Config();
                return $config;
            }
            return null;
        }
    }
}

namespace {
    // Global wire function if needed
    if (!function_exists('wire')) {
        function wire($name = 'config') {
            return \ProcessWire\wire($name);
        }
    }
}
