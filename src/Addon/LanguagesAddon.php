<?php

/**
 * TOBENTO
 *
 * @copyright   Tobias Strub, TOBENTO
 * @license     MIT License, see LICENSE file distributed with this source code.
 * @author      Tobias Strub
 * @link        https://www.tobento.ch
 */

declare(strict_types=1);

namespace Tobento\App\Testing\Addon;

use Tobento\Service\Language\LanguageFactory;
use Tobento\Service\Language\Languages;
use Tobento\Service\Language\LanguagesFactoryInterface;
use Tobento\Service\Language\LanguagesInterface;

trait LanguagesAddon
{
    /**
     * Adds multiple languages.
     *
     * @param string ...$locales
     * @return void
     */
    protected function withLanguages(string ...$locales): void
    {
        if (empty($locales)) {
            $locales = ['en'];
        }
        
        $app = $this->getApp();
        $app->on(LanguagesInterface::class, function() use ($locales): LanguagesInterface {            
            $languageFactory = new LanguageFactory();
            
            $languages = [];

            foreach($locales as $locale) {
                if (empty($languages)) {
                    $languages[] = $languageFactory->createLanguage(locale: $locale, default: true);
                } else {
                    $languages[] = $languageFactory->createLanguage(locale: $locale, slug: $locale);
                }
            }
            
            return new Languages(...$languages);
        });
    }
}