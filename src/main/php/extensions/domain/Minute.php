<?php
/**
 * 分ドメイン
 */
class Minute extends RangeDomain
{
	public static function start()    { return  0; }
	public static function step()     { return  1; }
	public static function end()      { return 59; }
	public static function format($i) { return sprintf("%02s",$i); }
}
