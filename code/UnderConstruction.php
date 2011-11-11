<?php
/**
 * Decorator to essentially create a static under construction {@link ErrorPage} in the assets folder 
 * with the aid of requireDefaultRecords().
 * 
 * @author Frank Mullenger <frankmullenger@gmail.com>
 * @package underconstruction
 */
class UnderConstruction_Decorator extends DataObjectDecorator {
   
  /**
   * Create an {@link ErrorPage} for status code 503
   * 
   * @see UnderConstruction_Extension::onBeforeInit()
   * @see DataObjectDecorator::requireDefaultRecords()
   * @return Void
   */
  function requireDefaultRecords() {
    
    // Ensure that an assets path exists before we do any error page creation
		if(!file_exists(ASSETS_PATH)) {
			mkdir(ASSETS_PATH);
		}

		$pageUnderConstructionErrorPage = DataObject::get_one('ErrorPage', "\"ErrorCode\" = '503'");
		$pageUnderConstructionErrorPageExists = ($pageUnderConstructionErrorPage && $pageUnderConstructionErrorPage->exists()) ? true : false;
		$pageUnderConstructionErrorPagePath = ErrorPage::get_filepath_for_errorcode(503);
		if(!($pageUnderConstructionErrorPageExists && file_exists($pageUnderConstructionErrorPagePath))) {
			if(!$pageUnderConstructionErrorPageExists) {
				$pageUnderConstructionErrorPage = new ErrorPage();
				$pageUnderConstructionErrorPage->ErrorCode = 503;
				$pageUnderConstructionErrorPage->Title = _t('UnderConstruction.TITLE', 'Under Construction');
				$pageUnderConstructionErrorPage->Content = _t('UnderConstruction.CONTENT', '<p>Sorry, this site is currently under construction.</p>');
				$pageUnderConstructionErrorPage->Status = 'New page';
				$pageUnderConstructionErrorPage->write();
				$pageUnderConstructionErrorPage->publish('Stage', 'Live');
			}

			// Ensure a static error page is created from latest error page content
			$response = Director::test(Director::makeRelative($pageUnderConstructionErrorPage->Link()));
			if($fh = fopen($pageUnderConstructionErrorPagePath, 'w')) {
				$written = fwrite($fh, $response->getBody());
				fclose($fh);
			}

			if($written) {
				DB::alteration_message('503 error page created', 'created');
			} else {
				DB::alteration_message(sprintf('503 error page could not be created at %s. Please check permissions', $pageUnderConstructionErrorPagePath), 'error');
			}
		}
	}
}

/**
 * Extension to display an {@link ErrorPage} if necessary.
 * 
 * @author Frank Mullenger <frankmullenger@gmail.com>
 * @package underconstruction
 */
class UnderConstruction_Extension extends Extension {
   
  /**
   * If current logged in member is not an admin and not trying to log in to the admin
   * or run a /dev/build then display an {@link ErrorPage}.
   * 
   * @see UnderConstruction_Decorator::requireDefaultRecords()
   * @return Void
   */
  public function onBeforeInit() {

    //Check to see if running /dev/build
    $runningDevBuild = $this->owner && $this->owner->data() instanceof ErrorPage;

    if (!Permission::check('ADMIN') 
        && strpos($_SERVER['REQUEST_URI'], '/admin') === false 
        && strpos($_SERVER['REQUEST_URI'], '/Security') === false 
        && !Director::isDev() 
        && !$runningDevBuild) {
      Debug::friendlyError(503);
      exit;
    }
  }
}