<?php

namespace Sirv;

/**
 * SirvClient - SDK for building Sirv URLs and HTML tags for images, spins, videos, 3D models, and galleries.
 *
 * @example
 * $sirv = new SirvClient(['domain' => 'demo.sirv.com', 'defaults' => ['q' => 80]]);
 * $url = $sirv->url('/image.jpg', ['w' => 300, 'format' => 'webp']);
 * $html = $sirv->image('/photo.jpg', ['alt' => 'A photo']);
 */
class SirvClient
{
    private string $domain;
    /** @var array<string, mixed> */
    private array $defaults;

    /**
     * Create a SirvClient instance.
     *
     * @param array{domain: string, defaults?: array<string, mixed>} $options
     * @throws \InvalidArgumentException if domain is not provided
     */
    public function __construct(array $options = [])
    {
        if (empty($options['domain'])) {
            throw new \InvalidArgumentException('domain is required');
        }
        $this->domain = rtrim($options['domain'], '/');
        $this->defaults = $options['defaults'] ?? [];
    }

    /**
     * Flatten a nested array into dot-notation key-value pairs.
     *
     * @param array<string, mixed> $arr
     * @param string $prefix
     * @return array<int, array{0: string, 1: string}>
     */
    private function flatten(array $arr, string $prefix = ''): array
    {
        $entries = [];
        foreach ($arr as $key => $value) {
            $fullKey = $prefix !== '' ? $prefix . '.' . $key : (string)$key;
            if ($value !== null && is_array($value)) {
                $entries = array_merge($entries, $this->flatten($value, $fullKey));
            } elseif ($value !== null) {
                if (is_bool($value)) {
                    $entries[] = [$fullKey, $value ? 'true' : 'false'];
                } else {
                    $entries[] = [$fullKey, (string)$value];
                }
            }
        }
        return $entries;
    }

    /**
     * Build a query string from merged defaults + params.
     *
     * @param array<string, mixed> $params
     * @return string
     */
    private function buildQuery(array $params = []): string
    {
        $merged = array_merge($this->defaults, $params);
        $entries = $this->flatten($merged);
        if (empty($entries)) {
            return '';
        }
        $parts = [];
        foreach ($entries as [$k, $v]) {
            $parts[] = rawurlencode($k) . '=' . rawurlencode($v);
        }
        return '?' . implode('&', $parts);
    }

    /**
     * Build a full Sirv URL.
     *
     * @param string $path Asset path (e.g. '/image.jpg')
     * @param array<string, mixed> $params Transformation parameters (nested arrays are flattened to dot-notation)
     * @return string
     */
    public function url(string $path, array $params = []): string
    {
        $normalizedPath = str_starts_with($path, '/') ? $path : '/' . $path;
        return 'https://' . $this->domain . $normalizedPath . $this->buildQuery($params);
    }

    /**
     * Generate a srcset string for responsive images.
     *
     * @param string $path Image path
     * @param array<string, mixed> $params Transformation parameters
     * @param array<string, mixed> $options srcset options:
     *   - widths (int[]): Explicit list of widths
     *   - minWidth (int): Minimum width for auto-generation
     *   - maxWidth (int): Maximum width for auto-generation
     *   - tolerance (float): Tolerance for auto-generating widths (0-1), default 0.15
     *   - devicePixelRatios (float[]): DPR values (e.g. [1, 2, 3])
     * @return string
     */
    public function srcSet(string $path, array $params = [], array $options = []): string
    {
        if (!empty($options['widths'])) {
            $entries = [];
            foreach ($options['widths'] as $w) {
                $entries[] = $this->url($path, array_merge($params, ['w' => $w])) . ' ' . $w . 'w';
            }
            return implode(', ', $entries);
        }

        if (isset($options['minWidth']) && isset($options['maxWidth'])) {
            $tolerance = $options['tolerance'] ?? 0.15;
            $widths = $this->generateWidths($options['minWidth'], $options['maxWidth'], $tolerance);
            $entries = [];
            foreach ($widths as $w) {
                $entries[] = $this->url($path, array_merge($params, ['w' => $w])) . ' ' . $w . 'w';
            }
            return implode(', ', $entries);
        }

        if (!empty($options['devicePixelRatios'])) {
            $baseQ = $params['q'] ?? $this->defaults['q'] ?? 80;
            $entries = [];
            foreach ($options['devicePixelRatios'] as $dpr) {
                $q = $this->dprQuality($baseQ, $dpr);
                $dprParams = array_merge($params, ['q' => $q]);
                if (isset($params['w'])) {
                    $dprParams['w'] = $params['w'] * $dpr;
                }
                if (isset($params['h'])) {
                    $dprParams['h'] = $params['h'] * $dpr;
                }
                $entries[] = $this->url($path, $dprParams) . ' ' . $dpr . 'x';
            }
            return implode(', ', $entries);
        }

        return '';
    }

