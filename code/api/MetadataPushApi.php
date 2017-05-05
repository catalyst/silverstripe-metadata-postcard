<?php
/**
 * Class to encapsulate the code to send / push records to the geocatalogue via
 * the CSW api end point. Uses my own CURL function as the ResfulServer in SilverStripe
 * does something incorrectly which results in Java errors coming back.
 *
 * @author Catalyst I.T. SilverStripe Team 2017 <silverstripedev@catalyst.net.nz>
 * @package niwa.metadata.postcards
 */
class MetadataPushApi
{
    protected $cataloguePushUrl;
    protected $catalogueUsername;
    protected $cataloguePassword;

    /**
     * Constructor.
     */
    public function __construct($cataloguePushUrl, $catalogueUsername, $cataloguePassword)
    {
        if ($cataloguePushUrl) {
            $this->cataloguePushUrl = $cataloguePushUrl;
        }

        // If a user name and password has been supplied then add basic auth.
        if ($catalogueUsername && $cataloguePassword) {
            $this->catalogueUsername = $catalogueUsername;
            $this->cataloguePassword = $cataloguePassword;
        }
    }

    /**
     * Does the push to the catalogue.
     *
     * @param Array $fields Associative array fields to include in the XML sent to the catalogue.
     *
     * @return String
     */
    public function execute($fields)
    {
        $identifier = null;

        // Set up the headers, according to the CSW this is XML.
        $headers = array('Content-Type: application/xml');

        // Put together the data to send, this must be XML.
        // These starting tags are from this CSW docs...
        // http://geonetwork-opensource.org/manuals/2.10.4/eng/developer/xml_services/csw_services.html#transaction
        // see also http://dublincore.org/documents/dc-xml-guidelines/
        $data = '<?xml version="1.0" encoding="UTF-8"?>' . "\n" .
                '<csw:Transaction xmlns:csw="http://www.opengis.net/cat/csw/2.0.2" version="2.0.2" service="CSW">' . "\n" .
                '<csw:Insert>' . "\n" .
                '<simpledc xmlns:dc="http://purl.org/dc/elements/1.1/" xmlns:dct="http://purl.org/dc/terms/" xmlns:geonet="http://www.fao.org/geonetwork" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:noNamespaceSchemaLocation="xml/schemas/dublin-core/schema.xsd">' . "\n";

        // Now loop though all the form fields passed in and add them to the XML. The fields should be an assosiative array
        // of xmlnames and values. The dc: prefix should be included in the xmlname for the field.
        foreach($fields as $key => $value) {
            //++ @TODO check if its correct for ISO field names to be lower case as well?
            // Dublin core field names should all be lower case so ensure this.
            // Also encode the value to ensure that any less or greater than symbols do not break the XML.
            $data .= '<' . strtolower($key) . '>' . htmlspecialchars($value) . '</' . strtolower($key) . '>' . "\n";
        }

        // Close the XML insert.
        $data .= '</simpledc>' . "\n" .
                 '</csw:Insert>' . "\n" .
                 '</csw:Transaction>';

        // Make the post request and get the response.
        list($response, $httpStatus, $curlError) = $this->makeCurlRequest($headers, $data);

        // We expect a status code of 200 for the insert/getrecords and getrecordsbyid requests.
        if ($httpStatus != 200) {
            throw new Exception('The catalogue responsed with the following HTTP code: ' . $httpStatus . ' - ' . $curlError);
        }

        // Check the response which should be XML if all was fine and get the identifier for the new record.
        $doc = new DOMDocument();

        try {
            $doc->loadXML($response);
        }
        catch (Exception $e) {
            throw new Exception('The response from the catalogue was not valid XML so this tool could not parse it.');
        }

        $xpath = new DOMXPath($doc);
        $idList = $xpath->query('//csw:InsertResult/csw:BriefRecord/identifier');

        if (($idList) && ($idList->length > 0)) {
            $identifier = $idList->item(0)->nodeValue;
        }

        if (!isset($identifier)) {
            throw new Exception('Identifier for the new record not found. The must have been an error trying to add the record to the catalogue.');
        }

        // Return the identifier of the new record.
        return $identifier;
    }

    /**
     * Makes a curl request to the catalogue and returns the response.
     *
     * @param  Array  $headers
     * @param  String  $body
     *
     * @return Array
     */
    public function makeCurlRequest($headers, $body)
    {
        $output = "";
        $statusCode = 0;
        $curlError = "";

        // create curl resource
        $ch = curl_init();

        // set url
        curl_setopt($ch, CURLOPT_URL, $this->cataloguePushUrl);

        // return the transfer as a string
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

        // If a username and password exist then add them as basic auth.
        if ($this->catalogueUsername && $this->cataloguePassword) {
            curl_setopt($ch, CURLOPT_USERPWD, $this->catalogueUsername . ':' . $this->cataloguePassword);
        }

        // Set headers and body.
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $body);

        // Execute the curl command and get the output.
        $output = curl_exec($ch);

        // Get the status code.
        $statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        // Get a curl error if there is one
        $curlError = curl_error($ch);

        // Normalise the status code
        if (curl_error($ch) !== '' || $statusCode == 0) {
            $statusCode = 500;
        }

        // close curl resource to free up system resources
        curl_close($ch);

        // Return the output, status code, and curl error
        return array($output, $statusCode, $curlError);
    }
}
