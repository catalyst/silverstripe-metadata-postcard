<?php
/**
 * Allows admins to specify the fields on the Add Metadata form of a metadata entry page.
 *
 * @author Catalyst I.T. SilverStripe Team 2017 <silverstripedev@catalyst.net.nz>
 * @package niwa.metadata.postcards
 */
class PostcardMetadataField extends DataObject
{
    /**
     * @var array
     */
    private static $db = array(
        'ApiName' => 'Varchar(255)',
        'Label' => 'Varchar(255)',
        'HelpText' => 'Text',
        'FieldType' => 'Enum(array("TEXTBOX", "TEXTAREA", "DROPDOWN", "PLACEHOLDER"))',
        'Readonly' => 'Boolean',
        'Required' => 'Boolean',
        'PlaceholderValue' => 'Varchar(255)',
        'DropdownOtherOption' => 'Boolean',
        'SortOrder' => 'Int',
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
    private static $has_many = array(
        'DropdownEntries' => 'DropdownEntry',
    );

    /**
     * @var array
     */
    private static $summary_fields = array(
        'ApiName',
        'Label',
        'FieldType',
    );

    /**
     * @return FieldList
     */
    public function getCMSFields()
    {
        // Add fields needed to enter the information about the metdata field.
        $fields = new FieldList(
            WarningMessage::create('The Api name must be set to the name of the field in the catalogue.'),
            TextField::create('ApiName'),
            $fieldLabel = TextField::create('Label')
                ->setRightTitle('The label is required for non-placeholder fields. The label name, with underscores instead of spaces, is also used to pass values to fields from the URL parameters.'),
            TextareaField::create('HelpText')
                ->setRightTitle('The help text is optional.'),
            CheckboxField::create('Readonly', 'Readonly (if populated from URL)?'),
            CheckboxField::create('Required', 'This field is required (the user must enter a value)?'),
            OptionsetField::create('FieldType', 'Field type', array(
                'TEXTBOX' => 'Text box',
                'TEXTAREA' => 'Text area',
                'DROPDOWN' => 'Dropdown',
                'PLACEHOLDER' => 'Placeholder'
            ), 'TEXTBOX'),
            $dropdownOther = CheckboxField::create('DropdownOtherOption', "Dropdown has 'Other' option"),
            $placeholderValue = TextField::create('PlaceholderValue')
                ->setRightTitle('Placeholder fields are not displayed to the user, but are sent to the Catalogue so require you to enter a value.')
        );

        // Define the display logic for fields that don't show all the time.
        $placeholderValue->hideUnless('FieldType')->isEqualTo('PLACEHOLDER');
        $placeholderValue->validateIf('FieldType')->isEqualTo('PLACEHOLDER');
        $dropdownOther->hideUnless('FieldType')->isEqualTo('DROPDOWN');

        // The label is very important if the field is not a placeholder as we need to display a label to the user
        // and the label is turned in to the name of the field when output on the form as well as being populated via URL.
        $fieldLabel->validateIf('FieldType')->isEqualTo('TEXTBOX')
                   ->orIf('FieldType')->isEqualTo('TEXTAREA')
                   ->orIf('FieldType')->isEqualTo('DROPDOWN');

        // Now output things for the dropdown values.
        if ($this->ID) {
            $fields->push(
                DisplayLogicWrapper::create(
                    NoticeMessage::create('You can control the order of the dropdown entries by clicking the small :: icon the left of the row and dragging it up or down.'),
                    GridField::create(
                        'DropdownEntries',
                        'Dropdown entries',
                        $this->DropdownEntries(),
                        GridFieldConfig::create()
                            ->addComponent(new GridFieldButtonRow('before'))
                            ->addComponent(new GridFieldToolbarHeader())
                            ->addComponent(new GridFieldTitleHeader())
                            ->addComponent(new GridFieldEditableColumns())
                            ->addComponent(new GridFieldDeleteAction())
                            ->addComponent(new GridFieldAddNewInlineButton())
                            ->addComponent(new GridFieldOrderableRows('SortOrder'))
                    )
                )->hideUnless('FieldType')->isEqualTo('DROPDOWN')->end()
            );
        } else {
            $fields->push(
                DisplayLogicWrapper::create(
                    NoticeMessage::create('Please save this field first, then you can enter dropdown items.')
                )->hideUnless('FieldType')->isEqualTo('DROPDOWN')->end()
            );
        }

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
                'ApiName',
                'Label',
                'FieldType',
                'PlaceholderValue',
            )
        );

        return $validator;
    }
}
