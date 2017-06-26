<?php
/**
 * Page to browse and search records in a geocatalogue.
 *
 * @author Catalyst I.T. SilverStripe Team 2017 <silverstripedev@catalyst.net.nz>
 * @package niwa.metadata.postcards
 */
class BrowseCataloguePage extends Page
{
    /**
     * @var string
     */
    private static $description = 'Allows users to browse data in a catalogue.';

    /**
     * @var array
     */
    private static $db = array(
        'CatalogueUrl' => 'Varchar(255)',
        'HelpBoxTitle' => 'Varchar(255)',
        'HelpBoxMessage' => 'HTMLText',
        'AddBoxTitle' => 'Varchar(255)',
        'AddBoxMessage' => 'HTMLText',
    );

    /**
     * @var array
     */
    private static $has_one = array(
        'AddCataloguePage' => 'SiteTree',
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

        // Add field to link to the catalogue.
        $fields->addFieldToTab(
            'Root.Catalogue',
            TextField::create('CatalogueUrl')->setRightTitle('This must be to the CSW API endpoint for the catalogue which usually means having /srv/en/csw/srv/en/csw on the end.')
        );

        // Add a tab for the boxes displayed on the right of the page. Help and add page.
        $fields->addFieldsToTab(
            'Root.HelpAndAdd',
            array(
                LiteralField::create('HelpBoxInstructions', '<p><strong>HELP: If you would like a box displayed to the right of the page with help information, then please fill out the fields below.</strong></p>'),
                TextField::create('HelpBoxTitle'),
                HtmlEditorField::create('HelpBoxMessage')->setRows(5),
                // Now the add box..
                LiteralField::create('AddBoxInstructions', '<p><strong>ADD: If you would like a box displayed to the right of the page with a link to the add metadata page, then please fill out the fields below including which page to link to.</strong></p>'),
                TreeDropdownField::create('AddCataloguePageID', 'Add Metadata page', 'SiteTree'),
                TextField::create('AddBoxTitle'),
                HtmlEditorField::create('AddBoxMessage')->setRows(5)
            )
        );

        return $fields;
    }

    /**
     * Ensure that the catalogue URL is trimmed of any whitespace and does not
     * have a slash on the end.
     */
    protected function onBeforeWrite()
    {
        parent::onBeforeWrite();

        if (($url = trim($this->CatalogueUrl)) != '') {
            $this->CatalogueUrl = rtrim($url, '/');
        }
    }
}

class BrowseCataloguePage_Controller extends Page_Controller
{
    protected $SearchKeyword;
    protected $RecordsPerPage = 10;
    protected $PageNumber = 1;
    protected $ErrorMessage = "";

    private static $allowed_actions = array('details', 'xml');

    /**
     * Ensure that the CSS we need is included.
     */
    public function init()
    {
        parent::init();
        Requirements::css('metadata-postcard-entry/css/metadata-global.css');
        Requirements::css('metadata-postcard-entry/css/browsepage.css');
    }

    /**
     * The index function takes care of getting the parameters and doing the search.
     *
     * @param  SS_HTTPRequest $request
     * @return Array
     */
    public function index(SS_HTTPRequest $request)
    {
        $data = array();

        // Get the search keyword and also check for page number.
        $this->SearchKeyword = $request->getVar('searchKeyword');
        $page = $request->getVar('page');

        // Check if it contains a value and also is an int.
        if (!empty($page) && is_numeric($page)) {
            $this->PageNumber = $request->getVar('page');
        }

        // If the catalogue url has been specified then call function to search/browse the catalogue.
        if ($this->CatalogueUrl) {
            // Call the function to get the records.
            list($mdArray, $pagination) = $this->searchCatalogue();

            if (!$this->ErrorMessage) {
                // Check to see if actually any records to display as there might not be any matches for what the user search for.
                if ($pagination['totalPages'] > 0) {
                    // Populate the array with information.
                    $data['records'] = new ArrayList($mdArray);
                    $data['pagination'] = $pagination;
                }
            }
        } else {
            $this->ErrorMessage = "The catalogue to browse has not been configured. Please contact the website administrator.";
        }

        // Return the array so the page is displayed.
        return $data;
    }

