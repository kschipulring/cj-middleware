<?php namespace Transcribe;

class Cache
{
	protected static $query_cache = array();

	public static function get( $key )
	{
		$key = self::create_key($key);
		return empty(self::$query_cache[$key]) ? FALSE : self::$query_cache[$key];
	}

	public static function set( $key, $value )
	{
		self::$query_cache[ self::create_key($key) ] = $value;
	}

	private static function create_key( $array )
	{
		return sha1(implode(':', array_filter($array)));
	}

	public static function show_cache()
	{
		return self::$query_cache;
	}
}
