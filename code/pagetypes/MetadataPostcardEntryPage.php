<?php
/**
 * Page for entry of metadata postcards. Contains normal page fields but also
 * the abilty to specify the add form fields, catalogue, and curator email information.
 *
 * @author Catalyst I.T. SilverStripe Team 2017 <silverstripedev@catalyst.net.nz>
 * @package niwa.metadata.postcards
 */
class MetadataPostcardEntryPage extends Page
{
    /**
     * @var string
     */
    private static $description = 'Allows submission of metadata to a catalogue.';

    /**
     * @var array
     */
    private static $db = array(
        'CataloguePushUrl' => 'Varchar(255)',
        'CatalogueViewUrl' => 'Varchar(255)',
        'CatalogueUsername' => 'Varchar(255)',
        'CataloguePassword' => 'Varchar(255)',
        'FromEmailAddress' => 'Varchar(255)',
        'CuratorEmailSubject' => 'Varchar(255)',
        'CuratorEmailBody' => 'Text',
        'CoordinatorEmailSubject' => 'Varchar(255)',
        'CoordinatorEmailBody' => 'Text',
        'PushSuccessMessage' => 'Text',
        'PushFailureMessage' => 'Text',
        'HelpBoxTitle' => 'Varchar(255)',
        'HelpBoxMessage' => 'HTMLText',
        'ProjectNumber' => 'Varchar(255)',          // These fields are only used for the URL builder
        'ProjectManager' => 'Varchar(255)',         // and are not fields on the form so need a value captured somewhere.
        'ProjectCoordinator' => 'Varchar(255)',
        'ProjectCoordinatorEmail' => 'Varchar(255)',
    );

    /**
     * @var array
     */
    private static $has_many = array(
        'Fields' => 'PostcardMetadataField',
        'Curators' => 'CatalogueCurator',
        'UrlParams' => 'UrlParameter',
    );

