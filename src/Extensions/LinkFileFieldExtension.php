<?php

namespace Sunnysideup\FileSearchField\Extensions;

use gorriecoe\Link\Models\Link;
use Sunnysideup\FileSearchField\Forms\FileSearchableDropdownField;
use SilverStripe\Assets\File;
use SilverStripe\Assets\Folder;
use SilverStripe\Assets\Image;
use SilverStripe\Core\ClassInfo;
use SilverStripe\Core\Extension;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\TreeDropdownField;

/**
 * Replaces the (folder-limited) file tree picker in gorriecoe Link with a
 * searchable dropdown that searches the whole file library.
 *
 * @property Link|LinkFileFieldExtension $owner
 */
class LinkFileFieldExtension extends Extension
{
    public function updateCMSFields(FieldList $fields): void
    {
        $old = $fields->dataFieldByName('FileID');
        if (! $old instanceof TreeDropdownField) {
            return;
        }

        // Files only: no images (this is a file linker) and no folders unless explicitly allowed.
        $exclude = ClassInfo::subclassesFor(Image::class);
        if (! $this->owner->config()->get('link_to_folders')) {
            $exclude = array_merge($exclude, ClassInfo::subclassesFor(Folder::class));
        }
        $list = File::get()->exclude('ClassName', array_values($exclude));

        $new = FileSearchableDropdownField::create(
            'FileID',
            $old->Title(),
            $list,
            null,
            'Title'
        )
            ->setIsLazyLoaded(true)
            ->setLazyLoadLimit(50)
            ->setIsClearable(true)
            ->setPlaceholder('Search by title or file name');

        $old->getContainerFieldList()->replaceField('FileID', $new);
    }
}
