## Introduction

This module adds a Metadata Postcard Entry page to a SilverStripe website. It includes all the functionality to
specify the form fields which appear on the page and map these to the Dublin Core fields sent to a catalogue
upon submit of the form.

This module also adds a Browse Catalogue page which supports browsing, filtering, and viewing the details CSW records of both Dublin Core and ISO format.

This module was developed by Catalyst IT for NIWA.

### Requirements

 * SilverStripe CMS and Framework 3.1+ (works with CWP as well)
 * fiendsofsilverstripe/backendmessages ^1.0
 * unclecheese/display-logic ^1.5


### Installation

Manual entry of this module in to your composer.json will be required with a VCS entry pointing to the Github of where this repository is located.

Run composer install to ensure that the dependencies are downloaded.

Then ensure a dev/build is done to create the new tables in the database.

Manual installation by downloading is possible, but note the backend messages and display logic modules are also required.

### Features

There are many features of this module which include...

* Metadata Postcard Entry page
* Browse Catalogue page
* Configure catalogue connection in the CMS
* Customisable push success and failure messages
* Optional list of records added this session with links to the records in the catalogue
* Ability to email one project coordinator after the first record in a user's session has been entered
* Ability to email multiple catalogue curators every time a record is entered
* Email subject and body can be customised
* Metadata entry form fields configured via the CMS including choosing which Dublin Core fields these map to
* Multiple field type options are available, and you can specify if fields are required
* Dropdown options can be populated from a vocab server, such as http://vocabs.ands.org.au, to ensure consistent names are used and also to save a lot of time by not having to enter the options manually
* Entry form fields can be populated by URL parameters
* URL builder tool to help you construct the correct url to populate entry forms for testing, you might then want have another IT system create links to the postcard entry page(s)
* Entry and Browse pages have optional help boxes and boxes linking to each other
* Regular WYSIWYG page content can be added to the entry and browse pages
* The module should be able to be dropped in to any existing SilverStripe 3.x website without affecting the rest of it in any way, or used in a new install of SilverStripe
* You can override the page templates to customise how the entry and browse pages are displayed


### Usage

Please refer to the detailed [User Guide](docs/en/index.md) for how to use this module.