    /**
     * Calculate quality for a given DPR. Higher DPR uses lower quality since pixels are smaller.
     *
     * @param int|float $baseQ Base quality at 1x
     * @param int|float $dpr Device pixel ratio
     * @return int
     */
    private function dprQuality($baseQ, $dpr): int
    {
        if ($dpr <= 1) {
            return (int)$baseQ;
        }
        return (int)round($baseQ * pow(0.75, $dpr - 1));
    }

    /**
     * Generate widths between min and max using a tolerance step.
     *
     * @param int|float $min
     * @param int|float $max
     * @param float $tolerance
     * @return int[]
     */
    private function generateWidths($min, $max, float $tolerance): array
    {
        $widths = [];
        $current = (float)$min;
        while ($current < $max) {
            $widths[] = (int)round($current);
            $current *= 1 + $tolerance * 2;
        }
        $widths[] = (int)round($max);
        return $widths;
    }

    /**
     * Serialize viewer options to semicolon-separated format for data-options.
     *
     * @param array<string, mixed> $opts
     * @return string
     */
    private function serializeViewerOptions(array $opts): string
    {
        $parts = [];
        foreach ($opts as $k => $v) {
            if (is_bool($v)) {
                $v = $v ? 'true' : 'false';
            }
            $parts[] = $k . ':' . $v;
        }
        return implode(';', $parts);
    }

