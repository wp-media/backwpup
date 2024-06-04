<?php

declare(strict_types=1);

/**
 * Localize Scripts.
 */

namespace Inpsyde\Restore;

use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Class LocalizeScripts.
 */
class LocalizeScripts
{
    /**
     * Translator.
     *
     * @var TranslatorInterface The translator instance
     */
    private $translator;

    /**
     * Strings List.
     *
     * @var array<string> The list of the strings to localize
     */
    private $list;

    /**
     * LocalizeScripts constructor.
     *
     * @param TranslatorInterface $translator The translator instance
     * @param array<string>       $list       the list of the strings to localize
     */
    public function __construct(TranslatorInterface $translator, array $list)
    {
        $this->translator = $translator;
        $this->list = $list;
    }

    /**
     * Localize.
     *
     * @return $this For concatenation
     */
    public function localize(): self
    {
        foreach ($this->list as &$item) {
            $item = $this->translator->trans($item);
        }

        return $this;
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