    /**
     * @return FieldList
     */
    public function getCMSFields()
    {
        $fields = parent::getCMSFields();

        // Remove a whole lot of tabs we don't need for this page type as we add a whole lot of our
        // own so the list gets very long making things more complex for users.
        $fields->removeByName('Translations');
        $fields->removeByName('PublishingSchedule');
        $fields->removeByName('Widgets');
        $fields->removeByName('RelatedPages');
        $fields->removeByName('Tags');

        // Add a tab for the help box displayed on the right of the page.
        $fields->addFieldsToTab(
            'Root.HelpBox',
            array(
                LiteralField::create('HelpBoxInstructions', '<p><strong>If you would like a box displayed to the top right of the page with help information, then please fill out the fields below. The curator email address(es) will automatically be output after the message.</strong></p>'),
                TextField::create('HelpBoxTitle'),
                HtmlEditorField::create('HelpBoxMessage')
            )
        );

        // Add tab for catalogue information such as the url, username and password.
        // Text field is used for the password to overcome issues with Chrome's very agreesive autofill
        // which can populate the values of the username and password field to that of the logged in admin
        // and this then getting saved without their knowing and the comms to the Catalogue being broken.
        //++ @TODO revisit this decision as may want the password hidden, what if undesired login to CMS???
        $fields->addFieldsToTab(
            'Root.Catalogue',
            array(
                TextField::create('CataloguePushUrl')
                    ->setRightTitle('URL that will allow pushing of records in to the catalogue using a CSW transaction.'),
                TextField::create('CatalogueViewUrl')
                    ->setRightTitle('URL to view records in a catalogue. The identifier of new records will be added to the end.'),
                TextField::create('CatalogueUsername'),
                TextField::create('CataloguePassword'),        // Decided to use textfield to prevent issues with agressive Chome autofill.
                EmailField::create('FromEmailAddress')
                    ->setRightTitle('This is the address that email messages from this system appear to be from. It should include the domain of this website.'),
                NoticeMessage::create('Special variable in the success message is {LINK} which will display a link to the newly created record in the catalogue on the screen.'),
                TextAreaField::create('PushSuccessMessage'),
                NoticeMessage::create('Special variable in the failure message is {ERROR} which will display the technical error message on screen.'),
                TextAreaField::create('PushFailureMessage')
            )
        );

        $fields->addFieldsToTab(
            'Root.ProjectCoordinator',
            array(
                LiteralField::create('CoordinatorNote', "<p><strong>One email is sent to the project coordinator after the first record in a user's session has been pushed to the catalogue, provided that Project_Coordinator, Project_Coordinator_email, Project_Manager, and Project_Number parameters are sent in the URL to this page. If any of these parameters are not provided no email will be sent to the project coordinator.</strong></p>"),
                NoticeMessage::create("Special variable is {PROJECT_NUMBER} which will be replaced with the project number when the email is sent."),
                TextField::create('CoordinatorEmailSubject'),
                NoticeMessage::create("Special variables are {PROJECT_COORDINATOR} (name), {PROJECT_MANAGER} (name), and {PROJECT_NUMBER} which will be replaced when the email is sent."),
                TextAreaField::create('CoordinatorEmailBody')->setRows(10)
            )
        );

        // Next a tab for the curator information
        $fields->addFieldsToTab(
            'Root.Curators',
            array(
                LiteralField::create('CuratorNote', "<p><strong>An email is sent to the curators listed below every time a record is pushed to the catalogue.</strong></p>"),
                TextField::create('CuratorEmailSubject'),
                NoticeMessage::create("Special variables are {NAME} which will be replaced with the curator's name and {LINK} which will be replaced with a link to the new entry in the catalogue when the email is sent."),
                TextAreaField::create('CuratorEmailBody')->setRows(10),
                GridField::create(
                    'Curators',
                    'Catalogue Curators',
                    $this->Curators(),
                    GridFieldConfig::create()
                        ->addComponent(new GridFieldButtonRow('before'))
                        ->addComponent(new GridFieldToolbarHeader())
                        ->addComponent(new GridFieldTitleHeader())
                        ->addComponent(new GridFieldEditableColumns())
                        ->addComponent(new GridFieldDeleteAction())
                        ->addComponent(new GridFieldAddNewInlineButton())
                )
            )
        );

        // Add the gridfield for the fields which are to appear on the form to thir own tab
        // allow the girdfield to be sortable so that the order here can be used to order the items.
        $fields->addFieldsToTab(
            'Root.MetadataFields',
            array(
                NoticeMessage::create('You can control the order of the fields by clicking the small :: icon the left of the row and dragging it up or down.'),
                GridField::create(
                    'Fields',
                    'Form fields',
                    $this->Fields(),
                    GridFieldConfig_RecordEditor::create()
                        ->removeComponentsByType('GridFieldAddExistingAutocompleter')
                        ->addComponent(new GridFieldOrderableRows('SortOrder'))
                )
            )
        );

        // Add another tab to assist a CMS user with knowing the correct URL to the page with field parameters.
        // After the creation of the form fields the user can select which fields are to have parameters sent via the URL
        // and specify the value, then when this tab is saved the URL encoded link to the form is displayed.
        $urlWithParams = $this->calculateUrlWithParams();

        $fields->addFieldsToTab(
            'Root.PageUrlParameters',
            array(
                LiteralField::create('UrlInstructions', "<p><strong>Only use this tab after for have specified the fields on the form. The purpose of this tab is to help you create a parameterised url to the page.</strong></p>"),
                NoticeMessage::create('1) Because these parameters are not fields on the form, please enter their URL parameter values here.'),
                TextField::create('ProjectNumber'),
                TextField::create('ProjectManager'),
                TextField::create('ProjectCoordinator'),
                TextField::create('ProjectCoordinatorEmail'),
                NoticeMessage::create('2) Next use this gridfield below to add other form fields you want to have parameters in the URL.'),
                GridField::create(
                    'UrlParams',
                    'Parameters',
                    $this->UrlParams(),
                    GridFieldConfig_RecordEditor::create()
                        ->removeComponentsByType('GridFieldAddExistingAutocompleter')
                        ->addComponent(new GridFieldOrderableRows('SortOrder'))
                ),
                NoticeMessage::create('3) The correct URL to the page with field parameters will be displayed below once the information above has been entered and saved.'),
                LiteralField::create('PageUrl', "<input type='text' id='pageUrl' style='width:99%;padding:5px;' readonly value='" . $urlWithParams . "'><br /><br />"),
                LiteralField::create('CopyButton', "<input type='button' id='copyButton' value='Copy URL to clipboard'>
                <script>document.querySelector('#copyButton').onclick = function() {
                        document.querySelector('#pageUrl').select();
                        document.execCommand('copy');
                        document.querySelector('#pageUrl').blur();
                        alert('The URL has been copied to the clipboard, use Ctl-V (or Cmd-V on Mac) to paste it where desired.');
                    };
                </script>&nbsp;&nbsp;&nbsp;&nbsp;"),
                LiteralField::create('TestLink', "<a href='$urlWithParams' target='_blank'>Test the URL</a>"),
            )
        );

        return $fields;
    }

    /**
     * Calculates the URL to the page containing parameters for the form fields which have been specified as
     * needing values passed via the URL. This helps when the CMS user wants to copy and paste the URL in to
     * an email to others, or display on the screen etc. Often a 3rd party system should create the paramertised URL.
     *
     * @return String
     */
    protected function calculateUrlWithParams()
    {
        // Get the absolute URL to the page including the site domain.
        // Get the Url parameter records for this page.
        // Also include the project coordinator field values in the URL parameters.
        $pageUrl = $this->AbsoluteLink();

        $parameters = "";

        // If values for the project number, Project Manager, Project Coordinator, or Project Coordinator
        // email fields have been specified include them as parameters in the URL.
        if ($this->ProjectNumber) {
            $parameters .= '&Project_Number=' . urlencode($this->ProjectNumber);
        }

        if ($this->ProjectManager) {
            $parameters .= '&Project_Manager=' . urlencode($this->ProjectManager);
        }

        if ($this->ProjectCoordinator) {
            $parameters .= '&Project_Coordinator=' . urlencode($this->ProjectCoordinator);
        }

        if ($this->ProjectCoordinatorEmail) {
            $parameters .= '&Project_Coordinator_email=' . urlencode($this->ProjectCoordinatorEmail);
        }

        // Get the URL parameters specified for this page. Loop and add them.
        $parameterFields = $this->UrlParams()->sort('SortOrder', 'Asc');

        foreach($parameterFields as $field) {
            $parameters .= '&' . str_replace(' ', '_', $field->PostcardMetadataField()->Label) . '=' . urlencode($field->Value);
        }

        // Remove first & and replace with ?.
        $parameters = ltrim($parameters, '&');

        // Put together the URL of the page with the params.
        $pageUrl .= '?' . $parameters;

        return $pageUrl;
    }

    /**
     * Ensure that the catalogue URL parts have any whitespace around them stripped to avoid
     * weird and unhelpful error that comes back from catalogue if there is a space in it.
     */
    protected function onBeforeWrite()
    {
        parent::onBeforeWrite();

        $this->CataloguePushUrl = trim($this->CataloguePushUrl);
        $this->CatalogueViewUrl = trim($this->CatalogueViewUrl);

        // Also ensure that the view URL has a / on the end.
        $this->CatalogueViewUrl = rtrim($this->CatalogueViewUrl, '/') . '/';
    }

    /**
     * @return ZenValidator
     */
    public function getCMSValidator()
    {
        $validator = ZenValidator::create();

        $validator->addRequiredFields(
            array(
                'CataloguePushUrl',
                'CatalogueViewUrl',
                'FromEmailAddress',
            )
        );

        return $validator;
    }
}


/**
 * Page controller class.
 */
class MetadataPostcardEntryPage_Controller extends Page_Controller
{
    /**
     * @var array
     */
    private static $allowed_actions = array(
        'MetadataEntryForm',
    );