    /**
     * Gets the details of a record by its ID and displays a page with the information about the record.
     *
     * @param  SS_HTTPRequest $request
     * @return Array
     */
    public function details(SS_HTTPRequest $request)
    {
        $data = array();
        $id = $request->getVar('id');

        // If the catalogue url has been specified then call function to search/browse the catalogue.
        if ($this->CatalogueUrl) {
            if ($id) {
                $record = $this->getRecordByID($id);

                if (!$this->ErrorMessage) {
                    // Create a new DOM documents and then load the response as XML in to it.
                    try {
                        $doc  = new DOMDocument();
                        $doc->loadXML($record);
                        // Call function to parse the document.
                        $mdArray = $this->parseDetails($doc);

                        // Check if there is an item in the returned array and if so set the data to that.
                        if (isset($mdArray[0])) {
                            $data = $mdArray[0];
                        }
                    } catch (Exception $e) {
                        $this->ErrorMessage = "Sorry, there was an error parsing the response from the catalogue.";
                    }
                }
            } else {
                $this->ErrorMessage = "The ID of the record to view was not specified.";
            }
        } else {
            $this->ErrorMessage = "The catalogue to browse has not been configured. Please contact the website administrator.";
        }

        // Render the page with the data for the record.
        return $this->renderWith(array('CatalogueEntryDetails', 'Page'), $data);
    }

    /**
     * Ouputs the XML for a record in a catalogue as a download.
     *
     * @param  SS_HTTPRequest $request
     * @return text/xml
     */
    public function xml(SS_HTTPRequest $request)
    {
        $id = $request->getVar('id');

        if ($id) {
            $record = $this->getRecordByID($id, true);
            $resp = $this->getResponse();
            $resp->addHeader("Content-Type", "text/xml");
            $resp->addHeader('Content-Disposition', "attachment; filename=\"" . $id . ".xml\";");
            return $record;
        }
    }

    /**
     * Searches the catalogue. This is either a browse type search or a search with the keyword the user entered.
     * The record position needs to passed in order for the pagination to work (this is not a page number).
     *
     * @return Array
     */
    protected function searchCatalogue()
    {
        $mdArray = array();
        $pagination = array();

        // Calculate the start position for the query which is not a page number but record number so
        // we must convert back from the page number the user sees to a record position.
        $startPosition = (($this->PageNumber - 1) * $this->RecordsPerPage) + 1;

        // Put together the xml data for the body of the request which is a POST.
        $data = '<csw:GetRecords xmlns:csw="http://www.opengis.net/cat/csw/2.0.2" xmlns:ogc="http://www.opengis.net/ogc" xmlns:gml="http://www.opengis.net/gml" service="CSW" version="2.0.2" resultType="results" outputSchema="csw:IsoRecord" maxRecords="' . $this->RecordsPerPage . '" startPosition="' . $startPosition . '">
        <csw:Query typeNames="gmd:MD_Metadata">
            <ogc:SortBy>
                <ogc:SortProperty>
                    <ogc:PropertyName>title</ogc:PropertyName>
                    <ogc:SortOrder>ASC</ogc:SortOrder>
                </ogc:SortProperty>
            </ogc:SortBy>
            <csw:ElementSetName>full</csw:ElementSetName>
            <csw:Constraint version="1.1.0">
                <Filter xmlns="http://www.opengis.net/ogc" xmlns:gml="http://www.opengis.net/gml">';

        // If the user entered a search keyword then we must add items to the constrant.
        if ($this->SearchKeyword) {
            // We must split what the user entered in to individual words
            $wordsToSearchFor = array();

            // split it by any number of commas or space characters, which include " ", \r, \t, \n and \f
            // This line from the geocatalogue module.
            $wordsToSearchFor = preg_split("/[\s,]+/", $this->SearchKeyword, -1, PREG_SPLIT_NO_EMPTY);

            $data .= "\n<And>\n";

            foreach ($wordsToSearchFor as $word) {
                $data .= '<PropertyIsLike wildCard="%" singleChar="_" escapeChar="\">
                <PropertyName>AnyText</PropertyName>
                <Literal>%' . $word . '%</Literal>
                </PropertyIsLike>' . "\n";
            }

            $data .= "</And>\n";
        }

        $data .= '</Filter>
            </csw:Constraint>
        </csw:Query>
        </csw:GetRecords>';

        // Get the response which comes back as a restful response.
        try {
            $response = $this->curlRequest($data);

            if ($response->getStatusCode() == 200) {
                $responseBody = $response->getBody();
            } else {
                throw new Exception('Server responsed with error');
            }
        } catch (Exception $e) {
            $this->ErrorMessage = "Sorry, there was an error trying to get the information from the catalogue.";
        }

        if (!$this->ErrorMessage) {
            // Escape any single quotes to ensure no issues in the parsing of the XML.
            $responseBody = str_replace("'", "\'", $responseBody);

            // Create a new DOM documents and then load the response as XML in to it.
            $doc  = new DOMDocument();

            try {
                // Try loading the response as XML.
                $doc->loadXML($responseBody);

                // Call function to parse the document.
                list($totalRecords, $nextPosition, $mdArray) = $this->parseSummary($doc);

                // Call function to calculate the pagination.
                $pagination = $this->calculatePagination($totalRecords, $nextPosition);
            } catch (Exception $e) {
                $this->ErrorMessage = "Sorry, there was an error parsing the response from the catalogue.";
            }
        }

        // Return the records and the pagination information.
        return array($mdArray, $pagination);
    }

