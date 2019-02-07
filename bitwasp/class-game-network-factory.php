<?php

namespace BitWasp\Bitcoin\Network;

/** Class GAME_NetworkFactory
 *
 * @package BitWasp\Bitcoin\Network
 */
class GAME_Network_Factory extends CW_NetworkFactory {

	/** BitWasp factory
	 *
	 * @return Networks\GAME
	 * @throws \BitWasp\Bitcoin\Exceptions\InvalidNetworkParameter Invalid Network Parameter exception.
	 */
	public static function GAME() {
		return new Networks\GAME();
	}
}
