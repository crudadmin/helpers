<?php

namespace AdminHelpers\Importer\Concerns;

use Exception;

trait HasImportSupport
{
    /**
     * Booted importer instance
     *
     * @var mixed
     */
    protected $importer;

    /**
     * Returns all imports
     *
     * @return void
     */
    public function getImports()
    {
        return collect(config('admin_helpers.importer.imports'));
    }

    /**
     * Finds import for given row type
     *
     * @return void
     */
    public function getImporter()
    {
        if ( $this->importer ){
            return $this->importer;
        }

        $class = $this->getImporterOptions()['class'] ?? null;

        if ( !$class ){
            throw new Exception(_('Nebol nájdený žiaden importný systém pre tento typ importu.'));
        }

        return $this->importer = new $class($this);
    }

    /**
     * @DEPRECATED
     * Use getImporter() instead
     *
     * @return void
     */
    public function loadImporter()
    {
        return $this->getImporter();
    }

    /**
     * Finds import options for given import type
     *
     * @return void
     */
    public function getImporterOptions($type = null)
    {
        $type = $type ?: $this->type;

        return $this->getImports()->first(fn($import) => strpos($import['class'], $type) !== false);
    }

    /**
     * Returns all import class names
     *
     * @return void
     */
    protected function getImportClassNameTypes()
    {
        return $this->getImports()->pluck('class')->map(function($classname){
            return class_basename($classname);
        })->toArray();
    }

    /**
     * Returns all import class names as options
     *
     * @return void
     */
    protected function getTypeOptions()
    {
        return array_combine($this->getImportClassNameTypes(), $this->getImports()->toArray());
    }

    /**
     * Returns all import extensions
     *
     * @return void
     */
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

    /**
     * Determine if import can be performed
     *
     * @param  mixed $update
     * @return void
     */
    public function canImport($update = false)
    {
        // No file attached, cannot import.
        if ( !$this->file ) {
            return false;
        }

        $importer = $this->getImporterOptions();

        if ( ($importer['autoimport'] ?? true) === false){
            return false;
        }

        if ( $update === true ) {
            return $this->canReimport();
        }

        // Allow import only that states during row creation.
        return in_array($this->state ?: '', ['new', 'ready', '']);
    }

    /**
     * Determine if import can be reimported
     *
     * @return void
     */
    public function canReimport()
    {
        return true;
    }

    /**
     * Determine if import can be processed
     *
     * @return void
     */
    public function canProcess()
    {
        return in_array($this->state, ['ready', 'error', $this->canReimport() ? 'completed' : '']);
    }
}