    /**
     * This function creates the metdata entry form by getting the fields defined
     * for the page, in their sort order, and creating form fields for them.
     *
     * @return Form
     */
    public function MetadataEntryForm()
    {
        $params = $this->getRequest()->getVars();

        // Check in the parameters sent to this page if there are certian fields needed to power the
        // functionality which emails project coordinators and if so get them as we will need to add
        // them to the bottom of the form as hidden fields, this way we can access them in the form
        // processing and send the email.
        $hiddenFields = array();

        if (!empty($params['Project_Coordinator'])) {
            $hiddenFields['_Project_Coordinator'] = $params['Project_Coordinator'];
        }

        if (!empty($params['Project_Coordinator_email'])) {
            $hiddenFields['_Project_Coordinator_email'] = $params['Project_Coordinator_email'];
        }

        if (!empty($params['Project_Manager'])) {
            $hiddenFields['_Project_Manager'] = $params['Project_Manager'];
        }

        if (!empty($params['Project_Number'])) {
            $hiddenFields['_Project_Number'] = $params['Project_Number'];
        }

        // Get the fields defined for this page, exclude the placeholder fields as they are not displayed to the user.
        $metadataFields = $this->Fields()->where("FieldType != 'PLACEHOLDER'")->Sort('SortOrder', 'asc');

        // Create fieldfield for the form fields.
        $formFields = FieldList::create();
        $actions = FieldList::create();
        $requiredFields = array();

        // Push the required fields message as a literal field at the top.
        $formFields->push(
            LiteralField::create('required', '<p>* Required fields</p>')
        );

        if ($metadataFields->count()) {
            foreach($metadataFields as $field) {
                // Create a version of the label with spaces replaced with underscores as that is how
                // any paraemters in the URL will come (plus we can use it for the field name)
                $fieldName = str_replace(' ', '_', $field->Label);
                $fieldLabel = $field->Label;

                // If the field is required then add it to the required fields and also add an
                // asterix to the end of the field label.
                if ($field->Required) {
                    $requiredFields[] = $fieldName;
                    $fieldLabel .= ' *';
                }

                // Check if there is a parameter in the GET vars with the corresponding name.
                $fieldValue = null;

                if (isset($params[$fieldName])) {
                    $fieldValue = $params[$fieldName];
                }

                // Define a var for the new field, means no matter the type created
                // later on in the code we can apply common things like the value.
                $newField = null;

                // Single line text field creation.
                if ($field->FieldType == 'TEXTBOX') {
                    $formFields->push(
                        $newField = TextField::create($fieldName, $fieldLabel)
                    );
                } else if ($field->FieldType == 'TEXTAREA') {
                    $formFields->push(
                        $newField = TextareaField::create($fieldName, $fieldLabel)
                    );
                } else if ($field->FieldType == 'DROPDOWN') {
                    // Some dropdowns have an 'other' option so must add the 'other' to the entries
                    // and also have a conditionally displayed text field for when other is chosen.
                    $entries = $field->DropdownEntries()->sort('SortOrder', 'Asc')->map('Key', 'Label');

                    if ($field->DropdownOtherOption == true) {
                        $entries->push('other', 'Other');
                    }

                    $formFields->push(
                        $newField = DropdownField::create(
                            $fieldName,
                            $fieldLabel,
                            $entries
                        )->setEmptyString('Select')
                    );

                    if ($field->DropdownOtherOption == true) {
                        $formFields->push(
                            TextField::create(
                                "${fieldName}_other",
                                "Please specify the 'other'"
                            )->hideUnless($fieldName)->isEqualTo("other")->end()
                        );

                        //++ @TODO
                        // Ideally if the dropdown is required then if other is selected the other field
                        // should also be required. Unfortunatley the conditional validation logic of ZEN
                        // does not work in the front end - so need to figure out how to do this.
                    }
                }

                // If a new field was created then set some things on it which are common no matter the type.
                if ($newField) {
                    // Set help text for the field if defined.
                    if (!empty($field->HelpText)) {
                        $newField->setRightTitle($field->HelpText);
                    }

                    // Field must only be made readonly if the admin specified that they should be
                    // provided that a value was specified in the URL for it.
                    if ($field->Readonly && $fieldValue) {
                        $newField->setReadonly(true);
                    }

                    // Set the value of the field one was plucked from the URL params.
                    if ($fieldValue) {
                        $newField->setValue($fieldValue);
                    }
                }
            }

            // Add fields to the bottom of the form for the user to include a message in the email sent to curators
            // this is entirely optional and will not be used in most cases so is hidden until a checkbox is ticked.
            $formFields->push(
                CheckboxField::create('AdditionalMessage', 'Include a message from me to the curators')
            );

            // For the email address, because its project managers filling out the form, check if the Project_Manager_email
            // has been specified in the URL params and if so pre-populate the field with that value.
            $formFields->push(
                $emailField = EmailField::create('AdditionalMessageEmail', 'My email address')
                    ->setRightTitle('Please enter your email address so the curator knows who the message below is from.')
                    ->hideUnless('AdditionalMessage')->isChecked()->end()
            );

            if (isset($params['Project_Manager_email'])) {
                $emailField->setValue($params['Project_Manager_email']);
            }

            $formFields->push(
                TextareaField::create('AdditionalMessageText', 'My message')
                    ->setRightTitle('You can enter a message here which is appended to the email sent the curator after the record has successfully been pushed to the catalogue.')
                    ->hideUnless('AdditionalMessage')->isChecked()->end()
            );

            // If there are any hidden fields then loop though and add them as hidden fields to the bottom of the form.
            if ($hiddenFields) {
                foreach($hiddenFields as $key => $val) {
                    $formFields->push(
                        HiddenField::create($key, '', $val)
                    );
                }
            }

            // We have at least one field so set the action for the form to submit the entry to the catalogue.
            $actions = FieldList::create(FormAction::create('sendMetadataForm', 'Send'));
        } else {
            $formFields->push(
                ErrorMessage::create('No metadata entry fields have been specified for this page.')
            );
        }

        // Set up the required fields validation.
        $validator = ZenValidator::create();
        $validator->addRequiredFields($requiredFields);

        // Create form.
        $form = Form::create($this, 'MetadataEntryForm', $formFields, $actions, $validator);

        // Check if the data for the form has been saved in the session, if so then populate
        // the form with this data, if not then just return the default form.
        $data = Session::get("FormData.{$form->getName()}.data");

        return $data ? $form->loadDataFrom($data) : $form;
    }

