<?php
namespace PAGEmachine\Searchable\ViewHelpers\Link;

/*
 * This file is part of the PAGEmachine Searchable project.
 */

/**
 * PageViewHelper
 * Works like the default link.page ViewHelper from fluid, but allows to pass all arguments as an array
 */
class PageViewHelper extends \TYPO3\CMS\Fluid\Core\ViewHelper\AbstractTagBasedViewHelper {

    /**
     * @var string
     */
    protected $tagName = 'a';

    /**
     * Arguments initialization
     *
     * @return void
     */
    public function initializeArguments()
    {
        $this->registerUniversalTagAttributes();
        $this->registerTagAttribute('target', 'string', 'Target of link', false);
        $this->registerTagAttribute('rel', 'string', 'Specifies the relationship between the current document and the linked document', false);
    }

	/**
	 *
	 * @param  array  $arguments
	 * @return array
	 */
	public function render($arguments = []) {

        $uriBuilder = $this->controllerContext->getUriBuilder();
        $uri = $uriBuilder->reset()
            ->setTargetPageUid($arguments['pageUid'] ?: null)
            ->setTargetPageType($arguments['pageType'] ?: 0)
            ->setNoCache($arguments['noCache'] ?: false)
            ->setUseCacheHash(!($arguments['noCacheHash'] ?: false))
            ->setSection($arguments['section'] ?: '')
            ->setLinkAccessRestrictedPages($arguments['linkAccessRestrictedPages'] ?: false)
            ->setArguments($arguments['additionalParams'] ?: [])
            ->setCreateAbsoluteUri($arguments['absolute'] ?: false)
            ->setAddQueryString($arguments['addQueryString'] ?: false)
            ->setArgumentsToBeExcludedFromQueryString($arguments['argumentsToBeExcludedFromQueryString'] ?: [])
            ->setAddQueryStringMethod($arguments['addQueryStringMethod'] ?: null)
            ->build();
        if ((string)$uri !== '') {
            $this->tag->addAttribute('href', $uri);
            $this->tag->setContent($this->renderChildren());
            $result = $this->tag->render();
        } else {
            $result = $this->renderChildren();
        }
        return $result;
	}





}