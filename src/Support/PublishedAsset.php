<?php

declare(strict_types=1);

namespace JohnRivera7\FilamentWidgetGrid\Support;

use Composer\InstalledVersions;
use Throwable;

/**
 * Path / @dev packages keep Filament's ?v=dev-main forever, so CDNs (Cloudflare)
 * keep serving stale public/css|js after filament:assets. Replace ?v= with a
 * content hash of the published file so each republish busts the cache.
 */
final class PublishedAsset
{
    public static function href(string $filamentHref, string $publicRelativePath): string
    {
        $version = self::versionForPublicPath($publicRelativePath);

        if (str_contains($filamentHref, '?v=')) {
            return (string) preg_replace('/\?v=[^&]*/', '?v=' . $version, $filamentHref);
        }

        $sep = str_contains($filamentHref, '?') ? '&' : '?';

        return $filamentHref . $sep . 'v=' . $version;
    }

    public static function versionForPublicPath(string $publicRelativePath): string
    {
        $absolute = public_path(ltrim($publicRelativePath, '/'));

        if (is_file($absolute)) {
            $hash = md5_file($absolute);

            if (is_string($hash) && $hash !== '') {
                return substr($hash, 0, 12);
            }
        }

        try {
            $ref = InstalledVersions::getReference('johnrivera7/filament-custom-dashboard-widgets');

            if (is_string($ref) && $ref !== '') {
                return substr($ref, 0, 12);
            }
        } catch (Throwable) {
            // fall through
        }

        return 'dev';
    }
}
