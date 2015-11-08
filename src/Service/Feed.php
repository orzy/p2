<?php
/**
 *  P2_Service_Feed
 *
 *  require
 *      * P2_Service_Base
 *
 *  @version 2.2.0
 *  @see     https://github.com/orzy/p2
 *  @license The MIT license (http://www.opensource.org/licenses/mit-license.php)
 */
class P2_Service_Feed extends P2_Service_Base {
	public $title;
	public $link;
	public $description;

	public function get($url) {
		$xml = parent::request($url);
		$items = array();
		
		if ($xml->channel) {	//RSS
			$channel = $xml->channel;

			$this->title = (string)$channel->title;
			$this->link = (string)$channel->link;
			$this->description = (string)$channel->description;

			if ($xml->item) {	//RSS 1.0
				$itemParent = $xml;
			} else {	//RSS 2.0
				$itemParent = $channel;
			}
			foreach ($itemParent->item as $item) {
				$items[] = $item;
			}
		} else {	//Atom
			$this->title = (string)$xml->title;
			$this->link = (string)$xml->link['href'];
			$this->description = (string)$xml->subtitle;

			foreach ($xml->entry as $item) {
				$items[] = $item;
			}
		}
		
		return $items;
	}
}
