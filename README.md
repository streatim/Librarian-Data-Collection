# Librarian-Data-Collection
A copy of the Mardigian Library's REC (Research Education Committee) Data forms and dashboard. 
Requires: PHP 7.1 or higher
Notes: 
- A .png of the basic Database Structure used by the Mardigian is provided. This structure doesn't include all databases called on - for example, any calls to the Librarian often call out to our staff table (with exceptions, the ML_LRC.HistoricalUsers table are historical users who are no longer in the Staff Table). 
- The table LibGuideUseData is an imperfect (and in progress) addition; the hope is to upload LibGuide usage reports and automatically calculate use per course, but that is currently in progress and is presently unconnected from the rest of the relational database.
- BridgeCourseProgram ties the CourseID to a ProgramID as determined by our Program table (not part of this relational database because it's sourced from a different one).
- CSS for the HTML forms relies on external libraries like Foundation Framework, Font Awesome Icons, and some localized stuff. As much as possible I tried to include what was used, but couldn't account for everything. The HTML will most likely need to be revised.  