    /**
     * Processes submits of the metadata entry form.
     * @param  Array $data
     * @param  Form $form
     */
    public function sendMetadataForm($data, $form)
    {
        // Specify array for the params to send to the API.
        $apiParams = array();

        // Loop through the defined fields grabbing the POSTed data for it, or if a placeholder
        // then just adding it and the specified value to the apiParams.
        foreach($this->Fields() as $field) {
            // Like when the fields are looped through to create the form, str_replace the label to create the fieldname.
            $fieldName = str_replace(' ', '_', $field->Label);

            // If the field type is not a placeholder then get the data POSTed for it.
            if ($field->FieldType != 'PLACEHOLDER') {
                if (isset($data[$fieldName])) {
                    // Check if the field is a dropdown and the 'other' option has been checked for it
                    // if so then if the value selected is 'other' we need to grab the value out of the _other
                    // field for it and pop that in the API params instead of the value selected in the dropdown.
                    if ($field->DropdownOtherOption && $data[$fieldName] == 'other') {
                        if (isset($data["${fieldName}_other"])) {
                            $apiParams[$field->XmlName] = trim($data["${fieldName}_other"]);
                        } else {
                            $apiParams[$field->XmlName] = trim($data[$fieldName]);
                        }
                    } else {
                        $apiParams[$field->XmlName] = trim($data[$fieldName]);
                    }
                }
            } else {
                // If it is placeholder then add it along with the admin defined placeholder value to the API params.
                $apiParams[$field->XmlName] = $field->PlaceholderValue;
            }
        }

        // Create an object of the MetadataPushAPI type sending the needed info to the constructor and then call the
        // execute command passing the apiParams. We need to try/catch it as it will throw and exception if there is
        // an issue or return the identifer of the newly created record if successful.
        $api = new MetadataPushApi($this->CataloguePushUrl, $this->CatalogueUsername, $this->CataloguePassword);
        $apiError = false;
        $apiErrorMessage = "";
        $newRecordId = "";

        try {
            $newRecordId = $api->execute($apiParams);
        }

        catch (Exception $exception) {
            $apiError = true;
            $apiErrorMessage = $exception->getMessage();
        }

        // If the push was not successful, then we need to retain the user's input on the form i.e. re-display it with all fields re-populated.
        if ($apiError) {
            // Remember the user's data, this will cause the form to re-populate.
            Session::set("FormData.{$form->getName()}.data", $data);

            // Get the failure error message defined in the CMS and replace the speical {ERROR} variable with
            // the error message from the Catalogue API or HTML error code message.
            $errorMessage = nl2br(str_replace('{ERROR}', $apiErrorMessage, $this->PushFailureMessage));

            // Populate the error message to display on the form when it is re-loaded.
            $form->sessionMessage($errorMessage, 'bad', false);

            // Return the user back to the form page they just posted.
            return $this->redirectBack();
        } else {
            // No error, build link to view the new record which is displayed on the page and also included in the email.
            $newRecordLink = $this->CatalogueViewUrl . $newRecordId;

            // See if the AdditionalMessage checkbox is ticked, if so then get the value of the email user address
            // and User email message fields as this needs to be included in the email to the curator.
            $additionalMessageEmail = null;
            $additionalMessageText = null;

            if (!empty($data['AdditionalMessage'])) {
                $additionalMessageEmail = $data['AdditionalMessageEmail'];
                $additionalMessageText = $data['AdditionalMessageText'];
            }

            // Email the curators.
            foreach($this->Curators() as $curator) {
                $this->EmailCurator($curator->Name, $curator->Email, $this->CuratorEmailSubject, $this->CuratorEmailBody, $newRecordLink, $additionalMessageEmail, $additionalMessageText);
            }

            // Ensure that any form data in the session is cleared.
            Session::clear("FormData.{$form->getName()}.data");

            // Get the success message replacing the link placeholder with a link to the newly created record.
            $successMessage = nl2br(str_replace('{LINK}', "<a href='" . $newRecordLink . "' target='_blank'>" . $newRecordLink . "</a>", $this->PushSuccessMessage));

            // Set the message in the session to this.
            $form->sessionMessage($successMessage, 'good', false);

            // Also add the link to the newly created record to an array in the session so we can display these on the form.
            $createdRecords = Session::get('PostcardRecordsCreated');

            // If there are no records already in the current session then call function to check for the hidden fields on
            // the form which provide the information required to email a project coordinator, and if they exist sent an email.
            if (count($createdRecords) == 0) {
                $this->EmailCoordinator($data);
            }

            // Add the new reocrd to the created records array and then set back in the session.
            $createdRecords[] = $newRecordLink;
            Session::set('PostcardRecordsCreated', $createdRecords);

            // Return the user back to the form. The redirect back will take the user back to the form
            // with the original URL parameters which means the fields with parameters on the URL will be
            // populated again like they want.
            return $this->redirectBack();
        }
    }