    /**
     * Calculates the pagination for the results. We get back a record position so need
     * to convert this in to page numbers using the number of records defined per page.
     *
     * @param  Int  $totalRecords
     * @param  Int  $nextPosition
     * @return Array
     */
    protected function calculatePagination($totalRecords, $nextPosition)
    {
        // Work out some pagination information.
        $totalPages = ceil($totalRecords / $this->RecordsPerPage);
        $isLastPage = ($this->PageNumber == $totalPages) ? true : false;
        $currentPage = $this->PageNumber;
        $nextPage = ceil($nextPosition / $this->RecordsPerPage);
        $prevPage = ($this->PageNumber - 1);
        $isFirstPage = $prevPage == 0 ? true : false;

        $pagination = array(
            'isFirstPage'  => $isFirstPage,
            'totalPages'   => $totalPages,
            'isLastPage'   => $isLastPage,
            'currentPage'  => $currentPage,
            'nextPage'     => $nextPage,
            'previousPage' => $prevPage
        );

        return $pagination;
    }

    /**
     * Gets a record from the catalogue by its ID. The response body is returned which should be XML.
     *
     * @param  String $ID the identifier of the record
     * @return String
     */
    protected function getRecordById($id, $xml=false)
    {
        $record = '';

        // Put together the xml body for the post to get the record.
        $data = '<?xml version="1.0"?>
        <csw:GetRecordById xmlns:csw="http://www.opengis.net/cat/csw/2.0.2" service="CSW" version="2.0.2" outputSchema="csw:IsoRecord">
        	<csw:ElementSetName>full</csw:ElementSetName>
        	<csw:Id>' . $id . '</csw:Id>
        </csw:GetRecordById>';

        // Get the response which comes back as a restful response.
        try {
            $response = $this->curlRequest($data);

            if ($response->getStatusCode() == 200) {
                $responseBody = $response->getBody();
            } else {
                throw new Exception('Server responsed with error');
            }
        } catch (Exception $e) {
            $this->ErrorMessage = "Sorry, there was an error trying to get the information from the catalogue.";
        }

        if (!$this->ErrorMessage) {
            // Escape any single quotes to ensure no issues in the parsing of the XML.
            $record = str_replace("'", "\'", $responseBody);

            // If we are to return the XML then the geocatalogue had this module to strip the outer tags around the response.
            if ($xml) {
                $record = preg_replace('/((\<csw\:GetRecordByIdResponse)|(<\/csw\:GetRecordByIdResponse)).*\>/', '', $record);
            }
        }

        return $record;
    }

    /**
     * Makes POST type curl request to the catalogue to get the information.
     *
     * @param  String  $data the data to send in the body of the request.
     * @return RestfulService_Response
     */
    public function curlRequest($data = null)
    {
        $ch = curl_init();

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/xml'));
        curl_setopt($ch, CURLOPT_URL, $this->CatalogueUrl);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);

        // We don't care about the SSL so disable (the geocatalogue module does this).
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

        $output = curl_exec($ch);

        // Get the status of the response to the curn request.
        $statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        // Normalise the status code (0 causes the code below to error as it does not know what 0 means).
        if (curl_error($ch) !== '' || $statusCode == 0) {
            $statusCode = 500;
        }

