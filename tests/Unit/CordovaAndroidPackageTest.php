<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class CordovaAndroidPackageTest extends TestCase
{
    public function testCordovaWrapperAlwaysLoadsTheLiveAkrabHttpsOrigin(): void
    {
        $root = dirname(__DIR__, 2);
        $config = (string) file_get_contents($root . '/mobile/config.xml');

        self::assertStringContainsString('<content src="https://akrab.portodq.com/"', $config);
        self::assertStringContainsString('<allow-navigation href="https://akrab.portodq.com/*"', $config);
        self::assertStringContainsString('<access origin="https://akrab.portodq.com"', $config);
        self::assertStringNotContainsString('<allow-navigation href="*"', $config);
        self::assertStringNotContainsString('<access origin="*"', $config);
        self::assertStringNotContainsString('http://akrab.portodq.com', $config);
    }

    public function testCordovaWrapperUsesLeastPrivilegeAndroidSettings(): void
    {
        $root = dirname(__DIR__, 2);
        $config = (string) file_get_contents($root . '/mobile/config.xml');
        $package = json_decode((string) file_get_contents($root . '/mobile/package.json'), true);

        self::assertSame('13.0.0', $package['devDependencies']['cordova'] ?? null);
        self::assertSame('15.1.0', $package['devDependencies']['cordova-android'] ?? null);
        self::assertSame([], $package['cordova']['plugins'] ?? null);
        self::assertStringContainsString('AndroidInsecureFileModeEnabled" value="false"', $config);
        self::assertStringContainsString('InspectableWebview" value="false"', $config);
        self::assertStringContainsString('loglevel" value="ERROR"', $config);
        self::assertStringContainsString('android:usesCleartextTraffic="false"', $config);
        self::assertStringContainsString('android:allowBackup="false"', $config);
        self::assertStringContainsString('android-minSdkVersion" value="24"', $config);
        self::assertStringContainsString('android-targetSdkVersion" value="36"', $config);
    }

    public function testLandingPageOffersVersionedApkAndChecksumDownloads(): void
    {
        $root = dirname(__DIR__, 2);
        $landing = (string) file_get_contents($root . '/index.php');
        $htaccess = (string) file_get_contents($root . '/.htaccess');
        $downloadEndpoint = (string) file_get_contents($root . '/download_apk.php');

        self::assertStringContainsString('/downloads/AKRAB-Android-v1.0.0.apk', $landing);
        self::assertStringContainsString('/downloads/AKRAB-Android-v1.0.0.apk.sha256', $landing);
        self::assertStringContainsString('application/vnd.android.package-archive', $htaccess);
        self::assertStringContainsString('Content-Disposition "attachment"', $htaccess);
        self::assertStringContainsString(
            'RewriteRule ^downloads/AKRAB-Android-v1\\.0\\.0\\.apk$ download_apk.php [L]',
            $htaccess
        );
        self::assertStringContainsString("__DIR__ . '/downloads/AKRAB-Android-v1.0.0.bin'", $downloadEndpoint);
        self::assertStringContainsString("Content-Disposition: attachment; filename=\"AKRAB-Android-v1.0.0.apk\"", $downloadEndpoint);
        self::assertStringContainsString("readfile(\$apkPath)", $downloadEndpoint);
        self::assertStringNotContainsString('$_GET', $downloadEndpoint);
        self::assertStringNotContainsString('$_POST', $downloadEndpoint);
    }
}
