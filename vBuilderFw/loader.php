<?php
use Nette\Application\Application,
	Nette\Application\Request,
	Nette\Application\Routers\Route,
	Nette\Utils\Strings;

require_once __DIR__ . '/Utils/shortcuts.php';

// Cestina pro manipulaci s retezci (iconv, etc.)
setlocale(LC_CTYPE, 'cs_CZ.UTF-8');

// Formulare: Podpora pro ORM
Nette\Application\UI\Form::extensionMethod('loadFromEntity', 'vBuilder\Orm\FormHelper::loadFromEntity');
Nette\Application\UI\Form::extensionMethod('fillInEntity', 'vBuilder\Orm\FormHelper::fillInEntity');
Nette\Forms\Container::extensionMethod('addBootstrapSelect', 'vBuilder\Forms\Controls\BootstrapSelect::addToContainer');

// -----------------------------------------------------------------------------
// Some predefines Route classes
// -----------------------------------------------------------------------------

Route::addStyle('#month');

/// @todo this should accept all translations (because we don't know the language yet)
Route::setStyleProperty('#month', Route::FILTER_IN, function ($val) {
	if(preg_match('/^([a-z]+)-([1-9][0-9]{3})/i', $val, $m)) {
		$months = array_flip(array_map('Nette\Utils\Strings::webalize', vBuilder\Utils\DateTime::monthName()));
		if(isset($months[$m[1]]))
			return $m[2] . '-' . str_pad($months[$m[1]], 2, "0", STR_PAD_LEFT);
	}

	return NULL;
});

// This knows the language so it's ok
Route::setStyleProperty('#month', Route::FILTER_OUT, function ($val) {
	if(preg_match('/^([1-9][0-9]{3})-([0-9]{1,2})/i', $val, $m)) {
		$months = array_map('Nette\Utils\Strings::webalize', vBuilder\Utils\DateTime::monthName());
		return $months[(int) $m[2]] . '-' . $m[1];
	}

	return NULL;
});

// -----------------------------------------------------------------------------

$container->application->onRequest[] = function (Application $app, Request $request) use ($container) {

	$runningTestMode = false;

	// Forwardy (jako je napr. presmerovani na error presenter neresim)
	if($request->method != Request::FORWARD) {

		// Pokud jsem v development modu, tak je vse OK
		if(!$container->parameters['productionMode']) return ;

		$host = $container->httpRequest->url->host;

		// Pokud jsem v produkcnim rezimu, musim zkontrolovat, jestli stranka neni ve vystavbe
		if(isset($container->parameters['underConstruction']) && $container->parameters['underConstruction'] == true) {

			// Pokud je stranka ve vystavbe a nejsem na testovaci domene, vyhodim vyjimku
			// Akceptovany jsou domeny koncici na test.*.* nebo .bilahora.v3net.cz
			if(!Strings::match($host, '#^(.+?\.)?test\.[^\.]+\.[^\.]+$#') && !Strings::match($host, '#\.bilahora\.v3net\.cz$#')) {
				if(!defined('VBUILDER_CONNECTOR') || !VBUILDER_CONNECTOR)
					throw new vBuilder\Application\UnderConstructionException();

				return ;
			} else {
				$runningTestMode = true;
			}
		}

		// Pokud nejsem v produkcnim rezimu, musim se postarat o zpetny redirect (nemsi existovat 2 URL se stejnym obsahem)
		else {

			if($matches = Strings::match($host, '#^(.+?\.)?test\.([^\.]+\.[^\.]+)$#')) {
				$newHost = $matches[1] . $matches[2];

				$url = clone $container->httpRequest->url;
				$url->host = $newHost;

				$container->httpResponse->redirect($url);
				exit;
			}

		}
	}

	// Test panel (in production mode) - inicializuju ho pri zpracovani requestu aplikace,
	// takze mam jistotu ze nejsem poustenej z konzole.

	if($runningTestMode) {
		$ajaxDetected = isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest';

		if(!$ajaxDetected) {
			register_shutdown_function(function () {

				// Render test bar
				$bar = new Nette\Diagnostics\Bar;
				$bar->addPanel(new vBuilder\Diagnostics\TestBar);
				echo $bar->render();

			});
		}
	}

};

