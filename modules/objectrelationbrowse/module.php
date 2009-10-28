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


$Module = array(  'name' => 'objectrelationbrowse' );
$ViewList = array();
$ViewList['list'] = array(
    'script' => 'list.php',
    'default_navigation_part' => 'ezsetupnavigationpart',
    'params' => array ( 'Limit', 'AttributeID', 'Phrase' ) );
?>