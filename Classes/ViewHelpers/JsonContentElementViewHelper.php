<?php
namespace Lowres\JsonRendering\ViewHelpers;

use Lowres\JsonRendering\Utils\DataProcessingUtil;
use TYPO3\CMS\Fluid\Core\ViewHelper\AbstractViewHelper;
use TYPO3Fluid\Fluid\Core\Rendering\RenderingContextInterface;

class JsonContentElementViewHelper extends AbstractViewHelper {

    private static $result = "";

    const TOKEN="__|__";

    public function initializeArguments()
    {
        $this->registerArgument('contentElement', 'array', 'The email address to resolve the gravatar for', true, []);
    }


    public static function renderStatic( array $arguments, \Closure $renderChildrenClosure, RenderingContextInterface $renderingContext ) {
        $processor = new DataProcessingUtil();
        return $processor->processContentElements( $arguments['contentElement'] );
    }

}