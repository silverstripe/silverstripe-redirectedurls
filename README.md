Redirected URLs
===============

**Author:** Sam Minnée, May 2011

This module provides a system for users to configure arbitrary redirections in the CMS. These can be
used for legacy redirections, friendly URLs, and anything else that involves redirecting one URL to
another.

The URLs may include query-strings, and can be imported from a CSV using the "Redirections" model
admin included.

The redirection is implemented as a plug-in to the 404 handler, which means that you can't create a
redirection for a page that already exists on the site.