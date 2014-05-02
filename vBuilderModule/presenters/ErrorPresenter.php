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
 * Error presenter
 *
 * @author Adam Staněk (V3lbloud)
 * @since May 2, 2014
 */
class ErrorPresenter implements Nette\Application\UI\Presenter {

	/**
	 * @param  Exception
	 * @return void
	 */
	public function renderDefault($exception) {
		dd("OK?");

		if ($exception instanceof Nette\Application\BadRequestException) {
			if($exception instanceof vBuilder\Application\UnsupportedBrowserException) {
				$this->setView('unsupportedBrowser');

			} else {
				$code = $exception->getCode();
				$this->setView(in_array($code, array(403, 404)) ? $code : 'default');

				// log to access.log
				Debugger::log("HTTP code $code: {$exception->getMessage()} in {$exception->getFile()}:{$exception->getLine()}", 'access');
			}

		} elseif($exception instanceof vBuilder\Application\UnderConstructionException)
			$this->setView('underConstruction');

		} else {
			$this->setView('500'); // load template 500.latte
			Debugger::log($exception, Debugger::ERROR); // and log exception
		}

		if ($this->isAjax()) { // AJAX request? Note this error in payload.
			$this->payload->error = TRUE;
			$this->terminate();
		}
	}

}
