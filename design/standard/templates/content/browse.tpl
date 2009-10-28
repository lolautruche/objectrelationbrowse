{let item_type=ezpreference( 'admin_list_limit' )
     number_of_items=min( $item_type, 3)|choose( 10, 10, 25, 50 )
     browse_list_count=fetch( content, list_count, hash( parent_node_id, $node_id, depth, 1))
     node_array=fetch( content, list, hash( parent_node_id, $node_id, depth, 1, offset, $view_parameters.offset, limit, $number_of_items, sort_by, $main_node.sort_array ) )
     select_name='SelectedObjectIDArray'
     select_type='checkbox'
     select_attribute='contentobject_id'
     browse_type=cond(is_set($browse.browse_type),$browse.browse_type,0)}

{section show=eq( $browse.return_type, 'NodeID' )}
    {set select_name='SelectedNodeIDArray'}
    {set select_attribute='node_id'}
{/section}

{section show=eq( $browse.selection, 'single' )}
    {set select_type='radio'}
{/section}

{section show=$browse.description_template}
    {include name=Description uri=$browse.description_template browse=$browse main_node=$main_node}
{section-else}

<div class="context-block">

{* DESIGN: Header START *}<div class="box-header"><div class="box-tc"><div class="box-ml"><div class="box-mr"><div class="box-tl"><div class="box-tr">

<h1 class="context-title">{'Browse'|i18n( 'design/admin/content/browse' )}</h1>

{* DESIGN: Mainline *}<div class="header-mainline"></div>

{* DESIGN: Header END *}</div></div></div></div></div></div>

{* DESIGN: Content START *}<div class="box-bc"><div class="box-ml"><div class="box-mr"><div class="box-bl"><div class="box-br"><div class="box-content">

<div class="block">

<p>{'To select objects, choose the appropriate radiobutton or checkbox(es), and click the "Choose" button.'|i18n( 'design/admin/content/browse' )}</p>
<p>{'To select an object that is a child of one of the displayed objects, click the object name and you will get a list of the children of the object.'|i18n( 'design/admin/content/browse' )}</p>

</div>

{* DESIGN: Content END *}</div></div></div></div></div></div>

</div>

{/section}


<div class="context-block">

<form name="browse" method="post" action={$browse.from_page|ezurl}>