    /**
     * Escape a string for use in an HTML attribute value.
     *
     * @param string $str
     * @return string
     */
    private function escapeAttr(string $str): string
    {
        return htmlspecialchars($str, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }

    /**
     * Generate an <img> tag for a Sirv image.
     *
     * @param string $path Image path
     * @param array<string, mixed> $options Options:
     *   - transform (array): Transformation parameters for the URL
     *   - viewer (array): Viewer options for data-options attribute
     *   - alt (string): Alt text
     *   - className (string): Additional CSS class(es)
     * @return string
     */
    public function image(string $path, array $options = []): string
    {
        $src = $this->url($path, $options['transform'] ?? []);
        $cls = isset($options['className']) ? 'Sirv ' . $options['className'] : 'Sirv';
        $html = '<img class="' . $cls . '" data-src="' . $this->escapeAttr($src) . '"';
        if (array_key_exists('alt', $options)) {
            $html .= ' alt="' . $this->escapeAttr((string)$options['alt']) . '"';
        }
        if (isset($options['viewer'])) {
            $html .= ' data-options="' . $this->escapeAttr($this->serializeViewerOptions($options['viewer'])) . '"';
        }
        $html .= '>';
        return $html;
    }

    /**
     * Generate a <div> tag for a Sirv zoom viewer.
     *
     * @param string $path Image path
     * @param array<string, mixed> $options Options:
     *   - transform (array): Transformation parameters
     *   - viewer (array): Viewer options
     *   - className (string): Additional CSS class(es)
     * @return string
     */
    public function zoom(string $path, array $options = []): string
    {
        return $this->viewerDiv($path, 'zoom', $options);
    }

    /**
     * Generate a <div> tag for a Sirv spin viewer.
     *
     * @param string $path Path to .spin file
     * @param array<string, mixed> $options Options:
     *   - viewer (array): Viewer options
     *   - className (string): Additional CSS class(es)
     * @return string
     */
    public function spin(string $path, array $options = []): string
    {
        return $this->viewerDiv($path, null, $options);
    }

    /**
     * Generate a <div> tag for a Sirv video.
     *
     * @param string $path Video path
     * @param array<string, mixed> $options Options:
     *   - viewer (array): Viewer options
     *   - className (string): Additional CSS class(es)
     * @return string
     */
    public function video(string $path, array $options = []): string
    {
        return $this->viewerDiv($path, null, $options);
    }

    /**
     * Generate a <div> tag for a Sirv 3D model viewer.
     *
     * @param string $path Path to .glb file
     * @param array<string, mixed> $options Options:
     *   - viewer (array): Viewer options
     *   - className (string): Additional CSS class(es)
     * @return string
     */
    public function model(string $path, array $options = []): string
    {
        return $this->viewerDiv($path, null, $options);
    }

    /**
     * Internal helper to generate viewer div tags.
     *
     * @param string $path
     * @param string|null $type data-type value (e.g. 'zoom'), or null to omit
     * @param array<string, mixed> $options
     * @return string
     */
    private function viewerDiv(string $path, ?string $type, array $options = []): string
    {
        $src = $this->url($path, $options['transform'] ?? []);
        $cls = isset($options['className']) ? 'Sirv ' . $options['className'] : 'Sirv';
        $html = '<div class="' . $cls . '" data-src="' . $this->escapeAttr($src) . '"';
        if ($type !== null) {
            $html .= ' data-type="' . $type . '"';
        }
        if (isset($options['viewer'])) {
            $html .= ' data-options="' . $this->escapeAttr($this->serializeViewerOptions($options['viewer'])) . '"';
        }
        $html .= '></div>';
        return $html;
    }

    /**
     * Generate a gallery container with multiple assets.
     *
     * @param array<int, array<string, mixed>> $items Gallery items, each with:
     *   - src (string): Asset path
     *   - type (string): Asset type override (e.g. 'zoom', 'spin')
     *   - transform (array): Per-item transformation params
     *   - viewer (array): Per-item viewer options
     * @param array<string, mixed> $options Options:
     *   - viewer (array): Gallery-level viewer options
     *   - className (string): Additional CSS class(es) for the gallery container
     * @return string
     */
    public function gallery(array $items, array $options = []): string
    {
        $cls = isset($options['className']) ? 'Sirv ' . $options['className'] : 'Sirv';
        $html = '<div class="' . $cls . '"';
        if (isset($options['viewer'])) {
            $html .= ' data-options="' . $this->escapeAttr($this->serializeViewerOptions($options['viewer'])) . '"';
        }
        $html .= '>';

        foreach ($items as $item) {
            $src = $this->url($item['src'], $item['transform'] ?? []);
            $child = '<div data-src="' . $this->escapeAttr($src) . '"';
            if (isset($item['type'])) {
                $child .= ' data-type="' . $item['type'] . '"';
            }
            if (isset($item['viewer'])) {
                $child .= ' data-options="' . $this->escapeAttr($this->serializeViewerOptions($item['viewer'])) . '"';
            }
            $child .= '></div>';
            $html .= $child;
        }

        $html .= '</div>';
        return $html;
    }

    /**
     * Generate a <script> tag to load Sirv JS.
     *
     * @param array<string, mixed> $options Options:
     *   - modules (string[]): Specific modules to load (e.g. ['spin', 'zoom'])
     *   - async (bool): Whether to add async attribute (default true)
     * @return string
     */
    public function scriptTag(array $options = []): string
    {
        $async = $options['async'] ?? true;
        $filename = 'sirv';
        if (!empty($options['modules'])) {
            $filename = 'sirv.' . implode('.', $options['modules']);
        }
        $html = '<script src="https://scripts.sirv.com/sirvjs/v3/' . $filename . '.js"';
        if ($async) {
            $html .= ' async';
        }
        $html .= '></script>';
        return $html;
    }
}