    /**
     * Emails a single curator that there has been a new entry in a catalogue.
     * @param String $name          Name of the curator, used in the email.
     * @param String $emailAddress  Email address to send the message to.
     * @param String $subject       The subject of the email.
     * @param String $body          The body of the emal.
     * @param String $newRecordLink URL ID of the newly created record in the Catalogue.
     * @param String $additionalMessageEmail Email entered on the form of who the message is from.
     * @param String $additionalMessageText Message entered by the user on the form.
     */
    protected function EmailCurator($name, $emailAddress, $subject, $body, $newRecordLink, $additionalMessageEmail, $additionalMessageText)
    {
        if ($name && $emailAddress && $subject && $body && $newRecordLink) {
            // Check for special variables of {NAME} and {LINK} in the body and replace with
            // the name of the curator and the postcardID link.
            $body = str_replace(array('{NAME}', '{LINK}'), array($name, $newRecordLink), $body);

            // If the user wants to include an additional message to the curator then add this in after the main body.
            if ($additionalMessageEmail || $additionalMessageText) {
                // Alter the subject to include plus message from {email}
                // Add the email and message to the body.
                $subject .= " PLUS message from " . $additionalMessageEmail;
                $body .= "\n\n----------------------------------------------------\n" .
                         "Message from " . $additionalMessageEmail . "...\n\n" .
                         $additionalMessageText;
            }

            $email = new Email(
                $this->FromEmailAddress,
                $emailAddress,
                $subject,
                $body
            );

            // Just send plain email, no need for a fancy template.
            $email->sendPlain();
        }
    }

