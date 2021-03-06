<?php
/*-------------------------------------------------------------------------------------------------------------|  www.vdm.io  |------/
 ____                                                  ____                 __               __               __
/\  _`\                                               /\  _`\   __         /\ \__         __/\ \             /\ \__
\ \,\L\_\     __   _ __    ___ ___     ___     ___    \ \ \/\ \/\_\    ____\ \ ,_\  _ __ /\_\ \ \____  __  __\ \ ,_\   ___   _ __
 \/_\__ \   /'__`\/\`'__\/' __` __`\  / __`\ /' _ `\   \ \ \ \ \/\ \  /',__\\ \ \/ /\`'__\/\ \ \ '__`\/\ \/\ \\ \ \/  / __`\/\`'__\
   /\ \L\ \/\  __/\ \ \/ /\ \/\ \/\ \/\ \L\ \/\ \/\ \   \ \ \_\ \ \ \/\__, `\\ \ \_\ \ \/ \ \ \ \ \L\ \ \ \_\ \\ \ \_/\ \L\ \ \ \/
   \ `\____\ \____\\ \_\ \ \_\ \_\ \_\ \____/\ \_\ \_\   \ \____/\ \_\/\____/ \ \__\\ \_\  \ \_\ \_,__/\ \____/ \ \__\ \____/\ \_\
    \/_____/\/____/ \/_/  \/_/\/_/\/_/\/___/  \/_/\/_/    \/___/  \/_/\/___/   \/__/ \/_/   \/_/\/___/  \/___/   \/__/\/___/  \/_/

/------------------------------------------------------------------------------------------------------------------------------------/

	@version		2.0.x
	@created		22nd October, 2015
	@package		Sermon Distributor
	@subpackage		external_sources.php
	@author			Llewellyn van der Merwe <https://www.vdm.io/>	
	@copyright		Copyright (C) 2015. All Rights Reserved
	@license		GNU/GPL Version 2 or later - http://www.gnu.org/licenses/gpl-2.0.html 
	
	A sermon distributor that links to Dropbox. 
                                                             
/----------------------------------------------------------------------------------------------------------------------------------*/

// No direct access to this file
defined('_JEXEC') or die('Restricted access');

// import the Joomla modellist library
jimport('joomla.application.component.modellist');

/**
 * External_sources Model
 */
class SermondistributorModelExternal_sources extends JModelList
{
	public function __construct($config = array())
	{
		if (empty($config['filter_fields']))
        {
			$config['filter_fields'] = array(
				'a.id','id',
				'a.published','published',
				'a.ordering','ordering',
				'a.created_by','created_by',
				'a.modified_by','modified_by',
				'a.description','description',
				'a.externalsources','externalsources',
				'a.update_method','update_method',
				'a.build','build'
			);
		}

		parent::__construct($config);
	}
	
	/**
	 * Method to auto-populate the model state.
	 *
	 * @return  void
	 */
	protected function populateState($ordering = null, $direction = null)
	{
		$app = JFactory::getApplication();

		// Adjust the context to support modal layouts.
		if ($layout = $app->input->get('layout'))
		{
			$this->context .= '.' . $layout;
		}
		$description = $this->getUserStateFromRequest($this->context . '.filter.description', 'filter_description');
		$this->setState('filter.description', $description);

		$externalsources = $this->getUserStateFromRequest($this->context . '.filter.externalsources', 'filter_externalsources');
		$this->setState('filter.externalsources', $externalsources);

		$update_method = $this->getUserStateFromRequest($this->context . '.filter.update_method', 'filter_update_method');
		$this->setState('filter.update_method', $update_method);

		$build = $this->getUserStateFromRequest($this->context . '.filter.build', 'filter_build');
		$this->setState('filter.build', $build);
        
		$sorting = $this->getUserStateFromRequest($this->context . '.filter.sorting', 'filter_sorting', 0, 'int');
		$this->setState('filter.sorting', $sorting);
        
		$access = $this->getUserStateFromRequest($this->context . '.filter.access', 'filter_access', 0, 'int');
		$this->setState('filter.access', $access);
        
		$search = $this->getUserStateFromRequest($this->context . '.filter.search', 'filter_search');
		$this->setState('filter.search', $search);

		$published = $this->getUserStateFromRequest($this->context . '.filter.published', 'filter_published', '');
		$this->setState('filter.published', $published);
        
		$created_by = $this->getUserStateFromRequest($this->context . '.filter.created_by', 'filter_created_by', '');
		$this->setState('filter.created_by', $created_by);

		$created = $this->getUserStateFromRequest($this->context . '.filter.created', 'filter_created');
		$this->setState('filter.created', $created);

		// List state information.
		parent::populateState($ordering, $direction);
	}
	
