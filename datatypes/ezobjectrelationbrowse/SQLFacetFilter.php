<?php
	class SQLFacetFilter
	{
		function SQLFacetFilter()
		{
		}

		function createSqlParts($params)
		{
			$test=false;

			// first optional param element should be either 'or' or 'and'

			$matchAll = $params['bolean'];

			if (count($params['object_id'])>0)
			{
				$sqlTables= ',ezcontentobject_link AS t0';
				$sqlJoins = ' ezcontentobject_tree.contentobject_id = t0.from_contentobject_id AND ezcontentobject_tree.contentobject_version = t0.from_contentobject_version AND ';
			    $sqlAddCondition = true;
			} else {
				$sqlTables= "";
				$sqlJoins = "";
			}

			if (count($params['object_id'])>0)
			{

				// object matching conditions are collected here
				$sqlCondArray = array();

				$t = 0;


				while(sizeof($params['object_id']) > 0) {

					$table = 't'.$t;
					$relatedobject_id = array_shift($params['object_id']);
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

						$sqlCondArray[] = $sqlCond;

						if($t++ > 0)
						{
							$sqlTables .= ',ezcontentobject_link AS '.$table;
							$sqlJoins .= ' ezcontentobject_tree.contentobject_id='.$table.'.from_contentobject_id AND ezcontentobject_tree.contentobject_version='.$table.'.from_contentobject_version AND ';

						}

					}


				}

				// add conditions to query
				if($sqlAddCondition and sizeof($sqlCondArray) > 0 and $test==true)
				{
					$sqlJoins .= ' ('.join($matchAll ? ' AND ' : ' OR ', $sqlCondArray).') AND ';
				} else {
					$sqlTables= "";
					$sqlJoins="";
				}

			}


			if ($params['subnode_id']>0)
			{
				$sqlTables .= " , ezcontentobject_tree n1, eznode_assignment n2, ezcontentobject_tree n3 ";
				$sqlJoins .= "  ezcontentobject_tree.parent_node_id = n1.node_id AND n1.contentobject_id = n2.contentobject_id AND n2.parent_node = '".$params[subnode_id]."'  AND n2.contentobject_id=n3.contentobject_id AND n3.parent_node_id = '".$params[subnode_id]."' AND n3.is_hidden=0 AND n3.is_invisible=0 AND ";
			}

			return array('tables' => $sqlTables, 'joins'  => $sqlJoins);
		}
	}

?>