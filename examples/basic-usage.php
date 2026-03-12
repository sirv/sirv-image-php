<?php

require_once __DIR__ . '/../src/SirvClient.php';

use Sirv\SirvClient;

$sirv = new SirvClient(['domain' => 'demo.sirv.com', 'defaults' => ['q' => 80]]);

// ── Build URLs ──────────────────────────────────────────
echo 'Basic URL: ' . $sirv->url('/image.jpg', ['w' => 300, 'h' => 200, 'format' => 'webp']) . "\n";
// https://demo.sirv.com/image.jpg?q=80&w=300&h=200&format=webp

// ── Nested params (dot-notation flattening) ─────────────
echo 'Nested: ' . $sirv->url('/image.jpg', [
    'crop' => ['type' => 'face', 'pad' => ['width' => 10, 'height' => 10]]
]) . "\n";
// https://demo.sirv.com/image.jpg?q=80&crop.type=face&crop.pad.width=10&crop.pad.height=10

// ── srcSet with explicit widths ─────────────────────────
echo 'srcSet widths: ' . $sirv->srcSet('/image.jpg', ['format' => 'webp'], [
    'widths' => [320, 640, 960, 1280, 1920]
]) . "\n";

// ── srcSet with auto-generated widths ───────────────────
echo 'srcSet auto: ' . $sirv->srcSet('/image.jpg', ['format' => 'webp'], [
    'minWidth' => 200, 'maxWidth' => 2000, 'tolerance' => 0.15
]) . "\n";

// ── srcSet with device pixel ratios ─────────────────────
echo 'srcSet DPR: ' . $sirv->srcSet('/hero.jpg', ['w' => 600, 'h' => 400], [
    'devicePixelRatios' => [1, 2, 3]
]) . "\n";

// ── Image tag ───────────────────────────────────────────
echo 'Image: ' . $sirv->image('/tomatoes.jpg', ['alt' => 'Fresh tomatoes']) . "\n";
// <img class="Sirv" data-src="https://demo.sirv.com/tomatoes.jpg" alt="Fresh tomatoes">

// ── Zoom viewer ─────────────────────────────────────────
echo 'Zoom: ' . $sirv->zoom('/product.jpg', [
    'viewer' => ['mode' => 'deep', 'wheel' => false]
]) . "\n";

// ── Spin viewer ─────────────────────────────────────────
echo 'Spin: ' . $sirv->spin('/product.spin', [
    'viewer' => ['autostart' => 'visible', 'autospin' => 'lazy']
]) . "\n";

// ── Video ───────────────────────────────────────────────
echo 'Video: ' . $sirv->video('/clip.mp4') . "\n";

// ── 3D Model ────────────────────────────────────────────
echo 'Model: ' . $sirv->model('/shoe.glb') . "\n";

// ── Gallery ─────────────────────────────────────────────
echo 'Gallery: ' . $sirv->gallery([
    ['src' => '/product.spin'],
    ['src' => '/front.jpg', 'type' => 'zoom'],
    ['src' => '/side.jpg', 'type' => 'zoom'],
    ['src' => '/video.mp4']
], [
    'viewer' => ['arrows' => 'true', 'thumbnails' => 'bottom']
]) . "\n";

// ── Script tag ──────────────────────────────────────────
echo 'Script: ' . $sirv->scriptTag(['modules' => ['spin', 'zoom']]) . "\n";
// <script src="https://scripts.sirv.com/sirvjs/v3/sirv.spin.zoom.js" async></script>
