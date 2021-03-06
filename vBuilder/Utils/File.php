<?php

/**
 * This file is part of vBuilder Framework (vBuilder FW).
 * 
 * Copyright (c) 2011 Adam Staněk <adam.stanek@v3net.cz>
 * 
 * For more information visit http://www.vbuilder.cz
 * 
 * vBuilder FW is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.

 * vBuilder FW is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.

 * You should have received a copy of the GNU General Public License
 * along with vBuilder FW. If not, see <http://www.gnu.org/licenses/>.
 */

namespace vBuilder\Utils;

use vBuilder, Nette;

/**
 * Utilities for easing file workflow
 *
 * @author Adam Staněk (velbloud)
 * @since Jun 11, 2011
 */
class File {
	
	/** Path to INI file specifing file mime types based on extension (from this class file directory) */
	const MIME_INI_FILEPATH = '../mime.ini';
	
	/**
	 * Tests if string is valid mime type
	 *
	 * @param string test mime type
	 * @return bool true if mime type is valid
	 */
	static function isValidMimeType($mime) {
		return Nette\Utils\Strings::match($mime, '/^[a-z\\-]*\\/[a-z\\-]*$/') !== null;
	}
	
	/**
	 * Tries to find out the mimetype for file specified in param.
	 * The query is based on file extension. If system contains mime_content_type
	 * function it takes precedence. If no match is found the application/octet-stream
	 * is returned.
	 * 
	 * @param string path to file
	 * @param string|null fiele extension, if null it will be taken as last .part in filepath
	 */
	static function getMimeType($filepath, $extension = null) {
		if(function_exists("mime_content_type")) {
			$mime = mime_content_type($filepath);
			if(self::isValidMimeType($mime)) return $mime;
		} 
		
		$cache = Nette\Environment::getCache("vBuilder.Download");
		if(!isset($cache["mime-types"])) {
			$m = parse_ini_file(__DIR__.'/'.self::MIME_INI_FILEPATH);
			if($m != false) $cache["mime-types"] = $m;
		}

		if($extension == null) $extension = pathinfo($filepath, PATHINFO_EXTENSION);
		if(array_key_exists($extension, $cache["mime-types"])) $mime = $cache["mime-types"][$extension];

		return self::isValidMimeType($mime) ? $mime : "application/octet-stream";
	}
	
	/**
	 * @param string $file path to a file or just filename
	 */
	public static function isImage($file) {
		$extension = pathinfo($file, PATHINFO_EXTENSION);
		if (file_exists($file)) {
			$mime = static::getMimeType($file);
			$byMime = Strings::startsWith($mime, 'image');
		} else {
			if (!$extension) {
				return false;
			}
			// we only have the filename and therefore we can only use the extension.
			// we will not care about mime-type
			$byMime = true;
		}
		$byExtension = in_array(Strings::lower($extension), array (
			'jpg',
			'png',
			'bmp',
			'gif'
		));
		return $byMime && $byExtension;
	}
	
	public static function getExtension($filename) {
		return pathinfo($filename, PATHINFO_EXTENSION);
	}
}
