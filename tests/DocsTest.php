<?php

declare(strict_types=1);

namespace Bitgen\Sdk\Tests;

use Bitgen\Sdk\Version;
use PHPUnit\Framework\TestCase;

/**
 * Client documentation (README.md, readme/**\/*.md): every ```php block runs against the package, every
 * relative link and anchor resolves, the README title carries the version, and no API route or path appears
 * (it is the SDK's documentation). What depends on the API contract (error codes) is not checked here: the
 * contract is not in the repository.
 */
final class DocsTest extends TestCase
{
    private const ROOT = __DIR__ . '/..';

    /** @var resource|null */
    private static $server = null;
    private static int $port = 0;

    /** The documentation server (tests/server/docs.php) answers realistic bodies to the examples */
    public static function setUpBeforeClass(): void
    {
        $socket = stream_socket_server('tcp://127.0.0.1:0', $errno, $error);
        self::assertNotFalse($socket);
        $name = stream_socket_get_name($socket, false);
        fclose($socket);
        self::assertNotFalse($name);
        self::$port = (int) substr($name, (int) strrpos($name, ':') + 1);
        // one worker process: orphaned PHP_CLI_SERVER_WORKERS would outlive proc_terminate and keep the runner's pipes open
        $process = proc_open([PHP_BINARY, '-S', '127.0.0.1:' . self::$port, self::ROOT . '/tests/server/docs.php'], [0 => ['pipe', 'r'], 1 => ['file', '/dev/null', 'w'], 2 => ['file', '/dev/null', 'w']], $pipes);
        self::assertIsResource($process);
        self::$server = $process;
        for ($i = 0; $i < 100; $i++) {
            $probe = @fsockopen('127.0.0.1', self::$port, $errno, $error, 0.1);
            if ($probe !== false) {
                fclose($probe);

                return;
            }
            usleep(50_000);
        }
        self::fail('the documentation server did not start');
    }

    public static function tearDownAfterClass(): void
    {
        if (self::$server !== null) {
            proc_terminate(self::$server);
            proc_close(self::$server);
            self::$server = null;
        }
    }

    /**
     * What every example can take for granted: the autoloader, `$client` pointed at the documentation server
     * (an example that builds its own client simply reassigns it) and `$customer`, the `Created` of a customer.
     */
    private static function prelude(): string
    {
        return "<?php\n"
            . 'require ' . var_export(realpath(self::ROOT . '/vendor/autoload.php'), true) . ";\n"
            . sprintf("\$client = new \\Bitgen\\Sdk\\BitgenClient(scope: 'YOUR_SCOPE_UUID', apiKey: 'YOUR_API_KEY', host: '127.0.0.1', port: %d, isSsl: false);\n", self::$port)
            . "\$customer = new \\Bitgen\\Sdk\\Model\\Created('CUSTOMER_UUID');\n";
    }

    /** @return list<string> */
    private static function docs(): array
    {
        $files = [self::ROOT . '/README.md'];
        $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator(self::ROOT . '/readme', \FilesystemIterator::SKIP_DOTS));
        foreach ($iterator as $file) {
            if ($file instanceof \SplFileInfo && $file->getExtension() === 'md') {
                $files[] = $file->getPathname();
            }
        }
        sort($files);

