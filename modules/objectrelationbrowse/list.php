<?php
// SOFTWARE NAME: eZ publish
// SOFTWARE RELEASE: 4.x
// BUILD VERSION: Objectrelationbrowse 3.0
// SOFTWARE LICENSE: GNU General Public License v2.0
// NOTICE: >
//   This program is free software; you can redistribute it and/or
//   modify it under the terms of version 2.0  of the GNU General
//   Public License as published by the Free Software Foundation.
//
//   This program is distributed in the hope that it will be useful,
//   but WITHOUT ANY WARRANTY; without even the implied warranty of
//   MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
//   GNU General Public License for more details.
//
//   You should have received a copy of version 2.0 of the GNU General
//   Public License along with this program; if not, write to the Free
//   Software Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston,
//   MA 02110-1301, USA.
//
//
// ## END COPYRIGHT, LICENSE AND WARRANTY NOTICE ##
//
// 1999-2008 Contactivity bv (info@contactivity.com)

include_once( 'kernel/content/ezcontentfunctioncollection.php' );

$http =& eZHTTPTool::instance();
$Result = array();
$Result["pagelayout"] = false;
$ini =& eZINI::instance();
$Module =& $Params['Module'];
$moduleINI =& eZINI::instance( 'module.ini' );
$return_value = array();
$return_value[] = "<?xml version='1.0' encoding='UTF-8'?><objects>";
$language = $ini->variable( 'RegionalSettings', 'ContentObjectLocale' );
$class_filter_array=array();
$objectNameFilter="";
$extended_attribute_filter=false;
$onlyTranslated=true;


//CONTENT CLASS
$limit = $Params['Limit'];
$phrase = $Params['Phrase'];
$objectNameFilter = "%".mysql_escape_string($phrase);
$attributeID = $Params['AttributeID'];
if ( !$attributeID OR !is_numeric( $attributeID ) )
    $attributeID = 0;
$classAttribute =& eZContentClassAttribute::fetch( $attributeID );
if ($classAttribute )
{
	$class_content = $classAttribute->content();
	$class_filter_array = $class_content['class_constraint_list'];
	$parentNodeID = $class_content['default_selection']['node_id'];
	if (!$parentNodeID) $parentNodeID=1;
}


//only query the database if search term(s) and nodeID have been passed
if ( ( $objectNameFilter AND $classAttribute ) )
{
	$phrase = addslashes ( $phrase );
  	include_once( 'kernel/classes/ezcontentobjecttreenode.php' );
	$treeParameters = array(  'OnlyTranslated' => $onlyTranslated,
						 'Language' => $language,
						 'AsObject' => true,
						 'LoadDataMap' => false,
						 'Limit' => $limit,
						 'SortBy' => array( 'name' ),
						 'ExtendedAttributeFilter' => $extended_attribute_filter,
						 'ClassFilterType' => 'include',
						 'ClassFilterArray' => $class_filter_array,
						 'IgnoreVisibility' => false,
						 'ObjectNameFilter' => $objectNameFilter,
						 'MainNodeOnly' => true );
	$children = eZContentObjectTreeNode::subTreeByNodeID( $treeParameters,$parentNodeID );
	//print_r($children);
  	$count = count($children);
 	$return_value[] = "<count>".$count."</count>";

  	if ($count > 0) {
  		foreach ($children as $child) {
  			$return_value[]="<ezobject><objectid>".$child->attribute('contentobject_id')."</objectid><objectname><![CDATA[". str_replace("", "", str_replace("\"", "'", $child->attribute('name') ) )."]]></objectname><nodeid>".$child->attribute('main_node_id')."</nodeid></ezobject>";
  		}
  	}

  	mysql_free_result($result);
}
else
{
	$return_value[] = "<attribute_id>".$attributeID ."</attribute_id><phrase>".$phrase."</phrase><count>0</count><ezobject/>";  //nodeID or keyword parameters empty
}
$return_value[] = "</objects>";
header('Content-Type: text/xml');
echo implode("", $return_value);
eZExecution::cleanExit();
?>

