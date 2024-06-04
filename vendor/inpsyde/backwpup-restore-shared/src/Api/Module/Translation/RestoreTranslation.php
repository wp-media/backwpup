<?php

declare(strict_types=1);

namespace Inpsyde\Restore\Api\Module\Translation;

use Inpsyde\Restore\Api\Module\Registry;
use Psr\Log\LoggerInterface;
use Symfony\Component\Translation\Translator;

final class RestoreTranslation implements TranslationInterface
{
    /**
     * @var Registry
     */
    private $registry;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * The language directory to set.
     *
     * @var string
     */
    private $langdir;

    /**
     * @param Registry        $registry The Registry instance
     * @param LoggerInterface $logger   The logger instance
     * @param string          $langdir  The language to set where retrieve the translations
     */
    public function __construct(Registry $registry, LoggerInterface $logger, $langdir = '')
    {
        $this->registry = $registry;
        $this->logger = $logger;
        $this->langdir = $langdir;
    }

    public function get_locale(): ?string
    {
        return $this->registry->locale;
    }

    /**
     * Set locale to browser language.
     *
     * @param string $ext Translation file extension
     */
    public function set_browser_lang(string $ext): void
    {
        $locale = $this->get_locale();

        if (!empty($locale)) {
            return;
        }

        $lang_dir = $this->get_lang_dir();
        $browser_languages = filter_input(INPUT_SERVER, 'HTTP_ACCEPT_LANGUAGE');
        if (!is_string($browser_languages) || $browser_languages === '') {
            $browser_languages = 'en';
        }
        $split_languages = explode(',', $browser_languages);

        // Build languages array with priority.
        $languages = [];

        foreach ($split_languages as $lang) {
            $lang_prio = explode(';', $lang);
            if (empty($lang_prio[1])) {
                $lang_prio[1] = '1';
            }
            $lang_prio[1] = str_ireplace('q=', '', $lang_prio[1]);
            if (!empty($lang_prio[0])) {
                $languages[$lang_prio[1]] = str_ireplace('-', '_', $lang_prio[0]);
            }
        }
        krsort($languages);

        // Check if language is available.
        foreach ($languages as $lang) {
            if (is_file($lang_dir . '/' . $lang . '.' . $ext)) {
                $this->registry->locale = $lang;

                header('Refresh:0');
                break;
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function get_lang_dir(): string
    {
        return $this->langdir;
    }

    /**
     * {@inheritdoc}
     */
    public function get_translator($translator_class, $file_loader, $ext): Translator
    {
        $locale = $this->get_locale();

        if (!$locale) {
            $locale = 'en_US';
        }

        $lang_dir = $this->get_lang_dir();
        $lang_file_path = $lang_dir . '/' . $locale . '.' . $ext;
        $id = strtolower(substr(\get_class($file_loader), 0, 4));

        // TODO: Refactor. Exclude dependency to DI Container properly
        $translator = new $translator_class($locale);

        if ($lang_file_path !== '.po' && file_exists($lang_file_path)) {
            $translator->addLoader($id, $file_loader);
            $translator->addResource($id, $lang_file_path, $locale);
        } elseif ($locale !== 'en_US') {
            $this->logger->warning('Requested language file does not exists at: ' . $lang_file_path);
        }

        return $translator;
    }
}
