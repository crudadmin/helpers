<?php

namespace AdminHelpers\Utilities\Concerns;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Cache;

trait HasBuildVersion
{
    /**
     * Returns basepath for client app
     *
     * @return void
     */
    public function getBundlePath()
    {
        if ( $basepath = env('NUXT_PATH') ) {
            return $basepath.'/.nuxt/dist/client';
        } else if ( $basepath = env('IONIC_PATH') ) {
            return $basepath.'/dist';
        }
    }

    /**
     * Returns bundle key for client app
     *
     * @return void
     */
    public function getBundleKey()
    {
        return Cache::remember('bundle_key', now()->addSeconds(60), function(){
            return $this->generateBundleKey();
        });
    }

    private function generateBundleKey()
    {
        $clientAppPath = $this->getBundlePath();

        //If path does not exists (in dev mode path also does not exists)
        if ( file_exists($clientAppPath) == false ){
            return;
        }

        $bundleFiles = collect(File::allFiles($clientAppPath))->filter(function($file){
            return in_array($file->getExtension(), ['js', 'html', 'css']);
        })->map(function($file) use ($clientAppPath){
            return str_replace($clientAppPath, '', $file->getRealPath());
        })->toArray();

        //If no build files ara available, we does not want force APP to refresh, probably this is onbuild state where
        //app is being builded right now
        if ( count($bundleFiles) == 0 ) {
            return;
        }

        return crc32(implode(';', $bundleFiles));
    }
}