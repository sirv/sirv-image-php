<?php

namespace Sirv\Tests;

use PHPUnit\Framework\TestCase;
use Sirv\SirvClient;

class SirvClientTest extends TestCase
{
    private const DOMAIN = 'demo.sirv.com';

    // ── Constructor ─────────────────────────────────────────

    public function testConstructorRequiresDomain(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('domain is required');
        new SirvClient();
    }

    public function testConstructorRequiresDomainWithEmptyArray(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new SirvClient([]);
    }

    public function testConstructorWithDomainOnly(): void
    {
        $sirv = new SirvClient(['domain' => self::DOMAIN]);
        $this->assertEquals('https://demo.sirv.com/image.jpg', $sirv->url('/image.jpg'));
    }

    public function testConstructorStripsTrailingSlash(): void
    {
        $sirv = new SirvClient(['domain' => 'demo.sirv.com/']);
        $this->assertEquals('https://demo.sirv.com/image.jpg', $sirv->url('/image.jpg'));
    }

    public function testConstructorWithDefaults(): void
    {
        $sirv = new SirvClient(['domain' => self::DOMAIN, 'defaults' => ['q' => 80]]);
        $this->assertEquals('https://demo.sirv.com/image.jpg?q=80', $sirv->url('/image.jpg'));
    }

    // ── url() ──────────────────────────────────────────────

    public function testUrlWithSimpleParams(): void
    {
        $sirv = new SirvClient(['domain' => self::DOMAIN]);
        $url = $sirv->url('/image.jpg', ['w' => 300, 'h' => 200, 'format' => 'webp']);
        $this->assertEquals('https://demo.sirv.com/image.jpg?w=300&h=200&format=webp', $url);
    }

    public function testUrlMergesDefaultsWithParams(): void
    {
        $sirv = new SirvClient(['domain' => self::DOMAIN, 'defaults' => ['q' => 80]]);
        $url = $sirv->url('/image.jpg', ['w' => 300, 'h' => 200, 'format' => 'webp']);
        $this->assertEquals('https://demo.sirv.com/image.jpg?q=80&w=300&h=200&format=webp', $url);
    }

    public function testParamsOverrideDefaults(): void
    {
        $sirv = new SirvClient(['domain' => self::DOMAIN, 'defaults' => ['q' => 80]]);
        $url = $sirv->url('/image.jpg', ['q' => 90]);
        $this->assertEquals('https://demo.sirv.com/image.jpg?q=90', $url);
    }

    public function testUrlWithNestedParamsFlattensToDotNotation(): void
    {
        $sirv = new SirvClient(['domain' => self::DOMAIN]);
        $url = $sirv->url('/image.jpg', [
            'crop' => ['type' => 'face', 'pad' => ['width' => 10, 'height' => 10]]
        ]);
        $this->assertStringContainsString('crop.type=face', $url);
        $this->assertStringContainsString('crop.pad.width=10', $url);
        $this->assertStringContainsString('crop.pad.height=10', $url);
    }

    public function testUrlWithDeeplyNestedParams(): void
    {
        $sirv = new SirvClient(['domain' => self::DOMAIN]);
        $url = $sirv->url('/image.jpg', [
            'text' => ['font' => ['family' => 'Arial', 'size' => 24], 'color' => 'white']
        ]);
        $this->assertStringContainsString('text.font.family=Arial', $url);
        $this->assertStringContainsString('text.font.size=24', $url);
        $this->assertStringContainsString('text.color=white', $url);
    }

    public function testUrlAddsLeadingSlashIfMissing(): void
    {
        $sirv = new SirvClient(['domain' => self::DOMAIN]);
        $this->assertEquals('https://demo.sirv.com/image.jpg', $sirv->url('image.jpg'));
    }

    public function testUrlWithNoParamsReturnsCleanUrl(): void
    {
        $sirv = new SirvClient(['domain' => self::DOMAIN]);
        $this->assertEquals('https://demo.sirv.com/image.jpg', $sirv->url('/image.jpg'));
    }

    public function testUrlEncodesSpecialCharacters(): void
    {
        $sirv = new SirvClient(['domain' => self::DOMAIN]);
        $url = $sirv->url('/image.jpg', ['subsampling' => '4:2:0']);
        $this->assertStringContainsString('subsampling=4%3A2%3A0', $url);
    }

    // ── srcSet() ───────────────────────────────────────────

    public function testSrcSetWithExplicitWidths(): void
    {
        $sirv = new SirvClient(['domain' => self::DOMAIN]);
        $srcset = $sirv->srcSet('/image.jpg', ['format' => 'webp'], ['widths' => [320, 640, 960]]);
        $this->assertStringContainsString('w=320 320w', $srcset);
        $this->assertStringContainsString('w=640 640w', $srcset);
        $this->assertStringContainsString('w=960 960w', $srcset);
        $this->assertStringContainsString('format=webp', $srcset);
    }

