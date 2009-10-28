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


include_once( 'kernel/classes/ezdatatype.php' );
include_once( 'kernel/classes/ezcontentobject.php' );
include_once( 'lib/ezutils/classes/ezintegervalidator.php' );
include_once( 'lib/ezutils/classes/ezinputvalidator.php' );
include_once( 'lib/ezi18n/classes/eztranslatormanager.php' );
include_once( 'lib/ezxml/classes/ezxml.php' );


class eZObjectRelationBrowseType extends eZDataType
{
     const DATA_TYPE_STRING = "ezobjectrelationbrowse";

    /*!
     Initializes with a string id and a description.
    */
    function eZObjectRelationBrowseType()
    {
        $this->eZDataType( self::DATA_TYPE_STRING, ezi18n( 'kernel/classes/datatypes', "Object relation browse", 'Datatype name' ),
                           array( 'serialize_supported' => true ) );
    }

    /*!
     Validates the input and returns true if the input was
     valid for this datatype.
    */
    function validateObjectAttributeHTTPInput( $http, $base, $contentObjectAttribute )
    {
       	$inputParameters = $contentObjectAttribute->inputParameters();
        $contentClassAttribute = $contentObjectAttribute->contentClassAttribute();
        $parameters = $contentObjectAttribute->validationParameters();
        if ( isset( $parameters['prefix-name'] ) and
             $parameters['prefix-name'] )
            $parameters['prefix-name'][] = $contentClassAttribute->attribute( 'name' );
        else
            $parameters['prefix-name'] = array( $contentClassAttribute->attribute( 'name' ) );

        $status = eZInputValidator::STATE_ACCEPTED;
		$postVariableName = $base . "_data_object_relation_browse_" . $contentObjectAttribute->attribute( "id" );
		$contentClassAttribute = $contentObjectAttribute->contentClassAttribute();
		$classContent = $contentClassAttribute->content();
		// Check if selection type is not browse
		/*
		if ( $classContent['selection_type'] != 0 )
		{
			$selectedObjectIDArray = $http->hasPostVariable( $postVariableName ) ? $http->postVariable( $postVariableName ) : false;
			if ( $contentObjectAttribute->validateIsRequired() and $selectedObjectIDArray === false )
			{
				$contentObjectAttribute->setValidationError( ezi18n( 'kernel/classes/datatypes',
																	 'Missing objectrelation browse input.' ) );
				return eZInputValidator::STATE_INVALID;
			}
			return $status;
        }
        */

        $content = $contentObjectAttribute->content();
        if ( $contentObjectAttribute->validateIsRequired() and count( $content['relation_browse'] ) == 0 )
		{
			$contentObjectAttribute->setValidationError( ezi18n( 'kernel/classes/datatypes',
																 'Missing objectrelation browse input.' ) );
			return eZInputValidator::STATE_INVALID;
        }


        for ( $i = 0; $i < count( $content['relation_browse'] ); ++$i )
        {
            $relationItem = $content['relation_browse'][$i];
            if ( $relationItem['is_modified'] )
            {
                $subObjectID = $relationItem['contentobject_id'];
                $subObjectVersion = $relationItem['contentobject_version'];
                $attributeBase = $base . '_ezorl_edit_object_' . $subObjectID;
                $object = eZContentObject::fetch( $subObjectID );
                if ( $object )
                {
                    $attributes = $object->contentObjectAttributes( true, $subObjectVersion );
                    $validationResult = $object->validateInput( $attributes, $attributeBase,
                                                                $inputParameters, $parameters );
                    $inputValidated = $validationResult['input-validated'];
                    $content['temp'][$subObjectID]['require-fixup'] = $validationResult['require-fixup'];
                    $statusMap = $validationResult['status-map'];
                    foreach ( $statusMap as $statusItem )
                    {
                        $statusValue = $statusItem['value'];
                        if ( $statusValue == eZInputValidator::STATE_INTERMEDIATE and
                             $status == eZInputValidator::STATE_ACCEPTED  )
                            $status = eZInputValidator::STATE_INTERMEDIATE;
                        else if ( $statusValue == eZInputValidator::STATE_INVALID )
                        {
                            $contentObjectAttribute->setHasValidationError( false );
                            $status = eZInputValidator::STATE_INVALID;
                        }
                    }

                    $content['temp'][$subObjectID]['attributes'] = $attributes;
                    $content['temp'][$subObjectID]['object'] = $object;
                }
            }
        }
        $contentObjectAttribute->setContent( $content );
        $contentObjectAttribute->store();
        return $status;
    }

    /*!
     Validates the input and returns true if the input was
     valid for this datatype.
    */
    function fixupObjectAttributeHTTPInput( $http, $base, $contentObjectAttribute )
    {
        $content = $contentObjectAttribute->content();
        for ( $i = 0; $i < count( $content['relation_browse'] ); ++$i )
        {
            $relationItem = $content['relation_browse'][$i];
			if ( $relationItem['is_modified'] )
			{
				$subObjectID = $relationItem['contentobject_id'];
				$attributeBase = $base . '_ezorl_edit_object_' . $subObjectID;
				$object = $content['temp'][$subObjectID]['object'];
				$requireFixup = $content['temp'][$subObjectID]['require-fixup'];
				if ( $object and
					 $requireFixup )
				{
					$attributes = $content['temp'][$subObjectID]['attributes'];
					$object->fixupInput( $attributes, $attributeBase );
				}
            }
        }
    }

    /*!
     Fetches the http post var string input and stores it in the data instance.
    */
    function fetchObjectAttributeHTTPInput( $http, $base, $contentObjectAttribute )
	{

		$content = $contentObjectAttribute->content();
		$ContentObjectID = $contentObjectAttribute->attribute( 'contentobject_id' );
		$ObjectVersion = $contentObjectAttribute->attribute( 'version' );
		$object = eZContentObject::fetch( $ContentObjectID );
		$contentClassAttributeID = $contentObjectAttribute->ContentClassAttributeID;
		$contentObjectAttributeID = $contentObjectAttribute->attribute( 'id' );
		$priorityBase = $base . '_priority';
		$priorities = array();
		if ( $http->hasPostVariable( $priorityBase ) )
			$priorities = $http->postVariable( $priorityBase );
		$reorderedRelationList = array();
		// Contains existing priorities
        $existsPriorities = array();


        //Only update the object when the customActionButton 'update priorities' has been clicked.
		if ($http->hasPostVariable( "CustomActionButton") )
		{
			   $modifiedArray=array_keys($http->postVariable( "CustomActionButton"));
			   if ( !$modifiedArray[]=strstr($modifiedArray[0],'priorities') AND !$modifiedArray[]=strstr($modifiedArray[0],'edit') )
					 return false;
		}

		if ( $http->hasPostVariable( "SelectionType_".$contentObjectAttribute->attribute( "id" ) ) )
	    {
			$selectedObjectIDArray=array();
			if ( $http->hasPostVariable( "SelectedObjectIDArray_".$contentObjectAttribute->attribute( "id" ) ) )
			{
				$selectedObjectIDArray = $http->postVariable( "SelectedObjectIDArray_".$contentObjectAttribute->attribute( "id" ) );
			}

			if ($selectedObjectIDArray)
			{
				$priority = 0;
				for ( $i = 0; $i < count( $content['relation_browse'] ); ++$i )
				{
					if ( $content['relation_browse'][$i]['priority'] > $priority )
						$priority = $content['relation_browse'][$i]['priority'];
				}

				$relationList = $content['relation_browse'];
				$newRelationList = array();
				$objectIDList = array();

				//remove the items that are not in the new selected object array
				for ( $i = 0; $i < count( $relationList ); ++$i )
				{
					$relationItem = $relationList[$i];
					if ( !in_array( $relationItem['contentobject_id'], $selectedObjectIDArray ) )
					{
						eZObjectRelationBrowseType::removeRelationObject( $contentObjectAttribute, $relationItem );
						$object->removeContentObjectRelation( $relationItem['contentobject_id'], $ObjectVersion);
					} else {
						$objectIDList[] = $relationItem['contentobject_id'];
					}

				}

				$content['relation_browse'] = $newRelationList;


				//then add the new items from the selected object array
				foreach ( $selectedObjectIDArray as $objectID )
				{
					$testObject = eZContentObject::fetch( $objectID );
					if ($testObject)
					{
						if ( !in_array( $objectID, $objectIDList ) )
						++$priority;
						$content['relation_browse'][] = $this->appendObject( $objectID, $priority, $contentObjectAttribute );

					}
				}
				$contentObjectAttribute->setContent( $content );
				$contentObjectAttribute->store();
				return true;
			}


			//process existing relations;
			$contentObjectAttributeID = $contentObjectAttribute->attribute( 'id' );
			$priorityBase = $base . '_priority';
			$priorities = array();
			if ( $http->hasPostVariable( $priorityBase ) )
				$priorities = $http->postVariable( $priorityBase );
			$reorderedRelationList = array();
			// Contains existing priorities
			$existsPriorities = array();

			for ( $i = 0; $i < count( $content['relation_browse'] ); ++$i )
			{
				if (isset($priorities[$contentObjectAttributeID][$i]))
				{
					$priorities[$contentObjectAttributeID][$i] = (int) $priorities[$contentObjectAttributeID][$i];
					$existsPriorities[$i] = $priorities[$contentObjectAttributeID][$i];
					// Change objects' priorities providing their uniqueness.
					for ( $j = 0; $j < count( $content['relation_browse'] ); ++$j )
					{
						if ( $i == $j ) continue;
						if ( isset($priorities[$contentObjectAttributeID][$i]) && isset($priorities[$contentObjectAttributeID][$j]) )
						{
							if ( $priorities[$contentObjectAttributeID][$i] == $priorities[$contentObjectAttributeID][$j] )
							{
								$index = $priorities[$contentObjectAttributeID][$i];
								while ( in_array( $index, $existsPriorities ) )
									++$index;
								$priorities[$contentObjectAttributeID][$j] = $index;
							}
						}
					}
				}
				$relationItem = $content['relation_browse'][$i];
				if ( $relationItem['is_modified'] )
				{
					$subObjectID = $relationItem['contentobject_id'];
					$subObjectVersion = $relationItem['contentobject_version'];
					$attributeBase = $base . '_ezorl_edit_object_' . $subObjectID;
					$object = $content['temp'][$subObjectID]['object'];
					//$is_object= eZContentObject::fetch( $subObjectID );
					if ( $object )
               		{
						$attributes = $content['temp'][$subObjectID]['attributes'];

						$customActionAttributeArray = array();
						$fetchResult = $object->fetchInput( $attributes, $attributeBase,
															$customActionAttributeArray,
															$contentObjectAttribute->inputParameters() );

						$content['temp'][$subObjectID]['attribute-input-map'] = $fetchResult['attribute-input-map'];
						$content['temp'][$subObjectID]['attributes'] = $attributes;
                    	$content['temp'][$subObjectID]['object'] = $object;
					}
				}
				if ( isset( $priorities[$contentObjectAttributeID][$i] ) )
					$relationItem['priority'] = $priorities[$contentObjectAttributeID][$i];
				$reorderedRelationList[$relationItem['priority']] = $relationItem;
			}
			ksort( $reorderedRelationList );
			unset( $content['relation_browse'] );
			$content['relation_browse'] = array();
			reset( $reorderedRelationList );
			$i = 0;
			$j = 0;
			while ( list( $key, $relationItem ) = each( $reorderedRelationList ) )
			{
				$content['relation_browse'][] = $relationItem;
				$subObjectID = $relationItem['contentobject_id'];
				$object =  eZContentObject::fetch( $subObjectID );
				if ( isset($object) )
				{
					$content['relation_browse'][$i]['priority'] = $j + 1;
					$j++;
				}
				else
				{
				   eZObjectRelationBrowseType::removeRelationObject( $contentObjectAttribute, $relationItem );
				   eZObjectRelationBrowseType::removeRelatedObjectItem( $contentObjectAttribute, $subObjectID );
            	}
				++$i;
			}
			$contentObjectAttribute->setContent( $content );
			$contentObjectAttribute->store();
			return true;
		}
    }


