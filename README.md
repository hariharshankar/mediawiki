The idea of the Memento extension is it to make it as straightforward to access articles of the past as it is to access their current version.

The Memento framework allows you to see versions of articles as they existed at some date in the past. All you need to do is enter a URL of an article in your browser and specify the desired date in a browser plug-in. This way you can browse the Web of the past. What the Memento extension will present to you is a version of the article as it existed on or very close to the selected date. Obviously, this will only work if previous (archived) versions are available on the Web. Fortunately, MediaWiki is a Content Management System which implies that it maintains all revisions made to an article. This extension leverages this archiving functionality and provides native Memento support for MediaWiki.

This package contains the source code, build scripts, and tests for the Memento MediaWiki Extension.

This file also contains installation information, but more comprehensive information about the extension is at:  http://www.mediawiki.org/wiki/Extension:Memento

Note: the released version of this extension does not contain this file, so the target audience for this file is those who wish to build/maintain the source code.

# Directory Contents

* Makefile - the build script that does all of the magic
* README.md - this file
* TODO - list of items to address in the codebase
* Memento/ - the source code for this extension
* externals/ - git submodule linking to the code verification rules at https://gerrit.wikimedia.org/r/p/mediawiki/tools/codesniffer.git
* scripts/ - command line scripts used for testing the extension by hand
* tests/integration/ - the integration tests
* tests/lib/ - libraries needed by the tests
* tests/data/ - data used by the tests


# Installation

To install this package within Mediawiki perform the following:
* copy the Memento directory into the extensions directory of your Mediawiki installation
* add the following to the LocalSettings.php file in your Mediawiki installation:
```
    require_once("$IP/extensions/Memento/Memento.php");
```

# Configuration

This extension has sensible defaults, but also allows the following settings to be added to LocalSettings.php in order to alter its behavior:

* $wgMementoTimemapNumberOfMementos - (default is 500) allows the user to alter the number of Mementos included in a TimeMap served up by this extension (default is 500)

* $wgMementoExcludeNamespaces - is an array of Mediawiki Namespace IDs (e.g. the integer values for Talk, Template, etc.) to exclude from Mementofication (default is an empty array); the list of Mediawiki Namespace IDs is at http://www.mediawiki.org/wiki/Manual:Namespace

* $wgMementoTimeNegotiationForThumbnails - EXPERIMENTAL: MediaWiki, by default, does not preserve temporal coherence for its oldid pages.  In other words, an oldid (URI-M) page will not contain the version of the image that existed when that page was created.  See http://arxiv.org/pdf/1402.0928.pdf for more information on this problem in web archives.
    * false - (default) do not attempt to match the old version of the image to the requested oldid page
    * true - attempt to match the old version of the image to the requested oldid page

# Packaging

To package the Memento Mediawiki Extension, type the following 
from this directory:

    make package

This serves to run everything needed to verify the code and package the zip for release.

# Automated Deployment for Testing

To deploy the Memento Mediawiki Extension locally for testing, one must first indicate to the shell where Mediawiki is installed, then run the appropriate make target.

```
    export MWDIR=<where your Mediawiki is installed>
    make deploy
```

To remove the software from a Mediawiki instance, type:

```
    make undeploy
```

# Integration Testing

Once the code is deployed, the integration tests can be run.

Running the unit and integration tests requires phpunit.

You will need to change the test data inside tests/integration/data to reflect your Mediawiki installation URIs and appropriate expected data.  Seeing as Mementos vary from site to site, it was decided not to come up with a "one size fits all" integration test set.  Example test data exists for our demo site in the 'demo-wiki' directory.

**For more information on the integration tests and the test data format, consult the tests/integration/integration-test-description.html and tests/integration/how-to-read-output.txt files.  Detailed test output is generated in the build/test-output directory once the integration tests are run.**

Before running the tests you will need to set the following environment variables:
* TESTDATADIR - the data directory containing the datasets for your test run
* TESTUSERNAME - the username for logging into your mediawiki instance
* TESTPASSWORD - the password that goes with TESTUSERNAME

Because of all of the possible combinations of configuration options, the following Make targets are intended to test the following capabilities:

* defaults-integration-test - test an installation with the default settings

* 302-style-time-negotiation-integration-test - test only the 302-style Time Negotiation capability of the install

* friendly-error-with-302-style-integration-test - test the 302-style Time Negotiation error states with friendly output

Of course, the fastest development process is:

1. edit tests or change code, if necessary
2. make undeploy && make clean unit-test package deploy
3. run the integration test battery matching your deployment

# Code compliance verification

Running the code compliance requires phpcs.

This git repository uses and external repository for coding convention rules, so we can update the coding convention rules at any time.  The git command for performing the initial import is:

```
    git submodule update --init
```

To see if the code complies with Mediawiki's coding conventions, run:

```
    make verify
```
