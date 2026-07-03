const clamp = ( value ) => Math.max( 0, Math.min( 1, value ) );

export function clickToFocalPoint( clientX, clientY, rect ) {
	return {
		x: clamp( ( clientX - rect.left ) / rect.width ),
		y: clamp( ( clientY - rect.top ) / rect.height ),
	};
}

export function focalToMarkerStyle( point ) {
	return {
		left: `${ Math.round( point.x * 100 ) }%`,
		top: `${ Math.round( point.y * 100 ) }%`,
	};
}