    function createNewObject( $contentObjectAttribute, $name )
	{
		$classAttribute = $contentObjectAttribute->attribute( 'contentclass_attribute' );
		$classContent = $classAttribute->content();
		$classID = $classContent['object_class'];
		if ( !isset( $classID ) or !is_numeric( $classID ) )
			return false;

		$defaultPlacementNode = ( is_array( $classContent['default_placement'] ) and isset( $classContent['default_placement']['node_id'] ) ) ? $classContent['default_placement']['node_id'] : false;
		if ( !$defaultPlacementNode )
		{
			eZDebug::writeError( 'Default placement is missing', 'eZObjectRelationBrowseType::createNewObject' );
			return false;
		}

		$node = eZContentObjectTreeNode::fetch( $defaultPlacementNode );
		// Check if current user can create a new node as child of this node.
		if ( !$node or !$node->canCreate() )
		{
			eZDebug::writeError( 'Default placement is wrong or the current user can\'t create a new node as child of this node.', 'eZObjectRelationBrowseType::createNewObject' );
			return false;
		}

		$classList = $node->canCreateClassList( false );
		$canCreate = false;
		// Check if current user can create object of class (with $classID)
		foreach ( $classList as $class )
		{
			if ( $class['id'] == $classID )
			{
				$canCreate = true;
				break;
			}
		}
		if ( !$canCreate )
		{
			eZDebug::writeError( 'The current user is not allowed to create objects of class (ID=' . $classID . ')', 'eZObjectRelationBrowseType::createNewObject' );
			return false;
		}

		$class = eZContentClass::fetch( $classID );
		if ( !$class )
			return false;

		$currentObject = $contentObjectAttribute->attribute( 'object' );
		$sectionID = $currentObject->attribute( 'section_id' );
		//instantiate object, same section, currentuser as owner (i.e. provide false as param)
		$newObjectInstance = $class->instantiate( false, $sectionID );
		$nodeassignment = $newObjectInstance->createNodeAssignment( $defaultPlacementNode, true );
		$nodeassignment->store();
		$newObjectInstance->sync();
		include_once( "lib/ezutils/classes/ezoperationhandler.php" );
		$operationResult = eZOperationHandler::execute( 'content', 'publish', array( 'object_id' => $newObjectInstance->attribute( 'id' ), 'version' => 1 ) );
		// so it updates the attributes
		$newObjectInstance->rename( $name );

		return $newObjectInstance->attribute( 'id' );
    }

    /*!
    */
    function storeObjectAttribute( $attribute )
    {
        $content = $attribute->content();
		if ( isset( $content['new_object'] ) )
		{
			$newID = $this->createNewObject( $attribute, $content['new_object'] );
			// if this is a single element selection mode (radio or dropdown), then the newly created item is the only one selected
			if ( $newID )
			{
				$content['relation_browse'] = array();
				$content['relation_browse'][] = $this->appendObject( $newID, 0, $attribute );
			}
			unset( $content['new_object'] );
			$attribute->setContent( $content );
			$attribute->store();
        }

        $contentClassAttributeID = $attribute->ContentClassAttributeID;
		$contentObjectID = $attribute->ContentObjectID;
        $contentObjectVersion = $attribute->Version;

        $obj = $attribute->object();
		//get eZContentObjectVersion
		$currVerobj = $obj->version( $contentObjectVersion );

		// create translation List
		// $translationList will contain for example eng-GB, ita-IT etc.
		$translationList = $currVerobj->translations( false );

		// get current language_code
		$langCode = $attribute->attribute( 'language_code' );
		// get count of LanguageCode in translationList
		$countTsl = count( $translationList );
		// order by asc
		sort( $translationList );

		if ( ( $countTsl == 1 ) or ( $countTsl > 1 and $translationList[0] == $langCode ) )
		{
             eZContentObject::fetch( $contentObjectID )->removeContentObjectRelation( false, $contentObjectVersion, $contentClassAttributeID, eZContentObject::RELATION_ATTRIBUTE );
        }

        foreach( $content['relation_browse'] as $relationItem )
        {
            // Installing content object, postUnserialize is not called yet,
			// so object's ID is unknown.
			if ( !$relationItem['contentobject_id'] || !isset( $relationItem['contentobject_id'] ) )
                continue;

            $subObjectID = $relationItem['contentobject_id'];
			$subObjectVersion = $relationItem['contentobject_version'];

            eZContentObject::fetch( $contentObjectID )->addContentObjectRelation( $subObjectID, $contentObjectVersion, $contentClassAttributeID, eZContentObject::RELATION_ATTRIBUTE );

            if ( $relationItem['is_modified'] )
            {
                // handling sub-objects
				$object = $content['temp'][$subObjectID]['object'];
				if ( isset($object) && isset($content['temp'][$subObjectID]) )
				{
					$attributes = $content['temp'][$subObjectID]['attributes'];
					if (isset($content['temp'][$subObjectID]['attribute-input-map']))
					{
						$attributeInputMap = $content['temp'][$subObjectID]['attribute-input-map'];
						$object->storeInput( $attributes, $attributeInputMap );
						$version = eZContentObjectVersion::fetchVersion( $subObjectVersion, $subObjectID );
						if ( $version )
						{
							$version->setAttribute( 'modified', time() );
							$version->setAttribute( 'status', eZContentObjectVersion::STATUS_DRAFT );
							$version->store();
						}

						$object->setAttribute( 'status', eZContentObject::STATUS_DRAFT );
						$object->store();
					}
				}
				else
				{
					eZContentObject::fetch( $contentObjectID )->removeContentObjectRelation( false, $contentObjectVersion, $contentClassAttributeID, eZContentObject::RELATION_ATTRIBUTE );
				}
            }
        }
        return eZObjectRelationBrowseType::storeObjectAttributeContent( $attribute, $content );
    }

    /*!
     \reimp
    */
    function onPublish( $contentObjectAttribute, $contentObject, $publishedNodes )
    {
        $content = $contentObjectAttribute->content();
         foreach( $content['relation_browse'] as $key => $relationItem )
        {
            if ( $relationItem['is_modified'] )
            {
                $subObjectID = $relationItem['contentobject_id'];
                $subObjectVersion = $relationItem['contentobject_version'];
                $object = eZContentObject::fetch( $subObjectID );

                if ( $object )
                {
                    $class = $object->contentClass();
					$time = time();

					// Make the previous version archived
					$currentVersion = $object->currentVersion();
					$currentVersion->setAttribute( 'status', eZContentObjectVersion::STATUS_ARCHIVED );
					$currentVersion->setAttribute( 'modified', $time );
                    $currentVersion->store();

                    $version = eZContentObjectVersion::fetchVersion( $subObjectVersion, $subObjectID );
                    $version->setAttribute( 'modified', $time );
                    $version->setAttribute( 'status', eZContentObjectVersion::STATUS_PUBLISHED );
                    $version->store();
                    $object->setAttribute( 'status', eZContentObject::STATUS_PUBLISHED );
                    if ( !$object->attribute( 'published' ) )
                        $object->setAttribute( 'published', $time );
                    $object->setAttribute( 'modified', $time );
                    $object->setAttribute( 'current_version', $version->attribute( 'version' ) );
                    $object->setAttribute( 'is_published', true );
                    $objectName = $class->contentObjectName( $object, $version->attribute( 'version' ) );
                    $object->setName( $objectName, $version->attribute( 'version' ) );
                    $object->store();
                }
                if ( $relationItem['parent_node_id'] > 0 )
                {
                    if ( !eZNodeAssignment::fetch( $object->attribute( 'id' ), $object->attribute( 'current_version' ), $relationItem['parent_node_id'], false ) )
                    {
						$nodeAssignment = eZNodeAssignment::create( array( 'contentobject_id' => $object->attribute( 'id' ),
																		   'contentobject_version' => $object->attribute( 'current_version' ),
																		   'parent_node' => $relationItem['parent_node_id'],
																		   'sort_field' => 2,
																		   'sort_order' => 0,
																		   'is_main' => 1 ) );
						$nodeAssignment->store();
					}
                    $operationResult = eZOperationHandler::execute( 'content', 'publish', array( 'object_id' => $object->attribute( 'id' ),
                                                                                                 'version' => $object->attribute( 'current_version' ) ) );
                    $objectNodeID = $object->attribute( 'main_node_id' );
                    $content['relation_browse'][$key]['node_id'] = $objectNodeID;
                }
                else
                {
                    if ( !eZNodeAssignment::fetch( $object->attribute( 'id' ), $object->attribute( 'current_version' ), $contentObject->attribute( 'main_node_id' ), false ) )
                    {
						$nodeAssignment = eZNodeAssignment::create( array( 'contentobject_id' => $object->attribute( 'id' ),
																			'contentobject_version' => $object->attribute( 'current_version' ),
																			'parent_node' => $contentObject->attribute( 'main_node_id' ),
																			'sort_field' => 2,
																			'sort_order' => 0,
																			'is_main' => 1 ) );
						$nodeAssignment->store();
					}
                }
                $content['relation_browse'][$key]['is_modified'] = false;
            }
        }
        eZObjectRelationBrowseType::storeObjectAttributeContent( $contentObjectAttribute, $content );
        $contentObjectAttribute->setContent( $content );
        $contentObjectAttribute->store();
    }

