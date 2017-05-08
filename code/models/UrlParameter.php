<?php
/**
 * Allows admins to specify the fields and values for URL parameters.
 *
 * @author Catalyst I.T. SilverStripe Team 2017 <silverstripedev@catalyst.net.nz>
 * @package niwa.metadata.postcards
 */
class UrlParameter extends DataObject
{
    /**
     * @var array
     */
    private static $db = array(
        'Value' => 'Varchar(255)',
        'SortOrder' => 'Int',
    );

    /**
     * @var array
     */
    private static $has_one = array(
        'MetadataPostcardEntryPage' => 'MetadataPostcardEntryPage',
        'PostcardMetadataField' => 'PostcardMetadataField',
    );

    /**
     * @var array
     */
    private static $summary_fields = array(
        'PostcardMetadataField.Label' => 'Field',
        'Value' => 'Value',
    );

    /**
     * @return FieldList
     */
    public function getCMSFields()
    {
        $fields = parent::getCMSFields();

        // Remove the sort order as this is hidden.
        $fields->removeByName('SortOrder');

        // Remove the Postcard metadata field dropdown and replace it with our own which must have
        // only the fields which appear on the metadata page this is linked to.
        $fields->removeByName('PostcardMetadataField');

        $fields->addFieldsToTab('Root.Main',
            array(
                NoticeMessage::create('Please select the field on the form to appear in the URL and enter the value for the parameter.'),
                DropdownField::create(
                    'PostcardMetadataFieldID',
                    'Metadata Field',
                    PostcardMetadataField::get()
                        ->filter('MetadataPostcardEntryPageID', $this->MetadataPostcardEntryPageID)
                        ->sort('SortOrder', 'Asc')
                        ->map('ID', 'Label')
                )->setEmptyString('Select')
            ),
            'Value'
        );

        return $fields;
    }
}