    public function testSrcSetWithMinMaxWidthTolerance(): void
    {
        $sirv = new SirvClient(['domain' => self::DOMAIN]);
        $srcset = $sirv->srcSet('/image.jpg', ['format' => 'webp'], [
            'minWidth' => 200, 'maxWidth' => 2000, 'tolerance' => 0.15
        ]);
        $entries = explode(', ', $srcset);
        $this->assertGreaterThan(2, count($entries));
        $this->assertStringContainsString('w=200', $entries[0]);
        $this->assertStringContainsString('w=2000', $entries[count($entries) - 1]);
    }

    public function testSrcSetWithDevicePixelRatios(): void
    {
        $sirv = new SirvClient(['domain' => self::DOMAIN, 'defaults' => ['q' => 80]]);
        $srcset = $sirv->srcSet('/hero.jpg', ['w' => 600, 'h' => 400], [
            'devicePixelRatios' => [1, 2, 3]
        ]);
        $this->assertStringContainsString('1x', $srcset);
        $this->assertStringContainsString('2x', $srcset);
        $this->assertStringContainsString('3x', $srcset);
        // 1x should have q=80
        $this->assertStringContainsString('q=80', $srcset);
        // 2x should have w=1200
        $this->assertStringContainsString('w=1200', $srcset);
        // 3x should have w=1800
        $this->assertStringContainsString('w=1800', $srcset);
    }

    public function testSrcSetWithDprUsesVariableQuality(): void
    {
        $sirv = new SirvClient(['domain' => self::DOMAIN, 'defaults' => ['q' => 80]]);
        $srcset = $sirv->srcSet('/hero.jpg', ['w' => 600], [
            'devicePixelRatios' => [1, 2, 3]
        ]);
        // q=80 at 1x, q=60 at 2x, q=45 at 3x (80 * 0.75^(dpr-1))
        $entries = explode(', ', $srcset);
        $this->assertStringContainsString('q=80', $entries[0]);
        $this->assertStringContainsString('q=60', $entries[1]);
    }

    public function testSrcSetReturnsEmptyStringWithNoOptions(): void
    {
        $sirv = new SirvClient(['domain' => self::DOMAIN]);
        $this->assertEquals('', $sirv->srcSet('/image.jpg'));
    }

    // ── image() ────────────────────────────────────────────

    public function testImageGeneratesImgTag(): void
    {
        $sirv = new SirvClient(['domain' => self::DOMAIN]);
        $html = $sirv->image('/tomatoes.jpg', ['alt' => 'Fresh tomatoes']);
        $this->assertEquals(
            '<img class="Sirv" data-src="https://demo.sirv.com/tomatoes.jpg" alt="Fresh tomatoes">',
            $html
        );
    }

    public function testImageWithTransformParams(): void
    {
        $sirv = new SirvClient(['domain' => self::DOMAIN]);
        $html = $sirv->image('/photo.jpg', ['transform' => ['w' => 300, 'format' => 'webp']]);
        $this->assertStringContainsString('data-src="https://demo.sirv.com/photo.jpg?w=300&amp;format=webp"', $html);
    }

    public function testImageWithViewerOptions(): void
    {
        $sirv = new SirvClient(['domain' => self::DOMAIN]);
        $html = $sirv->image('/photo.jpg', [
            'viewer' => ['autostart' => 'visible', 'threshold' => 200]
        ]);
        $this->assertStringContainsString('data-options="autostart:visible;threshold:200"', $html);
    }

    public function testImageWithCustomClassName(): void
    {
        $sirv = new SirvClient(['domain' => self::DOMAIN]);
        $html = $sirv->image('/photo.jpg', ['className' => 'hero-image']);
        $this->assertStringContainsString('class="Sirv hero-image"', $html);
    }

    public function testImageWithEmptyAlt(): void
    {
        $sirv = new SirvClient(['domain' => self::DOMAIN]);
        $html = $sirv->image('/photo.jpg', ['alt' => '']);
        $this->assertStringContainsString('alt=""', $html);
    }

    // ── zoom() ─────────────────────────────────────────────

    public function testZoomGeneratesDivWithDataTypeZoom(): void
    {
        $sirv = new SirvClient(['domain' => self::DOMAIN]);
        $html = $sirv->zoom('/product.jpg');
        $this->assertEquals(
            '<div class="Sirv" data-src="https://demo.sirv.com/product.jpg" data-type="zoom"></div>',
            $html
        );
    }

    public function testZoomWithViewerOptions(): void
    {
        $sirv = new SirvClient(['domain' => self::DOMAIN]);
        $html = $sirv->zoom('/product.jpg', [
            'viewer' => ['mode' => 'deep', 'wheel' => false]
        ]);
        $this->assertStringContainsString('data-type="zoom"', $html);
        $this->assertStringContainsString('data-options="mode:deep;wheel:false"', $html);
    }

    // ── spin() ─────────────────────────────────────────────

