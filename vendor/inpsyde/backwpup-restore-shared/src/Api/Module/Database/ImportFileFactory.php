<?php

declare(strict_types=1);

namespace Inpsyde\Restore\Api\Module\Database;

class ImportFileFactory
{
    /**
     * @var array<string, class-string<ImportFileInterface>>
     */
    private $types = [];

    /**
     * @param array<string, class-string<ImportFileInterface>> $types
     */
    public function __construct(array $types)
    {
        $this->types = $types;
    }

    public function import_file(string $type = 'sql'): ?ImportFileInterface
    {
        if (!empty($type) && isset($this->types[$type])) {
            return new $this->types[$type]();
        }

        return null;
    }
}