	/**
	 * Method to get an array of data items.
	 *
	 * @return  mixed  An array of data items on success, false on failure.
	 */
	public function getItems()
	{ 
		// check in items
		$this->checkInNow();

		// load parent items
		$items = parent::getItems();

		// set values to display correctly.
		if (SermondistributorHelper::checkArray($items))
		{
			// get user object.
			$user = JFactory::getUser();
			foreach ($items as $nr => &$item)
			{
				$access = ($user->authorise('external_source.access', 'com_sermondistributor.external_source.' . (int) $item->id) && $user->authorise('external_source.access', 'com_sermondistributor'));
				if (!$access)
				{
					unset($items[$nr]);
					continue;
				}

				// convert filetypes
				$filetypesArray = json_decode($item->filetypes, true);
				if (SermondistributorHelper::checkArray($filetypesArray))
				{
					$filetypesNames = '';
					$counter = 0;
					foreach ($filetypesArray as $filetypes)
					{
						if ($counter == 0)
						{
							$filetypesNames .= JText::_($this->selectionTranslation($filetypes, 'filetypes'));
						}
						else
						{
							$filetypesNames .= ', '.JText::_($this->selectionTranslation($filetypes, 'filetypes'));
						}
						$counter++;
					}
					$item->filetypes = $filetypesNames;
				}
			}
		} 

		// set selection value to a translatable value
		if (SermondistributorHelper::checkArray($items))
		{
			foreach ($items as $nr => &$item)
			{
				// convert externalsources
				$item->externalsources = $this->selectionTranslation($item->externalsources, 'externalsources');
				// convert update_method
				$item->update_method = $this->selectionTranslation($item->update_method, 'update_method');
				// convert filetypes
				$item->filetypes = $this->selectionTranslation($item->filetypes, 'filetypes');
				// convert build
				$item->build = $this->selectionTranslation($item->build, 'build');
			}
		}
 
        
		// return items
		return $items;
	}

