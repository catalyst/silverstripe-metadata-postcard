## Overview

This module adds the Metadata Postcard Entry page to the site and includes all the functionality to
specify the add data form fields which appear on the page, and map these to the fields sent to a catalogue
upon submit of the form.

This module was created for and is used in the NIWA metadata postcards site.

### Requirements

 * SilverStripe Framework 3.x


### Installation

Manual entry of this module in to your composer.json will be required with a VCS entry
pointing to the Gitlab of where the repository for this module is located.

### Usage

* First create a new page of the type Metadata Postcard Entry.
* Then add and configure the fields to appear on the data entry form.
* Specify the URL to the catalogue the data is to be pushed to upon submit of the form.
* Set up the email addresses of the curators to be emailed after a push to the catalogue has been successful.
* Configure email, success, and failure messages as desired.
