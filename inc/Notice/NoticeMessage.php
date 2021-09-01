<?php # -*- coding: utf-8 -*-

namespace Inpsyde\BackWPup\Notice;

/**
 * Class NoticeMessage
 *
 * @method content()
 * @method button_label()
 * @method cta_url()
 */
class NoticeMessage
{

    /**
     * Array of message data
     *
     * @var array
     */
    private $data;

    /**
     * NoticeMessage constructor
     *
     * @param string $template The notice template
     */
    public function __construct($template, $buttonLabel = null, $buttonUrl = null)
    {
        $this->data['template'] = "/notice/$template.php";
        $this->data['buttonLabel'] = $buttonLabel;
        $this->data['buttonUrl'] = $buttonUrl;
    }

    /**
     * Get a message variable
     *
     * @param string $name
     *
     * @return mixed
     */
    public function __get($name)
    {
        if (!isset($this->data[$name])) {
            return null;
        }

        return $this->data[$name];
    }

    /**
     * Sets a variable for the message
     *
     * @param string $name The variable to set
     * @param mixed $value The value to set
     */
    public function __set($name, $value)
    {
        $this->data[$name] = $value;
    }

    /**
     * Check if variable is set
     *
     * @param string $name The variable to check
     */
    public function __isset($name)
    {
        return isset($this->data[$name]);
    }
}
