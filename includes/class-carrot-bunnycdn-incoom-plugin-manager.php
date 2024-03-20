<?php
/**
 * Storage Manager
 *
 * @link       https://github.com/Samuelincoom/bunnycarrot
 * @since      1.0.2
 *
 * @package    carrot_bunnycdn_incoom_plugin
 * @subpackage carrot_bunnycdn_incoom_plugin/includes
 */

if(!defined('ABSPATH')) {
	die;
}

class carrot_bunnycdn_incoom_plugin_Manager{

	private static $driver = null;
	private static $registry = [];
	private static $instance = null;

	/**
	 * Gets the currently configured storage interface.
	 *
	 * @throws StorageException
	 * @return StorageInterface
	 */
	public static function storageInstance() {
		if (self::$instance) {
			return self::$instance;
		}

		if (!isset(self::$registry[self::driver()])) {
			throw new StorageException("Invalid driver '".self::driver()."'");
		}

		$class = self::$registry[self::driver()];
		self::$instance = new $class();

		return self::$instance;
	}

	public static function driver() {
		if (!self::$driver) {
			self::$driver = EnvironmentOptions::Option('ilab-media-storage-provider','ILAB_CLOUD_STORAGE_PROVIDER', 's3');
		}

		return self::$driver;
	}

	/**
	 * Resets the current storage interface
	 */
	public static function resetStorageInstance() {
		self::$instance = null;
	}

	/**
	 * Registers a storage driver
	 * @param $identifier
	 * @param $class
	 */
	public static function registerDriver($identifier, $class) {
		self::$registry[$identifier] = $class;
	}

	/**
	 * @param $identifier
	 *
	 * @return StorageInterface
	 */
	public static function driverClass($identifier) {
		if (!isset(self::$registry[$identifier])) {
			return null;
		}

		return self::$registry[$identifier];
	}
}
?>