	/**
	* Method to convert selection values to translatable string.
	*
	* @return translatable string
	*/
	public function selectionTranslation($value,$name)
	{
		// Array of externalsources language strings
		if ($name === 'externalsources')
		{
			$externalsourcesArray = array(
				0 => 'COM_SERMONDISTRIBUTOR_EXTERNAL_SOURCE_SELECT_AN_OPTION',
				1 => 'COM_SERMONDISTRIBUTOR_EXTERNAL_SOURCE_DROPBOX'
			);
			// Now check if value is found in this array
			if (isset($externalsourcesArray[$value]) && SermondistributorHelper::checkString($externalsourcesArray[$value]))
			{
				return $externalsourcesArray[$value];
			}
		}
		// Array of update_method language strings
		if ($name === 'update_method')
		{
			$update_methodArray = array(
				1 => 'COM_SERMONDISTRIBUTOR_EXTERNAL_SOURCE_MANUAL',
				2 => 'COM_SERMONDISTRIBUTOR_EXTERNAL_SOURCE_AUTOMATIC'
			);
			// Now check if value is found in this array
			if (isset($update_methodArray[$value]) && SermondistributorHelper::checkString($update_methodArray[$value]))
			{
				return $update_methodArray[$value];
			}
		}
		// Array of filetypes language strings
		if ($name === 'filetypes')
		{
			$filetypesArray = array(
				'.mp3' => 'COM_SERMONDISTRIBUTOR_EXTERNAL_SOURCE_MPTHREE',
				'.m4a' => 'COM_SERMONDISTRIBUTOR_EXTERNAL_SOURCE_MFOURA',
				'.ogg' => 'COM_SERMONDISTRIBUTOR_EXTERNAL_SOURCE_OGG',
				'.wav' => 'COM_SERMONDISTRIBUTOR_EXTERNAL_SOURCE_WAV',
				'.mp4' => 'COM_SERMONDISTRIBUTOR_EXTERNAL_SOURCE_MPFOUR',
				'.m4v' => 'COM_SERMONDISTRIBUTOR_EXTERNAL_SOURCE_MFOURV',
				'.mov' => 'COM_SERMONDISTRIBUTOR_EXTERNAL_SOURCE_MOV',
				'.wmv' => 'COM_SERMONDISTRIBUTOR_EXTERNAL_SOURCE_WMV',
				'.avi' => 'COM_SERMONDISTRIBUTOR_EXTERNAL_SOURCE_AVI',
				'.mpg' => 'COM_SERMONDISTRIBUTOR_EXTERNAL_SOURCE_MPG',
				'.ogv' => 'COM_SERMONDISTRIBUTOR_EXTERNAL_SOURCE_OGV',
				'.3gp' => 'COM_SERMONDISTRIBUTOR_EXTERNAL_SOURCE_THREEGP',
				'.3g2' => 'COM_SERMONDISTRIBUTOR_EXTERNAL_SOURCE_THREEGTWO',
				'.pdf' => 'COM_SERMONDISTRIBUTOR_EXTERNAL_SOURCE_PDF',
				'.doc' => 'COM_SERMONDISTRIBUTOR_EXTERNAL_SOURCE_DOC',
				'.docx' => 'COM_SERMONDISTRIBUTOR_EXTERNAL_SOURCE_DOCX',
				'.ppt' => 'COM_SERMONDISTRIBUTOR_EXTERNAL_SOURCE_PPT',
				'.pptx' => 'COM_SERMONDISTRIBUTOR_EXTERNAL_SOURCE_PPTX',
				'.pps' => 'COM_SERMONDISTRIBUTOR_EXTERNAL_SOURCE_PPS',
				'.ppsx' => 'COM_SERMONDISTRIBUTOR_EXTERNAL_SOURCE_PPSX',
				'.odt' => 'COM_SERMONDISTRIBUTOR_EXTERNAL_SOURCE_ODT',
				'.xls' => 'COM_SERMONDISTRIBUTOR_EXTERNAL_SOURCE_XLS',
				'.xlsx' => 'COM_SERMONDISTRIBUTOR_EXTERNAL_SOURCE_XLSX',
				'.zip' => 'COM_SERMONDISTRIBUTOR_EXTERNAL_SOURCE_ZIP',
				'.jpg' => 'COM_SERMONDISTRIBUTOR_EXTERNAL_SOURCE_JPG',
				'.jpeg' => 'COM_SERMONDISTRIBUTOR_EXTERNAL_SOURCE_JPEG',
				'.png' => 'COM_SERMONDISTRIBUTOR_EXTERNAL_SOURCE_PNG',
				'.gif' => 'COM_SERMONDISTRIBUTOR_EXTERNAL_SOURCE_GIF'
			);
			// Now check if value is found in this array
			if (isset($filetypesArray[$value]) && SermondistributorHelper::checkString($filetypesArray[$value]))
			{
				return $filetypesArray[$value];
			}
		}
		// Array of build language strings
		if ($name === 'build')
		{
			$buildArray = array(
				0 => 'COM_SERMONDISTRIBUTOR_EXTERNAL_SOURCE_SELECT_AN_OPTION',
				1 => 'COM_SERMONDISTRIBUTOR_EXTERNAL_SOURCE_MANUAL_LOCAL_SELECTION',
				2 => 'COM_SERMONDISTRIBUTOR_EXTERNAL_SOURCE_DYNAMIC_AUTOMATIC_BUILD'
			);
			// Now check if value is found in this array
			if (isset($buildArray[$value]) && SermondistributorHelper::checkString($buildArray[$value]))
			{
				return $buildArray[$value];
			}
		}
		return $value;
	}
	
