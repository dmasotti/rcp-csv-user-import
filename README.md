# Restrict Content Pro - CSV User Import

A plugin for importing a CSV of user accounts into Restrict Content Pro.

## Description

This plugin is an add-on for [Restrict Content Pro](http://pippinsplugins.com/restrict-content-pro-premium-content-plugin/), a complete subscription and premium content manager plugin for WordPress.

Once activated, this plugin will provide a new menu item under the Restrict menu called **CSV Import**.

In order to import correctly, you must preformat your CSV to match the requirements of the plugin. You CSV should have the following columns:

**user_email, first_name, last_name, user_login**

A sample CSV is included in the plugin's folder that you can use for reference.

The user's email address is the only column that requires a value. If user_login is left blank, the user's email address will be used for their login name.

When importing, every user has their password auto generated, so each user will need to go through the recover a lost password process.

**Note:** this plugin should be able to handle the importation of a few thousands users at a time, but if you have more than 5,000 (or if you are having problems with server timeouts), you will want to consider creating a custom shell script to import the users in batches.

## Installation

1. Upload rcp-user-import to wp-content/plugins
2. Click "Activate" in the WordPress plugins menu
3. Go to Restrict > CSV Import and follow directions

## Changelog

### 1.1.8

* New: Add user note to imported users saying they were imported from a CSV file.
* New: Add option to disable email notifications during import process.
* New: Add support for `Subscription ID` column (`rcp_merchant_subscription_id` meta).
* Fix: Imported members not given subscription level role.

### 1.1.7

* Tweak: Added backwards compatibility for using old column header format.
* New: Automatically set status to "Free" when importing members to a free subscription level.

### 1.1.6

* New: Add support for member joined date.
* Tweak: Improve compatibility with RCP core export file.
* Fix: Expiration dates with commas not being parsed correctly.
* Fix: Sanitize expiration date before saving.

### 1.1.5

* Only allow CSV files to be selected during import.
* Load the DatePicker styles on the import page.
* Add the $user object to the rcp_user_import_user_added action.
* Cleaned up and documented the code.

### 1.1.3

* Fixed an issue with updating existing members

### 1.1.2

* User update_user_meta() instead of add_user_meta()

### 1.1.1

* Properly esc the select element

### 1.1

* Improved the CSV import process to make it far more reliable.

### 1.0.3

* Added support for updating existing user accounts by passing the user ID to an "ID" column

### 1.0.2

* Added a new rcp_user_import_user_added hook that runs after each user is created

### 1.0.1

* Added support for subscription user roles

### 1.0

* Initial Release
