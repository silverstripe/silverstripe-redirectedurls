Redirected URLs
===============

**Author:** Sam Minnée

**Author:** Stig Lindqvist

**Author:** Russ Michell


This module provides a system for users to configure arbitrary redirections in the CMS. These can be
used for legacy redirections, friendly URLs, and anything else that involves redirecting one URL to
another.

The URLs may include query-strings, and can be imported from a CSV using the "Redirections" model
admin included.

The redirection is implemented as a plug-in to the 404 handler, which means that you can't create a
redirection for a page that already exists on the site.

Installation
------------
Either:
1. Download or git clone the 'redirectedurls' directory to your webroot, or;
2. Using composer run the following in the command line: 

  composer require silverstripe/redirectedurls dev-master

3. Run dev/build (http://www.mysite.com/dev/build?flush=all)

Usage
-----
 1. Click 'Redirects' in the main menu of the CMS.
 2. Click 'Add Redirected URL' to create a mapping of an old URL to a new URL on your SilverStripe website.
 3. Enter a 'From Base' which is the URL from your old website (not including the domain name). For example, "/about-us.html".
 4. Alternatively, depending on your old websites URL structure you can redirect based on a query string using the combination of 'From Base' and 'From Querystring' fields. For exmaple, "index.html" as the base and "page=about-us" as the query string.
 5. As a further alternative, you can include a trailing '/*' for a wildcard match to any file with the same stem. For example "/about/*";
 6. Complete the 'To' field which is the URL you wish to redirect traffic to if any traffic from. For example "/about-us".
 7. Alternatively you can terminate the 'To' field with '/*' to redirect to the specific file requested by the user. For example "/new-about/*". Note that if this specific file is not in the target directory tree, the 404 error will be handled by the target site. 
 8. Create a new Redirection for each URL mapping you need to redirect.

Importing
---------
 1. Create a CSV file with the columns headings 'FromBase', 'FromQuerystring' and 'To' and enter your URL mappings. 
 2. Click 'Redirects' in the main menu of the CMS.
 3. In the 'Import' section click 'Choose file', select your CSV file and then click 'Import from CSV'.