    public function testSpinGeneratesDivWithoutDataType(): void
    {
        $sirv = new SirvClient(['domain' => self::DOMAIN]);
        $html = $sirv->spin('/product.spin');
        $this->assertEquals(
            '<div class="Sirv" data-src="https://demo.sirv.com/product.spin"></div>',
            $html
        );
        $this->assertStringNotContainsString('data-type', $html);
    }

    public function testSpinWithViewerOptions(): void
    {
        $sirv = new SirvClient(['domain' => self::DOMAIN]);
        $html = $sirv->spin('/product.spin', [
            'viewer' => ['autostart' => 'visible', 'autospin' => 'lazy']
        ]);
        $this->assertStringContainsString('data-options="autostart:visible;autospin:lazy"', $html);
    }

    // ── video() ────────────────────────────────────────────

    public function testVideoGeneratesDivWithoutDataType(): void
    {
        $sirv = new SirvClient(['domain' => self::DOMAIN]);
        $html = $sirv->video('/clip.mp4');
        $this->assertEquals(
            '<div class="Sirv" data-src="https://demo.sirv.com/clip.mp4"></div>',
            $html
        );
    }

    // ── model() ────────────────────────────────────────────

    public function testModelGeneratesDivWithoutDataType(): void
    {
        $sirv = new SirvClient(['domain' => self::DOMAIN]);
        $html = $sirv->model('/shoe.glb');
        $this->assertEquals(
            '<div class="Sirv" data-src="https://demo.sirv.com/shoe.glb"></div>',
            $html
        );
    }

    // ── gallery() ──────────────────────────────────────────

    public function testGalleryGeneratesNestedDivs(): void
    {
        $sirv = new SirvClient(['domain' => self::DOMAIN]);
        $html = $sirv->gallery([
            ['src' => '/product.spin'],
            ['src' => '/front.jpg', 'type' => 'zoom']
        ]);
        $this->assertStringContainsString('<div class="Sirv">', $html);
        $this->assertStringContainsString('data-src="https://demo.sirv.com/product.spin"', $html);
        $this->assertStringContainsString('data-src="https://demo.sirv.com/front.jpg" data-type="zoom"', $html);
        $this->assertMatchesRegularExpression('/<\/div><\/div>$/', $html);
    }

    public function testGalleryWithGalleryLevelViewerOptions(): void
    {
        $sirv = new SirvClient(['domain' => self::DOMAIN]);
        $html = $sirv->gallery(
            [['src' => '/image1.jpg']],
            ['viewer' => ['arrows' => 'true', 'thumbnails' => 'bottom']]
        );
        $this->assertStringContainsString('data-options="arrows:true;thumbnails:bottom"', $html);
    }

    public function testGalleryWithPerItemViewerOptions(): void
    {
        $sirv = new SirvClient(['domain' => self::DOMAIN]);
        $html = $sirv->gallery([
            ['src' => '/product.jpg', 'type' => 'zoom', 'viewer' => ['mode' => 'deep']]
        ]);
        $this->assertStringContainsString('data-type="zoom"', $html);
        $this->assertStringContainsString('data-options="mode:deep"', $html);
    }

    public function testGalleryWithPerItemTransforms(): void
    {
        $sirv = new SirvClient(['domain' => self::DOMAIN]);
        $html = $sirv->gallery([
            ['src' => '/photo.jpg', 'transform' => ['w' => 800, 'format' => 'webp']]
        ]);
        $this->assertStringContainsString('w=800', $html);
        $this->assertStringContainsString('format=webp', $html);
    }

    public function testGalleryWithCustomClassName(): void
    {
        $sirv = new SirvClient(['domain' => self::DOMAIN]);
        $html = $sirv->gallery([['src' => '/img.jpg']], ['className' => 'product-gallery']);
        $this->assertStringContainsString('class="Sirv product-gallery"', $html);
    }

    // ── scriptTag() ────────────────────────────────────────

    public function testScriptTagWithNoModules(): void
    {
        $sirv = new SirvClient(['domain' => self::DOMAIN]);
        $html = $sirv->scriptTag();
        $this->assertEquals(
            '<script src="https://scripts.sirv.com/sirvjs/v3/sirv.js" async></script>',
            $html
        );
    }

    public function testScriptTagWithModules(): void
    {
        $sirv = new SirvClient(['domain' => self::DOMAIN]);
        $html = $sirv->scriptTag(['modules' => ['spin', 'zoom']]);
        $this->assertEquals(
            '<script src="https://scripts.sirv.com/sirvjs/v3/sirv.spin.zoom.js" async></script>',
            $html
        );
    }

    public function testScriptTagWithoutAsync(): void
    {
        $sirv = new SirvClient(['domain' => self::DOMAIN]);
        $html = $sirv->scriptTag(['async' => false]);
        $this->assertEquals(
            '<script src="https://scripts.sirv.com/sirvjs/v3/sirv.js"></script>',
            $html
        );
    }
}
