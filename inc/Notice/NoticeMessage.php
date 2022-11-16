<?php

namespace Inpsyde\BackWPup\Notice;

/**
 *@property string $id
 *@property string|null $buttonLabel
 *@property string|null $buttonUrl
 * @property string|null $dismissActionUrl
 *@property string|null $type
 * @property string                  $template
 * @property array<int, string>|null $jobs
 */
final class NoticeMessage
{
    /**
     * Array of message data.
     *
     * @var array<string, string|array<int, string>|null>
     */
    private $data = [];

    public function __construct(string $template, ?string $buttonLabel = null, ?string $buttonUrl = null)
    {
        $this->data['template'] = sprintf('/notice/%s.php', $template);
        $this->data['buttonLabel'] = $buttonLabel;
        $this->data['buttonUrl'] = $buttonUrl;
    }

    /**
     * Get a message variable.
     *
     * @param string $name
     *
     * @return string|array<int, string>|null
     */
    public function __get($name)
    {
        if (!isset($this->data[$name])) {
            return null;
        }

        return $this->data[$name];
    }

    /**
     * Sets a variable for the message.
     *
     * @param string                    $name  The variable to set
     * @param string|array<int, string> $value The value to set
     */
    public function __set($name, $value): void
    {
        $this->data[$name] = $value;
    }

    /**
     * Check if variable is set.
     *
     * @param string $name The variable to check
     */
    public function __isset($name)
    {
        return isset($this->data[$name]);
    }
}