        return $files;
    }

    /** @return list<array{string, string}> [lang, body] */
    private static function codeBlocks(string $text): array
    {
        preg_match_all('/^```(\w*)\n(.*?)^```$/ms', $text, $matches, PREG_SET_ORDER);
        $blocks = [];
        foreach ($matches as $match) {
            $blocks[] = [$match[1], $match[2]];
        }

        return $blocks;
    }

    /** The text without fenced blocks and inline code — where headings and links live */
    private static function prose(string $text): string
    {
        $text = (string) preg_replace('/^```.*?^```$/ms', '', $text);

        return (string) preg_replace('/`[^`\n]*`/', '', $text);
    }

    public function testEveryPhpExampleRuns(): void
    {
        $dir = sys_get_temp_dir() . '/bitgen-sdk-docs-' . getmypid();
        mkdir($dir);
        $count = 0;
        try {
            foreach (self::docs() as $doc) {
                $page = substr($doc, strlen(self::ROOT) + 1);
                $n = 0;
                foreach (self::codeBlocks((string) file_get_contents($doc)) as [$lang, $body]) {
                    if ($lang !== 'php') {
                        continue;
                    }
                    $n++;
                    $count++;
                    self::assertStringStartsWith("<?php\n", $body, sprintf('%s, example %d: a PHP example starts with <?php', $page, $n));
                    $file = sprintf('%s/%s_%d.php', $dir, str_replace(['/', '.'], '_', $page), $n);
                    file_put_contents($file, self::prelude() . substr($body, strlen("<?php\n")));
                    // stdout / stderr go to files: pipes could fill up and block a verbose example
                    $process = proc_open([PHP_BINARY, '-d', 'display_errors=stderr', $file], [1 => ['file', $file . '.out', 'w'], 2 => ['file', $file . '.err', 'w']], $pipes);
                    self::assertIsResource($process);
                    $exit = proc_close($process);
                    $stdout = (string) file_get_contents($file . '.out');
                    $stderr = (string) file_get_contents($file . '.err');
                    self::assertSame(0, $exit, sprintf("%s, example %d failed:\n%s%s", $page, $n, $stdout, $stderr));
                    self::assertSame('', $stderr, sprintf('%s, example %d wrote on stderr', $page, $n));
                }
            }
        } finally {
            foreach (glob($dir . '/*') ?: [] as $file) {
                unlink($file);
            }
            rmdir($dir);
        }
        self::assertGreaterThan(0, $count, 'no PHP example found in the documentation');
    }

    /** GitHub's anchor of a heading: lowercase, punctuation dropped, spaces → `-` */
    private static function slug(string $heading): string
    {
        $slug = strtolower(trim(str_replace('`', '', $heading)));
        $slug = (string) preg_replace('/[^\w\- ]/u', '', $slug);

        return str_replace(' ', '-', $slug);
    }

    /** @return list<string> */
    private static function anchors(string $file): array
    {
        preg_match_all('/^#{1,6} (.+)$/m', self::prose((string) file_get_contents($file)), $matches);

        return array_map(self::slug(...), $matches[1]);
    }

    public function testRelativeLinksAndAnchorsResolve(): void
    {
        $failures = [];
        foreach ([...self::docs(), self::ROOT . '/CONTRIBUTING.md'] as $doc) {
            $page = substr($doc, strlen(self::ROOT) + 1);
            preg_match_all('/\]\(([^)\s]+)\)/', self::prose((string) file_get_contents($doc)), $matches);
            foreach ($matches[1] as $target) {
                if (preg_match('/^[a-z]+:/', $target) === 1) {
                    continue;   // absolute URL
                }
                [$file, $anchor] = array_pad(explode('#', $target, 2), 2, null);
                $destination = $file === '' ? $doc : dirname($doc) . '/' . $file;
                if (!is_file($destination)) {
                    $failures[] = sprintf('%s: broken link %s', $page, $target);
                } elseif ($anchor !== null && !in_array($anchor, self::anchors($destination), true)) {
                    $failures[] = sprintf('%s: unknown anchor %s', $page, $target);
                }
            }
        }
        $readme = (string) file_get_contents(self::ROOT . '/README.md');
        foreach (array_slice(self::docs(), 1) as $doc) {
            $page = substr($doc, strlen(self::ROOT) + 1);
            if (!str_contains($readme, '](' . $page . ')')) {
                $failures[] = sprintf('README.md does not link %s', $page);
            }
        }
        self::assertSame([], $failures);
    }

    /**
     * The published documentation is the SDK's, not the API's: no HTTP route (`GET /custody/{user}`) and no bare API
     * path (`/customer`, `/bank`…) anywhere in it, code blocks included. The only tolerated occurrence is a data value
     * the API returns, listed explicitly.
     */
    public function testNoApiRouteInThePublishedDocumentation(): void
    {
        $route = '/\\b(GET|POST|PUT|PATCH|DELETE)\\s+\\/[^\\s`"\']*/';
        // a bare API path between backticks or quotes (prose, tables, code blocks alike)
        $path = '/[`"\']\\/(?:customer|account|bank|custody|trading|transaction|staking|applications|webhooks?|organization\\/|asset|ticker)(?:[\\/?{][^`"\']*)?[`"\']/';
        // the `path` of an ApikeyLog is a value the API returns ("GET /custody/…"), shown as such in apikeys.md
        $allowed = ['readme/resource/apikeys.md' => ['GET /custody/…']];
        $failures = [];
        foreach (self::docs() as $doc) {
            $page = substr($doc, strlen(self::ROOT) + 1);
            foreach (explode("\n", (string) file_get_contents($doc)) as $index => $line) {
                foreach ([$route, $path] as $pattern) {
                    preg_match_all($pattern, $line, $matches);
                    foreach ($matches[0] as $match) {
                        if (!in_array($match, $allowed[$page] ?? [], true)) {
                            $failures[] = sprintf('%s:%d: %s', $page, $index + 1, $match);
                        }
                    }
                }
            }
        }
        self::assertSame([], $failures, 'API routes and paths do not belong to the SDK documentation');
    }

    public function testTheReadmeTitleCarriesTheVersion(): void
    {
        $readme = (string) file_get_contents(self::ROOT . '/README.md');
        self::assertSame('# bitgen/sdk — v' . Version::VERSION, strtok($readme, "\n"));
    }
}