    /**
     * Checks if hidden fields exist on the form which contain information to email a project co-ordinator.
     * If so then it gets the coordinator subject and body, parses special variables and then sends the email.
     *
     * @param Array $data the posted form data.
     */
    protected function EmailCoordinator($data)
    {
        $projectNumber = !empty($data['_Project_Number']) ? $data['_Project_Number'] : '';
        $projectCoordinator = !empty($data['_Project_Coordinator']) ? $data['_Project_Coordinator'] : '';
        $projectCoordinatorEmail = !empty($data['_Project_Coordinator_email']) ? $data['_Project_Coordinator_email'] : '';
        $projectManager = !empty($data['_Project_Manager']) ? $data['_Project_Manager'] : '';

        // Ensure have all these parameters, if not then we cannot send an email. This if fine as some organisations may not want to do this.
        if ($projectNumber && $projectCoordinator && $projectCoordinatorEmail && $projectManager) {
            // Parse special variables in the email subject and body.
            $subject = str_replace('{PROJECT_NUMBER}', $projectNumber, $this->CoordinatorEmailSubject);
            $body = str_replace(
                    array('{PROJECT_NUMBER}', '{PROJECT_COORDINATOR}', '{PROJECT_MANAGER}'),
                    array($projectNumber, $projectCoordinator, $projectManager),
                    $this->CoordinatorEmailBody
            );

            // Send the email.
            $email = new Email(
                $this->FromEmailAddress,
                $projectCoordinatorEmail,
                $subject,
                $body
            );

            // Just send plain email, no need for a fancy template.
            $email->sendPlain();
        }
    }

    /**
     * Returns a list of previously created metadata postcard records from the session
     * these are output on the page so the user can see a history of records they have added this session.
     *
     * @return ArrayList
     */
    public function PreviouslyCreatedRecords()
    {
        $createdRecords = new ArrayList();
        $records = Session::get('PostcardRecordsCreated');

        if ($records) {
            foreach($records as $record) {
                $createdRecords->push(ArrayData::create(
                    array('Link' => $record)
                ));
            }
        }

        return $createdRecords;
    }
}
