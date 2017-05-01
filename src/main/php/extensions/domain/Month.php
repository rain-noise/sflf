<?php
/**
 * 月ドメイン
 */
class Month extends RangeDomain
{
	public static function start()    { return  1; }
	public static function step()     { return  1; }
	public static function end()      { return 12; }
	public static function format($i) { return sprintf("%02s",$i); }
}