	/**
	 * Method to build an SQL query to load the list data.
	 *
	 * @return	string	An SQL query
	 */
	protected function getListQuery()
	{
		// Get the user object.
		$user = JFactory::getUser();
		// Create a new query object.
		$db = JFactory::getDBO();
		$query = $db->getQuery(true);

		// Select some fields
		$query->select('a.*');

		// From the sermondistributor_item table
		$query->from($db->quoteName('#__sermondistributor_external_source', 'a'));

		// Filter by published state
		$published = $this->getState('filter.published');
		if (is_numeric($published))
		{
			$query->where('a.published = ' . (int) $published);
		}
		elseif ($published === '')
		{
			$query->where('(a.published = 0 OR a.published = 1)');
		}
		// Filter by search.
		$search = $this->getState('filter.search');
		if (!empty($search))
		{
			if (stripos($search, 'id:') === 0)
			{
				$query->where('a.id = ' . (int) substr($search, 3));
			}
			else
			{
				$search = $db->quote('%' . $db->escape($search) . '%');
				$query->where('(a.description LIKE '.$search.' OR a.externalsources LIKE '.$search.' OR a.update_method LIKE '.$search.')');
			}
		}

		// Filter by Externalsources.
		if ($externalsources = $this->getState('filter.externalsources'))
		{
			$query->where('a.externalsources = ' . $db->quote($db->escape($externalsources)));
		}
		// Filter by Update_method.
		if ($update_method = $this->getState('filter.update_method'))
		{
			$query->where('a.update_method = ' . $db->quote($db->escape($update_method)));
		}
		// Filter by Build.
		if ($build = $this->getState('filter.build'))
		{
			$query->where('a.build = ' . $db->quote($db->escape($build)));
		}

		// Add the list ordering clause.
		$orderCol = $this->state->get('list.ordering', 'a.id');
		$orderDirn = $this->state->get('list.direction', 'asc');	
		if ($orderCol != '')
		{
			$query->order($db->escape($orderCol . ' ' . $orderDirn));
		}

		return $query;
	}

	/**
	* Method to get list export data.
	*
	* @return mixed  An array of data items on success, false on failure.
	*/
	public function getExportData($pks)
	{
		// setup the query
		if (SermondistributorHelper::checkArray($pks))
		{
			// Set a value to know this is exporting method.
			$_export = true;
			// Get the user object.
			$user = JFactory::getUser();
			// Create a new query object.
			$db = JFactory::getDBO();
			$query = $db->getQuery(true);

			// Select some fields
			$query->select('a.*');

			// From the sermondistributor_external_source table
			$query->from($db->quoteName('#__sermondistributor_external_source', 'a'));
			$query->where('a.id IN (' . implode(',',$pks) . ')');

			// Order the results by ordering
			$query->order('a.ordering  ASC');

			// Load the items
			$db->setQuery($query);
			$db->execute();
			if ($db->getNumRows())
			{
				$items = $db->loadObjectList();

				// Get the basic encryption key.
				$basickey = SermondistributorHelper::getCryptKey('basic');
				// Get the encryption object.
				$basic = new FOFEncryptAes($basickey);

				// set values to display correctly.
				if (SermondistributorHelper::checkArray($items))
				{
					// get user object.
					$user = JFactory::getUser();
					foreach ($items as $nr => &$item)
					{
						$access = ($user->authorise('external_source.access', 'com_sermondistributor.external_source.' . (int) $item->id) && $user->authorise('external_source.access', 'com_sermondistributor'));
						if (!$access)
						{
							unset($items[$nr]);
							continue;
						}

						if ($basickey && !is_numeric($item->oauthtoken) && $item->oauthtoken === base64_encode(base64_decode($item->oauthtoken, true)))
						{
							// decrypt oauthtoken
							$item->oauthtoken = $basic->decryptString($item->oauthtoken);
						}
						// unset the values we don't want exported.
						unset($item->asset_id);
						unset($item->checked_out);
						unset($item->checked_out_time);
					}
				}
				// Add headers to items array.
				$headers = $this->getExImPortHeaders();
				if (SermondistributorHelper::checkObject($headers))
				{
					array_unshift($items,$headers);
				}
				return $items;
			}
		}
		return false;
	}