        // The geocatalogue module is using this for responses, has the getBody() method used in calling code.
        $restfulResponse = new RestfulService_Response($output, $statusCode);

        // close curl resource to free up system resources
        curl_close($ch);

        // Return the restful response.
        return $restfulResponse;
    }

    /**
     * From the geocatalogue module, parses the response from the catalogue and extracts the information
     * we want from MCP ISO type records. This has not been altered in any way from the geocatalogue module function.
     *
     * @param  DOMDocument  $doc
     * @return Array
     */
    protected function parseSummary($doc)
    {
        $response = $doc->getElementsByTagNameNS('http://www.opengis.net/cat/csw/2.0.2', "GetRecordsResponse");

        $status = $response->item(0)->getElementsByTagName("SearchResults");

        // get search summary
        $numberOfRecordsMatched = $status->item(0)->getAttribute('numberOfRecordsMatched');
        $numberOfRecordsReturned = $status->item(0)->getAttribute('numberOfRecordsReturned');
        $nextRecord = $status->item(0)->getAttribute('nextRecord');

        $metadata = $response->item(0)->getElementsByTagNameNS("http://bluenet3.antcrc.utas.edu.au/mcp", "MD_Metadata");

        $mdArray = array();

        // Pop these in vars so code below is less lenghty.
        $gmd = 'http://www.isotc211.org/2005/gmd';
        $gco = 'http://www.isotc211.org/2005/gco';

        foreach ($metadata as $item) {
            $mdItem = array();

            $element = $item->getElementsByTagNameNS($gmd, "fileIdentifier");
            if ($element->length > 0) {
                $node = $element->item(0)->getElementsByTagNameNS($gco, 'CharacterString');

                $mdItem['fileIdentifier'] = isset($node) ? $node->item(0)->nodeValue : '';
            }

            $element = $item->getElementsByTagNameNS($gmd, "metadataStandardName");
            if ($element->length > 0) {
                $node = $element->item(0)->getElementsByTagNameNS($gco, 'CharacterString');
                $mdItem['metadataStandardName'] = isset($node) ? $node->item(0)->nodeValue : '';
            }

            $element = $item->getElementsByTagNameNS($gmd, "metadataStandardVersion");
            if ($element->length > 0) {
                $node = $element->item(0)->getElementsByTagNameNS($gco, 'CharacterString');
                $mdItem['metadataStandardVersion'] = isset($node) ? $node->item(0)->nodeValue : '';
            }

            $element = $item->getElementsByTagNameNS($gmd, "parentIdentifier");
            if ($element->length > 0) {
                $node = $element->item(0)->getElementsByTagNameNS($gco, 'CharacterString');
                $mdItem['parentIdentifier'] = isset($node) ? $node->item(0)->nodeValue : '';
            }

            $element = $item->getElementsByTagNameNS($gmd, "hierarchyLevel");
            if ($element->length > 0) {
                $node = $element->item(0)->getElementsByTagNameNS($gmd, 'MD_ScopeCode');
                $mdItem['hierarchyLevel'] = isset($node) ? $node->item(0)->nodeValue : '';
            }

            $element = $item->getElementsByTagNameNS($gmd, "hierarchyLevelName");
            if ($element->length > 0) {
                $node = $element->item(0)->getElementsByTagNameNS($gco, 'CharacterString');
                $mdItem['hierarchyLevelName'] = isset($node) ? $node->item(0)->nodeValue : '';
            }

            $element = $item->getElementsByTagNameNS($gmd, "identificationInfo");
            if ($element->length > 0) {
                $mcp_element = $element->item(0)->getElementsByTagNameNS('http://bluenet3.antcrc.utas.edu.au/mcp', "MD_DataIdentification");

                $item = $mcp_element->item(0)->getElementsByTagNameNS($gmd, 'citation');
                $item = $item->item(0)->getElementsByTagNameNS($gmd, 'CI_Citation');
                $item = $item->item(0)->getElementsByTagNameNS($gmd, 'title');
                $mdItem['MDTitle'] = isset($item) ? trim($item->item(0)->nodeValue) : '';

                $item = $mcp_element->item(0)->getElementsByTagNameNS($gmd, 'abstract');
                $mdItem['MDAbstract'] = isset($item) ? trim($item->item(0)->nodeValue) : '';

                $item = $mcp_element->item(0)->getElementsByTagNameNS($gmd, 'topicCategory');
                $mdItem['MDTopicCategory'] = isset($item) ? trim($item->item(0)->nodeValue) : '';
            }
            $mdArray[] = ArrayData::create($mdItem);
        }

        return array($numberOfRecordsMatched, $nextRecord, $mdArray);
    }

    /**
     * From the geocatalogue module, this function parses and gets the full details of an ISO format MCP record.
     * I did have to alter it to create arraylists and arraydata in order to the template to loop and output,
     * I also altered some of the associative array index names, removing any 2 part names with colon (:) in the middle.
     *
     * @param  DOMDocument $doc
     * @return Array
     */
    protected function parseDetails($doc)
    {
        $mdArray = array();

        $xpath = new DOMXPath($doc);
        $xpath->registerNamespace("mcp", "http://bluenet3.antcrc.utas.edu.au/mcp");
        $xpath->registerNamespace("gmd", "http://www.isotc211.org/2005/gmd");
        $xpath->registerNamespace("gco", "http://www.isotc211.org/2005/gco");
        $xpath->registerNamespace("csw", "http://www.opengis.net/cat/csw/2.0.2");

        $metadataList = $xpath->query('mcp:MD_Metadata');

        foreach ($metadataList as $metadata) {
            $mdItem = array();

            $mdItem['fileIdentifier'] = $this->queryNodeValue($xpath, 'gmd:fileIdentifier/gco:CharacterString', $metadata);

            $element = $metadata->getElementsByTagNameNS('http://www.isotc211.org/2005/gmd', "dateStamp");
            if ($element->length > 0) {
                $node = $element->item(0)->getElementsByTagNameNS('http://www.isotc211.org/2005/gco', 'DateTime');
                $mdItem['dateStamp'] = isset($node) ? $node->item(0)->nodeValue : '';
            }

            $mdItem['metadataStandardName'] = $this->queryNodeValue($xpath, 'gmd:metadataStandardName/gco:CharacterString', $metadata);
            $mdItem['metadataStandardVersion'] = $this->queryNodeValue($xpath, 'gmd:metadataStandardVersion/gco:CharacterString', $metadata);

            $xmlOnlineResourceList = $xpath->query('gmd:distributionInfo/gmd:MD_Distribution/gmd:transferOptions/gmd:MD_DigitalTransferOptions/gmd:onLine/gmd:CI_OnlineResource', $metadata);
            $OnlineResources = ArrayList::create();
            foreach ($xmlOnlineResourceList as $item) {
                $ciOnlineResource = array();
                $ciOnlineResource['CIOnlineLinkage'] = $this->queryNodeValue($xpath, 'gmd:linkage/gmd:URL', $item);
                $ciOnlineResource['CIOnlineProtocol'] = $this->queryNodeValue($xpath, 'gmd:protocol/gco:CharacterString', $item);
                $ciOnlineResource['CIOnlineName'] = $this->queryNodeValue($xpath, 'gmd:name/gco:CharacterString', $item);
                $ciOnlineResource['CIOnlineDescription'] = $this->queryNodeValue($xpath, 'gmd:description/gco:CharacterString', $item);

                $ciOnlineResource['CIOnlineFunction'] = $this->queryNodeValue($xpath, 'gmd:linkage/gmd:function/gmd:CI_OnLineFunctionCode', $item);
                $OnlineResources->push(ArrayData::create($ciOnlineResource));
            }
            $mdItem['CIOnlineResources'] = $OnlineResources;

            $partyList = $xpath->query('gmd:contact/gmd:CI_ResponsibleParty', $metadata);
            foreach ($partyList as $party) {
                $mdContact = array();
                $mdContact['MDIndividualName'] = $this->queryNodeValue($xpath, 'gmd:individualName/gco:CharacterString', $party);
                $mdContact['MDOrganisationName'] = $this->queryNodeValue($xpath, 'gmd:organisationName/gco:CharacterString', $party);
                $mdContact['MDPositionName'] = $this->queryNodeValue($xpath,'gmd:positionName/gco:CharacterString', $party);

                $contact = $xpath->query('gmd:contactInfo/gmd:CI_Contact', $party)->item(0);

                $mdVoice = ArrayList::create();
                $voiceNumberList = $xpath->query('gmd:phone/gmd:CI_Telephone/gmd:voice', $contact);
                foreach ($voiceNumberList as $voiceNumber) {
                    $mdPhoneNumber = array();
                    $mdPhoneNumber['Value'] = $this->queryNodeValue($xpath,'gco:CharacterString', $voiceNumber);
                    $mdVoice->push(ArrayData::create($mdPhoneNumber));
                }
                $mdContact['MDVoice'] = $mdVoice;

                // allows only 1 facsimile number
                $mdContact['MDFacsimile'] = $this->queryNodeValue($xpath, 'gmd:phone/gmd:CI_Telephone/gmd:facsimile/gco:CharacterString', $contact);

                $addressList = $xpath->query('gmd:address/gmd:CI_Address', $contact);
                foreach ($addressList as $address) {
                    $mdContact['MDDeliveryPoint'] = $this->queryNodeValue($xpath,'gmd:deliveryPoint/gco:CharacterString', $address);
                    $mdContact['MDCity'] = $this->queryNodeValue($xpath,'gmd:city/gco:CharacterString', $address);
                    $mdContact['MDAdministrativeArea'] = $this->queryNodeValue($xpath,'gmd:administrativeArea/gco:CharacterString', $address);
                    $mdContact['MDPostalCode'] = $this->queryNodeValue($xpath,'gmd:postalCode/gco:CharacterString', $address);
                    $mdContact['MDCountry'] = $this->queryNodeValue($xpath,'gmd:country/gco:CharacterString', $address);
                    $mdContact['MDElectronicMailAddress'] = $this->queryNodeValue($xpath,'gmd:electronicMailAddress/gco:CharacterString', $address);
                }

                // add mdContact object to the contact relationship object
                $mdItem['MDContacts'] = ArrayData::Create($mdContact);
            }

            $xmlDataIdentificationList = $xpath->query('gmd:identificationInfo/mcp:MD_DataIdentification', $metadata);
            foreach ($xmlDataIdentificationList as $dataIdentification) {
                $mdItem['MDPurpose'] = $this->queryNodeValue($xpath, 'gmd:purpose/gco:CharacterString', $dataIdentification);
                $mdItem['MDAbstract'] = $this->queryNodeValue($xpath, 'gmd:abstract/gco:CharacterString', $dataIdentification);
                $mdItem['MDLanguage'] = $this->queryNodeValue($xpath, 'gmd:language/gco:CharacterString', $dataIdentification);

                $mdTopicCategory = ArrayList::Create();
                $xmlCategoryList = $xpath->query('gmd:topicCategory/gmd:MD_TopicCategoryCode', $dataIdentification);
                foreach ($xmlCategoryList as $category) {
                    if (trim($category->nodeValue)) {
                        $mdTopicCategoryItem = array();
                        $mdTopicCategoryItem['Value'] = trim($category->nodeValue);
                        $mdTopicCategory->push(ArrayData::create($mdTopicCategoryItem));
                    }
                }
                $mdItem['MDTopicCategory'] = $mdTopicCategory;

                $xmlCitationList = $xpath->query('gmd:citation/gmd:CI_Citation', $dataIdentification);
                foreach ($xmlCitationList as $citation) {
                    $mdItem['MDTitle'] = $this->queryNodeValue($xpath, 'gmd:title/gco:CharacterString', $citation);
                    $mdItem['MDEdition'] = $this->queryNodeValue($xpath, 'gmd:edition/gco:CharacterString', $citation);

                    $mdCitationDates = ArrayList::create();
                    $xmlDateList = $xpath->query('gmd:date/gmd:CI_Date', $citation);
                    foreach ($xmlDateList as $dateItem) {
                        $mdCitationDate=array();
                        $mdCitationDate['MDDateTime'] = $this->queryNodeValue($xpath, 'gmd:date/gco:DateTime', $dateItem);
                        $mdCitationDate['MDDate'] = $this->queryNodeValue($xpath, 'gmd:date/gco:Date', $dateItem);
                        $mdCitationDate['MDDateType'] = $this->queryNodeValue($xpath, 'gmd:dateType/gmd:CI_DateTypeCode', $dateItem);

                        $mdCitationDates->push(ArrayData::create($mdCitationDate));
                    }
                    $mdItem['MDCitationDates'] = $mdCitationDates;

                    $mdItem['MDPresentationForm'] = $this->queryNodeValue($xpath, 'gmd:presentationForm/gmd:CI_PresentationFormCode', $citation);
                }

                // Geographic Extend
                $xmlList = $xpath->query('gmd:extent/gmd:EX_Extent', $dataIdentification);
                foreach ($xmlList as $extent) {
                    $mdItem['MDGeographicDiscription'] = $this->queryNodeValue($xpath, 'gmd:description/gco:CharacterString', $extent);
                    $mdItem['MDWestBound'] = $this->queryNodeValue($xpath, 'gmd:geographicElement/gmd:EX_GeographicBoundingBox/gmd:westBoundLongitude/gco:Decimal', $extent);
                    $mdItem['MDEastBound'] = $this->queryNodeValue($xpath, 'gmd:geographicElement/gmd:EX_GeographicBoundingBox/gmd:eastBoundLongitude/gco:Decimal', $extent);
                    $mdItem['MDSouthBound'] = $this->queryNodeValue($xpath, 'gmd:geographicElement/gmd:EX_GeographicBoundingBox/gmd:southBoundLatitude/gco:Decimal', $extent);
                    $mdItem['MDNorthBound'] = $this->queryNodeValue($xpath, 'gmd:geographicElement/gmd:EX_GeographicBoundingBox/gmd:northBoundLatitude/gco:Decimal', $extent);
                }

                // 2do: need to be tested
                $mdResourceFormat = array();
                $xmlList = $xpath->query('gmd:resourceFormat/gmd:MD_Format', $dataIdentification);
                foreach ($xmlList as $item) {
                    $mdResourceFormat['Name'] = $this->queryNodeValue($xpath, 'gmd:name/gco:CharacterString/gmd:westBoundLongitude/gco:Decimal', $item);
                    $mdResourceFormat['Version'] = $this->queryNodeValue($xpath, 'gmd:version/gco:CharacterString/gmd:westBoundLongitude/gco:Decimal', $item);
                }
                $mdItem['MDResourceFormats'] = ArrayData::create($mdResourceFormat);

                // keywords
                $keywords = ArrayList::create();
                $xmlList = $xpath->query('gmd:descriptiveKeywords/gmd:MD_Keywords/gmd:keyword', $dataIdentification);
                foreach ($xmlList as $item) {
                    $word = $this->queryNodeValue($xpath, 'gco:CharacterString', $item);

                    if ($word) {
                        $keywords->push(array('Value' => $word));
                    }
                }
                $mdItem['MDKeywords'] = $keywords;

                // iso resource contraints
                $mdResourceConstraint = array();
                $xmlList = $xpath->query('gmd:resourceConstraints/gmd:MD_LegalConstraints', $dataIdentification);
                foreach ($xmlList as $item) {
                    $mdResourceConstraint['accessConstraints'] = $this->queryNodeValue($xpath, 'gmd:accessConstraints/gmd:MD_RestrictionCode/gco:CharacterString', $item);
                    $mdResourceConstraint['useConstraints'] = $this->queryNodeValue($xpath, 'gmd:useConstraints/gmd:MD_RestrictionCode/gco:CharacterString', $item);
                    $mdResourceConstraint['otherConstraints'] = $this->queryNodeValue($xpath, 'gmd:otherConstraints/gco:CharacterString', $item);
                }
                //+ This does not appear to be output in the template anywhere.
                $mdItem['MDResourceConstraints'] = ArrayData::create($mdResourceConstraint);

                // mcp resource contraints
                $mcpMDCreativeCommonList = ArrayList::create();
                $xmlList = $xpath->query('gmd:resourceConstraints/mcp:MD_CreativeCommons', $dataIdentification);
                foreach ($xmlList as $item) {
                    $mcpMDCreativeCommon = array();
                    $mcpMDCreativeCommon['useLimitation'] = $this->queryNodeValue($xpath, 'gmd:useLimitation/gco:CharacterString', $item);
                    $mcpMDCreativeCommon['jurisdictionLink'] = $this->queryNodeValue($xpath, 'mcp:jurisdictionLink/gmd:URL', $item);
                    $mcpMDCreativeCommon['licenseLink'] = $this->queryNodeValue($xpath, 'mcp:licenseLink/gmd:URL', $item);
                    $mcpMDCreativeCommon['imageLink'] = $this->queryNodeValue($xpath, 'mcp:imageLink/gmd:URL', $item);
                    $mcpMDCreativeCommon['licenseName'] = $this->queryNodeValue($xpath, 'mcp:licenseName/gco:CharacterString', $item);
                    $mcpMDCreativeCommonList->push(ArrayData::create($mcpMDCreativeCommon));
                }
                $mdItem['MCPMDCreativeCommons'] = $mcpMDCreativeCommonList;

                // 2do: need to be tested
                $mdItem['MDSpatialRepresentationType'] = $this->queryNodeValue($xpath, 'gmd:spatialRepresentationType/gmd:MD_SpatialRepresentationTypeCode', $dataIdentification);

                $partyList = $xpath->query('gmd:pointOfContact/gmd:CI_ResponsibleParty', $dataIdentification);
                foreach ($partyList as $party) {
                    $mdContact = array();
                    $mdContact['MDIndividualName'] = $this->queryNodeValue($xpath, 'gmd:individualName/gco:CharacterString', $party);
                    $mdContact['MDOrganisationName'] = $this->queryNodeValue($xpath, 'gmd:organisationName/gco:CharacterString', $party);
                    $mdContact['MDPositionName'] = $this->queryNodeValue($xpath, 'gmd:positionName/gco:CharacterString', $party);

                    $contact = $xpath->query('gmd:contactInfo/gmd:CI_Contact', $party)->item(0);

                    $mdVoice = ArrayList::create();
                    $voiceNumberList = $xpath->query('gmd:phone/gmd:CI_Telephone/gmd:voice', $contact);
                    foreach ($voiceNumberList as $voiceNumber) {
                        $mdPhoneNumber = array();
                        $mdPhoneNumber['Value'] = $this->queryNodeValue($xpath, 'gco:CharacterString', $voiceNumber);
                        $mdVoice->push(ArrayData::create($mdPhoneNumber));
                    }
                    $mdContact['MDVoice'] = $mdVoice;

                    // allows only 1 facsimile number
                    $mdContact['MDFacsimile'] = $this->queryNodeValue($xpath, 'gmd:phone/gmd:CI_Telephone/gmd:facsimile/gco:CharacterString', $contact);

                    $addressList = $xpath->query('gmd:address/gmd:CI_Address', $contact);
                    foreach ($addressList as $address) {
                        $mdContact['MDDeliveryPoint'] = $this->queryNodeValue($xpath, 'gmd:deliveryPoint/gco:CharacterString', $address);
                        $mdContact['MDCity'] = $this->queryNodeValue($xpath, 'gmd:city/gco:CharacterString', $address);
                        $mdContact['MDAdministrativeArea'] = $this->queryNodeValue($xpath, 'gmd:administrativeArea/gco:CharacterString', $address);
                        $mdContact['MDPostalCode'] = $this->queryNodeValue($xpath, 'gmd:postalCode/gco:CharacterString', $address);
                        $mdContact['MDCountry'] = $this->queryNodeValue($xpath, 'gmd:country/gco:CharacterString', $address);
                        $mdContact['MDElectronicMailAddress'] = $this->queryNodeValue($xpath, 'gmd:electronicMailAddress/gco:CharacterString', $address);
                    }

                    // add mdContact object to the contact relationship object
                    $mdItem['PointOfContact'] = ArrayData::create($mdContact);
                }
            }
            $mdArray[] = $mdItem;
        }

        return $mdArray;
    }

    /**
     * Also from the geocatalogue, this is used in the function above.
     *
     * @param  DOMXPath $xpath
     * @param  String $path
     * @param  DOMNodeList $field
     * @return DOMNodeList
     */
    protected function queryNodeValue($xpath, $path, $field)
    {
        if ($xpath->query($path, $field)->length > 0) {
            return $xpath->query($path, $field)->item(0)->nodeValue;
        }

        // Return empty string as the templates do not know how to render null.
        return '';
    }

    /**
     * Used on the template to convert date to d/m/Y
     *
     * @param String $date
     * @return String
     */
    protected function DateFormatNice($date)
    {
        return date('d/m/Y', strtotime($date));
    }
}
