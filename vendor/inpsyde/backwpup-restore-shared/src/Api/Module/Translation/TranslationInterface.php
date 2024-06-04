<?php

declare(strict_types=1);

namespace Inpsyde\Restore\Api\Module\Translation;

use Symfony\Component\Translation\Loader\PoFileLoader;
use Symfony\Component\Translation\Translator;

interface TranslationInterface
{
    /**
     * Get locale to use by the translator object.
     */
    public function get_locale(): ?string;

    /**
     * Get language dir where to search for language files.
     *
     * @return string
     */
    public function get_lang_dir();

    /**
     * Setup the translator object ready for the trans() call.
     *
     * @param class-string<Translator> $translator_class The class name used to create the
     *                                                   instance
     * @param PoFileLoader             $file_loader      The class used to load the po
     * @param string                   $ext              The extension of the file
     */
    public function get_translator(string $translator_class, PoFileLoader $file_loader, string $ext): Translator;
}
