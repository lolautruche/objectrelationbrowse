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


//This filter has been developed by Mark Boon, see: http://ez.no/developer/contribs/template_plugins/objectrelationfilter

class ObjectRelationBrowseFilter
{
	function ObjectRelationBrowseFilter()
	{
	}

	function createSqlParts($params)
	{

		$test=false;
		$sqlTables= ',ezcontentobject_link AS t0';

		$sqlJoins = ' ezcontentobject_tree.contentobject_id = t0.from_contentobject_id AND ezcontentobject_tree.contentobject_version = t0.from_contentobject_version AND ';

		// first optional param element should be either 'or' or 'and'
		if(!is_numeric($params[0]))
		{
			$matchAll = !(array_shift($params) === 'or');
		}
		else
		{
			$matchAll = true;
		}

		// object matching conditions are collected here
		$sqlCondArray = array();

		// remaining params are pairs of attribute id and object id which should be matched.
		// object id can also be an array of object ids, in that case the match is on either object id.
		$t = 0;
		while(sizeof($params) > 1) {

			$table = 't'.$t;

			$attribute_id = (int)array_shift($params);

			$relatedobject_id = array_shift($params);

			if ($relatedobject_id > 0)
			{

				$test=true;
				if(is_array($relatedobject_id))
				{
					$sqlCond = $table.'.to_contentobject_id IN('.join(',', $relatedobject_id).')';
				}
				else
				{
					$sqlCond = $table.'.to_contentobject_id='.(int)$relatedobject_id;
				}
				$sqlCondArray[] = $table.'.contentclassattribute_id='.$attribute_id.' AND '.$sqlCond;

				if($t++ > 0)
				{
					$sqlTables .= ',ezcontentobject_link AS '.$table;
					$sqlJoins .= ' ezcontentobject_tree.contentobject_id='.$table.'.from_contentobject_id AND ezcontentobject_tree.contentobject_version='.$table.'.from_contentobject_version AND ';
				}
			}
		}

		// add conditions to query
		if(sizeof($sqlCondArray) > 0 and $test==true)
		{
			$sqlJoins .= ' ('.join($matchAll ? ' AND ' : ' OR ', $sqlCondArray).') AND ';
		} else {
			$sqlTables= "";
			$sqlJoins="";
		}

		return array('tables' => $sqlTables, 'joins'  => $sqlJoins);
	}
}

?>