    function initializeObjectAttribute( $contentObjectAttribute, $currentVersion, $originalContentObjectAttribute )
	{

		static $copiedRelatedAccordance;
		if ( !isset( $copiedRelatedAccordance ) )
			$copiedRelatedAccordance = array();

		if ( $currentVersion != false )
		{
			$dataText = $originalContentObjectAttribute->attribute( 'data_text' );
			$contentObjectAttribute->setAttribute( 'data_text', $dataText );
			$contentObjectID = $contentObjectAttribute->attribute( 'contentobject_id' );
			$originalContentObjectID = $originalContentObjectAttribute->attribute( 'contentobject_id' );

			if ( $contentObjectID != $originalContentObjectID )
			{
				$classContent = eZObjectRelationBrowseType::defaultClassAttributeContent();
				if ( !$classContent['default_placement'] )
				{
					$content = $originalContentObjectAttribute->content();
					$contentModified = false;

					foreach ( $content['relation_browse'] as $key => $relationItem )
					{
						// create related object copies only if they are subobjects
						$object = eZContentObject::fetch( $relationItem['contentobject_id'] );
						$mainNode = $object->attribute( 'main_node' );

						if ( is_object( $mainNode ) )
						{
							$node = ( is_numeric( $relationItem['node_id'] ) and $relationItem['node_id'] ) ?
									  eZContentObjectTreeNode::fetch( $relationItem['node_id'] ) : null;

							if ( !$node or $node->attribute( 'contentobject_id' ) != $relationItem['contentobject_id'] )
							{
								$content['relation_browse'][$key]['node_id'] = $mainNode->attribute( 'node_id' );
								$node = $mainNode;
								$contentModified = true;
							}

							$parentNodeID = $node->attribute( 'parent_node_id' );
							if ( $relationItem['parent_node_id'] != $parentNodeID )
							{
								$content['relation_browse'][$key]['parent_node_id'] = $parentNodeID;
								$contentModified = true;
							}
						}
						else
						{
							if ( !isset( $copiedRelatedAccordance[ $relationItem['contentobject_id'] ] ) )
								$copiedRelatedAccordance[ $relationItem['contentobject_id'] ] = array();

							if ( isset( $copiedRelatedAccordance[ $relationItem['contentobject_id'] ] ) and
								 isset( $copiedRelatedAccordance[ $relationItem['contentobject_id'] ][ $contentObjectID ] ) )
							{
								$newObjectID = $copiedRelatedAccordance[ $relationItem['contentobject_id'] ][ $contentObjectID ][ 'to' ];
							}
							else
							{
								$newObject = $object->copy( true );
								$newObjectID = $newObject->attribute( 'id' );
								$copiedRelatedAccordance[ $relationItem['contentobject_id'] ][ $contentObjectID ] = array( 'to' => $newObjectID,
																														   'from' => $originalContentObjectID );
							}
							$content['relation_browse'][$key]['contentobject_id'] = $newObjectID;
							$contentModified = true;
						}
					}

					if ( $contentModified )
					{
						$contentObjectAttribute->setContent( $content );
						$contentObjectAttribute->store();
					}
				}
			}
		}
    }

    /*!
     \reimp
    */
    function validateClassAttributeHTTPInput( $http, $base, $classAttribute )
    {
        $selectionTypeName = 'ContentClass_ezobjectrelationbrowse_selection_type_' . $classAttribute->attribute( 'id' );
	           $state = eZInputValidator::STATE_ACCEPTED;
	           if ( $http->hasPostVariable( $selectionTypeName ) )
	           {
	               $selectionType = $http->postVariable( $selectionTypeName );
	               if ( $selectionType < 0 and
	                    $selectionType > 6 )
	               {
	                   $state = eZInputValidator::STATE_INVALID;
	               }
	           }
        return $state;
    }

    /*!
     \reimp
    */
    function fixupClassAttributeHTTPInput( $http, $base, $classAttribute )
    {
    }

    /*!
     \reimp
    */
    function fetchClassAttributeHTTPInput( $http, $base, $classAttribute )
    {
		$content = $classAttribute->content();
		$postVariable = 'ContentClass_ezobjectrelationbrowse_class_list_' . $classAttribute->attribute( 'id' );
		if ( $http->hasPostVariable( $postVariable ) )
		{
			$constrainedList = $http->postVariable( $postVariable );
			$constrainedClassList = array();
			foreach ( $constrainedList as $constraint )
			{
				if ( trim( $constraint ) != '' )
					$constrainedClassList[] = $constraint;
			}
			$content['class_constraint_list'] = $constrainedClassList;

        }

        $postVariable = 'ContentClass_ezobjectrelationbrowse_class_create_list_' . $classAttribute->attribute( 'id' );
		if ( $http->hasPostVariable( $postVariable ) )
		{
			$constrainedList = $http->postVariable( $postVariable );
			$constrainedClassList = array();
			foreach ( $constrainedList as $constraint )
			{
				if ( trim( $constraint ) != '' )
					$constrainedClassList[] = $constraint;
			}
			$content['class_create_constraint_list'] = $constrainedClassList;

        }

		$typeVariable = 'ContentClass_ezobjectrelationbrowse_type_' . $classAttribute->attribute( 'id' );
		if ( $http->hasPostVariable( $typeVariable ) )
		{
			$type = $http->postVariable( $typeVariable );
			$content['type'] = $type;
			$hasData = true;
		}

		$selectionTypeVariable = 'ContentClass_ezobjectrelationbrowse_selection_type_' . $classAttribute->attribute( 'id' );
		if ( $http->hasPostVariable( $selectionTypeVariable ) )
		{
			$selectionType = $http->postVariable( $selectionTypeVariable);
			$content['selection_type'] = $selectionType;
			$hasData = true;
        }

		$objectClassVariable = 'ContentClass_ezobjectrelationbrowse_object_class_' . $classAttribute->attribute( 'id' );
		if ( $http->hasPostVariable( $objectClassVariable ) )
		{
			$content['object_class'] = $http->postVariable( $objectClassVariable );
			$hasData = true;
        }

		$depthVariable = 'ContentClass_ezobjectrelationbrowse_depth_' . $classAttribute->attribute( 'id' );
		if ( $http->hasPostVariable( $depthVariable ) )
	    {
		   $depth = $http->postVariable( $depthVariable );
		   $content['depth'] = $depth;
		   $hasData = true;
        }

		if ( $http->hasPostVariable( 'ContentClass_ezobjectrelationbrowse_allow_edit_' . $classAttribute->attribute( 'id' ) . '_exists' ) )
        {
			if ( $http->hasPostVariable( 'ContentClass_ezobjectrelationbrowse_allow_edit_' . $classAttribute->attribute( 'id' ) ))
			{
				$data = $http->postVariable('ContentClass_ezobjectrelationbrowse_allow_edit_' . $classAttribute->attribute( 'id' ) );
				if ( isset( $data ) )
					$content['allow_edit'] = 1;
					   $hasData = true;
			}
			else
			{
				$content['allow_edit'] = 0;
					   $hasData = true;
			}

		}


        $classAttribute->setContent( $content );
		$classAttribute->store();
        return true;
    }

   /*!
	\reimp
   */
   function initializeClassAttribute( $classAttribute )
   {
	   $xmlText = $classAttribute->attribute( 'data_text5' );
	   if ( trim( $xmlText ) == '' )
	   {
		   $content = eZObjectRelationBrowseType::defaultClassAttributeContent();
		   return eZObjectRelationBrowseType::storeClassAttributeContent( $classAttribute, $content );
	   }
   }

   /*!
	\reimp
   */
   function preStoreClassAttribute( $classAttribute, $version )
   {
	   $content = $classAttribute->content();
	   return eZObjectRelationBrowseType::storeClassAttributeContent( $classAttribute, $content );
   }

   function storeClassAttributeContent( $classAttribute, $content )
   {
	  if ( is_array( $content ) )
	   {
		   $doc = eZObjectRelationBrowseType::createClassDOMDocument( $content );
		   eZObjectRelationBrowseType::storeClassDOMDocument( $doc, $classAttribute );
		   return true;
	   }
	   return false;
    }

    function storeObjectAttributeContent( $objectAttribute, $content )
    {
        if ( is_array( $content ) )
        {
            $doc = eZObjectRelationBrowseType::createObjectDOMDocument( $content );
            eZObjectRelationBrowseType::storeObjectDOMDocument( $doc, $objectAttribute );
            return true;
        }
        return false;
    }

    function storeClassDOMDocument( $doc, $classAttribute )
    {
        $docText = eZObjectRelationBrowseType::domString( $doc );
        $classAttribute->setAttribute( 'data_text5', $docText );
    }

