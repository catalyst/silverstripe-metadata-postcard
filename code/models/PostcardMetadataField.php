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
        'Label' => 'Varchar(255)',
        'HelpText' => 'Text',
        'FieldType' => 'Enum(array("TEXTBOX", "TEXTAREA", "DROPDOWN", "PLACEHOLDER", "KEYWORDS"))',
        'Readonly' => 'Boolean',
        'Required' => 'Boolean',
        'PlaceholderValue' => 'Varchar(255)',
        'KeywordsValue' => 'Varchar(255)',
        'DropdownOtherOption' => 'Boolean',
        'DropdownVocabularyUrl' => 'Varchar(255)',
        'PreventDropdownItemUpdate' => 'Boolean',
        'SortOrder' => 'Int',
    );

    /**
     * @var array
     */
    private static $has_one = array(
        'MetadataPostcardEntryPage' => 'MetadataPostcardEntryPage',
        'DublinCoreFieldType' => 'DublinCoreFieldType',
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
        'DublinCoreField' => 'DublinCoreFieldType.Label',
        'Label',
        'FieldType',
    );

    /**
     * @return FieldList
     */
    public function getCMSFields()
    {
        // Add fields needed to enter the information about the metdata field.
        $fields = FieldList::create(TabSet::create('Root'));

        $fields->addFieldsToTab('Root.Main', array(
            DropdownField::create(
                'DublinCoreFieldTypeID',
                'Dublin Core Field',
                DublinCoreFieldType::get()->sort('Label', 'Asc')->map('ID', 'Label')
            ),
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
                'PLACEHOLDER' => 'Placeholder',
                'KEYWORDS' => 'Keywords'
            ), 'TEXTBOX'),
            $dropdownOther = CheckboxField::create('DropdownOtherOption', "Dropdown has 'Other' option"),
            $dropdownVocab = TextField::create('DropdownVocabularyUrl', 'Dropdown Vocabulary Url (to XML)')
                ->setRightTitle('If specified, on save the items for this dropdown will be populated from the vocabulary. Note this can take some time, please be patient while the screen re-loads. To update the items from the vocabulary, simply save this field again.'),
            $dropdownLock = CheckboxField::create('PreventDropdownItemUpdate', 'Prevent dropdown items from being refreshed from the vocab server on save of this field (handy if you want to keep your modifications).'),
            $placeholderValue = TextField::create('PlaceholderValue')
                ->setRightTitle('Placeholder fields are not displayed to the user, but are sent to the Catalogue so require you to enter a value.'),
            $keywordsValue = TextField::create('KeywordsValue', 'Your Keywords')
        ));

        // Define the display logic for fields that don't show all the time.
        $placeholderValue->hideUnless('FieldType')->isEqualTo('PLACEHOLDER');
        $placeholderValue->validateIf('FieldType')->isEqualTo('PLACEHOLDER');
        $dropdownOther->hideUnless('FieldType')->isEqualTo('DROPDOWN');
        $dropdownVocab->hideUnless('FieldType')->isEqualTo('DROPDOWN');
        $keywordsValue->hideUnless('FieldType')->isEqualTo('KEYWORDS');

        // The label is very important if the field is not a placeholder as we need to display a label to the user
        // and the label is turned in to the name of the field when output on the form as well as being populated via URL.
        $fieldLabel->validateIf('FieldType')->isEqualTo('TEXTBOX')
                   ->orIf('FieldType')->isEqualTo('TEXTAREA')
                   ->orIf('FieldType')->isEqualTo('DROPDOWN')
                   ->orIf('FieldType')->isEqualTo('KEYWORDS');

        // Now output a gridfield for the dropdown values. When loaded from the vocabulary
        // server there can ge quite a long list so add this to its own tab.
        if ($this->ID) {
            // Only output the dropdown item tab if this field is of dropdown type.
            if ($this->FieldType == 'DROPDOWN') {
                $fields->addFieldsToTab('Root.DropdownItems', array(
                    NoticeMessage::create('You can control the order of the dropdown entries by clicking the small :: icon the left of the row and dragging it up or down. To move an item between pages, after dragging, drop it on the < or > buttons at the bottom of the gridfield.'),
                    GridField::create(
                            'DropdownEntries',
                            'Dropdown entries',
                            $this->DropdownEntries(),
                            GridFieldConfig_RecordEditor::create()
                                ->removeComponentsByType('GridFieldAddExistingAutocompleter')
                                ->addComponent(new GridFieldOrderableRows('SortOrder'))
                        )
                    )
                );

                // If there is a vocabulary message for this field then output it.
                if ($this->DropdownVocabularyUrl) {
                    $msg = LastVocabLoadMessage::get()->where(array('PostcardMetadataFieldID = ?' => $this->ID))->first();

                    if ($msg) {
                        if ($msg->Type == 'BAD') {
                            $fields->addFieldToTab('Root.Main', ErrorMessage::create(Date('d/m/Y', strtotime($msg->Date)) . ' : ' . $msg->Message));
                        } else {
                            $fields->addFieldToTab('Root.Main', NoticeMessage::create(Date('d/m/Y', strtotime($msg->Date)) . ' : ' . $msg->Message));
                        }
                    }
                }
            }
        } else {
            $fields->addFieldToTab('Root.Main',
                DisplayLogicWrapper::create(
                    NoticeMessage::create('Please save this field first, then you can enter dropdown items from the Dropdown Items tab (it will appear in the top-right of this screen).')
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
                'DublinCoreFieldTypeID',
                'Label',
                'FieldType',
                'PlaceholderValue',
            )
        );

        return $validator;
    }

    /**
     * Called before write.
     */
    protected function onBeforeWrite()
    {
        parent::onBeforeWrite();

        // Ensure there is no unwated whitespace around this.
        $this->DropdownVocabularyUrl = trim($this->DropdownVocabularyUrl);
    }

    /**
     * If the field type is a dropdown and a vocabularly url has been specified
     * then after save call a function to populate the dropdown items from the vocab.
     */
    protected function onAfterWrite()
    {
        // Call parent function.
        parent::onAfterWrite();

        // If the type of this field is dropdown and a vocabularly url has been specified, provided the user
        // has not chosen to prevent update, then update/populate the dropdown items from the vocab server.
        if ($this->FieldType == 'DROPDOWN' && $this->DropdownVocabularyUrl && !$this->PreventDropdownItemUpdate)
        {
            $errorMessage = $this->populateDropdownFromVocab();

            // If there was an error mesaaqge then set a bad message, if not then all must have been ok and set a good one.
            if ($errorMessage) {
                $this->setLastVocabMessage('BAD', $errorMessage);
            } else {
                $this->setLastVocabMessage('GOOD', 'Items successfully loaded from the vocabulary server.');
            }
        }
    }

    /**
     * Sets the last vocab message.
     *
     * @param String $status  GOOD OR BAD.
     * @param String $message The message set.
     */
    protected function setLastVocabMessage($status, $message)
    {
        $msg = LastVocabLoadMessage::get()->where(array('PostcardMetadataFieldID = ?' => $this->ID))->first();

        if (!$msg) {
            $msg = LastVocabLoadMessage::create();
            $msg->PostcardMetadataFieldID = $this->ID;
        }

        $msg->Date = Date('Y-m-d');
        $msg->Type = $status;
        $msg->Message = $message;
        $msg->Write();
    }

    /**
     * Populates the item for a dropdown. Can be called recursivley.
     *
     * @param  String  $nextUrl
     * @param  integer $sortOrder
     * @param  String $errorMessage
     */
    protected function populateDropdownFromVocab($nextUrl="", $sortOrder=1, $errorMessage="")
    {
        // This function is called recursively because the vocab data is paginated.
        // The first time the nextUrl is empty so the base url to the vocab server is used and some parameters added.
        // If in the response there is a next URL then we call this function again passing that in to load the next page of data.
        $results = null;
        $status = 0;
        $statusMessage = "";

        if ($nextUrl) {
            list($results, $status, $statusMessage) = $this->makeCurlRequest($nextUrl);
        } else {
            // Delete all current entries for this dropdown as we are going to re-populate the list from the vocab.
            $deleteResult = $this->deleteCurrentItems();

            // If there was an error deleting then throw exception as don't want to double-up the entries.
            if (!$deleteResult) {
                $errorMessage .= "Error deleting current items, can't reload. ";
            } else {
                // Add parameters to sort the results by label and get the maximum allowed number of records (50) per request.
                list($results, $status, $statusMessage) = $this->makeCurlRequest($this->DropdownVocabularyUrl . '?_sort=prefLabel&_pageSize=50');
            }
        }

        // If no error then process the output
        if ($status == 200 && $results) {
            // Parse the XML.
            $xml = "";

            try {
                $xml = new SimpleXMLElement($results);
            }
            catch (Exception $e) {
                $errorMessage .= 'The XML failed to parse. ';
            }

            if ($xml) {
                // If have some measurements.
                if (isset($xml->items)) {
                    // Loop through the items.
                    foreach($xml->items->item as $anItem) {
                        // Get the prefLabel which is the label to sell.
                        if (isset($anItem->prefLabel)) {
                            // Create a dropdown entry object, set its properties, and then write.
                            $dropdownEntry = DropdownEntry::create();
                            $dropdownEntry->PostcardMetadataFieldID = $this->ID;
                            $dropdownEntry->Key = trim($anItem->prefLabel);
                            $dropdownEntry->Label = trim($anItem->prefLabel);
                            $dropdownEntry->SortOrder = $sortOrder;
                            $dropdownEntry->write();
                            $sortOrder ++;
                        }
                    }

                    // Now check for a 'next' tag in the results, if there is then there is more than 1
                    // page of results so we need to get that link then recursivley call this function
                    if (isset($xml->next)) {
                        $errorMessage .= $this->populateDropdownFromVocab($xml->next['href'], $sortOrder, $errorMessage);
                    }
                } else {
                    $errorMessage .= "No items found in the XML so can't populate the dropdown items. ";
                }
            } else {
                $errorMessage .= 'No XML to process. ';
            }
        } else {
            $errorMessage .= 'Error getting data from the vocab server. ' . $status . ' : ' . $statusMessage . '. ';
        }

        // Ensure that the error message is
        return $errorMessage;
    }

    /**
     * Deletes all the current items for a dropdown.
     */
    protected function deleteCurrentItems()
    {
        $sql = new SQLQuery();
        $sql->setFrom('DropdownEntry');
        $sql->addWhere(array('PostcardMetadataFieldID' => $this->ID));
        $sql->setDelete(true);
        $result = $sql->execute();

        return $result;
    }

    /**
     * Actually makes the request to the vocabularly service.
     *
     * @param  String  $url
     * @return Array
     */
    protected function makeCurlRequest($url)
    {
        $output = "";
        $statusCode = 0;
        $curlError = "";

        // create curl resource
        $ch = curl_init();

        // Set url.
        curl_setopt($ch, CURLOPT_URL, $url);

        // return the transfer as a string
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

        // Execute the curl command and get the output.
        $output = curl_exec($ch);

        // Get the status code.
        $statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        // Get a curl error if there is one
        $curlError = curl_error($ch);

        // close curl resource to free up system resources
        curl_close($ch);

        // Return the output, statusCode, and curl error.
        return array($output, $statusCode, $curlError);
    }
}