	/**
	* Method to get header.
	*
	* @return mixed  An array of data items on success, false on failure.
	*/
	public function getExImPortHeaders()
	{
		// Get a db connection.
		$db = JFactory::getDbo();
		// get the columns
		$columns = $db->getTableColumns("#__sermondistributor_external_source");
		if (SermondistributorHelper::checkArray($columns))
		{
			// remove the headers you don't import/export.
			unset($columns['asset_id']);
			unset($columns['checked_out']);
			unset($columns['checked_out_time']);
			$headers = new stdClass();
			foreach ($columns as $column => $type)
			{
				$headers->{$column} = $column;
			}
			return $headers;
		}
		return false;
	} 
	
	/**
	 * Method to get a store id based on model configuration state.
	 *
	 * @return  string  A store id.
	 *
	 */
	protected function getStoreId($id = '')
	{
		// Compile the store id.
		$id .= ':' . $this->getState('filter.id');
		$id .= ':' . $this->getState('filter.search');
		$id .= ':' . $this->getState('filter.published');
		$id .= ':' . $this->getState('filter.ordering');
		$id .= ':' . $this->getState('filter.created_by');
		$id .= ':' . $this->getState('filter.modified_by');
		$id .= ':' . $this->getState('filter.description');
		$id .= ':' . $this->getState('filter.externalsources');
		$id .= ':' . $this->getState('filter.update_method');
		$id .= ':' . $this->getState('filter.build');

		return parent::getStoreId($id);
	}

	/**
	* Build an SQL query to checkin all items left checked out longer then a set time.
	*
	* @return  a bool
	*
	*/
	protected function checkInNow()
	{
		// Get set check in time
		$time = JComponentHelper::getParams('com_sermondistributor')->get('check_in');
		
		if ($time)
		{

			// Get a db connection.
			$db = JFactory::getDbo();
			// reset query
			$query = $db->getQuery(true);
			$query->select('*');
			$query->from($db->quoteName('#__sermondistributor_external_source'));
			$db->setQuery($query);
			$db->execute();
			if ($db->getNumRows())
			{
				// Get Yesterdays date
				$date = JFactory::getDate()->modify($time)->toSql();
				// reset query
				$query = $db->getQuery(true);

				// Fields to update.
				$fields = array(
					$db->quoteName('checked_out_time') . '=\'0000-00-00 00:00:00\'',
					$db->quoteName('checked_out') . '=0'
				);

				// Conditions for which records should be updated.
				$conditions = array(
					$db->quoteName('checked_out') . '!=0', 
					$db->quoteName('checked_out_time') . '<\''.$date.'\''
				);

				// Check table
				$query->update($db->quoteName('#__sermondistributor_external_source'))->set($fields)->where($conditions); 

				$db->setQuery($query);

				$db->execute();
			}
		}

		return false;
	}
}
