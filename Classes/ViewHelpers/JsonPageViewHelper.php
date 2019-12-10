<?php
namespace Lowres\JsonRendering\ViewHelpers;

use InvalidArgumentException;
use Lowres\JsonRendering\Utils\DataProcessingUtil;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3Fluid\Fluid\Core\Rendering\RenderingContextInterface;

class JsonPageViewHelper extends \TYPO3\CMS\Fluid\Core\ViewHelper\AbstractViewHelper {

    const TOKEN="__|__";

    public function initializeArguments()
    {
        $this->registerArgument('pageData', 'array', 'The email address to resolve the gravatar for', true, []);
    }


    public static function renderStatic(  array $arguments, \Closure $renderChildrenClosure, RenderingContextInterface $renderingContext ) {
        $processor = new DataProcessingUtil();
        return $processor->processPage( $arguments['pageData'] );
    }


//    /**
//     * @param mixed $pageData
//     * @return string
//     */
//    private static function process($pageData) {
//        $backendConfigurationArr = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(ExtensionConfiguration::class)->get('t3ext_json_rendering');
//
//        // first we need to split the string of content elements back to single ces
//        $rawContentString = $pageData['contentElements'];
//        $contentelements = explode(self::TOKEN, $rawContentString);
//        // the last element is always empty, so we can remove it
//        array_pop( $contentelements );
//
//        // json_decode the JSON-strings of every single ce
//        $decodedContentElements = array_map(function( $tmpCe ) {
//            $res = null;
//            try {
//                $res = \GuzzleHttp\json_decode( $tmpCe );
//            } catch( InvalidArgumentException $e){
//                $res = ['error'=>'can not json decode '.$tmpCe ];
//            }
//            return $res;
//        }, $contentelements);
//
//
//        // remove unwanted fields from page
//        $remove = explode(',',$backendConfigurationArr['excludefields']['page']);
//        $remove = array_map( function ($item) {
//            return trim($item);
//        }, $remove);
//        $page = $pageData['data'];
//        $page = array_diff_key($page, array_flip($remove));
////        var_dump($remove); die();
//        $result['page'] = $page;
//        $result['contentElements'] = $decodedContentElements;
//        $result['structure'] = \GuzzleHttp\json_decode( $pageData['structure'] );
//
//
//        return json_encode($result, JSON_PRETTY_PRINT);
//    }

}