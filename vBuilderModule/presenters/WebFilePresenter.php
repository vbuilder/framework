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

namespace vBuilderModule;

use vBuilder,
	Nette;

/**
 * Presenter for generated CSS / JS files
 *
 * @author Adam Staněk (V3lbloud)
 * @since May 1, 2014
 */
class WebFilePresenter extends Nette\Object implements Nette\Application\IPresenter {

	/** @var Nette\Http\Response @inject */
	public $httpResponse;

	/** @var Nette\DI\Container @inject */
	public $container;

	public function run(Nette\Application\Request $request) {
		$dir = realpath($this->container->parameters['tempDir'] . '/webfiles');

		try {
			if(!$dir)
				throw new Nette\Application\BadRequestException("File not found");

			list($request) = $app->getRequests();
			$filePath = $dir . '/' . $request->parameters['file'];

			$this->httpResponse->setContentType(
				$request->parameters['type'] == 'js' ? 'text/javascript' : 'text/css',
				'utf-8'
			);

			return new vBuilder\Application\Responses\FileResponse(
				$filePath,
				$request->parameters['file'],
				FALSE
			);

		} catch(Nette\Application\BadRequestException $e) {
			$this->httpResponse->setCode(404 /* Not found */);
			return new Nette\Application\Responses\TextResponse("Not found");
		}
	}

}
