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
 * Class Breadcrumb
 *
 * Base class for handling breadcrumb additions/fixes
 * @copyright  Winans Creative 2012
 * @author     Blair Winans <blair@winanscreative.com>
 * @package    Frontend
 */


abstract class Breadcrumb extends Frontend
{

	/**
	 * Base method for handling breadcrumbs across different modules
	 * @param array - the items array
	 * @param string - the URL key for the reader page ('events', 'items', etc)
	 * @param string - the jumpTo field for the DB lookup
	 * @param string - the title field for the DB lookup
	 * @param string - the table for the title field
	 * @param array - an array of listing module strings to check for the type
	 * @param bool - whether to show the item title as the active last itme in the breadcrumb
	 * @return array
	 */
	public function handleBreadcrumbs($arrItems, $strURLKey, $strJumpTo, $strTitleField, $strTable, $arrListings, $blnShowItem=false)
	{

		if($this->Input->get($strURLKey))
		{
			global $objPage;
			$blnUseReferrer = false;
			$items = array();
			
			//Get the referer to try and determine the correct path to the item
			$intReferrer = $this->getReferringPageID();
			$objReferrer = $intReferrer > 0 ? $this->getPageDetails($intReferrer) : $objPage;
						
			//Get all listing modules that reference this jumpTo page
			$arrListingModules = $this->Database->prepare("SELECT * FROM tl_module WHERE type IN('".implode("','", $arrListings)."') AND $strJumpTo=?")
												->execute($objPage->id)
												->fetchEach('id');
												
			if(!count($arrListingModules))
				$arrListingModules = array(0);
																								
			$strClause = $objReferrer->layout ? " id=".$objReferrer->layout : " fallback='1'";
			
			//First check the referrer's page layout to see if it may contain a listing module
			$objLayout = $this->Database->prepare("SELECT modules FROM tl_layout WHERE$strClause")
												->limit(1)
												->execute();
	
			$arrModules = deserialize($objLayout->modules,true);
						
			$arrIntersect = count($arrModules) ? array_intersect($arrModules, $arrListingModules) : $arrListingModules;
			
			if(!count($arrIntersect))
				$arrIntersect = $arrListingModules;

			//Now check for pages that contain that module as a content element
			$arrPages = $this->Database->execute("SELECT p.id FROM tl_content c LEFT JOIN tl_article a ON a.id=c.pid LEFT JOIN tl_page p ON a.pid=p.id WHERE c.type='module' AND c.module IN(".implode(',', $arrListingModules).")")->fetchEach('id');
			
			//Otherwise we need to find the closest listing page we can find
			if(!in_array($objReferrer->id, $arrPages) && !count($arrIntersect))
			{
				//First check for a page with a listing content element
				if(count($arrPages))
				{
					$objReferrer = $this->getPageDetails($arrPages[0]);
				}
				
				//Then we check for any page layout… this is the last resort
				else
				{
					$objLayouts = $this->Database->execute("SELECT modules FROM tl_layout");
					
					while($objLayouts->next())
					{
						$arrModules = deserialize($objLayouts->modules);
						$arrIntersect = array_intersect($arrModules, $arrListingModules);
						
						if(count($arrIntersect))
						{
							//We found one. Now to find the first example of where it came from
							$objPages = $this->Database->execute("SELECT id FROM tl_page");
							
							while($objPages->next())
							{
								$objPageLayout = $this->getPageDetails($objPages->id);
								
								if($objPageLayout->layout==$objLayouts->id)
								{
									$objReferrer = $objPageLayout;
									continue 2;
								}
							}
						}
					}
				}
			}
			
			$arrTrails = $this->getPageTrails($objReferrer);
						
			if(count($arrTrails>1))
			{
				$items = $this->buildBreadcrumb($this->getPageTrails($objReferrer));			
			
				// Active item
				$items[] = array
				(
					'isRoot' => false,
					'isActive' => $blnShowItem ? false : true,
					'href' => $this->generateFrontendUrl($objReferrer->row()),
					'title' => (($objReferrer->pageTitle) ? specialchars($objReferrer->pageTitle) : specialchars($objReferrer->title)),
					'link' => $objReferrer->title
				);
			}
						
			
			if($blnShowItem)
			{
				$strAlias = $this->Input->get($strURLKey);
	
				if (is_null($strAlias))
				{
					//@todo: Make this editable in a language file
					$strAlias = 'Event';
				}
	
				// Get title
				$objItem = $this->Database->prepare("SELECT $strTitleField FROM $strTable WHERE id=? OR alias=?")
											 ->limit(1)
											 ->execute((is_numeric($strAlias) ? $strAlias : 0), $strAlias);
	
				if ($objItem->numRows)
				{
					$items[] = array
					(
						'isRoot' => false,
						'isActive' => true,
						'title' => specialchars($objItem->$strTitleField),
						'link' => $objProduct->$strTitleField
					);
				}
			}
			
			$arrItems = $items;
		}
								
		return $arrItems;
	}

	
	/**
	 * Return a pageID from any alias
	 * @param string - the alias of the page
	 * @return int
	 */
	protected function getPageIdFromAlias($strURL)
	{
		global $objPage;

		$strAlias = $strURL;
		$strAlias = preg_replace('/\?.*$/i', '', $strAlias);
		$strAlias = preg_replace('/' . preg_quote($GLOBALS['TL_CONFIG']['urlSuffix'], '/') . '$/i', '', $strAlias);
		$arrAlias = explode('/', $strAlias);
		// Skip index.php and empty data
		if (strtolower($arrAlias[0]) == 'index.php' || $arrAlias[0]=='')
		{
			array_shift($arrAlias);
		}

		$objPages = $this->Database->prepare("SELECT id FROM tl_page WHERE alias=?")
											   ->execute($arrAlias[0]);
		while($objPages->next())
		{
			$objPageDetails = $this->getPageDetails($objPages->id);
			//Make sure we are getting the same rootId.. Could be more than one when doing it by alias
			if($objPageDetails->rootId == $objPage->rootId)
			{
				$pageId = $objPages->id;
			}
		}

		return $pageId;

	}
	
	
	/**
	 * Return the referring page ID
	 * @return int
	 */
	protected function getReferringPageID()
	{
		$strReferer = $this->getReferer();

		return $this->getPageIdFromAlias($strReferer);
	}
	
	
	/**
	 * Get an array of the page trails from any page ID
	 * @param object - A page object
	 * @return array
	 */
	protected function getPageTrails($objPage)
	{
		if(is_null($objPage))
			return array();
		
		$arrReturn = array();
		$pages = array();
		$page = $objPage->id;
		
		// Get all pages up to the root page
		do
		{
			$objPages = $this->Database->prepare("SELECT * FROM tl_page WHERE id=?")
									   ->limit(1)
									   ->execute($page);

			$type = $objPages->type;
			$page = $objPages->pid;
			$pages[] = $objPages->row();
		}
		while ($page > 0 && $type != 'root' && $objPages->numRows);

		if ($type == 'root')
		{

			if (!$GLOBALS['BREADCRUMB_INCLUDEROOT'])
			{
				array_pop($pages);
			}

			$arrReturn = $pages;
		}
						
		return $arrReturn;
	}
	
	
	/**
	 * Build the breadcrumb based on a page trail array
	 * @param array - an array of page trails
	 * @return array
	 */
	protected function buildBreadcrumb($pages)
	{		
		global $objPage;
		
		$items = array();
		$type = null;

		// Link to website root
		if ($GLOBALS['BREADCRUMB_INCLUDEROOT'])
		{
			//Pop the last item off since it will be the root page
			$arrHome = array_pop($pages);

			$items[] = array
			(
				'isRoot' => true,
				'isActive' => false,
				'href' => $this->Environment->base,
				'title' => $arrHome['name'],
				'link' => $arrHome['title']
			);
		}
		
		// Build breadcrumb menu
		for ($i=(count($pages)-1); $i>0; $i--)
		{
			if (($pages[$i]['hide'] && !$GLOBALS['BREADCRUMB_SHOWHIDDEN']) || (!$pages[$i]['published'] && !BE_USER_LOGGED_IN))
			{
				continue;
			}
			
			// Get href
			switch ($pages[$i]['type'])
			{
				case 'redirect':
					$href = $pages[$i]['url'];

					if (strncasecmp($href, 'mailto:', 7) === 0)
					{
						$this->import('String');
						$href = $this->String->encodeEmail($href);
					}
					break;

				case 'forward':
					$objNext = $this->Database->prepare("SELECT id, alias FROM tl_page WHERE id=?")
											  ->limit(1)
											  ->execute($pages[$i]['jumpTo']);

					if ($objNext->numRows)
					{
						$href = $this->generateFrontendUrl($objNext->fetchAssoc());
						break;
					}
					// DO NOT ADD A break; STATEMENT

				default:
					$href = $this->generateFrontendUrl($pages[$i]);
					break;
			}
			
			$items[] = array
			(
				'isRoot' => false,
				'isActive' => false,
				'href' => $href,
				'title' => (strlen($pages[$i]['pageTitle']) ? specialchars($pages[$i]['pageTitle']) : specialchars($pages[$i]['title'])),
				'link' => $pages[$i]['title']
			);
		}
				
		return $items;
	
	}

}