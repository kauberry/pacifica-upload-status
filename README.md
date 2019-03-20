# MyEMSL Uploader Status Site
[![Build Status](https://travis-ci.org/EMSL-MSC/pacifica-upload-status.svg?branch=master)](https://travis-ci.org/EMSL-MSC/pacifica-upload-status)

## Overview
This site will provide a conduit for lots of different types of researchers to
determine the status of their data within the MyEMSL system. Division directors
will probably want to see a different, higher level view of system operations
(see the [pacifica-reporting](https://github.com/EMSL-MSC/pacifica-reporting)
submodule), while an instrument operator will most likely care about the
particular instrument(s) in their charge.

These views will likely include...

#### Site-level overviews for EMSL Management
* 30,000 ft view aggregated over classes like instrument type, facilities, researchers, projects, etc.
* Fewer details immediately visible, but still available


#### Facility-level views for individual program managers and capability leads
* Collections of instruments that can be personalized by user id or client-side cookie
* More details available immediately, with breakdowns within a given instrument at the project and operator level


#### Instrument-level views for custodians and operators
* Details for a given instrument that include a date-selectable range of
  activity (i.e. what has happened on that instrument over a given period of
  time) that shows files uploaded and various metadata about those files
* Lots of detail available, with extra detail on jobs that are still in
  progress for upload


#### A specialized set of views for EMSL Users (external and internal)
* Probably some combination of the above views, with a more limited access scope


The development version of the site is hosted (internal to PNNL) at
[https://dev1.my.emsl.pnl.gov/myemsl/status/index.php/status/overview](https://dev1.my.emsl.pnl.gov/myemsl/status/index.php/status/overview)