{* DESIGN: Header START *}<div class="box-header"><div class="box-tc"><div class="box-ml"><div class="box-mr"><div class="box-tl"><div class="box-tr">
{switch match=$browse_type}
        {case}
		{let current_node=fetch( content, node, hash( node_id, $browse.start_node ) )}

		{section show=$browse.start_node|gt( 1 )}
		    <h2 class="context-title">
		    <a href={concat( '/content/browse/', $main_node.parent_node_id, '/' )|ezurl}><img src={'back-button-16x16.gif'|ezimage} alt="{'Back'|i18n( 'design/admin/content/browse' )}" /></a>
		    {$current_node.object.content_class.identifier|class_icon( original, $current_node.object.content_class.name|wash )}&nbsp;{$current_node.name|wash}&nbsp;[{$current_node.children_count}]</h2>
		{section-else}
		    <h2 class="context-title"><img src={'back-button-16x16.gif'|ezimage} alt="Back" /> {'folder'|class_icon( small, $current_node.object.content_class.name|wash )}&nbsp;{'Top level'|i18n( 'design/admin/content/browse' )}&nbsp;[{$current_node.children_count}]</h2>
		{/section}
		{/let}

		{* DESIGN: Subline *}<div class="header-subline"></div>

		{* DESIGN: Header END *}</div></div></div></div></div></div>

		{* DESIGN: Content START *}<div class="box-ml"><div class="box-mr"><div class="box-content">

		{* Items per page and view mode selector. *}
		<div class="context-toolbar">
		<div class="block">
		<div class="left">
		    <p>
		    {switch match=$number_of_items}
		    {case match=25}
			<a href={'/user/preferences/set/admin_list_limit/1'|ezurl}>10</a>
			<span class="current">25</span>
			<a href={'/user/preferences/set/admin_list_limit/3'|ezurl}>50</a>

			{/case}

			{case match=50}
			<a href={'/user/preferences/set/admin_list_limit/1'|ezurl}>10</a>
			<a href={'/user/preferences/set/admin_list_limit/2'|ezurl}>25</a>
			<span class="current">50</span>
			{/case}

			{case}
			<span class="current">10</span>
			<a href={'/user/preferences/set/admin_list_limit/2'|ezurl}>25</a>
			<a href={'/user/preferences/set/admin_list_limit/3'|ezurl}>50</a>
			{/case}

			{/switch}
		    </p>
		</div>
		<div class="right">
		    <p>
		    <a href={'content/browse/2'|ezurl}>{'Content'|i18n( 'design/admin/content/browse' )}</a>
		    <a href={'content/browse/43'|ezurl}>{'Media'|i18n( 'design/admin/content/browse' )}</a>
		    <a href={'content/browse/5'|ezurl}>{'Users'|i18n( 'design/admin/content/browse' )}</a>
		    {switch match=ezpreference( 'admin_children_browsemode' )}
		    {case match='thumbnail'}
		      <a href={'/user/preferences/set/admin_children_browsemode/list'|ezurl} title="{'Display sub items using a simple list.'|i18n( 'design/admin/content/browse' )}">{'List'|i18n( 'design/admin/content/browse' )}</a>
		      <span class="current">{'Thumbnail'|i18n( 'design/admin/content/browse' )}</span>
		    {/case}
		    {case}
		      <span class="current">{'List'|i18n( 'design/admin/content/browse' )}</span>
		      <a href={'/user/preferences/set/admin_children_browsemode/thumbnail'|ezurl} title="{'Display sub items as thumbnails.'|i18n( 'design/admin/content/browse' )}">{'Thumbnail'|i18n( 'design/admin/content/browse' )}</a>
		    {/case}
		    {/switch}
		    </p>
		</div>
		<div class="break"></div>
		</div>
		</div>

		{* Display the actual list of nodes. *}
		{switch match=ezpreference( 'admin_children_browsemode' )}
		    {case match='thumbnail'}
			{include uri='design:content/browse_mode_thumbnail.tpl'}
		    {/case}
		    {case}
			{include uri='design:content/browse_mode_list.tpl'}
		    {/case}
		{/switch}
		
		<div class="context-toolbar">
			{include name=Navigator
		         uri='design:navigator/google.tpl'
		         page_uri=concat( '/content/browse/', $main_node.node_id )
		         item_count=$browse_list_count
		         view_parameters=$view_parameters
		         item_limit=$number_of_items}
		</div>

	{/case}
	{case match=1}
		<h2 class="context-title">{"Dynamic List"|i18n("design/standard/content/browse")} - {$main_node.name|wash}</h2>
		
		{* DESIGN: Subline *}<div class="header-subline"></div>
		
		{* DESIGN: Header END *}</div></div></div></div></div></div>
		
		{* DESIGN: Content START *}<div class="box-ml"><div class="box-mr"><div class="box-content">
		<div class="block">
		<script language="javascript"  type="text/javascript">
			
			var url = "{"list.php"|ezroot(no)}?node={$browse.start_node}&param=2"; // The server-side script
			{literal}
			function handleHttpResponse() {
			 if (http.readyState == 4) {
			    if (http.responseText.indexOf('invalid') == -1) {
			      // Use the XML DOM to unpack the objectid and objectname data
			      document.getElementById("count").innerHTML='loading...';
			      var xmlDocument = http.responseXML;
			      var count = xmlDocument.getElementsByTagName('count').item(0).firstChild.data;
			      var objectlist = '<select id="items_left"  style="width: 300px;" multiple size="15" onChange="javascript:setTitle(items_left);">';
			      var nodelist = '';
			      var namelist = '';


				 for (i = 0; i < count; i++)
				 {



					 if (xmlDocument.getElementsByTagName('objectid'))
						var objectid = xmlDocument.getElementsByTagName('objectid').item(i).firstChild.data;

					  if (xmlDocument.getElementsByTagName('objectname'))
						var objectname = xmlDocument.getElementsByTagName('objectname').item(i).firstChild.data;

					  if (xmlDocument.getElementsByTagName('nodeid'))
						var nodeid = xmlDocument.getElementsByTagName('nodeid').item(i).firstChild.data;
					  objectlist = objectlist + "<option value='"+ objectid + "'>" + objectname + "</option>";
					  nodelist = nodelist + "<input type='hidden' name='node_" + objectid + "' value='" + nodeid + "'/>";
					  namelist = namelist + "<input type='hidden' name='name_" + objectid + "' value='" + objectname + "'/>";
				  }

				  document.getElementById("selectbox").innerHTML=objectlist + '</select>';
				  document.getElementById("nodelist").innerHTML=nodelist;
				  document.getElementById("namelist").innerHTML=namelist;
				  document.getElementById('count').innerHTML= count;
				  if (count>0) 
				  {
					  document.getElementById("items_left").options[0].selected=true;
					  from=document.getElementById("items_left");
					  setTitle(from);
				  }

				 if (count > 99)
				 {
				   document.getElementById("count").innerHTML='100+';
				   //document.getElementById("selectbox").innerHTML= objectlist + "<option value=''></option></select>";
				  }
			      isWorking = false;
			    }
			  }
			}
			var isWorking = false;

			function updateName() 
			{
			  document.getElementById("selectedtitle").innerHTML="";
			  document.getElementById("count").innerHTML='loading.';
			  if (!isWorking && http) {
			    var objectnameValue = document.getElementById("objectname").value;
			    http.open("GET", url + escape(objectnameValue), true);
			    http.onreadystatechange = handleHttpResponse;
			    isWorking = true;
			    http.send(null);
			    document.getElementById("count").innerHTML='loading..';
			  }
			}

			function setTitle(from)
			{
				SI=from.selectedIndex;
				object_title=from.options[SI].text;
				keyword=document.getElementById("objectname").value.split(" ");
					for (var i in keyword)
					{
						object_title=object_title.replace(keyword[i],'<b>' + keyword[i] + '</b>');
					}	
				document.getElementById("selectedtitle").innerHTML=object_title;

			}

			function getHTTPObject() {
			  var xmlhttp;
			  /*@cc_on
			  @if (@_jscript_version >= 5)
			    try {
			      xmlhttp = new ActiveXObject("Msxml2.XMLHTTP");
			    } catch (e) {
			      try {
				xmlhttp = new ActiveXObject("Microsoft.XMLHTTP");
			      } catch (E) {
				xmlhttp = false;
			      }
			    }
			  @else
			  xmlhttp = false;
			  @end @*/
			  if (!xmlhttp && typeof XMLHttpRequest != 'undefined') {
			    try {
			      xmlhttp = new XMLHttpRequest();
				  xmlhttp.overrideMimeType("text/xml");
			    } catch (e) {
			      xmlhttp = false;
			    }
			  }
			  return xmlhttp;
			}
			var http = getHTTPObject(); // We create the HTTP Object


			/*
			scripts below by Babvailiica
			www.babailiica.com
			*/

			function move_item(from, to)
			{
			  var f;
			  var SI; /* selected Index */
			  if(from.options.length>0)
			  {
			    for(i=0;i<from.length;i++)
			    {
			      if(from.options[i].selected)
			      {
				SI=from.selectedIndex;
				f=from.options[SI].index;
				to.options[to.length]=new Option(from.options[SI].text,from.options[SI].value);
				from.options[f]=null;

				i--; /* make the loop go through them all */
			      }
			    }
			  }
			}

			function selectall(obj) {
				if (obj.length < 1) 
					return false;
				obj = (typeof obj == "string") ? document.getElementById(obj) : obj;
				if (obj.tagName.toLowerCase() != "select")
					return;
				for (i=0; i<obj.length; i++) {
					obj[i].selected = true;
				}
				if (i==0) {
					return false;
				}
			}


			function up(obj) {
				obj = (typeof obj == "string") ? document.getElementById(obj) : obj;
				if (obj.tagName.toLowerCase() != "select" && obj.length < 2)
					return false;
				var sel = new Array();
				for (i=0; i<obj.length; i++) {
					if (obj[i].selected == true) {
						sel[sel.length] = i;
					}
				}
				for (i in sel) {
					if (sel[i] != 0 && !obj[sel[i]-1].selected) {
						var tmp = new Array(obj[sel[i]-1].text, obj[sel[i]-1].value, obj[sel[i]-1].style.color, obj[sel[i]-1].style.backgroundColor, obj[sel[i]-1].className, obj[sel[i]-1].id);
						obj[sel[i]-1].text = obj[sel[i]].text;
						obj[sel[i]-1].value = obj[sel[i]].value;
						obj[sel[i]-1].style.color = obj[sel[i]].style.color;
						obj[sel[i]-1].style.backgroundColor = obj[sel[i]].style.backgroundColor;
						obj[sel[i]-1].className = obj[sel[i]].className;
						obj[sel[i]-1].id = obj[sel[i]].id;
						obj[sel[i]].text = tmp[0];
						obj[sel[i]].value = tmp[1];
						obj[sel[i]].style.color = tmp[2];
						obj[sel[i]].style.backgroundColor = tmp[3];
						obj[sel[i]].className = tmp[4];
						obj[sel[i]].id = tmp[5];
						obj[sel[i]-1].selected = true;
						obj[sel[i]].selected = false;
					}
				}
			}

			function down(obj) {
				obj = (typeof obj == "string") ? document.getElementById(obj) : obj;
				if (obj.tagName.toLowerCase() != "select" && obj.length < 2)
					return false;
				var sel = new Array();
				for (i=obj.length-1; i>-1; i--) {
					if (obj[i].selected == true) {
						sel[sel.length] = i;
					}
				}
				for (i in sel) {
					if (sel[i] != obj.length-1 && !obj[sel[i]+1].selected) {
						var tmp = new Array(obj[sel[i]+1].text, obj[sel[i]+1].value, obj[sel[i]+1].style.color, obj[sel[i]+1].style.backgroundColor, obj[sel[i]+1].className, obj[sel[i]+1].id);
						obj[sel[i]+1].text = obj[sel[i]].text;
						obj[sel[i]+1].value = obj[sel[i]].value;
						obj[sel[i]+1].style.color = obj[sel[i]].style.color;
						obj[sel[i]+1].style.backgroundColor = obj[sel[i]].style.backgroundColor;
						obj[sel[i]+1].className = obj[sel[i]].className;
						obj[sel[i]+1].id = obj[sel[i]].id;
						obj[sel[i]].text = tmp[0];
						obj[sel[i]].value = tmp[1];
						obj[sel[i]].style.color = tmp[2];
						obj[sel[i]].style.backgroundColor = tmp[3];
						obj[sel[i]].className = tmp[4];
						obj[sel[i]].id = tmp[5];
						obj[sel[i]+1].selected = true;
						obj[sel[i]].selected = false;
					}
				}
			}

			function viceversa(obj) {
				obj = (typeof obj == "string") ? document.getElementById(obj) : obj;
				if (obj.tagName.toLowerCase() != "select" && obj.length < 2)
					return false;
				var elements = new Array();
				for (i=obj.length-1; i>-1; i--) {
					elements[elements.length] = new Array(obj[i].text, obj[i].value, obj[i].style.color, obj[i].style.backgroundColor, obj[i].className, obj[i].id, obj[i].selected);
				}
				for (i=0; i<obj.length; i++) {
					obj[i].text = elements[i][0];
					obj[i].value = elements[i][1];
					obj[i].style.color = elements[i][2];
					obj[i].style.backgroundColor = elements[i][3];
					obj[i].className = elements[i][4];
					obj[i].id = elements[i][5];
					obj[i].selected = elements[i][6];
				}
			}


			{/literal}
		</script>


		<br/>
		<input type="text" size="40" name="objectname" id="objectname" autocomplete="off"  onKeyUp="javascript:updateName();"/> <input type="submit" value="{'Go'|i18n('design/standard/content/view')}" onClick="updateName();return false;">
		<br/><br/>
		<p>{'Items found:'|i18n('design/standard/content/view')} <span id="count" style="display: inline;">0</span></p>
		</p>
		<div id="nodelist" style="display: inline;"></div>
		<div id="namelist" style="display: inline;"></div>
		
		<table border=0 class=wrap align=left cellpadding=3 cellspacing=0><tr><td>
		<div id="selectbox"><select size="15" style="width: 300px;" id="items_left"><option value="">{'Enter your search term(s)'|i18n('design/standard/content/view')}</option></select></div></td>
		<td valign="top"><img src={"reverse.gif"|ezimage} onclick="viceversa('items_left');" title="{'Reverse list'|i18n('design/standard/content/view')}" /></td>
		<td>
		<input type="button" value = "     {'Add'|i18n('design/standard/content/view')}    >  " onClick="move_item(items_left, items_right)" style="width: 100px;"><br>
		<input type="button" value = "< {'Remove'|i18n('design/standard/content/view')} " onClick="move_item(items_right,items_left)"  style="width: 100px;">
		</td>
		<td valign="top" style="width: 20px;"></td>
		<td>
		<select id="items_right" name="SelectedObjectIDArray[]" multiple size="15" style="width: 300px;">
		</select>
		</td>
		<td valign="top">
		<img src={"move-up.gif"|ezimage} onclick="up('items_right');" title="{'Move selected item up'|i18n('design/standard/content/view')}" /><br/><br/>
		<img src={"move-down.gif"|ezimage} onclick="down('items_right');" title="{'Move selected item down'|i18n('design/standard/content/view')}" />
		</td>
		</tr>
		</table><br/>	
		<br clear="all">
		<span id="selectedtitle" style="display: inline;" class="small">&nbsp;</span>
		<br/><hr><br/>
		</div>
        {/case}
{/switch}



