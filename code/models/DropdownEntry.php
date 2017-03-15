<?php
/**
 * Class to contain entries in dropdowns used on the forms in projects.
 *
 * @author Catalyst I.T. SilverStripe Team 2017 <silverstripedev@catalyst.net.nz>
 * @package niwa.metadata.postcards
 */
class DropdownEntry extends DataObject
{
    /**
     * @var array
     */
    private static $db = array(
        'Key' => 'Varchar(255)',
        'Label' => 'Varchar(255)',
        'SortOrder' => 'Int',
    );

    /**
     * @var array
     */
    private static $has_one = array(
        'PostcardMetadataField' => 'PostcardMetadataField',
    );

    /**
     * @var array
     */
    private static $summary_fields = array(
        'Key',
        'Label'
    );

    /**
     * Fields displayed when adding/editing a record in the CMS.
     *
     * @return FieldList
     */
    public function getCMSFields()
    {
        $fields = new FieldList(
            TextField::create('Key'),
            TextField::create('Label')
        );

        return $fields;
    }

    /**
     * @return ZenValidator
     */
    public function getCMSValidator()
    {
        $validator = ZenValidator::create();

        $validator->addRequiredFields(
            array(
                'Key',
                'Label',
            )
        );

        return $validator;
    }
}
