<?php
/**
 * Admin for the metadata postcard module. Allows Dublin Core fields to be added or altered.
 *
 * @author Catalyst I.T. SilverStripe Team 2017 <silverstripedev@catalyst.net.nz>
 * @package niwa.metadata.postcards
 */
class MetadataPostcardAdmin extends ModelAdmin
{
    /**
     * @var string
     */
    private static $url_segment = 'metadata-postcards';

    /**
     * @var string
     */
    private static $menu_title = 'Metadata Postcard';

    /**
     * hide the importer option.
     *
     * @var bool
     */
    public $showImportForm = false;

    /**
     * @var array
     */
    private static $managed_models = array(
        'DublinCoreFieldType',
    );
}
