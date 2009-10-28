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


include_once('autoload.php');

$FunctionList = array();

// Fetches reverse related objects array
$FunctionList['reverse_related_objects'] = array( 'name' => 'ReverseRelatedObjects',
                                 'call_method' => array( 'class' => 'eZObjectRelationBrowseType',
                                 'method' => 'fetchReverseRelatedObjects' ),
                                 'parameter_type' => 'standard',
                                 'parameters' => array( array( 'name' => 'node_id_array',
                                                               'type' => 'array',
                                                               'required' => true,
                                                               'default' => 2),
                                                        array( 'name' => 'parent_node_id',
                                                               'type' => 'integer',
                                                               'required' => true,
                                                               'default' => 2 ),
                                                        array( 'name' => 'tree',
                                                               'type' => 'bool',
                                                               'required' => false,
                                                               'default' => false ) ) );
$FunctionList['node'] = array( 'name' => 'Node',
                                 'call_method' => array( 'class' => 'eZObjectRelationBrowseType',
                                 'method' => 'fetchNode' ),
                                 'parameter_type' => 'standard',
                                 'parameters' => array( array( 'name' => 'node_id',
                                                               'type' => 'integer',
                                                               'required' => true,
                                                               'default' => 2),
                                                        array( 'name' => 'locale',
                                                               'type' => 'string',
                                                               'required' => false,
                                                               'default' => false ) ) );

$FunctionList['facetfiltered'] = array(
		'name' => 'facetfiltered',
		'operation_types' 	=> array( 'read' ),
		'call_method' 		=> array(
			'include_file' => 'extension/objectrelationbrowse/datatypes/ezobjectrelationbrowse/FacetFilterLib.php',
			'class' => 'FacetFilterLib',
			'method' => 'fetchFilteredObjects'
		),
		'parameter_type' => 'standard',
		'parameters' => array(
			array(
				'name' => 'rev_selected_object_ids',
				'type' => 'array',
				'required' => false,
				'default' => false
			),
			array(
				'name' => 'top_node_id',
				'type' => 'integer',
				'required' => true,
				'default' => 2
			),
			array(
				'name' => 'rev_top_node_id',
				'type' => 'integer',
				'required' => true,
				'default' => 2
			),
			array(
				'name' => 'phrase',
				'type' => 'string',
				'required' => false,
				'default' => false
			),
			array( 'name' => 'sort_by',
				'type' => 'array',
				'required' => false,
				'default' => array()
			),
			array(
				'name' => 'limit',
				'type' => 'integer',
				'required' => false,
				'default' => 10
			),
			array(
				'name' => 'offset',
				'type' => 'integer',
				'required' => false,
				'default' => 0
			),
			array(
				'name' => 'boolop',
				'type' => 'string',
				'required' => false,
				'default' => 'and'
			)
		)
	);
?>
