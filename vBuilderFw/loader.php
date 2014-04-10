<?php
use Nette\Application\Application,
	Nette\Application\Request,
	Nette\Utils\Strings;

require_once __DIR__ . '/Utils/shortcuts.php';

// Cestina pro manipulaci s retezci (iconv, etc.)
setlocale(LC_CTYPE, 'cs_CZ.UTF-8');

// Formulare: Podpora pro ORM
Nette\Application\UI\Form::extensionMethod('loadFromEntity', 'vBuilder\Orm\FormHelper::loadFromEntity');
Nette\Application\UI\Form::extensionMethod('fillInEntity', 'vBuilder\Orm\FormHelper::fillInEntity');
Nette\Forms\Container::extensionMethod('addBootstrapSelect', 'vBuilder\Forms\Controls\BootstrapSelect::addToContainer');

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

