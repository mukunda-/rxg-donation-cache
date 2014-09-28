<?php

/** ---------------------------------------------------------------------------
 * Test case to see if classes can access local functions without
 * a $this reference.
 *
 */
class Butt { 
	
	/** -----------------------------------------------------------------------
	 * Poop.
	 *
	 * Tries to pee without a prefix.
	 *
	 * @return none
	 */
	function poop() { 
		pee();
	}
	
	/** -----------------------------------------------------------------------
	 * Pee.
	 *
	 * @return none
	 */
	function pee() {
		
	}
}

// Construct a new Butt and try to poop.
$butt = new Butt();

$butt->poop();

?>