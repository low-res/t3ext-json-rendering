<?php
namespace Lowres\JsonRendering\ViewHelpers;

use InvalidArgumentException;
use Lowres\JsonRendering\Utils\DataProcessingUtil;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3Fluid\Fluid\Core\Rendering\RenderingContextInterface;

class JsonPageViewHelper extends \TYPO3\CMS\Fluid\Core\ViewHelper\AbstractViewHelper {

    public function initializeArguments()
    {
        $this->registerArgument('pageData', 'array', 'The email address to resolve the gravatar for', true, []);
    }


    public static function renderStatic(  array $arguments, \Closure $renderChildrenClosure, RenderingContextInterface $renderingContext ) {
        $processor = new DataProcessingUtil();
        return $processor->processPage( $arguments['pageData'] );
    }


}