# Sirv Image Transformation - PHP

A fluent URL builder for [Sirv](https://sirv.com) dynamic image transformations. Build transformed image URLs with an intuitive, chainable API — no API credentials required.

## Installation

```bash
composer require sirv/sirv-image
```

## Quick Start

```php
use Sirv\SirvImage;

$url = (new SirvImage('https://demo.sirv.com/image.jpg'))
    ->resize(300, 200)
    ->format('webp')
    ->quality(80)
    ->toUrl();

// https://demo.sirv.com/image.jpg?w=300&h=200&format=webp&q=80
```

## Constructor

```php
// Full image URL
new SirvImage('https://demo.sirv.com/image.jpg');

// Base URL + path
new SirvImage('https://demo.sirv.com', '/image.jpg');
```

## API Reference

All methods return `$this` for chaining. Call `->toUrl()` to get the final URL. Casting to string also works.

### Resize

| Method | Parameters | Description |
|--------|-----------|-------------|
| `resize($width, $height, $option)` | `int\|null, int\|null, string\|null` | Resize with optional scale option |
| `width($w)` | `int` | Set width |
| `height($h)` | `int` | Set height |
| `scaleByLongest($s)` | `int` | Resize by longest dimension |
| `thumbnail($size = 256)` | `int` | Create square thumbnail |

### Crop

| Method | Parameters | Description |
|--------|-----------|-------------|
| `crop($w, $h, $x, $y, $type, $padW, $padH)` | all nullable | Crop image |
| `clipPath($name)` | `string` | Apply clipping path |

### Rotation

| Method | Description |
|--------|-------------|
| `rotate($degrees)` | Rotate (-180 to 180) |
| `flip()` | Flip vertically |
| `flop()` | Flip horizontally |

### Format & Quality

| Method | Parameters | Description |
|--------|-----------|-------------|
| `format($fmt)` | `string` | Output format |
| `quality($q)` | `int` | JPEG quality (0-100) |
| `webpFallback($fmt)` | `string` | WebP fallback |
| `subsampling($value)` | `string` | Chroma subsampling |
| `pngOptimize($enabled = true)` | `bool` | PNG optimization |
| `gifLossy($level)` | `int` | GIF lossy compression |

### Color Adjustments

`brightness($v)`, `contrast($v)`, `exposure($v)`, `hue($v)`, `saturation($v)`, `lightness($v)`, `shadows($v)`, `highlights($v)` — all accept `int` (-100 to 100).

`grayscale()`, `colorLevel($black, $white)`, `histogram($channel)`

### Color Effects

| Method | Parameters | Description |
|--------|-----------|-------------|
| `colorize($color, $opacity)` | `string, int` | Color overlay |
| `colortone($preset)` | `string` | Preset colortone |
| `colortone(null, $options)` | `array` | Custom colortone with color, level, mode |

### Effects

`blur($v)`, `sharpen($v)`, `vignette($value, $color)`, `opacity($v)`

### Text Overlay

```php
->text('Hello', [
    'fontSize' => 32,
    'fontFamily' => 'Arial',
    'fontStyle' => 'italic',
    'fontWeight' => 700,
    'color' => 'white',
    'opacity' => 80,
    'outlineWidth' => 2,
    'outlineColor' => 'black',
    'backgroundColor' => 'red',
    'backgroundOpacity' => 50,
    'align' => 'center',
    'position' => 'center',
    'positionX' => 10,
    'positionY' => 20,
    'positionGravity' => 'northwest',
])
```

### Watermark

```php
->watermark('/logo.png', [
    'position' => 'southeast',
    'opacity' => 50,
    'scaleWidth' => '20%',
    'scaleOption' => 'fit',
    'rotate' => 45,
    'layer' => 'front',
])
```

### Canvas & Frame

```php
->canvas(['width' => 800, 'height' => 600, 'color' => 'ffffff',
          'aspectRatio' => '16:9', 'borderWidth' => 10])
->frame(['style' => 'solid', 'color' => '333', 'width' => 5,
         'rimColor' => 'gold', 'rimWidth' => 2])
```

### Other

| Method | Description |
|--------|-------------|
| `page($num)` | PDF page |
| `profile($name)` | Saved profile |

## Examples

### E-commerce Product

```php
$url = (new SirvImage('https://demo.sirv.com/products/shoe.jpg'))
    ->resize(800, 600, 'fill')
    ->format('webp')
    ->quality(85)
    ->watermark('/brand-logo.png', ['position' => 'southeast', 'opacity' => 30])
    ->toUrl();
```

### Vintage Effect

```php
$url = (new SirvImage('https://demo.sirv.com/photo.jpg'))
    ->colortone('sepia')
    ->vignette(40)
    ->frame(['style' => 'solid', 'color' => 'f5e6d3', 'width' => 20])
    ->toUrl();
```

## Testing

```bash
./vendor/bin/phpunit tests/
```

## License

MIT
