<?php

declare(strict_types=1);

namespace Inpsyde\Restore\Api\Module\Database;

use Symfony\Component\Translation\Translator;

class ImportFileFactory
{
    /**
     * @var Translator
     */
    private $translation;

    /**
     * @var array<string, class-string<ImportFileInterface>>
     */
    private $types = [];

    /**
     * @param array<string, class-string<ImportFileInterface>> $types
     */
    public function __construct(array $types, Translator $translation)
    {
        $this->types = $types;
        $this->translation = $translation;
    }

    public function import_file(string $type = 'sql'): ?ImportFileInterface
    {
        if (!empty($type) && isset($this->types[$type])) {
            return new $this->types[$type]($this->translation);
        }

        return null;
    }
}
