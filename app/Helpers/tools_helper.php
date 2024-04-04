<?php
	function MakeOperationNumber ( int $operation ): string {
		$trash = '010203040506070809';
		return str_pad ( $operation, 7, substr ( str_shuffle ( $trash ), 0, 10 ), STR_PAD_LEFT );
	}
