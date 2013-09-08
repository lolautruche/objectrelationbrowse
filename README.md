# ObjectRelationBrowse

The main purpose of the objectrelationbrowse datatype is to make it possible to create object relations using a variety of list types (AJAX, listbox, list of checkboxes, dropdown, etc.). This release supports an AJAX interface embedded in the edit form.

**This repository is a fork of the [original extension](https://code.google.com/p/objectrelationbrowse/), which is not maintained any more**


## Description
One of the most powerful features of eZ publish is the option to create relationships between many kinds of objects. However, the standard 'browse' method used to relate objects proved to be too cumbersome when working with a large number of objects (>100.000) and many relations (>10) per object.

We needed a datatype that would:

* Display all available objects for relation in a single list;
* Provide functionality to dynamically filter objects from the list on basis of the name of the object and wildcards;
* Allow the selection of multiple objects from a list in a single "browse & select" action; 
* Store the object relation between objects, so that it would become possible to look up reverse relationships.

## Features

* Display all available objects in a single, dynamically filterable list;
* Handle a one to one or one to many relationships;
* Store the object relation between objects so a reverse object relation becomes available;
* Instead of the standard browse or the dynamic list, it is also possible to display lists as list boxes, dropdowns or checkboxes.

## Credits

Original credits go to [Sebastiaan Van der Vliet](https://github.com/fumaggo) 
who originally designed this extension.
