<?php
require_once __DIR__ . '/Utils/shortcuts.php';

// Cestina pro manipulaci s retezci (iconv, etc.)
setlocale(LC_CTYPE, 'cs_CZ.UTF-8');

// Formulare: Podpora pro ORM
Nette\Application\UI\Form::extensionMethod('loadFromEntity', 'vBuilder\Orm\FormHelper::loadFromEntity');
Nette\Application\UI\Form::extensionMethod('fillInEntity', 'vBuilder\Orm\FormHelper::fillInEntity');

Nette\Diagnostics\Debugger::addPanel(new vBuilder\Diagnostics\OrmSessionBar);