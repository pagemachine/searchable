<?php
namespace PAGEmachine\Searchable\ViewHelpers\Link;

use TYPO3\CMS\Fluid\ViewHelpers\Link\PageViewHelper as FluidPageViewHelper;

/*
 * This file is part of the PAGEmachine Searchable project.
 */

/**
 * PageViewHelper
 * Works like the default link.page ViewHelper from fluid, but allows to pass all arguments as an array
 */
class PageViewHelper extends \TYPO3\CMS\Fluid\Core\ViewHelper\AbstractTagBasedViewHelper {

	/**
	 *
	 * @var \TYPO3\CMS\Fluid\ViewHelpers\Link\PageViewHelper
	 * @inject
	 */
	protected $fluidPageViewHelper;

	/**
	 *
	 * @param  array  $arguments
	 * @return string
	 */
	public function render($arguments = []) {

		return $this->fluidPageViewHelper->render(
			$arguments['pageUid'] ?: null, 
			$arguments['additionalParams'] ?: [], 
			$arguments['pageType'] ?: 0, 
			$arguments['noCache'] ?: false, 
			$arguments['noCacheHash'] ?: false,
			$arguments['section'] ?: '',
			$arguments['linkAccessRestrictedPages'] ?: false,
			$arguments['absolute'] ?: false,
			$arguments['addQueryString'] ?: false,
			$arguments['argumentsToBeExcludedFromQueryString'] ?: [],
			$arguments['addQueryStringMethod'] ?: null
		);
	}





}