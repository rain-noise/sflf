<?php
/**
 * 性別ドメイン
 */
class Sex extends Domain
{
	public static $MALE;
	public static $FEMALE;
	
	public static function init() {
		self::$MALE   = new Sex(1, '男性');
		self::$FEMALE = new Sex(2, '女性');
	}
}
Sex::init();
