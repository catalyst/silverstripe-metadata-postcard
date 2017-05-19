<?php
/**
 * Small class to hold information about the last load of a vocabularly for metadata
 * field dropdown. Is created/updated in onAfterWrite so this information needed to
 * be seperate from the metadataField itself to avoid recursion.
 *
 * @author Catalyst I.T. SilverStripe Team 2017 <silverstripedev@catalyst.net.nz>
 * @package niwa.metadata.postcards
 */
class LastVocabLoadMessage extends DataObject
{
    /**
     * @var array
     */
    private static $db = array(
        'Message' => 'Varchar(255)',
        'Type' => 'ENUM(array("GOOD", "BAD"))',
        'Date' => 'Date',
    );

    /**
     * @var array
     */
    private static $has_one = array(
        'PostcardMetadataField' => 'PostcardMetadataField',
    );

    // A CMS user cannot see, create, or edit records of this class; entirely used by code in the PostcardMetadataField class.
}
