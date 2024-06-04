<?php

declare(strict_types=1);

namespace Inpsyde\Restore\Api\Module\Database;

use Inpsyde\Restore\Api\Module\Registry;
use Psr\Log\LoggerInterface;
use Symfony\Component\Translation\Translator;

/**
 * Class DatabaseTypeFactory.
 */
class DatabaseTypeFactory
{
    /**
     * @var Translator|null
     */
    private $translation;

    /**
     * @var Registry
     */
    private $registry;

    /**
     * @var array<string, class-string<DatabaseInterface>>
     */
    private $types = [];

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * DatabaseTypeFactory constructor.
     *
     * @param array<string, class-string<DatabaseInterface>> $types
     */
    public function __construct(array $types, Registry $registry, Translator $translation)
    {
        $this->types = $types;
        $this->registry = $registry;
        $this->translation = $translation;
    }

    /**
     * Database Type.
     *
     * @param string $type The database type; mysqli, or none for auto-detect
     */
    public function database_type(string $type = ''): ?DatabaseInterface
    {
        if (!empty($type)) {
            if (isset($this->types[$type])) {
                $db = new $this->types[$type]($this->registry, $this->translation);
                $db->set_logger($this->logger);

                return $db;
            }

            return null;
        }

        foreach ($this->types as $type) {
            $database = new $type($this->registry, $this->translation);
            /** @var DatabaseInterface $database */
            if ($database->can_use()) {
                $database->set_logger($this->logger);

                return $database;
            }
        }

        return null;
    }

    public function set_logger(LoggerInterface $logger): void
    {
        $this->logger = $logger;
    }
}
