<?php

namespace Inpsyde\BackWPup;

/**
 * Azure destination configuration.
 */
class MsAzureDestinationConfiguration {

	public const MSAZURE_ACCNAME   = 'msazureaccname';
	public const MSAZURE_KEY       = 'msazurekey';
	public const MSAZURE_CONTAINER = 'msazurecontainer';

	/**
	 * Azure account name.
	 *
	 * @var string
	 */
	private $msazureaccname;

	/**
	 * Azure account key.
	 *
	 * @var string
	 */
	private $msazurekey;

	/**
	 * Azure container name.
	 *
	 * @var string
	 */
	private $msazurecontainer;

	/**
	 * Whether the container is new.
	 *
	 * @var bool
	 */
	private $new = false;

	/**
	 * MsAzureDestinationConfiguration constructor.
	 *
	 * @param string $msazureaccname   Account name.
	 * @param string $msazurekey       Account key.
	 * @param string $msazurecontainer Container name.
	 *
	 * @throws \UnexpectedValueException When configuration is invalid.
	 */
	public function __construct( $msazureaccname, $msazurekey, $msazurecontainer ) {
		$items                  = [ $msazureaccname, $msazurekey, $msazurecontainer ];
		$are_config_parts_valid = array_filter( $items );
		if ( count( $are_config_parts_valid ) !== count( $items ) ) {
			throw new \UnexpectedValueException(
				'Invalid configuration data.'
			);
		}

		$this->msazureaccname   = $msazureaccname;
		$this->msazurekey       = $msazurekey;
		$this->msazurecontainer = $msazurecontainer;
	}

	/**
	 * Builds a configuration for a new container.
	 *
	 * @param string $account_name Account name.
	 * @param string $key          Account key.
	 * @param string $container    Container name.
	 *
	 * @return self
	 */
	public static function with_new_container( string $account_name, string $key, string $container ): self {
		$configuration      = new self( $account_name, $key, $container );
		$configuration->new = true;

		return $configuration;
	}

	/**
	 * Returns the account name.
	 *
	 * @return string
	 */
	public function msazureaccname(): string {
		return $this->msazureaccname;
	}

	/**
	 * Returns the account key.
	 *
	 * @return string
	 */
	public function msazurekey(): string {
		return $this->msazurekey;
	}

	/**
	 * Returns the container name.
	 *
	 * @return string
	 */
	public function msazurecontainer(): string {
		return $this->msazurecontainer;
	}

	/**
	 * Whether the container is new.
	 *
	 * @return bool
	 */
	public function is_new(): bool {
		return $this->new;
	}
}
