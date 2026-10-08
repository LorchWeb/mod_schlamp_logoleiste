<?php
/** Standalone checks with API stubs, not a Joomla installation test. */
namespace Joomla\Registry {
    final class Registry {
        public function __construct(private array $values = []) {}
        public function get(string $key, mixed $default = null): mixed { return $this->values[$key] ?? $default; }
    }
}
namespace Joomla\CMS\Uri {
    final class Uri { public static function root(bool $pathOnly = false): string { return '/joomla'; } }
}
namespace Joomla\CMS\Language {
    final class Text {
        public static array $strings = [];
        public static function _(string $key): string { return self::$strings[$key] ?? $key; }
    }
}
namespace {
    use Joomla\Registry\Registry;
    use Joomla\CMS\Language\Text;
    use Schlamp\Module\Logoleiste\Site\Helper\LogoHelper;

    define('_JEXEC', 1);
    $source = dirname(__DIR__);
    $temporary = sys_get_temp_dir() . '/schlamp-logos-' . bin2hex(random_bytes(6));
    mkdir($temporary . '/images/logoleiste', 0777, true);
    mkdir($temporary . '/images/empty');
    mkdir($temporary . '/outside');
    define('JPATH_ROOT', $temporary);
    require $source . '/src/Helper/LogoHelper.php';
    $checks = 0;
    $check = static function (bool $condition, string $message) use (&$checks): void {
        if (!$condition) throw new \RuntimeException($message);
        ++$checks;
    };
    $render = static function (array $logos, Registry $params) use ($source): string {
        ob_start();
        require $source . '/tmpl/default.php';
        return ob_get_clean();
    };
    try {
        $labels = ['01-Partner.svg', '02-Haus & Grund.svg', '03-Immobilien.svg', '04-Portal.svg', '05-Wohnen.svg', '10-Verband.svg'];
        foreach ($labels as $index => $filename) {
            $label = htmlspecialchars(pathinfo($filename, PATHINFO_FILENAME), ENT_XML1);
            $color = ['#3e7300', '#008eb4', '#333333', '#00796b', '#8d6500', '#15356e'][$index];
            file_put_contents($temporary . '/images/logoleiste/' . $filename, '<svg xmlns="http://www.w3.org/2000/svg" width="260" height="120" viewBox="0 0 260 120"><rect x="5" y="25" width="250" height="70" rx="8" fill="' . $color . '"/><text x="130" y="67" font-family="Arial" font-size="20" text-anchor="middle" fill="white">' . $label . '</text></svg>');
        }
        file_put_contents($temporary . '/images/logoleiste/ignored.txt', 'not an image');
        file_put_contents($temporary . '/images/logoleiste/.hidden.svg', '<svg/>');
        mkdir($temporary . '/images/logoleiste/folder.png');
        $params = new Registry(['links' => "01-Partner.svg | https://example.com/?a=1&b=2 | Partner\n02-Haus & Grund.svg | /partner | Haus & Grund"]);
        $logos = LogoHelper::getLogos($params);
        $check(count($logos) === 6, 'Only supported direct image files');
        $check(str_ends_with($logos[1]['src'], '02-Haus%20%26%20Grund.svg'), 'URL encoding and natural sorting');
        $check(str_starts_with($logos[0]['src'], '/joomla/images/'), 'Subdirectory installation URL');
        $check($logos[1]['alt'] === 'Haus & Grund' && $logos[1]['url'] === '/partner', 'Link mapping');
        $check($logos[5]['alt'] === 'Verband', 'Numeric filename prefix removed');
        foreach (['../outside', 'images/../outside', 'images/logoleiste/../../outside', 'images/missing', 'images/empty', 'https://example.com/images', 'images//logoleiste'] as $folder) {
            $check(LogoHelper::getLogos(new Registry(['folder' => $folder])) === [], 'Reject missing/outside/empty: ' . $folder);
        }
        foreach (['javascript:alert(1)', 'data:text/html,test', '//example.com', '/%2fexample.com', '/%5cexample.com', "https://exa\tmple.com", 'https://user:pass@example.com', 'https://example.com/%0aattack'] as $url) {
            $map = LogoHelper::parseLinks('logo.svg | ' . $url . ' | Logo');
            $check($map['logo.svg']['url'] === '', 'Unsafe URL rejected: ' . $url);
        }
        $map = LogoHelper::parseLinks("# comment\nlogo.svg | | Eigene Bezeichnung\nother.svg | https://example.com/a?x=1&y=2 | Other");
        $check($map['logo.svg']['label'] === 'Eigene Bezeichnung', 'Label without link');
        $check($map['other.svg']['url'] === 'https://example.com/a?x=1&y=2', 'HTTPS query preserved');
        Text::$strings = parse_ini_file($source . '/language/de-DE/mod_schlamp_logoleiste.ini');
        $check(is_array(Text::$strings), 'German INI parses');
        $check(is_array(parse_ini_file($source . '/language/en-GB/mod_schlamp_logoleiste.ini')), 'English INI parses');
        $html = $render($logos, $params);
        $check(str_contains($html, 'a=1&amp;b=2') && str_contains($html, 'Haus &amp; Grund'), 'Output escaping');
        $check(str_contains($html, 'aria-label="Partner und Mitgliedschaften"'), 'Default region label');
        $check($render([], $params) === '', 'No markup for empty folder');
        $hostile = $render([['src' => 'x" onerror="alert(1)', 'alt' => '<script>bad</script>', 'url' => '/?x="bad']], new Registry(['label' => '"<test>', 'duration' => -100, 'new_window' => 1]));
        $check(!str_contains($hostile, '<script>') && !str_contains($hostile, ' onerror="'), 'Attribute and text injection escaped');
        $check(str_contains($hostile, '--sl-duration:5s') && str_contains($hostile, 'rel="noopener noreferrer"'), 'Bounds and new window protection');

        $fixed = $render($logos, new Registry(['width' => 290, 'height' => 150, 'padding' => 10]));
        $check(str_contains($fixed, '--sl-width:290px') && str_contains($fixed, '--sl-height:150px'), 'Explicit frame dimensions');
        $check(str_contains($fixed, '--sl-background:transparent'), 'Transparent default');
        $check(!str_contains($fixed, '<button'), 'No pause control markup');
        $colored = $render($logos, new Registry(['background_enabled' => 1, 'background_color' => '#123456']));
        $check(str_contains($colored, '--sl-background:#123456'), 'Optional background');
        $invalid = $render($logos, new Registry(['background_enabled' => 1, 'background_color' => 'red;position:fixed']));
        $check(str_contains($invalid, '--sl-background:transparent'), 'Background rejects CSS injection');

        if (!empty($argv[1])) {
            $destination = $argv[1];
            if (!is_dir($destination)) mkdir($destination, 0777, true);
            foreach ($logos as &$logo) {
                $file = basename(rawurldecode($logo['src']));
                copy($temporary . '/images/logoleiste/' . $file, $destination . '/' . $file);
                $logo['src'] = rawurlencode($file);
            }
            unset($logo);
            copy($source . '/media/css/logoleiste.css', $destination . '/logoleiste.css');
            copy($source . '/media/js/logoleiste.js', $destination . '/logoleiste.js');
            $page = '<!doctype html><html lang="de"><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Logoleiste – Funktionsprüfung mit Platzhaltern</title><link rel="stylesheet" href="logoleiste.css"><style>body{margin:0;font-family:Arial;background:#eee}main{margin:70px auto;max-width:1560px}h1{font-size:18px;padding:20px}section{margin-bottom:40px}</style><script defer src="logoleiste.js"></script><body><h1>Funktionsprüfung – neutrale Platzhalter</h1><a href="#end">Testfokus vor Modulen</a><main>';
            $page .= $render($logos, $params);
            $page .= $render(array_slice($logos, 0, 2), new Registry(['duration' => 12, 'visible_desktop' => 4]));
            $page .= $render(array_slice($logos, 0, 1), new Registry());
            $page .= $render(array_slice($logos, 0, 2), new Registry(['width' => 290, 'height' => 150, 'padding' => 10, 'background_enabled' => 1, 'background_color' => '#123456']));
            $page .= $render(array_slice($logos, 0, 2), new Registry(['width' => 70, 'height' => 150, 'gap' => 0]));
            $page .= '</main><a id="end" href="#">Testfokus nach Modulen</a></body></html>';
            file_put_contents($destination . '/index.html', $page);
        }
        echo "PASS: $checks PHP logic/rendering checks\n";
    } finally {
        $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($temporary, \FilesystemIterator::SKIP_DOTS), \RecursiveIteratorIterator::CHILD_FIRST);
        foreach ($iterator as $entry) {
            if ($entry->isDir()) rmdir($entry->getPathname());
            else unlink($entry->getPathname());
        }
        rmdir($temporary);
    }
}
