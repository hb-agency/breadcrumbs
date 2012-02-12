<?php if (!defined('TL_ROOT')) die('You can not access this file directly!');

/**
 * Contao Open Source CMS
 * Copyright (C) 2005-2010 Leo Feyer
 *
 * Formerly known as TYPOlight Open Source CMS.
 *
 * This program is free software: you can redistribute it and/or
 * modify it under the terms of the GNU Lesser General Public
 * License as published by the Free Software Foundation, either
 * version 3 of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU
 * Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public
 * License along with this program. If not, please visit the Free
 * Software Foundation website at <http://www.gnu.org/licenses/>.
 *
 * PHP version 5
 * @copyright  Leo Feyer 2005-2010
 * @author     Leo Feyer <http://www.contao.org>
 * @package    Frontend
 * @license    LGPL
 * @filesource
 */


/**
 *Hooks
 */
$GLOBALS['TL_HOOKS']['generateBreadcrumb'][] = array('BreadcrumbRoot','checkForRoot');
$GLOBALS['TL_HOOKS']['generateBreadcrumb'][] = array('BreadcrumbEvents','addBreadcrumb');
$GLOBALS['TL_HOOKS']['generateBreadcrumb'][] = array('BreadcrumbIsotope','addBreadcrumb');



/**
 *Global Settings
 */
$GLOBALS['BREADCRUMB_INCLUDEROOT'] = false;
$GLOBALS['BREADCRUMB_SHOWHIDDEN'] = false;
$GLOBALS['BREADCRUMB_SHOWPRODUCT'] = true;
$GLOBALS['BREADCRUMB_SHOWEVENT'] = true;

/**
 *Global listing module types
 */
$GLOBALS['LISTINGMODS'] = array(
	
	'events' => array(
		'eventlist',
		'iso_eventlist',
	),
	'news' => array(
		'newslist',
	),
	'products' => array(
		'iso_productlist',
		'iso_productvariantlist',
	)
 
);

?>