    function storeObjectDOMDocument( $doc, $objectAttribute )
    {
        $docText = eZObjectRelationBrowseType::domString( $doc );
        $objectAttribute->setAttribute( 'data_text', $docText );
    }

    /*!
     \static
     \return the XML structure in \a $domDocument as text.
             It will take of care of the necessary charset conversions
             for content storage.
    */
    function domString( $domDocument )
    {
        $ini = eZINI::instance();
        $xmlCharset = $ini->variable( 'RegionalSettings', 'ContentXMLCharset' );
        if ( $xmlCharset == 'enabled' )
        {
            include_once( 'lib/ezi18n/classes/eztextcodec.php' );
            $charset = eZTextCodec::internalCharset();
        }
        else if ( $xmlCharset == 'disabled' )
            $charset = true;
        else
            $charset = $xmlCharset;
        if ( $charset !== true )
        {
            include_once( 'lib/ezi18n/classes/ezcharsetinfo.php' );
            $charset = eZCharsetInfo::realCharsetCode( $charset );
        }
        $domString = $domDocument->saveXML();
        return $domString;
    }

    function createClassDOMDocument( $content )
    {
        $doc = new DOMDocument( '1.0', 'utf-8' );
        $root = $doc->createElement( 'related-objects' );
        $constraints = $doc->createElement( 'constraints' );
		foreach ( $content['class_constraint_list'] as $constraintClassIdentifier )
		{
			unset( $constraintElement );
			$constraintElement = $doc->createElement( 'allowed-class' );
			$constraintElement->setAttribute( 'contentclass-identifier', $constraintClassIdentifier );
			$constraints->appendChild( $constraintElement );
		}
		$root->appendChild( $constraints );

		$createConstraints = $doc->createElement( 'create-constraints' );
		foreach ( $content['class_create_constraint_list'] as $constraintClassIdentifier )
		{
			unset( $constraintElement );
			$constraintElement = $doc->createElement( 'allowed-create-class' );
			$constraintElement->setAttribute( 'contentclass-identifier', $constraintClassIdentifier );
			$createConstraints->appendChild( $constraintElement );
		}
		$root->appendChild( $createConstraints );

		$constraintType = $doc->createElement( 'type' );
        $constraintType->setAttribute( 'value', $content['type'] );
        $root->appendChild( $constraintType );

        $allowEdit = $doc->createElement( 'allow_edit' );
		$allowEdit->setAttribute( 'value',  $content['allow_edit'] );
		$root->appendChild( $allowEdit );

        $selectionType = $doc->createElement( 'selection_type' );
		$selectionType->setAttribute( 'value', $content['selection_type'] );
        $root->appendChild( $selectionType );

		$objectClass = $doc->createElement( 'object_class' );
        $objectClass->setAttribute( 'value', $content['object_class'] );
        $root->appendChild( $objectClass );

        $placementNode = $doc->createElement( 'contentobject-placement' );
		if ( $content['default_placement'] )
		{
			$placementNode->setAttribute( 'node-id',  $content['default_placement']['node_id'] );
		}
		$root->appendChild( $placementNode );
        $doc->appendChild( $root );

		$selectionNode = $doc->createElement( 'contentobject-selection' );
		if ( $content['default_selection'] )
		{
			$selectionNode->setAttribute( 'node-id',  $content['default_selection']['node_id'] );
		}
		$root->appendChild( $selectionNode );
        $doc->appendChild( $root );

        $depth = $doc->createElement( 'depth' );
		if ( $content['depth'] )
		{
			$depth->setAttribute( 'value',  $content['depth'] );
		}
		$root->appendChild( $depth );
        $doc->appendChild( $root );

		$doc->appendChild( $root );
        return $doc;
    }

    function createObjectDOMDocument( $content )
    {
        $doc = new DOMDocument( '1.0', 'utf-8' );
        $root = $doc->createElement( 'related-objects' );
        $relationList = $doc->createElement( 'relation-list' );
        $attributeDefinitions = eZObjectRelationBrowseType::contentObjectArrayXMLMap();

        foreach ( $content['relation_browse'] as $relationItem )
        {
            unset( $relationElement );
            $relationElement = $doc->createElement( 'relation-item' );

            foreach ( $attributeDefinitions as $attributeXMLName => $attributeKey )
			{
				if ( isset( $relationItem[$attributeKey] ) && $relationItem[$attributeKey] !== false )
				{
					$value = $relationItem[$attributeKey];
					$relationElement->setAttribute( $attributeXMLName, $value );
				}
			}
			$relationList->appendChild( $relationElement );
        }
        $root->appendChild( $relationList );
		$doc->appendChild( $root );
        return $doc;
    }

    static function contentObjectArrayXMLMap()
    {
        return array( 'identifier' => 'identifier',
                      'priority' => 'priority',
                      'in-trash' => 'in_trash',
                      'contentobject-id' => 'contentobject_id',
                      'contentobject-version' => 'contentobject_version',
                      'node-id' => 'node_id',
                      'parent-node-id' => 'parent_node_id',
                      'contentclass-id' => 'contentclass_id',
                      'contentclass-identifier' => 'contentclass_identifier',
                      'is-modified' => 'is_modified',
                      'contentobject-remote-id' => 'contentobject_remote_id' );
    }

    /*!
     \reimp
    */
    function deleteStoredObjectAttribute( $objectAttribute, $version = null )
    {
        $content = $objectAttribute->content();
        if ( is_array( $content ) and
             is_array( $content['relation_browse'] ) )
        {
            $db = eZDB::instance();
            $db->begin();
            foreach ( $content['relation_browse'] as $deletionItem )
            {
                eZObjectRelationBrowseType::removeRelationObject( $objectAttribute, $deletionItem );
            }
            $db->commit();
        }
    }