{section var=PersistentData show=$browse.persistent_data loop=$browse.persistent_data}
    <input type="hidden" name="{$PersistentData.key|wash}" value="{$PersistentData.item|wash}" />
{/section}

<input type="hidden" name="BrowseActionName" value="{$browse.action_name}" />
{section show=$browse.browse_custom_action}
    <input type="hidden" name="{$browse.browse_custom_action.name}" value="{$browse.browse_custom_action.value}" />
{/section}

{section show=$cancel_action}
<input type="hidden" name="BrowseCancelURI" value="{$cancel_action}" />
{/section}

{* DESIGN: Content END *}</div></div></div>

<div class="controlbar">
{* DESIGN: Control bar START *}<div class="box-bc"><div class="box-ml"><div class="box-mr"><div class="box-tc"><div class="box-bl"><div class="box-br">
<div class="block">
    <input class="button" type="submit" {section show=eq($browse_type,1)}onClick="javascript:selectall('items_right')"{/section} name="SelectButton" value="{'Select'|i18n('design/standard/content/browse')}" />
    <input class="button" type="submit" name="BrowseCancelButton" value="{'Cancel'|i18n( 'design/admin/content/browse' )}" />
</div>
{* DESIGN: Control bar END *}</div></div></div></div></div></div>
</div>

</form>

{/let}

</div>
