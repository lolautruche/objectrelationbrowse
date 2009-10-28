
Object Relation Browse Datatype
-------------------------------

/*
    Object Relation Browse extension for eZ publish 4.x
    Developed by Contactivity bv, Leiden the Netherlands
    http://www.contactivity.com, info@contactivity.com
    

    This file may be distributed and/or modified under the terms of the
    GNU General Public License" version 2 as published by the Free
    Software Foundation and appearing in the file LICENSE.GPL included in
    the packaging of this file.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.
    
    The "GNU General Public License" (GPL) is available at
    http://www.gnu.org/copyleft/gpl.html.
    
    
*/


1. Context
----------
One of the most powerful features of eZ publish is the option to create relationships between many kinds of objects. However, the standard 'browse' method used to relate objects proved to be too cumbersome when working with a large number of objects (>100.000) and many relations (>10) per object.


We needed a datatype that would:
- display all available objects for relation in a single list;
- provide functionality to dynamically filter objects from the list on basis of the name of the object and wildcards;
- allow the selection of multiple objects from a list in a single "browse & select" action; 
- store the object relation between objects, so that it would become possible to look up reverse relationships.


2. Features
-----------
We have created the objectrelationbrowse datatype to:
- display all available objects in a single, dynamically filterable list (see:dynamic_list.png). 

The HTML code for dynamic filtering has been developed together with *- pike, pike@labforculture.org.

- handle a one to one or one to many relationships; and
- store the object relation between objects so a reverse object relation becomes available.

Moreover,
- instead of the standard browse or the dynamic list, it is also possible to display lists as list boxes, dropdowns or checkboxes (see: class_edit.png).


3. Example of use
-----------------
For an online library project we used the datatype to manage relationships between authors (>35.000 objects) and publications (>20.000 objects), publications and topics (>20.000 terms), users and transactions. 


4. Settings
-----------
a. Type: Allow user to add 'only new objects', 'only existing objects' or 'new and existing objects'. If 'only new objects' is selected, the content/browse option is disabled. If 'only existing objects' is selected, the "Create new object" option is disabled.

b. Existing Objects
-	Selection method: indicates which browse method to use to search for related objects. 'Browse' is the standard eZ publish method for browsing, 'Dynamic list' uses Ajax. Other options are 'dropdown', 'multiple selection list' and 'list with checkboxes' and 'list with radiobuttons'.
-	Depth: used to control the number of 'levels' displayed in the dropdown/listboxes;
-	Default selection node: indicates where the user starts browsing for related objects, or the node from which a dynamic list will be created.
-	Selectable content classes: indicates the type(s) (class) of content objects the user may add as related items

c. New Objects
-	Createable content classes: Select the type (class) of content objects the user may create as related items.
-	Default location for new objects: indicates where newly created objects will be placed in the content tree. If no default node has been specified, newly created objects will not appear in the content tree. 

d. Allow inline edit 
-	indicates if the user should be allowed to edit the related objects. 


5. Known bugs, limitations, etc.
-------------------------------
The datatype has the following limitations and bugs:
- Information collection functionality is not supported;
 

6. Feedback
--------------------------------
Please send all remarks, comments and suggestions for improvement to info@contactivity.com.


7. Disclaimer
-------------------------
This program is free software; you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation; either version 2 of the License, or (at your option) any later version.

This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU General Public License for more details.