    /*!
     \reimp
    */
    function customObjectAttributeHTTPAction( $http, $action, $contentObjectAttribute, $parameters )
    {

       $contentobjectID = false;
        if ( eZDataType::fetchActionValue( $action, 'new_class', $classID ) or
             $action == 'new_class' )
        {
            if ( $action == 'new_class' )
            {
                $base = $parameters['base_name'];
                $classVariableName = $base . '_new_class';
                if ( $http->hasPostVariable( $classVariableName ) )
                {
                    $classVariable = $http->postVariable( $classVariableName );
                    $classID = $classVariable[$contentObjectAttribute->attribute( 'id' )];
                    $class = eZContentClass::fetch( $classID );
                }
                else
                    return false;
            }
            else
                $class = eZContentClass::fetch( $classID );
			if ( $class )
            {
                $ObjectVersion= $contentObjectAttribute->attribute( 'version' );
				$ContentObjectID = $contentObjectAttribute->attribute( 'contentobject_id' );
                $classAttribute = $contentObjectAttribute->attribute( 'contentclass_attribute' );
                $class_content = $classAttribute->content();
                $content = $contentObjectAttribute->content();
                $priority = 0;
                for ( $i = 0; $i < count( $content['relation_browse'] ); ++$i )
                {
                    if ( $content['relation_browse'][$i]['priority'] > $priority )
                        $priority = $content['relation_browse'][$i]['priority'];
                }

                $base = $parameters['base_name'];
                $nodePlacement = false;
                $nodePlacementName = $base . '_object_initial_node_placement';
                if ( $http->hasPostVariable( $nodePlacementName ) )
                {
                    $nodePlacementMap = $http->postVariable( $nodePlacementName );
                    if ( isset( $nodePlacementMap[$contentObjectAttribute->attribute( 'id' )] ) )
                        $nodePlacement = $nodePlacementMap[$contentObjectAttribute->attribute( 'id' )];
                }
                $relationItem = eZObjectRelationBrowseType::createInstance( $class,
                                                                          $priority + 1,
                                                                          $contentObjectAttribute,
                                                                          $nodePlacement );
                if ( $class_content['default_placement'] )
                {
                    $relationItem['parent_node_id'] = $class_content['default_placement']['node_id'];
                }

                $content['relation_browse'][] = $relationItem;

                $hasAttributeInput = false;
                $attributeInputVariable = $base . '_has_attribute_input';
                if ( $http->hasPostVariable( $attributeInputVariable ) )
                {
                    $attributeInputMap = $http->postVariable( $attributeInputVariable );
                    if ( isset( $attributeInputMap[$contentObjectAttribute->attribute( 'id' )] ) )
                        $hasAttributeInput = $attributeInputMap[$contentObjectAttribute->attribute( 'id' )];
                }

                if ( $hasAttributeInput )
                {
                    $object = $relationItem['object'];
                    $attributes = $object->contentObjectAttributes();
                	$ContentObjectID = $relationItem['contentobject_id'];
                    if ($object)
                    {
						$attributes = $object->contentObjectAttributes();
						 foreach ( $attributes as $attribute )
						{
							$attributeBase = $base . '_ezorl_init_class_' . $object->attribute( 'contentclass_id' ) . '_attr_' . $attribute->attribute( 'contentclassattribute_id' );
							$oldAttributeID = $attribute->attribute( 'id' );
							$attribute->setAttribute( 'id', false );
							if ( $attribute->fetchInput( $http, $attributeBase ) )
							{
								$attribute->setAttribute( 'id', $oldAttributeID );
								$attribute->store();
							}
						}
					}
                }

				$contentObjectAttribute->setContent( $content );
				$contentObjectAttribute->store();

				//not sure
				//if ($ContentObjectID)
				//{
				//	$ObjectVersion= $contentObjectAttribute->attribute( 'version' );
				//	$ContentObjectID = $contentObjectAttribute->attribute( 'contentobject_id' );
				//	$ContentObject = eZContentObject::fetch( $ContentObjectID );
				//	$ContentObject->addContentObjectRelation( $relationItem['contentobject_id'] , $ObjectVersion, $ContentObjectID, $classAttribute );
            	//}
            }
            else

                eZDebug::writeError( "Unknown class ID $classID, cannot instantiate object",
                                     'eZObjectRelationBrowseType::customObjectAttributeHTTPAction' );
        }
        else if ( eZDataType::fetchActionValue( $action, 'edit_objects', $contentobjectID ) or
                  $action == 'edit_objects' or
                  $action == 'remove_objects' )
        {
            $base = $parameters['base_name'];
            $selectionBase = $base . '_selection';
            $selections = array();
            $http = eZHTTPTool::instance();
            if ( $http->hasPostVariable( $selectionBase ) )
            {
                $selectionMap = $http->postVariable( $selectionBase );
                $selections = $selectionMap[$contentObjectAttribute->attribute( 'id' )];
            }
            if ( $contentobjectID !== false )
                $selections[] = $contentobjectID;
            if ( $action == 'edit_objects' or
                 eZDataType::fetchActionValue( $action, 'edit_objects', $contentobjectID ) )
            {
                $content = $contentObjectAttribute->content();
                foreach ( $content['relation_browse'] as $key => $relationItem )
                {
                    if ( !$relationItem['is_modified'] and
                         in_array( $relationItem['contentobject_id'], $selections ) )
                    {
                        $object = eZContentObject::fetch( $relationItem['contentobject_id'] );
                        if ( $object->attribute( 'can_edit' ) )
                        {
                            $content['relation_browse'][$key]['is_modified'] = true;
							$version = $object->createNewVersion();
                            $content['relation_browse'][$key]['contentobject_version'] = $version->attribute( 'version' );
                        }
                    }
                }
                $contentObjectAttribute->setContent( $content );
                $contentObjectAttribute->store();
            }
            else if ( $action == 'remove_objects' )
            {
				$content = $contentObjectAttribute->content();
				$relationList = $content['relation_browse'];
				$newRelationList = array();
				foreach( $relationList as $relationItem )
				{
					if ( in_array( $relationItem['contentobject_id'], $selections ) )
					{
						eZObjectRelationBrowseType::removeRelationObject( $contentObjectAttribute, $relationItem );
					}
					else
					{
						$newRelationList[] = $relationItem;
					}
				}
				$content['relation_browse'] = $newRelationList;

				$contentObjectAttribute->setContent( $content );
                $contentObjectAttribute->store();
            }
        }
        else if ( $action == 'browse_objects' )
        {
            $module = $parameters['module'];
            $redirectionURI = $parameters['current-redirection-uri'];

            $ContentObjectID= $contentObjectAttribute->attribute( 'contentobject_id' );
	   		$object = eZContentObject::fetch( $ContentObjectID );
            $ini = eZINI::instance( 'content.ini' );
            $browseType = 'AddRelatedObjectToDataType';
            $browseTypeINIVariable = $ini->variable( 'ObjectRelationDataTypeSettings', 'ClassAttributeStartNode' );
            foreach ( $browseTypeINIVariable as $value )
            {
                list( $classAttributeID, $type ) = explode( ';',$value );
                if ( is_numeric( $classAttributeID ) and
                     $classAttributeID == $contentObjectAttribute->attribute( 'contentclassattribute_id' ) and
                     strlen( $type ) > 0 )
                {
                    $browseType = $type;
                    break;
                }
            }
             $classAttribute = $contentObjectAttribute->attribute( 'contentclass_attribute' );
             $class_content = $classAttribute->content();
             $createType = $class_content['type'];
             $defaultPlacement = $class_content['default_placement'];
             $browseParameters = array( 'action_name' => 'AddRelatedObject_' . $contentObjectAttribute->attribute( 'id' ),
                                       'type' =>  $browseType,
                                       'ignore_nodes_select' => $object->attribute( 'main_node_id' ),
                                       'create_type' => $createType,
                                       'default_placement' => $defaultPlacement,
										'browse_custom_action' => array( 'name' => 'CustomActionButton[' . $contentObjectAttribute->attribute( 'id' ) . '_set_object_relation_browse]',
										'value' => $contentObjectAttribute->attribute( 'id' ) ),
                                       'persistent_data' => array( 'HasObjectInput' => 0 ),
                                       'from_page' => $redirectionURI ."#". $contentObjectAttribute->attribute( 'id' ));
            $base = $parameters['base_name'];

			if ( isset( $class_content['class_constraint_list'] ) )
			{
				$browseParameters['class_constraint_list'] = $class_content['class_constraint_list'];
				$browseParameters['class_array'] = $class_content['class_constraint_list'];
			}

			if ( isset( $class_content['class_create_constraint_list'] ) )
			{
				$browseParameters['class_create_constraint_list'] = $class_content['class_create_constraint_list'];
				$browseParameters['class_create_array'] = $class_content['class_create_constraint_list'];
			}

            $nodePlacementName = $base . '_browse_for_object_start_node';
            if ( $http->hasPostVariable( $nodePlacementName ) )
            {
                $nodePlacement = $http->postVariable( $nodePlacementName );
                if ( isset( $nodePlacement[$contentObjectAttribute->attribute( 'id' )] ) )
                    $browseParameters['start_node'] = eZContentBrowse::nodeAliasID( $nodePlacement[$contentObjectAttribute->attribute( 'id' )] );
            }
            $browseType = $base . '_browse_for_object_browse_type';
			if ( $http->hasPostVariable( $browseType ) )
			{
				$type = $http->postVariable( $browseType );
				if ( isset( $type[$contentObjectAttribute->attribute( 'id' )] ) )
					$browseParameters['browse_type'] = $type[$contentObjectAttribute->attribute( 'id' )];
            } else {
		    	$browseParameters['browse_type']=0;
		    }

            eZContentBrowse::browse( $browseParameters,
                                     $module );
        }
        else if ( $action == 'set_object_relation_browse' )
        {
            if ( !$http->hasPostVariable( 'BrowseCancelButton' ) )
			{
				$selectedObjectIDArray = $http->postVariable( "SelectedObjectIDArray" );
				$ContentObjectID= $contentObjectAttribute->attribute( 'contentobject_id' );
			    $object = eZContentObject::fetch( $ContentObjectID );
			    $contentClassAttributeID = $contentObjectAttribute->ContentClassAttributeID;
				$content = $contentObjectAttribute->content();
				$priority = 0;
				for ( $i = 0; $i < count( $content['relation_browse'] ); ++$i )
				{
					if ( $content['relation_browse'][$i]['priority'] > $priority )
						$priority = $content['relation_browse'][$i]['priority'];
				}

				$ObjectVersion= $contentObjectAttribute->attribute( 'version' );

				foreach ( $selectedObjectIDArray as $objectID )
				{
					// Check if the given object ID has a numeric value, if not go to the next object.
					if ( !is_numeric( $objectID ) )
					{
						eZDebug::writeError( "Related object ID (objectID): '$objectID', is not a numeric value.",
							"eZObjectRelationBrowseType::customObjectAttributeHTTPAction" );

						continue;
					}

					/* Here we check if current object is already in the related objects list.
					 * If so, we don't add it again.
					 * FIXME: Stupid linear search. Maybe there's some better way?
					 */
					$found = false;
					foreach ( $content['relation_browse'] as $i )
					{
						if ( $i['contentobject_id'] == $objectID )
						{
							$found = true;
							break;
						}
					}
					if ( $found )
						continue;

					++$priority;
					$content['relation_browse'][] = $this->appendObject( $objectID, $priority, $contentObjectAttribute );
					//not sure
					$object->addContentObjectRelation( $objectID, $ObjectVersion, $ContentObjectID, $contentClassAttributeID);
					$contentObjectAttribute->setContent( $content );
					$contentObjectAttribute->store();
				}
            }
        }
        else if ( $action == 'update_priorities' )
        {
			$base = $parameters['base_name'];
			eZObjectRelationBrowseType::fetchObjectAttributeHTTPInput( $http, $base, $contentObjectAttribute );
        }
        else
        {
            eZDebug::writeError( "Unknown custom HTTP action: " . $action,
                                 'eZObjectRelationBrowseType' );
        }
    }

    /*!
     \reimp
    */

   function handleCustomObjectHTTPActions( $http, $attributeDataBaseName,
                                               $customActionAttributeArray, $customActionParameters )
       {
           $contentObjectAttribute = $customActionParameters['contentobject_attribute'];
           $content = $contentObjectAttribute->content();
           foreach( $content['relation_browse'] as $relationItem )
           {
               $subObjectID = $relationItem['contentobject_id'];
               $subObjectVersion = $relationItem['contentobject_version'];

               $attributeBase = $attributeDataBaseName . '_ezorl_edit_object_' . $subObjectID;
               if ( eZContentObject::recursionProtect( $subObjectID ) )
               {
                   if ( isset ( $content['temp'][$subObjectID] ) )
                       $object = $content['temp'][$subObjectID]['object'];
                   else
                       $object = eZContentObject::fetch( $subObjectID );
                   if ( $object )
                       $object->handleAllCustomHTTPActions( $attributeBase,
                                                            $customActionAttributeArray,
                                                            $customActionParameters,
                                                            $subObjectVersion );
               }
           }
    }

    /*!
     \static
     \return \c true if the relation item \a $relationItem exist in the content tree.
    */
    static function isItemPublished( $relationItem )
    {
        return is_numeric( $relationItem['node_id'] ) and $relationItem['node_id'] > 0;
    }

