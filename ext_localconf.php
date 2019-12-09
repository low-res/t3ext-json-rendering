<?php
if (!defined('TYPO3_MODE')) {
	die ('Access denied.');
}

// Formulare registrieren
//call_user_func(function () {
//    if (TYPO3_MODE === 'BE') {
//        \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addTypoScriptSetup(
//            trim('
//                module.tx_form {
//                    settings {
//                        yamlConfigurations {
//                            90 = EXT:hardermehltheme/Configuration/form.yaml
//                            100 = EXT:hardermehltheme/Configuration/Forms/kontakt.yaml
//                        }
//                    }
//                }
//            ')
//        );
//    }
//});