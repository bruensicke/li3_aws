<?php

namespace li3_aws\core;

use li3_aws\util\S3 as S3_utility;

use lithium\util\String;

class S3 extends \lithium\core\staticObject {

	/**
	 * Class dependencies.
	 *
	 * @var array
	 */
	protected static $_classes = array(
		'socket' => 'li3_aws\util\S3',
		'connection' => 'lithium\data\Connections',
		'logger' => 'lithium\analysis\Logger',
		'cache' => 'lithium\storage\Cache'
	);

	/**
	 * Holds instance of s3 utitlity class
	 *
	 * @var object
	 */
	public static $_object;

	public static function buckets() {
		$buckets = self::_connect()->listBuckets();
		return $buckets;
	}

	public static function bucket($name, array $options = array()) {
		$defaults = array(
			'cache' => 'default',
			'cache_key' => 's3.bucket.{:nane}');
		$options += $defaults;
		$cache = static::$_classes['cache'];
		$cache_key = String::insert($options['cache_key'], compact('name'));
		if (!empty($options['cache']) && ($result = $cache::read($options['cache'], $cache_key))) {
			return $result;
		}

		$_object = self::_connect();
		$location = $_object->getBucketLocation($name);
		if ($location == 'EU') {
			$_object->setEndpoint('s3-eu-west-1.amazonaws.com');
		}
		$result = $_object->getBucket($name);
		$folders = array();
		foreach ($result as $key => $item) {
			$result[$key]['is_folder'] = (boolean) (substr($key, -1) == '/');
			$result[$key]['is_file'] = (boolean) (!$result[$key]['is_folder']);
			$result[$key]['url'] = $name . '.' . S3_utility::$endpoint . '/' . $key;
		}
		$cache::write($options['cache'], $cache_key, $result);
		return $result;
	}

	/**
	 * Returns new Socket_Beanstalk instance, creates one, if necessary
	 *
	 * @return object
	 */
	protected static function _connect() {
		if (static::$_object) {
			return static::$_object;
		}
		$logger = static::$_classes['logger'];
		$connection = static::$_classes['connection'];
		$socket = static::$_classes['socket'];
		$config = $connection::get('s3', array('config' => true));
		$socket = new $socket($config['key'], $config['secret']);
		return static::$_object = $socket;
	}

	/**
	 * auxillary method to pass all calls directly to the beanstalk_queue object
	 *
	 * @param string $method $method to be called
	 * @param string $args all arguments as array
	 * @return mixed
	 */
	public static function __callStatic($method, $args) {
		if (!$_object = static::_connect()) {
			return false;
		}
		return call_user_func_array(array($_object, $method), $args);
	}
}

?>