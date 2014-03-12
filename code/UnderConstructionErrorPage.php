<?php
class UnderConstructionErrorPage extends ErrorPage {
}

class UnderConstructionErrorPage_Controller extends ErrorPage_Controller {

	public function init(){
		parent::init();

		//Allowing to clear requirements via config
		if (UnderConstructionErrorPage::config()->clear_requirements) {
			Requirements::clear();
		}

	}

}