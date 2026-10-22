<?php

namespace Sunnysideup\FileSearchField\Forms;

use Override;
use SilverStripe\Assets\File;
use SilverStripe\Control\HTTPRequest;
use SilverStripe\Control\HTTPResponse;
use SilverStripe\Forms\SearchableDropdownField;
use SilverStripe\Security\SecurityToken;

/**
 * Searchable dropdown for files that searches Title, Name and Filename (incl. folder path)
 * across the whole file library, regardless of folder.
 */
class FileSearchableDropdownField extends SearchableDropdownField
{
    // Required: search() is overridden here, so allowed_actions is checked on this class.
    private static array $allowed_actions = [
        'search',
    ];

    /**
     * Keep the CSS class of the parent field so admin JS/CSS still recognises it.
     */
    #[Override]
    public function Type()
    {
        return 'searchabledropdown';
    }

    #[Override]
    public function search(HTTPRequest $request): HTTPResponse
    {
        $response = HTTPResponse::create();
        $response->addHeader('Content-Type', 'application/json');
        if (!SecurityToken::singleton()->checkRequest($request)) {
            $response->setStatusCode(400);
            $response->setBody(json_encode(['message' => 'Invalid CSRF token']));
            return $response;
        }

        $term = trim((string) $request->getVar('term'));
        $list = $this->getSourceList();
        $options = [];
        if ($list && $term !== '') {
            // "child and adolescent" should also match "child-and-adolescent.pdf"
            $variants = array_unique([$term, str_replace(' ', '-', $term), str_replace(' ', '_', $term)]);
            $variants = array_values($variants);
            $filters = [
                'Title:PartialMatch' => $variants,
                'Name:PartialMatch' => $variants,
                'Filename:PartialMatch' => $variants,
            ];
            $matches = $list->filterAny($filters)->sort('Title')->limit($this->getLazyLoadLimit());
            foreach ($matches as $item) {
                $options[] = [
                    'value' => $item->ID,
                    'label' => $this->labelFor($item),
                ];
            }
        }

        $response->setBody(json_encode($options));
        return $response;
    }

    /**
     * "Title (Folder/file-name.pdf)" so same-titled files in different folders can be told apart.
     */
    protected function labelFor(File $file): string
    {
        $title = (string) $file->Title;
        $filename = (string) $file->Filename;
        if ($title === '') {
            return $filename;
        }
        return $filename !== '' && $filename !== $title ? sprintf('%s (%s)', $title, $filename) : $title;
    }
}
