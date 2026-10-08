<?php
/** @license GNU General Public License version 2 or later */
namespace Schlamp\Module\Logoleiste\Site\Helper;

defined('_JEXEC') or die;

use Joomla\CMS\Uri\Uri;
use Joomla\Registry\Registry;

final class LogoHelper
{
    /** Read only direct image children of a directory inside Joomla's images directory. */
    public static function getLogos(Registry $params): array
    {
        $folder = trim(str_replace('\\', '/', (string) $params->get('folder', 'images/logoleiste')), '/ ');
        $segments = explode('/', $folder);

        if ($segments[0] !== 'images' || array_intersect($segments, ['..', '.', '']) !== [] || preg_match('/[\x00-\x1f\x7f]/', $folder)) {
            return [];
        }

        $images = realpath(JPATH_ROOT . '/images');
        $directory = realpath(JPATH_ROOT . '/' . $folder);

        if ($images === false || $directory === false || !self::isInside($directory, $images) || !is_dir($directory) || !is_readable($directory)) {
            return [];
        }

        $files = @scandir($directory);

        if ($files === false) {
            return [];
        }

        natcasesort($files);
        $mapping = self::parseLinks((string) $params->get('links', ''));
        $logos = [];

        foreach ($files as $file) {
            if (str_starts_with($file, '.') || preg_match('/[\x00-\x1f\x7f]/', $file) || !in_array(strtolower(pathinfo($file, PATHINFO_EXTENSION)), ['jpg', 'jpeg', 'png', 'webp', 'svg', 'gif', 'avif'], true)) {
                continue;
            }

            $path = realpath($directory . '/' . $file);

            if ($path === false || !self::isInside($path, $directory) || !is_file($path) || !is_readable($path)) {
                continue;
            }

            $name = preg_replace('/^\d+[-_ ]+/', '', pathinfo($file, PATHINFO_FILENAME));
            $name = trim(str_replace(['_', '-'], ' ', $name));
            $entry = $mapping[$file] ?? [];
            $logos[] = [
                'src' => rtrim(Uri::root(true), '/') . '/' . implode('/', array_map('rawurlencode', [...$segments, $file])),
                'alt' => $entry['label'] ?? ($name !== '' ? $name : $file),
                'url' => $entry['url'] ?? '',
            ];
        }

        return $logos;
    }

    /** filename | URL | accessible label; invalid URLs leave an unlinked logo. */
    public static function parseLinks(string $input): array
    {
        $links = [];

        foreach (preg_split('/\R/u', $input) ?: [] as $line) {
            $line = trim($line);

            if ($line === '' || str_starts_with($line, '#')) {
                continue;
            }

            $parts = array_map('trim', explode('|', $line, 3));
            $filename = $parts[0];

            if ($filename === '' || strpbrk($filename, '/\\') !== false) {
                continue;
            }

            $links[$filename] = ['url' => self::safeUrl($parts[1] ?? '')];

            if (($parts[2] ?? '') !== '') {
                $links[$filename]['label'] = $parts[2];
            }
        }

        return $links;
    }

    private static function safeUrl(string $url): string
    {
        // Reject browser-normalised backslashes, whitespace and encoded controls.
        if ($url === '' || preg_match('/[\s\\\\\x00-\x1f\x7f]/u', rawurldecode($url))) {
            return '';
        }

        if (str_starts_with($url, '/') && !str_starts_with(rawurldecode($url), '//')) {
            return $url;
        }

        if (filter_var($url, FILTER_VALIDATE_URL) !== false && in_array(strtolower((string) parse_url($url, PHP_URL_SCHEME)), ['https', 'http'], true) && parse_url($url, PHP_URL_USER) === null && parse_url($url, PHP_URL_PASS) === null) {
            return $url;
        }

        return '';
    }

    private static function isInside(string $path, string $parent): bool
    {
        $path = str_replace('\\', '/', $path);
        $parent = rtrim(str_replace('\\', '/', $parent), '/');

        if (PHP_OS_FAMILY === 'Windows') {
            $path = strtolower($path);
            $parent = strtolower($parent);
        }

        return $path === $parent || str_starts_with($path, $parent . '/');
    }
}
