<?php
declare(strict_types=1);

namespace Coinsnap\Payment\Test\Unit;

use Composer\Semver\Semver;
use PHPUnit\Framework\TestCase;

// The module uses PHP 8 syntax (match, promoted constructor properties, mixed), so composer.json must
// allow the PHP versions Magento 2.4.4+ runs on and refuse the ones the code cannot be parsed by.
class ComposerJsonTest extends TestCase
{
    private function phpConstraint(): string
    {
        $composer = json_decode((string) file_get_contents(__DIR__ . '/../../composer.json'), true);
        $this->assertIsArray($composer, 'composer.json is valid JSON');

        return (string) ($composer['require']['php'] ?? '');
    }

    public function testAllowsSupportedMagentoPhpVersions(): void
    {
        foreach (['8.1.0', '8.2.0', '8.3.0', '8.4.0'] as $version) {
            $this->assertTrue(Semver::satisfies($version, $this->phpConstraint()), "PHP $version allowed");
        }
    }

    public function testRefusesPhpVersionsThatCannotParseTheModule(): void
    {
        foreach (['5.6.40', '7.4.33', '8.0.30'] as $version) {
            $this->assertFalse(Semver::satisfies($version, $this->phpConstraint()), "PHP $version refused");
        }
    }
}
