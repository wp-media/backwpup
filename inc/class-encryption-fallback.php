<?php
/**
 * Encrypt / decrypt data using a trivial character substitution algorithm.
 *
 * This should never be used, really. However, if neither Open SSL or mcrypt are available, we don't really
 * have choice for a better double way encryption.
 * If should consider to require either Open SSL or mcrypt to avoid the usage of this class.
 *
 * Normally we should implement an interface, but at the moment with PHP 5.2 and the "poor" autoloader we have,
 * an interface would just be another file in "/inc" folder causing confusion. Also, I don't want to create an
 * interface in a file named `class-...php`. When we get rid of PHP 5.2, we setup a better autoloader and we get rid of
 * WP coding standard, we finally could consider to introduce an interface.
 */
class BackWPup_Encryption_Fallback
{
    public const PREFIX = 'ENC1$';

    /**
     * @var string
     */
    private $key;

    /**
     * @var string
     */
    private $key_type;

    /**
     * @param string $enc_key
     * @param string $key_type
     */
    public function __construct($enc_key, $key_type)
    {
        $this->key = md5((string) $enc_key);
        $this->key_type = (string) $key_type;
    }

    /**
     * @return bool
     */
    public static function supported()
    {
        /** @TODO: Should we inform the user about the security risk? and how? */
        return true;
    }

    /**
     * Encrypt a string (Passwords).
     *
     * @param string $string value to encrypt
     *
     * @return string encrypted string
     */
    public function encrypt($string)
    {
        $result = '';

        for ($i = 0; $i < strlen($string); ++$i) {
            $char = substr($string, $i, 1);
            $key_char = substr($this->key, ($i % strlen($this->key)) - 1, 1);
            $char = chr(ord($char) + ord($key_char));
            $result .= $char;
        }

        return BackWPup_Encryption::PREFIX . self::PREFIX . $this->key_type . base64_encode($result);
    }

    /**
     * Decrypt a string (Passwords).
     *
     * @param string $string value to decrypt
     *
     * @return string decrypted string
     */
    public function decrypt($string)
    {
        if (
            !is_string($string)
            || !$string
            || strpos($string, BackWPup_Encryption::PREFIX . self::PREFIX . $this->key_type) !== 0
        ) {
            return '';
        }

        $no_prefix = substr($string, strlen(BackWPup_Encryption::PREFIX . self::PREFIX . $this->key_type));

        $encrypted = base64_decode($no_prefix, true);
        if ($encrypted === false) {
            return '';
        }

        $result = '';

        for ($i = 0; $i < strlen($encrypted); ++$i) {
            $char = substr($encrypted, $i, 1);
            $key_char = substr($this->key, ($i % strlen($this->key)) - 1, 1);
            $char = chr(ord($char) - ord($key_char));
            $result .= $char;
        }

        return $result;
    }
}
