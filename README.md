# criticalpath
Simple Critical Path implementation in PHP and JS

This is part of another project, which I thought could help others too.
Both cpath.js and cpath.php have no dependencies.
Usage example is shown at the end of the code.

When adding activities, make sure there is only ONE activity that has no precedessors 
and ONLY ONE activity that has no successors (i.e. the activity diagram starts and ends only once)
