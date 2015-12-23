<?php
/*--------------------------------------------------------------------------------------------------------|  www.vdm.io  |------/
    __      __       _     _____                 _                                  _     __  __      _   _               _
    \ \    / /      | |   |  __ \               | |                                | |   |  \/  |    | | | |             | |
     \ \  / /_ _ ___| |_  | |  | | _____   _____| | ___  _ __  _ __ ___   ___ _ __ | |_  | \  / | ___| |_| |__   ___   __| |
      \ \/ / _` / __| __| | |  | |/ _ \ \ / / _ \ |/ _ \| '_ \| '_ ` _ \ / _ \ '_ \| __| | |\/| |/ _ \ __| '_ \ / _ \ / _` |
       \  / (_| \__ \ |_  | |__| |  __/\ V /  __/ | (_) | |_) | | | | | |  __/ | | | |_  | |  | |  __/ |_| | | | (_) | (_| |
        \/ \__,_|___/\__| |_____/ \___| \_/ \___|_|\___/| .__/|_| |_| |_|\___|_| |_|\__| |_|  |_|\___|\__|_| |_|\___/ \__,_|
                                                        | |                                                                 
                                                        |_| 				
/-------------------------------------------------------------------------------------------------------------------------------/

	@version		1.3.0
	@build			23rd December, 2015
	@created		22nd October, 2015
	@package		Sermon Distributor
	@subpackage		view.html.php
	@author			Llewellyn van der Merwe <https://www.vdm.io/>	
	@copyright		Copyright (C) 2015. All Rights Reserved
	@license		GNU/GPL Version 2 or later - http://www.gnu.org/licenses/gpl-2.0.html 
	
	A sermon distributor that links to Dropbox. 
                                                             
/-----------------------------------------------------------------------------------------------------------------------------*/

// No direct access to this file
defined('_JEXEC') or die('Restricted access');

// import Joomla view library
jimport('joomla.application.component.view');

/**
 * Sermondistributor View class for the Series
 */
class SermondistributorViewSeries extends JViewLegacy
{
	// Overwriting JView display method
	function display($tpl = null)
	{
		// get combined params of both component and menu
		$this->app = JFactory::getApplication();
		$this->params = $this->app->getParams();
		$this->menu = $this->app->getMenu()->getActive();
		// get the user object
		$this->user = JFactory::getUser();
		// [3164] Initialise variables.
		$this->items	= $this->get('Items');
		$this->pagination	= $this->get('Pagination');
		$this->series	= $this->get('Series');
		$this->numberdownloads	= $this->get('NumberDownloads');
		$this->numbersermons	= $this->get('NumberSermons');

		// [3193] Check for errors.
		if (count($errors = $this->get('Errors')))
		{
			JError::raiseWarning(500, implode("\n", $errors));
			return false;
		}
		// add a hit to the series
		if ($this->hit($this->series->id))
		{
			$this->series->hits++;
		}
		// set some total defaults
		$this->sermonTotal = count($this->numbersermons);
		$this->downloadTotal = 0;
		if (isset($this->numberdownloads) && SermondistributorHelper::checkArray($this->numberdownloads))
		{
			foreach ($this->numberdownloads as $download)
			{
				$this->downloadTotal += $download->counter;
			}
		}
		// set the FooTable style
		$style = $this->params->get('series_sermons_table_color', 0);
		if (5 == $style)
		{
			$this->fooTableStyle = 1;
		}
		elseif ($style <= 4)
		{
			$this->fooTableStyle = 0;
		}
		else
		{
			$this->fooTableStyle = 2;
		}

		// [3210] Set the toolbar
		$this->addToolBar();

		// [3212] set the document
		$this->_prepareDocument();

		parent::display($tpl);
	}

	 /**
	 * Increment the hit counter for the series.
	 *
	 * @param   integer  $pk  Primary key of the series to increment.
	 *
	 * @return  boolean  True if successful;
	 */
	public function hit($pk = 0)
	{
		if ($pk)
		{
			$db = JFactory::getDbo();
			$query = $db->getQuery(true);

			// Fields to update.
			$fields = array(
			    $db->quoteName('hits') . ' = '.$db->quoteName('hits').' + 1'
			);

			// Conditions for which records should be updated.
			$conditions = array(
			    $db->quoteName('id') . ' = ' . $pk
			);

			$query->update($db->quoteName('#__sermondistributor_series'))->set($fields)->where($conditions);

			$db->setQuery($query);
			return $db->execute();
		}
		return false;
	}

