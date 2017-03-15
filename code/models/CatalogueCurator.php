<?php
/**
 * Class to contain information about Catalogue curators
 *
 * @author Catalyst I.T. SilverStripe Team 2017 <silverstripedev@catalyst.net.nz>
 * @package niwa.metadata.postcards
 */
class CatalogueCurator extends DataObject
{
    /**
     * @var array
     */
    private static $db = array(
        'Name' => 'Varchar(255)',
        'Email' => 'Varchar(255)',
    );

    /**
     * @var array
     */
    private static $has_one = array(
        'MetadataPostcardEntryPage' => 'MetadataPostcardEntryPage',
    );

    /**
     * @var array
     */
    private static $summary_fields = array(
        'Name',
        'Email',
    );

    /**
     * Fields displayed when adding/editing a record in the CMS.
     *
     * @return FieldList
     */
    public function getCMSFields()
    {
        $fields = new FieldList(
            TextField::create('Name'),
            EmailField::create('Email')
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
                'Name',
                'Email',
            )
        );

        return $validator;
    }
}
