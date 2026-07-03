export function SubTabs( { tabs, subTab, onSelect } ) {
	const active = subTab || tabs[ 0 ][ 0 ];
	return (
		<div className="moap-subtabs">
			{ tabs.map( ( [ key, label ] ) => (
				<button
					key={ key }
					className={ `moap-subtab${
						key === active ? ' is-active' : ''
					}` }
					onClick={ () => onSelect( key ) }
				>
					{ label }
				</button>
			) ) }
		</div>
	);
}

export function Breadcrumb( { title, onHome } ) {
	return (
		<div className="moap-detail__crumb">
			{ /* eslint-disable-next-line jsx-a11y/anchor-is-valid */ }
			<a
				href="#"
				onClick={ ( e ) => {
					e.preventDefault();
					onHome();
				} }
			>
				Dashboard
			</a>
			<span className="moap-detail__sep">›</span>
			<span>{ title }</span>
		</div>
	);
}
