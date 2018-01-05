<?php

namespace SetranMedia\WpPluginner\Support;

use Illuminate\Config\Repository;
use Symfony\Component\Finder\Finder;

if ( ! defined( 'ABSPATH' ) ) exit;

class Config extends Repository
{

    public function loadConfigurationFiles($path)
    {
        if ( ! function_exists( 'wp_create_nonce' ) ) {
            require_once( ABSPATH . 'wp-includes/pluggable.php' );
        }
        $this->configPath = $path;
        $files = $this->getConfigurationFiles();
        foreach ($files as $fileKey => $path) {
            $this->set($fileKey, require $path);
        }
    }

    protected function getConfigurationFiles()
    {
        $path = $this->configPath;

        if (!is_dir($path)) {
            return [];
        }

        $files = [];
        $phpFiles = Finder::create()->files()->name('*.php')->in($path)->depth(0);

        foreach ($phpFiles as $file) {
            $files[basename($file->getRealPath(), '.php')] = $file->getRealPath();
        }

        return $files;
    }

}
