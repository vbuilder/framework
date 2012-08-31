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

Nette\Diagnostics\Debugger::addPanel(new vBuilder\Diagnostics\OrmSessionBar);

$container->application->onRequest[] = function (Application $app, Request $request) use ($container) {
	
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
				throw new vBuilder\Application\UnderConstructionException();
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
	
};