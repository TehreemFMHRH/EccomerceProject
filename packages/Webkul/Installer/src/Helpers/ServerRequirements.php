<?php

namespace Webkul\Installer\Helpers;

class ServerRequirements
{
    
    private $minPhpVersion = '8.1.0';

    
    public function validate(): array
    {
        // Server Requirements
        $requirements = [
            'php' => [
                'calendar',
                'ctype',
                'curl',
                'dom',
                'fileinfo',
                'filter',
                'gd',
                'hash',
                'intl',
                'json',
                'mbstring',
                'openssl',
                'pcre',
                'pdo',
                'session',
                'tokenizer',
                'xml',
            ],
        ];

        $results = [];

        foreach ($requirements as $type => $requirement) {
            foreach ($requirement as $item) {
                $results['requirements'][$type][$item] = true;

                if (! extension_loaded($item)) {
                    $results['requirements'][$type][$item] = false;

                    $results['errors'] = true;
                }
            }
        }

        return $results;
    }

    
    public function checkPHPversion(?string $minPhpVersion = null)
    {
        $minVersionPhp = $minPhpVersion ?? $this->minPhpVersion;

        $currentPhpVersion = $this->getPhpVersionInfo();

        $supported = version_compare($currentPhpVersion['version'], $minVersionPhp) >= 0;

        return [
            'full'      => $currentPhpVersion['full'],
            'current'   => $currentPhpVersion['version'],
            'minimum'   => $minVersionPhp,
            'supported' => $supported,
        ];
    }

    
    private static function getPhpVersionInfo()
    {
        $currentVersionFull = PHP_VERSION;

        preg_match("#^\d+(\.\d+)*#", $currentVersionFull, $filtered);

        return [
            'full'    => $currentVersionFull,
            'version' => $filtered[0] ?? $currentVersionFull,
        ];
    }
}
