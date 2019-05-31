<?php

namespace quieteroks\components\i18n;

use Yii;
use yii\i18n\PhpMessageSource;

/**
 * Auto search modules translations.
 * Easy to use i18n with many translated modules, without config all by once.
 *
 * Config example:
 * 'i18n' => [
 *     'translations' => [
 *         '*' => [
 *             'class' => 'quieteroks\components\i18n\PhpModuleMessageSource',
 *             'basePath' => '@app/modules',
 *             'defaultName' => 'translate',
 *             'delimiter' => '/',
 *             'sourceLanguage' => 'en-US',
 *         ],
 *     ],
 * ],
 *
 * Example use source:
 *   module             - app/modules/module/messages/ru/translate.php
 *   module/name        - app/modules/module/messages/ru/name.php
 *   module/one/name    - app/modules/module/messages/ru/one/name.php
 *   module/one/two     - app/modules/module/messages/ru/one/two/translate.php
 */
class PhpModuleMessageSource extends PhpMessageSource
{
    /**
     * @var string
     */
    public $basePath = '@app/modules';
    /**
     * @var string
     */
    public $defaultName = 'translate';
    /**
     * @var string
     */
    public $delimiter = '/';

    /**
     * Returns message file path for the modules specified language.
     *
     * @param string $category
     * @param string $language
     * @return string
     */
    protected function getMessageFilePath($category, $language)
    {
        $module = $category;
        $name = $this->defaultName;

        if (strpos($category, $this->delimiter) !== false) {
            [$module, $name] = explode($this->delimiter, $category, 2);
        }

        $messageFile = Yii::getAlias($this->basePath) . "/{$module}/messages/{$language}";
        $name = str_replace($this->delimiter, '/', $name);

        if (isset($this->fileMap[$name])) {
            $messageFile .= '/' . $this->fileMap[$name];
        } elseif (!is_file($messageFile . '/' . $name . '.php')) {
            $messageFile .= '/' . $name . '/' . $this->defaultName . '.php';
        } else {
            $messageFile .= '/' . $name . '.php';
        }

        return $messageFile;
    }
}
