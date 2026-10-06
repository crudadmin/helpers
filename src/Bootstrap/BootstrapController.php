<?php

namespace AdminHelpers\Bootstrap;

use Illuminate\Routing\Controller;

class BootstrapController extends Controller
{
    /**
     * Sections of the bootstrap request selected by the app-type header.
     *
     * @return \AutoAjax\AutoAjax
     */
    public function index()
    {
        $only = $this->getRequestedSections();

        return autoAjax()->store(
            BootstrapResolver::make()->only($only)
        );
    }

    /**
     * Section names from ?only=a,b (or an array), empty for all sections.
     *
     * @return array<int, string>
     */
    protected function getRequestedSections()
    {
        $only = request('only');

        $only = is_array($only) ? $only : explode(',', (string) $only);

        return array_values(array_filter(array_map(
            fn ($section) => is_string($section) ? trim($section) : null,
            $only
        )));
    }
}
