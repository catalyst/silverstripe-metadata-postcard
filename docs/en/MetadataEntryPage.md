## Metadata Postcard Entry Page user guide

This file contains the user guide for the Metadata Postcard Entry page. The main feature of this page type is that it allows to easily create a page for users to enter information which then gets sent through to a geo catalogue.

You specify the fields which appear of the form and can give them user-friendly names and help text, while under the hood these are mapped to Dublin Core fields.

### Page creation

* Log in to the CMS
* Choose Pages from the main menu
* Click Add new
* Choose Metadata Postcard Entry Page
* Enter the normal SilverStripe page information such as Page name and Content
* Save or Save and publish the page

#### Help and Browse tab

* All information on this tab is optional.
* If you would like a help box on the right of the page enter a Help Box Title and Message
* If you would like a box which prompts the user to visit a browse page enter the following
 * Browse box title
 * Browse box message
 * Select the Browse Catalogue page
 * Optionally enter some custom text for the link label

#### Catalogue tab, enter the following

* Catalogue CSW push URL
* Catalogue View URL (used to create urls allowing the user to view newly created records)
* Catalogue Username and Password. This tool needs uses these details to log in to the catalogue and create records
* From email address - the address emails from this site appear to be from. Should include the domain for your site so emails don't get marked as spam
* Optionally tick the checkbox to display a list of the records created this session on the screen. Users will need their own account on your catalogue to log in and see the records. Note because records were created by the account used by the form, users may not see their records until they are published in the catalogue.
* Enter the message to be displayed when the push to the catalogue has been successful
 * It can contain the special placeholder variable {LINK} which will be replaced with the link to the newly created record.
* Enter the message displayed if the push to the catalogue fails.
 * It can contain the special variable {ERROR} to display details of the issue.

#### Project Coordinator tab

If certain URL parameters are passed to the Metadata Entry page the system will send an email the first time a user creates a record their current session. This is intended to inform Project Coordinators that records are being added.

If you would like to use this feature then please ensure the Coordinator Email Subject and Coordinator Email Body are filled out. There are special variables in curly brackets, example {PROJECT_MANAGER} which will be replaced with values when the email is sent.

Also this feature will only be triggered if the following parameters are sent to the entry page via a GET request - i.e. as URL parameters...

* Project_Coordinator
* Project_Coordinator_email
* Project_Manager
* Project_Code

NOTES: The parameters Project_Administrator and Project_Administrator_email can be used instead of Project_Coordinator and Project_Coordinator_email. Also note the parameter names are case insensitive.

#### Curators

On this tab you can add one or more curators which will get emailed EVERY time a record is pushed to the catalogue using the form on this page. Here you can enter the email subject and body and again there are special {VARIABLES} which will be replaced.

Click Add above the table of Catalogue Curators to add a new curator and enter a name and email for them.

Because their name is one of the special {VARIABLES} Curators get an individual email each, not a single email with multiple recipients.

#### Metadata Fields tab

This tab is where you can see the fields on the form users of the website fill out and add/update/delete fields.

To add a field follow these steps...

* Click Add Postcard Metadata field, you will be taken to an add screen
* Select the Dublin Core Field the form field will map to when the push is done to the CSW API of the catalogue
* Enter a label for the form field. Note the label (with underscores instead of spaces) is also the name of the parameter for this field if you would like to send URL parameters to the page to pre-populate some fields on the form.
* If desired enter some Help text for the field. This is revealed when the user clicks on the small (?) icon to the right of the field label.
* Choose if the field is readonly. This is only useful if populated automatically from URL parameters
* Tick "This field is required" if the user must enter a value for the field
* Now choose the field type, the following options are available
 * Text box - single line text input
 * Text area - multi line text input
 * Dropdown - dropdown/select allowing the user to choose from pre-defined options. See note on this below.
 * Placeholder - these are fields which are hidden to the user of the form but get sent through to the catalogue. You must specify a value for these fields.
 * Keywords - a textarea type field which displays the admin entered keywords to the user as read only and allows them to enter additional keywords. All keywords a sent to the catalogue on submit of the form.
* Lastly ensure you save the field record.
* You can get back to the Entry Page by clicking the small "<" icon to the top-left of the screen next to the SilverStripe CMS breadcrumb.

##### Notes on Dropdown fields

* You will be able to enter the options for the dropdown once the Postcard Metadata field has been created. These appear in a Dropdown Items tab to the top-right of the CMS interface.
* The list of options for dropdowns can be populated by a vocabulary server such as seen here http://vocabs.ands.org.au/repository/api/lda/ga/ga-analysis/v0-1/concept.xml
 * The URL must be to the XML endpoint
 * On save of the field the website will attempt to connect to the vocab server and download a list of options
 * By default every time you save the field it will re-populate the list of options, if you wish to prevent this because you have added/updated/or deleted the list of options, tick the "Prevent dropdown items from being refreshed" checkbox.

##### Field order

You can change the order of the fields on the form by clicking and dragging up and down on the little :: to the left of the rows in the list of form fields.

Be sure to save and publish the page after you have re-arranged the order of the fields.

#### Page URL parameters

This tab contains a tool which assists in testing the auto-population of form fields from URL parameters. You can also use it to be sure what parameter names are needed in any other IT system you plan to use to create links to your site with a metadata entry form.

The first set of fields from Project name down to Project coordinator email are only required if you plan to use the features which email a project coordinator or curators.

Below that you can Add Url Parameters for fields you want populated by URL parameters. To do this...

* Click the Add Url Parameter button
* Select the form field you would like to be populated by url parameter
* Enter the value of the url parameter
* Click Save

Near the bottom of the screen the correct url for the metadata entry page, parameters, and value is displayed. This is only refreshed when the page loads, so if you make changes please ensure you save and publish the page and the URL will update.

The URL can be quite lengthy so there are a couple of options. One is to copy the URL to the clipboard which you can then paste using Ctl-v, the other is to test the url straight away by clicking the "Test the URL" link.