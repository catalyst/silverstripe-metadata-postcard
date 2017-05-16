<?php
/**
 * Simple lookup table model of dublin core fields allowing the user to select from a dropdown in the CMS.
 * This has a model admin so admins can add and edit items in the list in case they want to add extended fields etc.
 *
 * @author Catalyst I.T. SilverStripe Team 2017 <silverstripedev@catalyst.net.nz>
 * @package niwa.metadata.postcards
 */
class DublinCoreFieldType extends DataObject
{
    /**
     * @var array
     */
    private static $db = array(
        'XmlName' => 'Varchar(255)',
        'Label' => 'Varchar(255)',
    );

    /**
     * @var array
     */
    private static $summary_fields = array(
        'XmlName',
        'Label',
    );

    /**
     * @return FieldList
     */
    public function getCMSFields()
    {
        // Crete fieldlist for the XML name which must be dc:etc and the label.
        $fields = new FieldList(
            WarningMessage::create('The XML name must be set to the name of this field in the XML sent to the catalogue and include the correct prefix (for example dc:)'),
            TextField::create('XmlName'),
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
                'XmlName',
                'Label'
            )
        );

        return $validator;
    }

    /**
     * Add the default values for this.
     */
    public function requireDefaultRecords()
    {
        parent::requireDefaultRecords();

        // All dublin core field types as detailed on this web page...
        // http://www.ogcnetwork.net/node/630
        $options = array(
            'dc:title' => 'Title',
            'dc:creator' => 'Creator',
            'dc:subject' => 'Subject',
            'dc:description' => 'Description',
            'dc:publisher' => 'Publisher',
            'dc:contributor' => 'Contributor',
            'dc:modified' => 'Date',
            'dc:type' => 'Type',
            'dc:format' => 'Format',
            'dc:identifier' => 'Identifier',
            'dc:source' => 'Source',
            'dc:language' => 'Language',
            'dc:relation' => 'Relation',
            'dc:rights' => 'Rights'
        );

        // Run through and create all of them if not already existing.
        foreach ($options as $key => $value) {
            $existing = DublinCoreFieldType::get()->filter('XmlName', $key)->first();

            if (!$existing) {
                $icon = new self();
                $icon->XmlName = $key; // This field is effectively the Unique key for this table.
                $icon->Label = $value;
                $icon->write();

                DB::alteration_message(' - ' . $value . ' added.');
            } elseif ($existing->Label != $value) {
                $existing->Label = $value;
                $existing->write();

                DB::alteration_message(' - ' . $value . ' updated.');
            }
        }
    }
}
