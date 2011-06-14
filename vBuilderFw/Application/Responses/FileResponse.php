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


namespace vBuilder\Application\Responses;

use vBuilder,
	 Nette,
	 vBuilder\Utils\File;

/**
 * Enhanced FileResponse by changable content disposition header, 
 * auto detect of file mime type and proper Last-modfied header.
 *
 * @author Adam Staněk (velbloud)
 * @since Jun 11, 2011
 */
class FileResponse extends Nette\Application\Responses\FileResponse {

	/** @var string value for content disposition header */
	private $contentDisposition = 'inline';

	/**
	 * @param string file path
	 * @param string file name (what will user get)
	 * @param string mime type (if null, auto detect)
	 */
	public function __construct($filepath, $filename = null, $contentType = null) {
		parent::__construct($filepath, $filename, $contentType == null ? File::getMimeType($filepath)
								 : $contentType);
	}

	/**
	 * Gets content disposition
	 * 
	 * @return string (attachment|inline)
	 */
	final public function getContentDisposition() {
		return $this->contentDisposition;
	}

	/**
	 * Sets content disposition
	 *
	 * @param string inline or attachment (force download)
	 * @throws InvalidArgumentException if value is not supported
	 */
	final public function setContentDisposition($disposition) {
		$values = array("attachment", "inline");
		if(in_array($disposition, $values))
			$this->contentDisposition = $disposition;
		else
			throw new \InvalidArgumentException("Content disposition must be one of these: ".implode(",", $values));
	}

	/**
	 * Sends response to output.
	 * @return void
	 */
	public function send(Nette\Http\IRequest $httpRequest, Nette\Http\IResponse $httpResponse) {
		$httpResponse->setContentType($this->getContentType());
		$httpResponse->addHeader("Last-Modified", gmdate("U", \filemtime($this->getFile())));
		$httpResponse->setHeader('Content-Disposition', $this->getContentDisposition().'; filename="'.$this->getName().'"');

		$filesize = $length = filesize($this->getFile());
		$handle = fopen($this->getFile(), 'r');

		if($this->resuming) {
			$httpResponse->setHeader('Accept-Ranges', 'bytes');
			$range = $httpRequest->getHeader('Range');
			if($range !== NULL) {
				$range = substr($range, 6); // 6 == strlen('bytes=')
				list($start, $end) = explode('-', $range);
				if($start == NULL) {
					$start = 0;
				}
				if($end == NULL) {
					$end = $filesize - 1;
				}

				if($start < 0 || $end <= $start || $end > $filesize - 1) {
					$httpResponse->setCode(416); // requested range not satisfiable
					return;
				}

				$httpResponse->setCode(206);
				$httpResponse->setHeader('Content-Range', 'bytes '.$start.'-'.$end.'/'.$filesize);
				$length = $end - $start + 1;
				fseek($handle, $start);
			} else {
				$httpResponse->setHeader('Content-Range', 'bytes 0-'.($filesize - 1).'/'.$filesize);
			}
		}

		$httpResponse->setHeader('Content-Length', $length);
		while(!feof($handle)) {
			echo fread($handle, 4e6);
		}
		fclose($handle);
	}

}
