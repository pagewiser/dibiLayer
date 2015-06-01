<?php

namespace Pagewiser\DAL\Dibi;

/**
 * Class EventListenerInject
 *
 * use \Pagewiser\DAL\Dibi\EventListenerInject
 */
trait EventListenerInject {


	public function injectEventListener(\Pagewiser\DAL\Dibi\EventListener $evl)
	{
		exit;
		$this->addEventListener($evl);
	}


	public function injectProperties(\Nette\DI\Container $dic)
	{
		exit;
	}

}
