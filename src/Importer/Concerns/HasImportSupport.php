<?php

namespace AdminHelpers\Importer\Concerns;

trait HasImportSupport
{
    public function getImports()
    {
        return collect(config('admin_helpers.importer.imports'));
    }

    public function getImporter()
    {
        return $this->getImports()
                    ->first(fn($import) => strpos($import['class'], $this->type) !== false);
    }

    public function loadImporter()
    {
        $class = $this->getImporter()['class'];

        return new $class($this);
    }

    protected function getImportClassNameTypes()
    {
        return $this->getImports()->pluck('class')->map(function($classname){
            return class_basename($classname);
        })->toArray();
    }

    protected function getTypeOptions()
    {
        return array_combine($this->getImportClassNameTypes(), $this->getImports()->toArray());
    }

    protected function getImportExtensions()
    {
        // Take extensions from imports
        $extensions = $this->getImports()->pluck('extensions')->flatten()->unique()->filter()->toArray();

        // Add extensions from config
        $extensions = array_merge($extensions, config('admin_helpers.importer.extensions', []));

        // Filter extension
        $extensions = array_values(array_filter(array_unique($extensions)));

        return implode(',', $extensions);
    }
}