        /**
	 * Prepares the document
	 */
	protected function _prepareDocument()
	{

		// [3566] always make sure jquery is loaded.
		JHtml::_('jquery.framework');
		// [3568] Load the header checker class.
		require_once( JPATH_COMPONENT_SITE.'/helpers/headercheck.php' );
		// [3570] Initialize the header checker.
		$HeaderCheck = new HeaderCheck;

		// [3575] Load uikit options.
		$uikit = $this->params->get('uikit_load');
		// [3577] Set script size.
		$size = $this->params->get('uikit_min');
		// [3579] Set css style.
		$style = $this->params->get('uikit_style');

		// [3582] The uikit css.
		if ((!$HeaderCheck->css_loaded('uikit.min') || $uikit == 1) && $uikit != 2 && $uikit != 3)
		{
			$this->document->addStyleSheet(JURI::root(true) .'/media/com_sermondistributor/uikit/css/uikit'.$style.$size.'.css');
		}
		// [3587] The uikit js.
		if ((!$HeaderCheck->js_loaded('uikit.min') || $uikit == 1) && $uikit != 2 && $uikit != 3)
		{
			$this->document->addScript(JURI::root(true) .'/media/com_sermondistributor/uikit/js/uikit'.$size.'.js');
		}

		// [3596] Load the script to find all uikit components needed.
		if ($uikit != 2)
		{
			// [3599] Set the default uikit components in this view.
			$uikitComp = array();
			$uikitComp[] = 'data-uk-grid';

			// [3608] Get field uikit components needed in this view.
			$uikitFieldComp = $this->get('UikitComp');
			if (isset($uikitFieldComp) && SermondistributorHelper::checkArray($uikitFieldComp))
			{
				if (isset($uikitComp) && SermondistributorHelper::checkArray($uikitComp))
				{
					$uikitComp = array_merge($uikitComp, $uikitFieldComp);
					$uikitComp = array_unique($uikitComp);
				}
				else
				{
					$uikitComp = $uikitFieldComp;
				}
			}
		}

		// [3624] Load the needed uikit components in this view.
		if ($uikit != 2 && isset($uikitComp) && SermondistributorHelper::checkArray($uikitComp))
		{
			// [3627] load just in case.
			jimport('joomla.filesystem.file');
			// [3629] loading...
			foreach ($uikitComp as $class)
			{
				foreach (SermondistributorHelper::$uk_components[$class] as $name)
				{
					// [3634] check if the CSS file exists.
					if (JFile::exists(JPATH_ROOT.'/media/com_sermondistributor/uikit/css/components/'.$name.$style.$size.'.css'))
					{
						// [3637] load the css.
						$this->document->addStyleSheet(JURI::root(true) .'/media/com_sermondistributor/uikit/css/components/'.$name.$style.$size.'.css');
					}
					// [3640] check if the JavaScript file exists.
					if (JFile::exists(JPATH_ROOT.'/media/com_sermondistributor/uikit/js/components/'.$name.$size.'.js'))
					{
						// [3643] load the js.
						$this->document->addScript(JURI::root(true) .'/media/com_sermondistributor/uikit/js/components/'.$name.$size.'.js');
					}
				}
			}
		}  

		// [6766] Add the CSS for Footable.
		$this->document->addStyleSheet(JURI::root() .'media/com_sermondistributor/footable/css/footable.core.min.css');

		// [6768] Use the Metro Style
		if (!isset($this->fooTableStyle) || 0 == $this->fooTableStyle)
		{
			$this->document->addStyleSheet(JURI::root() .'media/com_sermondistributor/footable/css/footable.metro.min.css');
		}
		// [6773] Use the Legacy Style.
		elseif (isset($this->fooTableStyle) && 1 == $this->fooTableStyle)
		{
			$this->document->addStyleSheet(JURI::root() .'media/com_sermondistributor/footable/css/footable.standalone.min.css');
		}

		// [6778] Add the JavaScript for Footable
		$this->document->addScript(JURI::root() .'media/com_sermondistributor/footable/js/footable.js');
		$this->document->addScript(JURI::root() .'media/com_sermondistributor/footable/js/footable.sort.js');
		$this->document->addScript(JURI::root() .'media/com_sermondistributor/footable/js/footable.filter.js');
		$this->document->addScript(JURI::root() .'media/com_sermondistributor/footable/js/footable.paginate.js'); 
		// [3473] load the meta description
		if (isset($this->series->metadesc) && $this->series->metadesc)
		{
			$this->document->setDescription($this->series->metadesc);
		}
		elseif ($this->params->get('menu-meta_description'))
		{
			$this->document->setDescription($this->params->get('menu-meta_description'));
		}
		// [3482] load the key words if set
		if (isset($this->series->metakey) && $this->series->metakey)
		{
			$this->document->setMetadata('keywords', $this->series->metakey);
		}
		elseif ($this->params->get('menu-meta_keywords'))
		{
			$this->document->setMetadata('keywords', $this->params->get('menu-meta_keywords'));
		}
		// [3491] check the robot params
		if (isset($this->series->robots) && $this->series->robots)
		{
			$this->document->setMetadata('robots', $this->series->robots);
		}
		elseif ($this->params->get('robots'))
		{
			$this->document->setMetadata('robots', $this->params->get('robots'));
		}
		// [3500] check if autor is to be set
		if (isset($this->series->created_by) && $this->params->get('MetaAuthor') == '1')
		{
			$this->document->setMetaData('author', $this->series->created_by);
		}
		// [3505] check if metadata is available
		if (isset($this->series->metadata) && $this->series->metadata)
		{
			$mdata = json_decode($this->series->metadata,true);
			foreach ($mdata as $k => $v)
			{
				if ($v)
				{
					$this->document->setMetadata($k, $v);
				}
			}
		} 
		// add the document default css file
		$this->document->addStyleSheet(JURI::root(true) .'/components/com_sermondistributor/assets/css/series.css'); 
        }

	/**
	 * Setting the toolbar
	 */
	protected function addToolBar()
	{
		// adding the joomla toolbar to the front
		JLoader::register('JToolbarHelper', JPATH_ADMINISTRATOR.'/includes/toolbar.php');
		
		// set help url for this view if found
		$help_url = SermondistributorHelper::getHelpUrl('series');
		if (SermondistributorHelper::checkString($help_url))
		{
			JToolbarHelper::help('COM_SERMONDISTRIBUTOR_HELP_MANAGER', false, $help_url);
		}
		// now initiate the toolbar
		$this->toolbar = JToolbar::getInstance();
	}

        /**
	 * Escapes a value for output in a view script.
	 *
	 * @param   mixed  $var  The output to escape.
	 *
	 * @return  mixed  The escaped value.
	 */
	public function escape($var, $sorten = false, $length = 40)
	{
                // use the helper htmlEscape method instead.
		return SermondistributorHelper::htmlEscape($var, $this->_charset, $sorten, $length);
	}
}
