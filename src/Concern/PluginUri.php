<?php

namespace SetranMedia\WpPluginner\Concern;

trait PluginUri
{
    public function getBaseUriAttribute()
    {
        return untrailingslashit($this->pluginUri);
    }

    public function getPublicUriAttribute()
    {
        return $this->pluginUri . 'public';
    }

    public function getStorageUriAttribute()
    {
        return $this->pluginUri . 'storage';
    }
}
