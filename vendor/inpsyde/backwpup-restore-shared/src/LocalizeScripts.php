<?php

declare(strict_types=1);

/**
 * Localize Scripts.
 */

namespace Inpsyde\Restore;

/**
 * Class LocalizeScripts.
 */
class LocalizeScripts
{
    /**
     * Strings List.
     *
     * @var array<string> The list of the strings to localize
     */
    private $list;

    /**
     * LocalizeScripts constructor.
     *
     * @param array<string>       $list       the list of the strings to localize
     */
    public function __construct(array $list)
    {
        $this->list = $list;
    }

    /**
     * Output Localized strings.
     *
     * @return $this For concatenation
     */
    public function output(): self
    {
        ?>
        <script type="text/javascript">
          /* <![CDATA[ */
          <?php printf("var backwpupRestoreLocalized = %s\n", wp_json_encode($this->list) ?: ''); ?>
          /* ]]> */
        </script>
        <?php

        return $this;
    }
}
