<?php
/**
 * ページサイズドメイン
 */
class PageSize extends Domain
{
	protected static function generate() {
		return array(
			 new PageSize(10,'10件')
			,new PageSize(25,'25件')
			,new PageSize(50,'50件')
			,new PageSize(100,'100件')
		);
	}
}
