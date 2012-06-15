<?php

/*
 * This file is part of the CCDN MessageBundle
 *
 * (c) CCDN (c) CodeConsortium <http://www.codeconsortium.com/> 
 * 
 * Available on github <http://www.github.com/codeconsortium/>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace CCDNMessage\MessageBundle\Manager;

use CCDNComponent\CommonBundle\Manager\ManagerInterface;
use CCDNComponent\CommonBundle\Manager\BaseManager;

use CCDNMessage\MessageBundle\Entity\Folder;

/**
 * 
 * @author Reece Fowell <reece@codeconsortium.com> 
 * @version 1.0
 */
class FolderManager extends BaseManager implements ManagerInterface
{
	
	
	/**
	 *
	 * @access public
	 * @param $user_id
	 * @return $this
	 */	
	public function setupDefaults($user_id)
	{
		$user = $this->container->get('ccdn_user_user.user.repository')->findOneById($user_id);
		
		if ( ! $user)
		{
			echo "error, cannot setup PM folders for non-user.";
		}
		
		$folderNames = array(1 => 'inbox', 2 => 'sent', 3 => 'drafts', 4 => 'junk', 5 => 'trash');
		
		foreach($folderNames as $key => $folderName)
		{
			$folder = new Folder();
			$folder->setOwnedBy($user);
			$folder->setName($folderName);
			$folder->setSpecialType($key);
			$folder->setCacheReadCount(0);
			$folder->setCacheUnreadCount(0);
			$folder->setCacheTotalMessageCount(0);
			
			$this->persist($folder);
		}
		
		return $this;
	}
	
	
	/**
	 *
	 * @access public
	 * @param $folder
	 * @return $this
	 */
	public function updateFolderCounterCaches($folder)
	{
		$user = $this->container->get('security.context')->getToken()->getUser();
		
		$readCount = $this->container->get('ccdn_message_message.folder.repository')->getReadCounterForFolderById($folder->getId(), $user->getId());
		$readCount = $readCount['readCount'];
		$unreadCount = $this->container->get('ccdn_message_message.folder.repository')->getUnreadCounterForFolderById($folder->getId(), $user->getId());
		$unreadCount = $unreadCount['unreadCount'];
		$totalCount = ($readCount + $unreadCount);
		
		$folder->setCacheReadCount($readCount);
		$folder->setCacheUnreadCount($unreadCount);
		$folder->setCacheTotalMessageCount($totalCount);
		
		$this->persist($folder);
		
		return $this;
	}
	
	
	/**
	 *
	 * @access public
	 * @param Array $folders
	 */
	public function checkQuotaAllowanceUsed($folders)
	{
		$totalMessageCount = 0;
		
		foreach($folders as $key => $folder)
		{
			$totalMessageCount += $folder->getCacheTotalMessageCount();
		}

		return $totalMessageCount;
	}
	
	
	
	/**
	 *
	 * @access public
	 * @param Array $folders
	 */
	public function getCurrentFolder($folders, $folder_name)
	{
		// find the current folder	
		$currentFolder = null;
		
		foreach($folders as $key => $folder)
		{
			if ($folder->getName() == $folder_name)
			{
				$currentFolder = $folder;
				
				break;
			}
		}
		
		return $currentFolder;
	}
	
	
	
	/**
	 *
	 * @access public
	 * @param Array $folders
	 */
	public function getUsedAllowance($folders, $quota)
	{
		$totalMessageCount = 0;
		
		foreach($folders as $key => $folder)
		{
			$totalMessageCount += $folder->getCacheTotalMessageCount();
		}
		
		$usedAllowance = ($totalMessageCount / $quota) * 100;
		
		// where 100 represents 100%, if the number should exceed then reset it to 100%
		if ($usedAllowance > 99)
		{
			$usedAllowance = 100;
		}
		
		return array('used_allowance' => $usedAllowance, 'total_message_count' => $totalMessageCount);
	}
	
}