    /*!
     \private
     Removes the relation object \a $deletionItem if the item is owned solely by this
     version and is not published in the content tree.
    */
    function removeRelationObject( $contentObjectAttribute, $deletionItem )
    {
        if ( eZObjectRelationBrowseType::isItemPublished( $deletionItem ) )
        {
            return;
        }

        $hostObject = $contentObjectAttribute->attribute( 'object' );
        $hostObjectVersions = $hostObject->versions();
        $isDeletionAllowed = true;

        // check if the relation item to be deleted is unique in the domain of all host-object versions
        foreach ( $hostObjectVersions as $version )
        {
            if ( $isDeletionAllowed and
                 $version->attribute( 'version' ) != $contentObjectAttribute->attribute( 'version' ) )
            {
                $relationAttribute = eZPersistentObject::fetchObjectList( eZContentObjectAttribute::definition(),
                                                                           null,
                                                                           array( 'version' => $version->attribute( 'version' ),
                                                                                  'contentobject_id' => $hostObject->attribute( 'id' ),
                                                                                  'contentclassattribute_id' => $contentObjectAttribute->attribute( 'contentclassattribute_id' ) ) );

                if ( count( $relationAttribute ) > 0 )
                {
                    $relationContent = $relationAttribute[0]->content();
                    if ( is_array( $relationContent ) and
                         is_array( $relationContent['relation_browse'] ) )
                    {
                        foreach( $relationContent['relation_browse'] as $relationItem )
						{
							if ( $deletionItem['contentobject_id'] == $relationItem['contentobject_id'] &&
								 $deletionItem['contentobject_version'] == $relationItem['contentobject_version'] )
							{
								$isDeletionAllowed = false;
								break 2;
							}
						}
                    }
                }
            }
        }


        if ( $isDeletionAllowed )
        {
            $subObjectVersion = eZContentObjectVersion::fetchVersion( $deletionItem['contentobject_version'],
			                                                                      $deletionItem['contentobject_id'] );
			if ( $subObjectVersion instanceof eZContentObjectVersion )
			{
				$subObjectVersion->removeThis();
			}
			else
			{
				eZDebug::writeError( 'Cleanup of subobject-version failed. Could not fetch object from relation list.\n' .
									 'Requested subobject id: ' . $deletionItem['contentobject_id'] . '\n' .
									 'Requested Subobject version: ' . $deletionItem['contentobject_version'],
									 'eZObjectRelationBrowseType::removeRelationObject' );
            }
        }
    }


    function createInstance( &$class, $priority, &$contentObjectAttribute, $nodePlacement = false )
    {
        $currentObject = $contentObjectAttribute->attribute( 'object' );
        $sectionID = $currentObject->attribute( 'section_id' );
        $object = $class->instantiate( false, $sectionID );
        if ( !is_numeric( $nodePlacement ) or $nodePlacement <= 0 )
            $nodePlacement = false;
        $object->sync();
        $relationItem = array( 'identifier' => false,
                               'priority' => $priority,
                               'in_trash' => false,
                               'contentobject_id' => $object->attribute( 'id' ),
                               'contentobject_version' => $object->attribute( 'current_version' ),
                               'contentobject_remote_id' => $object->attribute( 'remote_id' ),
                               'node_id' => false,
                               'parent_node_id' => $nodePlacement,
                               'contentclass_id' => $class->attribute( 'id' ),
                               'contentclass_identifier' => $class->attribute( 'identifier' ),
                               'is_modified' => true );
        $relationItem['object'] = $object;
        return $relationItem;
    }

    function appendObject( $objectID, $priority, &$contentObjectAttribute )
    {
        $object = eZContentObject::fetch( $objectID );
        $class = $object->attribute( 'content_class' );
        $sectionID = $object->attribute( 'section_id' );
        if ($object)
        {
			 $relationItem = array( 'identifier' => false,
								   'priority' => $priority,
								   'in_trash' => false,
								   'contentobject_id' => $object->attribute( 'id' ),
								   'contentobject_version' => $object->attribute( 'current_version' ),
								   'contentobject_remote_id' => $object->attribute( 'remote_id' ),
								   'node_id' => $object->attribute( 'main_node_id' ),
								   'parent_node_id' => $object->attribute( 'main_parent_node_id' ),
								   'contentclass_id' => $class->attribute( 'id' ),
								   'contentclass_identifier' => $class->attribute( 'identifier' ),
                               'is_modified' => false );
			$relationItem['object'] =& $object;
			return $relationItem;
		}
		else
		{
			return false;
		}
    }

    function fixRelatedObjectItem ( $contentObjectAttribute, $objectID, $mode )
	{
		switch ( $mode )
		{
			case 'move':
			{
				eZObjectRelationBrowseType::fixRelationsMove( $objectID, $contentObjectAttribute );
			} break;

			case 'trash':
			{
				eZObjectRelationBrowseType::fixRelationsTrash( $objectID, $contentObjectAttribute );
			} break;

			case 'restore':
			{
				eZObjectRelationBrowseType::fixRelationsRestore( $objectID, $contentObjectAttribute );
			} break;

			case 'remove':
			{
				eZObjectRelationBrowseType::fixRelationsRemove( $objectID, $contentObjectAttribute );
			} break;

			case 'swap':
			{
				eZObjectRelationBrowseType::fixRelationsSwap( $objectID, $contentObjectAttribute );
			} break;

			default:
			{
				eZDebug::writeWarning( $mode, 'Unknown mode eZObjectRelationBrowseType::fixRelatedObjectItem()' );
			} break;
		}
	}

	function fixRelationsMove ( $objectID, $contentObjectAttribute )
	{
		$this->fixRelationsSwap( $objectID, $contentObjectAttribute );
	}

	function fixRelationsTrash ( $objectID, $contentObjectAttribute )
	{
		$content = $contentObjectAttribute->attribute( 'content' );
		foreach ( array_keys( $content['relation_browse'] ) as $key )
		{
			if ( $content['relation_browse'][$key]['contentobject_id'] == $objectID )
			{
				$content['relation_browse'][$key]['in_trash'] = true;
				$content['relation_browse'][$key]['node_id'] = null;
				$content['relation_browse'][$key]['parent_node_id']= null;
			}
		}
		eZObjectRelationBrowseType::storeObjectAttributeContent( $contentObjectAttribute, $content );
		$contentObjectAttribute->setContent( $content );
		$contentObjectAttribute->storeData();
	}

	function fixRelationsRestore ( $objectID, $contentObjectAttribute )
	{
		$content = $contentObjectAttribute->content();

		foreach ( array_keys( $content['relation_browse'] ) as $key )
		{
			if ( $content['relation_browse'][$key]['contentobject_id'] == $objectID )
			{
				$priority = $content['relation_browse'][$key]['priority'];
				$content['relation_browse'][$key] = $this->appendObject( $objectID, $priority, $contentObjectAttribute);
			}
		}
		eZObjectRelationBrowseType::storeObjectAttributeContent( $contentObjectAttribute, $content );
		$contentObjectAttribute->setContent( $content );
		$contentObjectAttribute->storeData();
	}

	function fixRelationsRemove ( $objectID, $contentObjectAttribute )
	{
		$this->removeRelatedObjectItem( $contentObjectAttribute, $objectID );
		$contentObjectAttribute->storeData();
	}

	function fixRelationsSwap ( $objectID, $contentObjectAttribute )
	{
		$content = $contentObjectAttribute->content();

		foreach ( array_keys( $content['relation_browse'] ) as $key )
		{
			$relatedObject = $content['relation_browse'][$key];
			if ( $relatedObject['contentobject_id'] == $objectID )
			{
				$priority = $content['relation_browse'][$key]['priority'];
				$content['relation_browse'][$key] = $this->appendObject($objectID, $priority, $contentObjectAttribute );
			}
		}

		eZObjectRelationBrowseType::storeObjectAttributeContent( $contentObjectAttribute, $content );
		$contentObjectAttribute->setContent( $content );
		$contentObjectAttribute->storeData();
   	 }

    /*!
     Returns the content.
    */
    function objectAttributeContent( $contentObjectAttribute )
    {
        $xmlText = $contentObjectAttribute->attribute( 'data_text' );
        if ( trim( $xmlText ) == '' )
        {
			$objectAttributeContent = eZObjectRelationBrowseType::defaultObjectAttributeContent();
			return $objectAttributeContent;
        }
        $doc = eZObjectRelationBrowseType::parseXML( $xmlText );
        $content = eZObjectRelationBrowseType::createObjectContentStructure( $doc );

        return $content;
    }


	function objectDisplayInformation( $contentObjectAttribute, $mergeInfo = false )
	{
		$classAttribute = $contentObjectAttribute->contentClassAttribute();
		$content =  eZObjectRelationBrowseType::classAttributeContent( $classAttribute );
		$editGrouped = ( $content['selection_type'] == 0 or $content['selection_type'] == 1  );
		$info = array( 'edit' => array( 'grouped_input' => $editGrouped ),
					   'collection' => array( 'grouped_input' => $editGrouped ) );
		return eZDataType::objectDisplayInformation( $contentObjectAttribute, $info );
    }


    /*!
     \reimp
    */
    function classAttributeContent( $classAttribute )
    {
        $xmlText = $classAttribute->attribute( 'data_text5' );
        if ( trim( $xmlText ) == '' )
        {
            return eZObjectRelationBrowseType::defaultClassAttributeContent();
        }
        $doc = eZObjectRelationBrowseType::parseXML( $xmlText );
        return eZObjectRelationBrowseType::createClassContentStructure( $doc );
    }

    static function parseXML( $xmlText )
    {
        $dom = new DOMDocument( '1.0', 'utf-8' );
		$dom->loadXML( $xmlText );
        return $dom;
    }

    function defaultClassAttributeContent()
    {
       return array( 'type' => 0,
       				 'object_class' => '',
					  'selection_type' => 0,
					  'allow_edit' => 0,
					  'default_selection' => false,
					  'class_constraint_list' => array(),
					  'class_create_constraint_list' => array(),
					  'default_placement' => false,
					  'depth' => 0 );
    }

    function defaultObjectAttributeContent()
    {
        return array( 'relation_browse' => array() );
    }

