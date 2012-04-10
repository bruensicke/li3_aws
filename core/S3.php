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
		'collection' => 'lithium\util\Collection',
		'logger' => 'lithium\analysis\Logger',
		'cache' => 'lithium\storage\Cache'
	);

	/**
	 * Holds instance of s3 utitlity class
	 *
	 * @var object
	 */
	public static $_object;

	// can be private, public-read, public-read-write or authenticated-read
	public static function create($input = array(), array $options = array()) {
		$defaults = array('access' => 'private', 'meta' => array(), 'type' => 'text/plain');
		$options += $defaults;
		extract($input);
		if (!empty($input['file'])) {
			$body = file_get_contents($input['file']);
			$type = substr(strrchr($file, '.'), 1);
			switch ($type) {
				case 'bmp':
				case 'png':
				case 'gif':
				case 'jpg':
				case 'jpeg':
					$options['type'] = "image/$type";
			}
		}
		$_object = self::_connect($input['bucket']);
		$success = $_object->putObjectString($body, $bucket, $path, $options['access'], $options['meta'], $options['type']);
		if (!$success) {
			return false;
		}
		return String::insert("http://{:bucket}.s3.amazonaws.com/{:path}", compact('bucket', 'path'));
	}

	public static function info($bucket, $path) {
		$_object = self::_connect($bucket);
		$result = $_object->getObjectInfo($bucket, $path);
		$result['url'] = String::insert("http://{:bucket}.s3.amazonaws.com/{:path}", compact('bucket', 'path'));
		return $result;
	}

	public static function buckets() {
		$data = self::_connect()->listBuckets();
		return new self::$_classes['collection'](compact('data'));
	}

	public static function bucket($name, $folder = null, array $options = array()) {
		$defaults = array(
			'cache' => 'default',
			'cache_key' => 's3.bucket.{:name}',
			'delimiter' => '/'
		);
		$options += $defaults;
		$cache = static::$_classes['cache'];
		$cache_key = String::insert($options['cache_key'], compact('name'));
		if (!empty($options['cache']) && ($result = $cache::read($options['cache'], $cache_key))) {
			return $result;
		}

		$_object = self::_connect($name);
		$data = $_object->getBucket($name, $folder, $options['delimiter']);
		// debug($data);exit;
		$folders = array();
		foreach ($data as $key => $item) {
			$data[$key]['name'] = (!empty($data[$key]['name'])) ? $data[$key]['name'] : $key;
			$data[$key]['bucket'] = $name;
			$data[$key]['is_folder'] = (boolean) (substr($key, -1) == '/');
			$data[$key]['is_file'] = (boolean) (!$data[$key]['is_folder']);
			$data[$key]['url'] = $name . '.' . S3_utility::$endpoint . '/' . $key;
		}
		// debug($data);exit;
		$result = new self::$_classes['collection'](compact('data'));
		// $cache::write($options['cache'], $cache_key, $result);
		return $result;
	}

	/**
	 * Returns new Socket_Beanstalk instance, creates one, if necessary
	 *
	 * @return object
	 */
	protected static function _connect($bucket = null) {
		if (static::$_object) {
			return static::$_object;
		}
		$logger = static::$_classes['logger'];
		$connection = static::$_classes['connection'];
		$socket = static::$_classes['socket'];
		$config = $connection::get('s3', array('config' => true));
		$socket = new $socket($config['key'], $config['secret']);
		if (!empty($bucket)) {
			$location = $socket->getBucketLocation($bucket);
			if ($location == 'EU') {
				$socket->setEndpoint('s3-eu-west-1.amazonaws.com');
			}
		}
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