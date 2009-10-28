<?php

// SOFTWARE NAME: eZ publish
// SOFTWARE RELEASE: 4.x
// BUILD VERSION: Objectrelationbrowse 3.0
// COPYRIGHT NOTICE: Copyright (C) 1999-2007 Contactivity bv (info@contactivity.com)
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

	include_once( 'autoload.php' );
	include_once( 'kernel/common/template.php' );
	


	$http = eZHTTPTool::instance();
	$Result = array();
	$Module = $Params['Module'];
	$action= $Module->currentView();
	$Result = array();

	$ini = eZINI::instance();
	$language = $ini->variable( 'RegionalSettings', 'ContentObjectLocale' );


	//SET DEFAULTS
	//$objectNameFilter="";
	$extendedAttributeFilter=false;
	$revSelectedObjectIDs=array();
	$revRemainingObjectIDs=array();
	$revRemainingObjectID=0;
	//$revEnabledObjectIDs=array();
	$onlyTranslated=true;
	$mainNodeOnly=true;
	$request_value = array();
	$result_value = array();
	$debug=false;
	$view="";
	$sortby=array( 'name' );
	$objectNameFilter=false;
	$class_filter_type="include";

	$class_filter_array=array( 'resource','article','file', 'profile');
	$currentUser = eZUser::currentUser();
	$roleListArray = $currentUser->roleIDList();
	$rss_node_array=array();
	$boolop="and";
	$list_items=array();
	$subNodeID = false;


	//GET PARAMETERS
	$topNodeID 		= $Params['TopNodeID'];
	$revTopNodeID 	= $Params['RevTopNodeID'];

	if ($http->hasVariable('SubNodeID')  )
	{
		$subNodeID=(int) $http->variable('SubNodeID');
	}
	else
	{
		$subNodeID = 2;
	}

	if (!$topNodeID)
		$topNodeID=$http->variable('TopNodeID');

	if (!$revTopNodeID)
		$revTopNodeID=$http->variable('RevTopNodeID');

	$debug=$http->variable('Debug');


	$phrase=$http->variable('Phrase');

	if ($http->hasVariable('SortBy')  )
	{
		$sortby=array( $http->variable('SortBy') );
	}

	if ($http->hasVariable('boolop')  )
	{
		$boolop=$http->variable('boolop');
	}



	$extendedAttributeFilter=array();
	$extendedAttributeFilter['id'] = "SQLFacetFilterParts";
	$extendedAttributeFilter['params']['bolean'] = $boolop;
	$extendedAttributeFilter['params']['subnode_id']=$subNodeID;

	if ($http->hasVariable('RevSelectedObjectIDs')  )
	{
		$objectIDs=$http->variable('RevSelectedObjectIDs');

		if ($boolop=="and")
		{
			foreach( $objectIDs as $objectID)
			{
				$revSelectedObjectIDs[]=$objectID;
				$extendedAttributeFilter['params']['object_id'][]=$objectID;
			}
		} else {
			$extendedAttributeFilter['params']['object_id'][]=$objectIDs;
		}
	}


	list($uCacheHandler, $uCachedContent) = eZTemplateCacheBlock::retrieve(
			array('extension/labforculture/modules/labforculture/ajaxfacetfilter',implode($roleListArray,"," ), $boolop, $phrase, $action, implode( $revSelectedObjectIDs,"," ) ), $topNodeID, 600
	);

	$ajaxnodeexpired = (get_class( $uCachedContent ) == 'ezclusterfilefailure' );


	if ( !$ajaxnodeexpired and 1==0 )
	{
		$view = $uCachedContent;
	}
	else
	{

		//BUILD REQUEST XML

		$request_value[] = "<request>";
		$request_value[] = "<topNodeID>".$topNodeID."</topNodeID>";
		$request_value[] = "<revTopNodeID>".$revTopNodeID."</revTopNodeID>";
		$request_value[] = "<phrase>".$phrase."</phrase>";

		if (count($revSelectedObjectIDs)>0)
		{
			$request_value[] = "<revSelectedObjectIDs>";
			foreach ($revSelectedObjectIDs as $revSelectedObjectID)
			{
				$request_value[] = "<objectID>".$revSelectedObjectID."</objectID>";
			}
			$request_value[] = "</revSelectedObjectIDs>";
		}
		else
		{
			$request_value[] = "<revSelectedObjectIDs/>";
		}
		$request_value[] = "</request>";

		//BUILD RESULT XML
		if ( ( $topNodeID && $revTopNodeID) )
		{
			//include_once( 'kernel/classes/ezcontentobjecttreenode.php' );
			if ($phrase)
				$objectNameFilter = "%".mysql_escape_string($phrase);
				$treeParameters = array(  'OnlyTranslated' => $onlyTranslated,
								 'AsObject' => true,
								 'Depth' => 5,
								 'LoadDataMap' => false,
								 'Limitation' => array(),
								 'SortBy' => array( $sortby, false),
								  'ExtendedAttributeFilter' => $extendedAttributeFilter,
								 'IgnoreVisibility' => false,
								 'ClassFilterType' => $class_filter_type,
								 'ClassFilterArray' => $class_filter_array,
								 'ObjectNameFilter' => $objectNameFilter,
								 'MainNodeOnly' => $mainNodeOnly );

			$children = eZContentObjectTreeNode::subTreeByNodeID( $treeParameters, $topNodeID );
			
			$result_value[]="<result>";

			if (count($children) > 0)
			{
				$result_value[]="<numRemainingNodes>".count($children)."</numRemainingNodes>";
				$result_value[]="<revRemainingObjectIDs>";

				foreach ($children as $child) {

					$objectID = $child->attribute('contentobject_id');
					$object = eZContentObject::fetch( $objectID );
					$versionID = $object->attribute( 'current_version');


					$normalRelatedArray = $object->relatedContentObjectArray( $versionID, $objectID, 0, array( 'AllRelations' => true ) );

					if ($debug)
					{
						$result_value[]="<RemainingNode>";
						$result_value[]="<Name><![CDATA[".$object->attribute('name')."]]></Name>";
						$result_value[]="<ID>".$object->attribute('main_node_id')."</ID>";
						$result_value[]="<NumRevRelated>".count($normalRelatedArray)."</NumRevRelated>";
						$result_value[]="</RemainingNode>";
					}

					foreach ( $normalRelatedArray as $normalRelated)
					{
						$revRemainingObjectID = $normalRelated->attribute('id');
						if ($debug)
						{
								$result_value[]="<objectName><![CDATA[".$normalRelated->attribute('name')."]]></objectName>";
								$result_value[]="<objectID>".$revRemainingObjectID."</objectID>";
						}

						if (!in_array($revRemainingObjectID,$revRemainingObjectIDs))
						{
							$result_value[]="<objectID>".$revRemainingObjectID."</objectID>";
							$result_value[]="<objectPublished>".$object->attribute('published')."</objectPublished>";

							if ( $action=="filteredrss" )
							{
								$index=$object->attribute('published');
								$rss_node_array[$index]=$object->attribute('main_node');
							}

							if ( $action=="filteredpeople" )
							{
								$index=$object->attribute('name');
								$list_items[$index]=$object->attribute('main_node');
							}

							$revRemainingObjectIDs[]=$revRemainingObjectID;
						}
					}

				}
				$result_value[]="</revRemainingObjectIDs>";
			}
			else
			{
				$result_value[]="<numRemainingNodes>0</numRemainingNodes>";
				$result_value[]="<revRemainingObjectIDs/>";
			}
			$result_value[]="</result>";

		}
		else
		{
			$result_value[] = "<error>missing parameters</error>";  //nodeID or keyword parameters empty
		}


			$view = "<"."?xml version=\"1.0\" encoding=\"UTF-8\"?".">";
			$view.="<response>";
			$view.= implode("\n", $request_value);
			$view.= implode("\n", $result_value);
			$view.= "</response>";

		$uCacheHandler->storeCache( array('scope' => $roleList, 'binarydata' => $view, 'action' => $action ));
	}

	$Result["pagelayout"] = false;
	$httpCharset = eZTextCodec::httpCharset();
	header( 'Content-Type: text/xml; charset=' . $httpCharset );
	header( 'Cache-Control: max-age=86400, private' );
	header( 'Content-Length: '.strlen($view) );
	header( 'X-Powered-By: Contactivity bv' );
	while ( @ob_end_clean() );

	echo $view;

	eZExecution::cleanExit();



?>