    function createClassContentStructure( $doc )
    {

		$content = eZObjectRelationBrowseType::defaultClassAttributeContent();
		$root = $doc->documentElement;
		$objectPlacement = $root->getElementsByTagName( 'contentobject-placement' )->item( 0 );

		if ( $objectPlacement and $objectPlacement->hasAttributes() )
		{
			$nodeID = $objectPlacement->getAttribute( 'node-id' );
			$content['default_placement'] = array( 'node_id' => $nodeID );
		}

		$objectBrowse = $root->getElementsByTagName( 'contentobject-selection' )->item( 0 );

		if ( $objectBrowse  and $objectBrowse->hasAttributes() )
		{
			$nodeID = $objectBrowse->getAttribute( 'node-id' );
			$content['default_selection'] = array( 'node_id' => $nodeID );
		}

		$constraints = $root->getElementsByTagName( 'constraints' )->item( 0 );
		if ( $constraints )
		{
			$allowedClassList = $constraints->getElementsByTagName( 'allowed-class' );
			foreach( $allowedClassList as $allowedClass )
			{
				$content['class_constraint_list'][] = $allowedClass->getAttribute( 'contentclass-identifier' );
			}
        }

        $createConstraints = $root->getElementsByTagName( 'create-constraints' )->item( 0 );
		if ( $createConstraints )
		{
			$allowedClassList = $createConstraints->getElementsByTagName( 'allowed-create-class' );
			foreach( $allowedClassList as $allowedClass )
			{
				$content['class_create_constraint_list'][] = $allowedClass->getAttribute( 'contentclass-identifier' );
			}
        }


		$type = $root->getElementsByTagName( 'type' )->item( 0 );
		if ( $type )
		{
			$content['type'] = $type->getAttribute( 'value' );
		}


		$selection_type = $root->getElementsByTagName( 'selection_type' )->item( 0 );
		if ( $selection_type )
		{
			$content['selection_type'] = $selection_type->getAttribute( 'value' );
		}

		$depth = $root->getElementsByTagName( 'depth' )->item( 0 );
		if ( $depth  )
		{
			$content['depth'] = $depth->getAttribute( 'value' );
		}

		$allow_edit = $root->getElementsByTagName( 'allow_edit' )->item( 0 );
		if ( $allow_edit )
		{
			$content['allow_edit'] = $allow_edit->getAttribute( 'value' );
		}

		$objectClass = $root->getElementsByTagName( 'object_class' )->item( 0 );
		if ( $objectClass )
		{
			$content['object_class'] = $objectClass->getAttribute( 'value' );
		}


		//print_r($content);
		return $content;
    }

    function createObjectContentStructure( $doc )
    {
        $content = eZObjectRelationBrowseType::defaultObjectAttributeContent();
        $root = $doc->documentElement;
        $relationList = $root->getElementsByTagName( 'relation-list' )->item( 0 );
        if ( $relationList )
        {
            $contentObjectArrayXMLMap = eZObjectRelationBrowseType::contentObjectArrayXMLMap();
            $relationItems = $relationList->getElementsByTagName( 'relation-item' );
            foreach ( $relationItems as $relationItem )
            {
                $hash = array();

                foreach ( $contentObjectArrayXMLMap as $attributeXMLName => $attributeKey )
				{
					$attributeValue = $relationItem->hasAttribute( $attributeXMLName ) ? $relationItem->getAttribute( $attributeXMLName ) : false;
					$hash[$attributeKey] = $attributeValue;
                }
                $content['relation_browse'][] = $hash;
           }
        }
        return $content;
    }

    /*!
     \reimp
    */
    function customClassAttributeHTTPAction( $http, $action, $classAttribute )
    {
        switch ( $action )
        {
            case 'browse_for_placement':
			{
				$module = $classAttribute->currentModule();
				include_once( 'kernel/classes/ezcontentbrowse.php' );
				$customActionName = 'CustomActionButton[' . $classAttribute->attribute( 'id' ) . '_browsed_for_placement]';
				eZContentBrowse::browse( array( 'action_name' => 'SelectObjectRelationListNode',
												'content' => array( 'contentclass_id' => $classAttribute->attribute( 'contentclass_id' ),
																	'contentclass_attribute_id' => $classAttribute->attribute( 'id' ),
																	'contentclass_version' => $classAttribute->attribute( 'version' ),
																	'contentclass_attribute_identifier' => $classAttribute->attribute( 'identifier' ) ),
												'persistent_data' => array( $customActionName => '',
																			'ContentClassHasInput' => false ),
												'description_template' => 'design:class/datatype/browse_objectrelationbrowse_placement.tpl',
												'from_page' => $module->currentRedirectionURI() ),
										 $module );
			} break;
			case 'browsed_for_placement':
			{
				include_once( 'kernel/classes/ezcontentbrowse.php' );
				$nodeSelection = eZContentBrowse::result( 'SelectObjectRelationListNode' );
				if ( count( $nodeSelection ) > 0 )
				{
					$nodeID = $nodeSelection[0];
					$content = $classAttribute->content();
					$content['default_placement'] = array( 'node_id' => $nodeID );
					$classAttribute->setContent( $content );
				}
			} break;

			case 'disable_placement':
			{
				$content = $classAttribute->content();
				$content['default_placement'] = false;
				$classAttribute->setContent( $content );
			} break;
            case 'browse_for_selection':
			{
				$module = $classAttribute->currentModule();
				include_once( 'kernel/classes/ezcontentbrowse.php' );
				$customActionName = 'CustomActionButton[' . $classAttribute->attribute( 'id' ) . '_browsed_for_selection]';
				eZContentBrowse::browse( array( 'action_name' => 'SelectObjectRelationNode',
												'content' => array( 'contentclass_id' => $classAttribute->attribute( 'contentclass_id' ),
																	'contentclass_attribute_id' => $classAttribute->attribute( 'id' ),
																	'contentclass_version' => $classAttribute->attribute( 'version' ),
																	'contentclass_attribute_identifier' => $classAttribute->attribute( 'identifier' ) ),
												'persistent_data' => array( $customActionName => '',
																			'ContentClassHasInput' => false ),
												'description_template' => 'design:class/datatype/browse_objectrelationbrowse_placement.tpl',
												'from_page' => $module->currentRedirectionURI() ),
										 $module );
			} break;
			case 'browsed_for_selection':
			{
				include_once( 'kernel/classes/ezcontentbrowse.php' );
				$nodeSelection = eZContentBrowse::result( 'SelectObjectRelationNode' );
				if ( count( $nodeSelection ) > 0 )
				{
					$nodeID = $nodeSelection[0];
					$content = $classAttribute->content();
					$content['default_selection'] = array( 'node_id' => $nodeID );
					$classAttribute->setContent( $content );
				}
			} break;
			case 'disable_selection':
			{
				$content = $classAttribute->content();
				$content['default_selection'] = false;
				$classAttribute->setContent( $content );
            } break;
            default:
            {
                eZDebug::writeError( "Unknown objectrelationbrowse action '$action'", 'eZContentObjectRelationBrowseType::customClassAttributeHTTPAction' );
            } break;
        }
    }

    /*!
     Returns the meta data used for storing search indeces.
    */
    function metaData( $contentObjectAttribute )
    {
		$metaDataArray = array();
		$content = $contentObjectAttribute->content();
		foreach( $content['relation_browse'] as $relationItem )
		{
			$subObjectID = $relationItem['contentobject_id'];
			if ( !$subObjectID )
				continue;

            if ( isset( $content['temp'] ) )
				$attributes = $content['temp'][$subObjectID]['attributes'];
			else
            {
                $subObjectVersion = $relationItem['contentobject_version'];
                $object = eZContentObject::fetch( $subObjectID );
                if ( eZContentObject::recursionProtect( $subObjectID ) )
                {
                    if ( !$object )
					{
						continue;
					}
                    $attributes = $object->contentObjectAttributes( true, $subObjectVersion );
                }
            }

            $metaDataArray = array_merge( $metaDataArray, eZContentObjectAttribute::metaDataArray( $attributes ) );
        }

        return $metaDataArray;
    }

    function toString( $contentObjectAttribute )
	{
		$objectAttributeContent = $contentObjectAttribute->attribute( 'content' );
		$objectIDList = array();
		foreach( $objectAttributeContent['relation_browse'] as $objectInfo )
		{
			$objectIDList[] = $objectInfo['contentobject_id'];
		}
		return implode( '-', $objectIDList );
    }

    function fromString( $contentObjectAttribute, $string )
	{
		$objectIDList = explode( '-', $string );

		$content = eZObjectRelationBrowseType::defaultObjectAttributeContent();
		$priority = 0;
		foreach( $objectIDList as $objectID )
		{
			$object = eZContentObject::fetch( $objectID );
			if ( $object )
			{
				++$priority;
				$content['relation_browse'][] = $this->appendObject( $objectID, $priority, $contentObjectAttribute );
			}
			else
			{
				eZDebug::writeWarning( $objectID, "Can not create relation because object is missing" );
			}
		}
		$contentObjectAttribute->setContent( $content );
		return true;
    }


    function hasObjectAttributeContent( $contentObjectAttribute )
    {
        $content = $contentObjectAttribute->content();
        return count( $content['relation_browse'] ) > 0;
    }

    /*!
     \reimp
    */
    function isIndexable()
    {
        return true;
    }

    /*!
     Returns the content of the string for use as a title
    */
    function title( $contentObjectAttribute, $name = null )
    {
        $objectAttributeContent = $this->objectAttributeContent( $contentObjectAttribute );
		if ( count( $objectAttributeContent['relation_browse'] ) > 0 )
		{
			$target = $objectAttributeContent['relation_browse'][0];
			$targetObject = eZContentObject::fetch( $target['contentobject_id'], false );
			return $targetObject['name'];
		}
		else
		{
			return false;
        }
    }

