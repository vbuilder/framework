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
 *
 * vBuilder FW is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with vBuilder FW. If not, see <http://www.gnu.org/licenses/>.
 */

namespace vBuilder\Utils;

use vBuilder,
		Nette;

/**
 * File system routines
 *
 * @author Adam Staněk (velbloud)
 * @since Oct 22, 2011
 */
class FileSystem {

	/**
	 * Returns parsed path tokens without escape characters
	 *
	 * 'a/b/c'   => ['a', 'b', 'c']
	 * 'a\\/b/c' => ['a\b', 'c']
	 *
	 * @param string path
	 * @param char directory separator
	 *
	 * @return string
	 */
	static function getPathTokens($path, $separator = DIRECTORY_SEPARATOR) {
		return Strings::splitWithEscape($path, $separator, $separator == '\\' ? '\\\\' : '\\', false);
	}

	/**
	 * Returns escaped path token
	 *
	 * @param string path
	 * @param char directory separator
	 *
	 * @return string
	 */
	static function escapePath($pathToken, $separator = DIRECTORY_SEPARATOR) {
		$escape = $separator == '\\' ? '\\\\' : '\\';
		return str_replace(array($escape, $separator), array($escape . $escape, $escape . $separator), $pathToken);
	}

	/**
	 * Resolves ../ and ./ tokens in path
	 *
	 * @note Function works only with given path and does not actually check FS.
	 *
	 * @param string path
	 * @param char directory separator
	 * @return string
	 */
	static function normalizePath($path, $separator = DIRECTORY_SEPARATOR) {
		$tokens = self::getPathTokens($path, $separator);

		$out=array();
		foreach($tokens as $i => $token){
			if($token == '' || $token == '.') continue;
			if($token == '..' && count($out) > 0 && end($out) != '..') array_pop($out);
			else $out[]= self::escapePath($token);
		}

		return ($path[0] == '/' ? '/' : '') . join('/', $out);
	}

	/**
	 * Creates relative path
	 *
	 * @note Function works only with given path and does not actually check FS.
	 *
	 * @param string from (base path)
	 * @param string to (target path)
	 * @param char directory separator
	 * @return string
	 */
	static function getRelativePath($from, $to, $separator = DIRECTORY_SEPARATOR) {

		$from = rtrim($from, $separator) . $separator;
		$to = rtrim($to, $separator) . $separator;

		$from		= self::getPathTokens($from, $separator);
		$to			= self::getPathTokens($to, $separator);
		$relPath	= $to;

		foreach($from as $depth => $dir) {
			// find first non-matching dir
			if($dir === $to[$depth]) {
				// ignore this directory
				array_shift($relPath);
			} else {
				// get number of remaining dirs to $from
				$remaining = count($from) - $depth;
				if($remaining > 1) {
					// add traversals up to first matching dir
					$padLength = (count($relPath) + $remaining - 1) * -1;
					$relPath = array_pad($relPath, $padLength, '..');
					break;
				} else {
					$relPath[0] = '.' . $separator . $relPath[0];
				}
			}
		}

		return rtrim(implode($separator, $relPath), $separator);
	}

	/**
	 * Creates directory (all of them if necessary) if directory does not exist
	 *
	 * @param string directory path
	 * @param string creation mode
	 *
	 * @throws Nette\IOException if cannot create directory
	 */
	static function createDirIfNotExists($dirpath, $mode = 0770) {
		if(!is_dir($dirpath)) {
			if(@mkdir($dirpath, $mode, true) === false) // @ - is escalated to exception
				throw new Nette\IOException("Cannot create directory '".$dirpath."'");
		}
	}

	/**
	 * Creates all directories in the file path
	 *
	 * @param string file path
	 *
	 * @throws Nette\IOException if cannot create directory
	 */
	static function createFilePath($filePath) {
		$dirpath = pathinfo($filePath, PATHINFO_DIRNAME);
		self::createDirIfNotExists($dirpath);
	}

	/**
	 * Finds files matching given base path while trying multiple extensions
	 *
	 * @param string absolute base path (file name path without extension)
	 * @param array of extensions (if empty all possible extensions will be matched)
	 *
	 * @return array of absolute file paths
	 */
	static function findFilesWithBaseName($basePath, $extensions = array()) {
		$pi = pathinfo($basePath);
		$files = array();

		foreach(Nette\Utils\Finder::findFiles($pi['basename'] . '.*')->in($pi['dirname']) as $file) {
			$matched = count($extensions) == 0;
			foreach($extensions as $curr) {
				// Podporovano az od PHP 5.3.6
				// $ext = $file->getExtension();
				$ext = pathinfo($file->getFilename(), PATHINFO_EXTENSION);

				if(mb_strtolower($curr) == mb_strtolower($ext)) {
					$matched = true;
					break;
				}
			}

			if(!$matched) continue;

			$files[] = $file->getPathname();
		}

		return $files;
	}

	/**
	 * Tries to delete all of given files
	 *
	 * @param array of absolute file paths
	 *
	 * @return int number of deleted files
	 * @throws Nette\IOException if file has been found, but can't unlink it during the permission violation
	 */
	static function tryDeleteFiles(array $files) {
		$success = 0;

		foreach($files as $path) {
			if(file_exists($path)) {
				if(!unlink($path))
					throw new Nette\IOException("Failed to delete " . var_export($path, true));

				$success++;
			}
		}

		return $success;
	}

}
