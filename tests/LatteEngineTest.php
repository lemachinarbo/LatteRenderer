<?php

namespace ProcessWire;

require_once __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/../LatteEngine.php';

class LatteEngineTest
{
    private string $tempDir;
    private string $pagesDir;

    public function __construct()
    {
        $this->tempDir = __DIR__ . DIRECTORY_SEPARATOR . 'tmp';
        $this->pagesDir = $this->tempDir . DIRECTORY_SEPARATOR . 'pages';

        // Mock the config paths in the bootstrap to use our local tmp
        $config = wire('config');
        $config->paths->templates = $this->tempDir . DIRECTORY_SEPARATOR;
    }

    public function setUp()
    {
        if (is_dir($this->tempDir)) {
            $this->recursiveRmdir($this->tempDir);
        }
        mkdir($this->tempDir, 0777, true);
        mkdir($this->pagesDir, 0777, true);
    }

    private function recursiveRmdir($dir) {
        if (is_dir($dir)) {
            $objects = scandir($dir);
            foreach ($objects as $object) {
                if ($object != "." && $object != "..") {
                    $path = $dir . DIRECTORY_SEPARATOR . $object;
                    if (is_dir($path) && !is_link($path)) {
                        $this->recursiveRmdir($path);
                    } else {
                        unlink($path);
                    }
                }
            }
            rmdir($dir);
        }
    }

    protected function assertEquals($expected, $actual, $message = '') {
        if ($expected !== $actual) {
            $msg = $message ?: "Expected " . var_export($expected, true) . " but got " . var_export($actual, true);
            throw new \Exception($msg);
        }
    }

    protected function assertTrue($condition, $message = '') {
        if ($condition !== true) {
            $msg = $message ?: "Expected true but got " . var_export($condition, true);
            throw new \Exception($msg);
        }
    }

    protected function assertFalse($condition, $message = '') {
        if ($condition !== false) {
            $msg = $message ?: "Expected false but got " . var_export($condition, true);
            throw new \Exception($msg);
        }
    }

    protected function assertStringContainsString($needle, $haystack, $message = '') {
        if (strpos($haystack, $needle) === false) {
            $msg = $message ?: "Expected '$haystack' to contain '$needle'";
            throw new \Exception($msg);
        }
    }

    public function testConstructorThrowsExceptionMissingLayout()
    {
        $latte = new \Latte\Engine();
        try {
            new LatteEngine($latte, []);
            return false;
        } catch (\RuntimeException $e) {
            $this->assertStringContainsString('Layout path is required', $e->getMessage());
        }
        return true;
    }

    public function testConstructorSetsProviders()
    {
        $latte = new \Latte\Engine();
        $engine = new LatteEngine($latte, ['layout' => 'main.latte']);

        $provider = $latte->providers['coreParentFinder'];
        $this->assertTrue(is_callable($provider));
        $this->assertEquals('main.latte', $provider());
        return true;
    }

    public function testHasTemplate()
    {
        $latte = new \Latte\Engine();
        $engine = new LatteEngine($latte, ['layout' => 'main.latte']);

        $this->assertFalse($engine->hasTemplate('home'));

        touch($this->pagesDir . DIRECTORY_SEPARATOR . 'home.latte');
        $this->assertTrue($engine->hasTemplate('home'));

        $this->assertFalse($engine->hasTemplate('about'));
        touch($this->pagesDir . DIRECTORY_SEPARATOR . 'default.latte');
        $this->assertTrue($engine->hasTemplate('about'));
        return true;
    }

    public function testRenderThrowsExceptionInvalidTemplate()
    {
        $latte = new \Latte\Engine();
        $engine = new LatteEngine($latte, ['layout' => 'main.latte']);

        $page = new Page();
        $page->template->name = '!!!invalid!!!';

        try {
            $engine->render(['page' => $page]);
            return false;
        } catch (\RuntimeException $e) {
            $this->assertStringContainsString('Invalid or missing template name', $e->getMessage());
        }
        return true;
    }

    public function testRenderSuccessful()
    {
        $templateFile = $this->pagesDir . DIRECTORY_SEPARATOR . 'home.latte';
        touch($templateFile);

        $latte = new \Latte\Engine();
        $engine = new LatteEngine($latte, ['layout' => 'main.latte']);

        $page = new Page();
        $page->template->name = 'home';

        ob_start();
        $engine->render(['page' => $page, 'foo' => 'bar']);
        $output = ob_get_clean();

        $this->assertStringContainsString('Rendered', $output);
        $this->assertEquals($templateFile, $latte->rendered[0]['file']);
        $this->assertEquals('bar', $latte->rendered[0]['params']['foo']);
        return true;
    }

    public function testSetGlobalParams()
    {
        $latte = new \Latte\Engine();
        $engine = new LatteEngine($latte, ['layout' => 'main.latte']);

        $engine->setGlobalParams(['global' => 'value']);

        touch($this->pagesDir . DIRECTORY_SEPARATOR . 'home.latte');
        $page = new Page();
        $page->template->name = 'home';

        ob_start();
        $engine->render(['page' => $page, 'local' => 'val']);
        ob_get_clean();

        $this->assertEquals('value', $latte->rendered[0]['params']['global']);
        $this->assertEquals('val', $latte->rendered[0]['params']['local']);
        return true;
    }

    public function testRenderPartial()
    {
        $latte = new \Latte\Engine();
        $engine = new LatteEngine($latte, ['layout' => 'main.latte']);

        $file = $this->tempDir . DIRECTORY_SEPARATOR . 'partial.latte';
        touch($file);

        $output = $engine->renderPartial($file, ['key' => 'val']);
        $this->assertStringContainsString('Rendered', $output);
        $this->assertEquals($file, $latte->rendered[0]['file']);
        $this->assertEquals('val', $latte->rendered[0]['params']['key']);
        return true;
    }

    public function testRenderBlock()
    {
        $file = $this->pagesDir . DIRECTORY_SEPARATOR . 'home.latte';
        touch($file);

        $latte = new \Latte\Engine();
        $engine = new LatteEngine($latte, ['layout' => 'main.latte']);

        $result = $engine->renderBlockToString($file, ['a' => 'b'], 'content');
        $this->assertStringContainsString('Rendered', $result);
        $this->assertStringContainsString('block content', $result);
        $this->assertEquals('content', $latte->rendered[0]['block']);
        return true;
    }

    public function testRenderBlockByTemplate()
    {
        touch($this->pagesDir . DIRECTORY_SEPARATOR . 'home.latte');

        $latte = new \Latte\Engine();
        $engine = new LatteEngine($latte, ['layout' => 'main.latte']);

        $result = $engine->renderBlockByTemplate('home', ['x' => 'y'], 'sidebar');
        $this->assertStringContainsString('Rendered', $result);
        $this->assertStringContainsString('block sidebar', $result);
        return true;
    }

    public function testMergeParamsPriority()
    {
        $latte = new \Latte\Engine();
        $engine = new LatteEngine($latte, ['layout' => 'main.latte']);

        $engine->setGlobalParams(['var' => 'global']);

        $page = new Page();
        $page->template->name = 'home';
        $page->templateParams = ['var' => 'page'];

        touch($this->pagesDir . DIRECTORY_SEPARATOR . 'home.latte');

        ob_start();
        $engine->render(['page' => $page, 'var' => 'local']);
        ob_get_clean();

        $this->assertEquals('page', $latte->rendered[0]['params']['var']);
        return true;
    }
}