    /*!
     \reimp
    */
    function serializeContentClassAttribute( $classAttribute, $attributeNode, $attributeParametersNode )
	{
		$dom = $attributeParametersNode->ownerDocument;
		$content = $classAttribute->content();
		if ( $content['default_selection'] )
		{
			$defaultSelectionNode = $dom->createElement( 'default-selection' );
			$defaultSelectionNode->setAttribute( 'node-id', $content['default_selection']['node_id'] );
			$attributeParametersNode->appendChild( $defaultSelectionNode );
        }

		if ( $content['default_placement'] )
		{
			$defaultPlacementNode = $dom->createElement( 'default-placement' );
			$defaultPlacementNode->setAttribute( 'node-id', $content['default_placement']['node_id'] );
			$attributeParametersNode->appendChild( $defaultPlacementNode );
		}

		if ( is_numeric( $content['depth'] ) )
		{
			$depth = $dom->createElement( 'depth', $content['depth'] );
			$attributeParametersNode->appendChild( $depth );
		}
		else
		{
			$depth = $dom->createElement( 'depth', '0' );
			$attributeParametersNode->appendChild( $depth );
		}

		if ( is_numeric( $content['allow_edit'] ) )
		{
			$allowEdit = $dom->createElement( 'allow_edit', $content['allow_edit'] );
			$attributeParametersNode->appendChild( $allowEdit );
		}
		else
		{
			$allowEdit = $dom->createElement( 'allow_edit', '0' );
			$attributeParametersNode->appendChild( $allowEdit );
		}
		if ( is_numeric( $content['type'] ) )
		{
			$typeNode = $dom->createElement( 'type', $content['type'] );
			$attributeParametersNode->appendChild( $typeNode );
		}
		else
		{
			$typeNode = $dom->createElement( 'type', '0' );
			$attributeParametersNode->appendChild( $typeNode );
		}

		$classConstraintsNode = $dom->createElement( 'class-constraints' );
		$attributeParametersNode->appendChild( $classConstraintsNode );
		foreach ( $content['class_constraint_list'] as $classConstraint )
		{
			$classConstraintIdentifier = $classConstraint;
			$classConstraintNode = $dom->createElement( 'class-constraint' );
			$classConstraintNode->setAttribute( 'class-identifier', $classConstraintIdentifier );
			$classConstraintsNode->appendChild( $classConstraintNode );
        }


        if ( isset( $content['object_class'] ) && is_numeric( $content['object_class'] ) )
		{
			$objectClassNode = $dom->createElement( 'object-class', $content['object_class'] );
			$attributeParametersNode->appendChild( $objectClassNode );
        }


        $classCreateConstraintsNode = $dom->createElement( 'create-constraints' );
		$attributeParametersNode->appendChild( $classCreateConstraintsNode );
		foreach ( $content['class_create_constraint_list'] as $classConstraint )
		{
			$classConstraintIdentifier = $classConstraint;
			$classConstraintNode = $dom->createElement( 'create-constraint' );
			$classConstraintNode->setAttribute( 'class-identifier', $classConstraintIdentifier );
			$classConstraintsNode->appendChild( $classConstraintNode );
        }

        //print_r($classConstraintsNode);
    }

	/*!
     \reimp
    */
    function unserializeContentClassAttribute( $classAttribute, $attributeNode,$attributeParametersNode )
    {
        $content = $classAttribute->content();

		$defaultPlacementNode = $attributeParametersNode->getElementsByTagName( 'default-placement' )->item( 0 );
		$content['default_placement'] = false;

		if ( $defaultPlacementNode )
		{
			$content['default_placement'] = array( 'node_id' => $defaultPlacementNode->getAttribute( 'node-id' ) );
        }

        $defaultSelectionNode =$attributeParametersNode->getElementsByTagName(  'default-selection' )->item( 0 );
		if ( $defaultSelectionNode )
		{
			$content['default_selection'] = array( 'node_id' => $defaultSelectionNode->getAttribute( 'node-id' ) );
        }

        $content['type'] = $attributeParametersNode->getElementsByTagName( 'type' )->item( 0 )->textContent;

        $content['depth'] = $attributeParametersNode->getElementsByTagName( 'depth' )->item( 0 )->textContent;

        $content['allow_edit'] = $attributeParametersNode->getElementsByTagName( 'allow_edit' )->item( 0 )->textContent;

		$classConstraintsNode = $attributeParametersNode->getElementsByTagName( 'class-constraints' )->item( 0 );
		$classConstraintList = $classConstraintsNode->getElementsByTagName( 'class-constraint' );
		$content['class_constraint_list'] = array();
		foreach ( $classConstraintList as $classConstraintNode )
		{
			$classIdentifier = $classConstraintNode->getAttribute( 'class-identifier' );
			$content['class_constraint_list'][] = $classIdentifier;
        }


        $classCreateConstraintsNode = $attributeParametersNode->getElementsByTagName( 'create-constraints' )->item( 0 );
		$classConstraintList = $classCreateConstraintsNode->getElementsByTagName( 'create-constraint' );
		$content['class_create_constraint_list'] = array();
		foreach ( $classConstraintList as $classConstraintNode )
		{
			$classIdentifier = $classConstraintNode->getAttribute( 'class-identifier' );
			$content['class_create_constraint_list'][] = $classIdentifier;
        }


        $objectClassNode = $attributeParametersNode->getElementsByTagName( 'object-class' )->item( 0 );
		if ( $objectClassNode )
		{
			$content['object_class'] = $objectClassNode->textContent;
        }

        $classAttribute->setContent( $content );
        $this->storeClassAttributeContent( $classAttribute, $content );

    }

	function serializeContentObjectAttribute( $package, $objectAttribute )
	{
		$node = $this->createContentObjectAttributeDOMNode( $objectAttribute );

		$dom = new DOMDocument( '1.0', 'utf-8' );
		eZDebug::writeDebug( $objectAttribute->attribute( 'data_text' ), 'xml string from data_text field' );
		$success = $dom->loadXML( $objectAttribute->attribute( 'data_text' ) );
		$rootNode = $dom->documentElement;
		$relationList = $rootNode->getElementsByTagName( 'relation-list' )->item( 0 );
		if ( $relationList )
		{
			require_once( 'kernel/classes/ezcontentobject.php' );
			$relationItems = $relationList->getElementsByTagName( 'relation-item' );
			for ( $i = 0; $i < $relationItems->length; $i++ )
			{
				$relationItem = $relationItems->item( $i );
				// Add related object remote id as attribute to the relation item.
				$relatedObjectID = $relationItem->getAttribute( 'contentobject-id' );
				$relatedObject = eZContentObject::fetch( $relatedObjectID );
				$relatedObjectRemoteID = $relatedObject->attribute( 'remote_id' );
				$relationItem->setAttribute( 'contentobject-remote-id', $relatedObjectRemoteID );

				$attributes = $relationItem->attributes;
				// Remove all other relation item attributes except of "priority".
				// This loop intentionally starts with the last attribute, otherwise you will get unexpected results
				for ( $j = $attributes->length - 1; $j >= 0; $j-- )
				{
					$attribute = $attributes->item( $j );
					$attrName = $attribute->localName;

					eZDebug::writeDebug( $attrName );
					if ( $attrName != 'priority' && $attrName != 'contentobject-remote-id' )
					{
						$success = $relationItem->removeAttribute( $attribute->localName );
						if ( !$success )
						{
							eZDebug::writeError( 'failed removing attribute ' . $attrName . ' from relation-item element' );
						}
					}
				}
			}
		}

		eZDebug::writeDebug( $dom->saveXML(), 'old xml doc' );

		$importedRootNode = $node->ownerDocument->importNode( $rootNode, true );
		$node->appendChild( $importedRootNode );

		return $node;
	}

	/*!
	 \reimp
	*/
	function unserializeContentObjectAttribute( $package, $objectAttribute, $attributeNode )
	{
		$rootNode = $attributeNode->getElementsByTagName( 'ezobjectrelationbrowse' )->item( 0 );
		$xmlString = $rootNode ? $rootNode->textContent : '';
		$objectAttribute->setAttribute( 'data_text', $xmlString );
	}

	function postUnserializeContentObjectAttribute( $package, $objectAttribute )
	{
		$xmlString = $objectAttribute->attribute( 'data_text' );
		$doc = $this->parseXML( $xmlString );
		$rootNode = $doc->documentElement;

		$relationList = $rootNode->getElementsByTagName( 'relation-list' )->item( 0 );
		if ( !$relationList )
			return false;

		require_once( 'kernel/classes/ezcontentobject.php' );
		$relationItems = $relationList->getElementsByTagName( 'relation-item' );
		foreach ( $relationItems as $i => $relationItem )
		{
			$relatedObjectRemoteID = $relationItem->getAttribute( 'contentobject-remote-id' );
			$object = eZContentObject::fetchByRemoteID( $relatedObjectRemoteID );

			if ( $object === null )
			{
				eZDebug::writeWarning( "Object with remote id '$relatedObjectRemoteID' not found: removing the link.",
									   'eZObjectRelationBrowseType::unserializeContentObjectAttribute()' );
				unset( $relationItems[$i] );
				continue;
			}

			$relationItems[$i]->setAttribute( 'contentobject-id',        $object->attribute( 'id' ) );
			$relationItems[$i]->setAttribute( 'contentobject-version',   $object->attribute( 'current_version' ) );
			$relationItems[$i]->setAttribute( 'node-id',                 $object->attribute( 'main_node_id' ) );
			$relationItems[$i]->setAttribute( 'parent-node-id',          $object->attribute( 'main_parent_node_id' ) );
			$relationItems[$i]->setAttribute( 'contentclass-id',         $object->attribute( 'contentclass_id' ) );
			$relationItems[$i]->setAttribute( 'contentclass-identifier', $object->attribute( 'class_identifier' ) );
		}

		$newXmlString = $doc->saveXML( $rootNode );

		$objectAttribute->setAttribute( 'data_text', $newXmlString );
		return true;
	}

	/*!
	 Removes objects with given ID from the relations list
	*/
	function removeRelatedObjectItem( $contentObjectAttribute, $objectID )
	{
		$xmlText = $contentObjectAttribute->attribute( 'data_text' );
		if ( trim( $xmlText ) == '' ) return;

		$doc = eZObjectRelationBrowseType::parseXML( $xmlText );

		$return = false;
		$root = $doc->documentElement;
		$relationList = $root->getElementsByTagName( 'relation-list' )->item( 0 );
		if ( $relationList )
		{
			$relationItems = $relationList->getElementsByTagName( 'relation-item' );
			if ( !empty( $relationItems ) )
			{
				foreach( $relationItems as $relationItem )
				{
					if ( $relationItem->getAttribute( 'contentobject-id' ) == $objectID )
					{
						$relationList->removeChild( $relationItem );
						$return = true;
					}
				}
			}
		}
		eZObjectRelationBrowseType::storeObjectDOMDocument( $doc, $contentObjectAttribute );
		return $return;
	}

    /// \privatesection
}

eZDataType::register( eZObjectRelationBrowseType::DATA_TYPE_STRING, "ezobjectrelationbrowsetype" );

?>
